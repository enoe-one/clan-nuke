<?php
require_once 'config.php';

// Vérifier que l'utilisateur est connecté
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';
$current_user_id = $_SESSION['user_id'];
$current_user_role = $_SESSION['role'];

// Créer les tables si elles n'existent pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        is_archived_sender BOOLEAN DEFAULT FALSE,
        is_archived_recipient BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        read_at TIMESTAMP NULL,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_sender (sender_id),
        INDEX idx_recipient (recipient_id),
        INDEX idx_created (created_at)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS message_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message_id INT NOT NULL,
        sender_id INT NOT NULL,
        recipient_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        action VARCHAR(50) DEFAULT 'sent',
        logged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (message_id) REFERENCES messages(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE,
        INDEX idx_logged (logged_at)
    )");
} catch (PDOException $e) {
    // Tables déjà créées
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'send_message':
                $recipient_id = $_POST['recipient_id'];
                $title = $_POST['title'];
                $content = $_POST['content'];
                
                // Vérifier que le destinataire existe
                $stmt = $pdo->prepare("SELECT id, role FROM users WHERE id = ?");
                $stmt->execute([$recipient_id]);
                $recipient = $stmt->fetch();
                
                if (!$recipient) {
                    throw new Exception("Destinataire introuvable.");
                }
                
                // Insérer le message
                $stmt = $pdo->prepare("INSERT INTO messages (sender_id, recipient_id, title, content) 
                    VALUES (?, ?, ?, ?)");
                $stmt->execute([$current_user_id, $recipient_id, $title, $content]);
                $message_id = $pdo->lastInsertId();
                
                // Logger le message
                $stmt = $pdo->prepare("INSERT INTO message_logs (message_id, sender_id, recipient_id, title, content, action) 
                    VALUES (?, ?, ?, ?, ?, 'sent')");
                $stmt->execute([$message_id, $current_user_id, $recipient_id, $title, $content]);
                
                logAdminAction($pdo, $current_user_id, 'Envoi message', "À: " . $recipient['id']);
                $success = "Message envoyé avec succès !";
                break;
                
            case 'mark_read':
                $message_id = $_POST['message_id'];
                
                // Vérifier que le message appartient à l'utilisateur
                $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND recipient_id = ? AND is_read = 0");
                $stmt->execute([$message_id, $current_user_id]);
                
                if ($stmt->rowCount() > 0) {
                    $success = "Message marqué comme lu.";
                }
                break;
                
            case 'archive_message':
                $message_id = $_POST['message_id'];
                $is_sender = $_POST['is_sender'] ?? 0;
                
                if ($is_sender) {
                    $stmt = $pdo->prepare("UPDATE messages SET is_archived_sender = 1 
                        WHERE id = ? AND sender_id = ?");
                    $stmt->execute([$message_id, $current_user_id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE messages SET is_archived_recipient = 1 
                        WHERE id = ? AND recipient_id = ?");
                    $stmt->execute([$message_id, $current_user_id]);
                }
                
                $success = "Message archivé.";
                break;
                
            case 'delete_message':
                $message_id = $_POST['message_id'];
                
                // Seul l'admin principal (Enoe) peut supprimer des messages
                if ($current_user_role !== 'super_admin') {
                    throw new Exception("Permission refusée.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM messages WHERE id = ?");
                $stmt->execute([$message_id]);
                
                logAdminAction($pdo, $current_user_id, 'Suppression message', "Message ID: $message_id");
                $success = "Message supprimé.";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer la liste des utilisateurs (pour envoyer des messages)
$users = $pdo->query("SELECT id, username, role FROM users WHERE id != $current_user_id ORDER BY username")->fetchAll();

// Récupérer les messages reçus
$stmt = $pdo->prepare("
    SELECT m.*, u.username as sender_username, u.role as sender_role
    FROM messages m
    JOIN users u ON m.sender_id = u.id
    WHERE m.recipient_id = ? AND m.is_archived_recipient = 0
    ORDER BY m.created_at DESC
");
$stmt->execute([$current_user_id]);
$received_messages = $stmt->fetchAll();

// Récupérer les messages envoyés
$stmt = $pdo->prepare("
    SELECT m.*, u.username as recipient_username, u.role as recipient_role
    FROM messages m
    JOIN users u ON m.recipient_id = u.id
    WHERE m.sender_id = ? AND m.is_archived_sender = 0
    ORDER BY m.created_at DESC
");
$stmt->execute([$current_user_id]);
$sent_messages = $stmt->fetchAll();

// Récupérer les logs (pour modérateurs et admins)
$can_view_logs = false;
$message_logs = [];

// Vérifier les permissions pour voir les logs
if ($current_user_role === 'super_admin') {
    // Admin principal voit TOUS les logs
    $can_view_logs = true;
    $message_logs = $pdo->query("
        SELECT ml.*, 
               us.username as sender_username, us.role as sender_role,
               ur.username as recipient_username, ur.role as recipient_role
        FROM message_logs ml
        JOIN users us ON ml.sender_id = us.id
        JOIN users ur ON ml.recipient_id = ur.id
        ORDER BY ml.logged_at DESC
        LIMIT 100
    ")->fetchAll();
} elseif (hasAccess('access_moderation')) {
    // Modérateurs voient les logs SAUF les messages entre membres de l'état-major
    $can_view_logs = true;
    $message_logs = $pdo->query("
        SELECT ml.*, 
               us.username as sender_username, us.role as sender_role,
               ur.username as recipient_username, ur.role as recipient_role
        FROM message_logs ml
        JOIN users us ON ml.sender_id = us.id
        JOIN users ur ON ml.recipient_id = ur.id
        WHERE NOT (us.role IN ('etat_major', 'chef', 'super_admin') 
                   AND ur.role IN ('etat_major', 'chef', 'super_admin'))
        ORDER BY ml.logged_at DESC
        LIMIT 100
    ")->fetchAll();
}

// Statistiques
$stats = [
    'unread' => $pdo->prepare("SELECT COUNT(*) FROM messages WHERE recipient_id = ? AND is_read = 0")->execute([$current_user_id]) ? $pdo->query("SELECT COUNT(*) FROM messages WHERE recipient_id = $current_user_id AND is_read = 0")->fetchColumn() : 0,
    'total_received' => count($received_messages),
    'total_sent' => count($sent_messages),
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '/includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-envelope text-blue-500 mr-3"></i>Messagerie
                    </h1>
                    <p class="text-gray-400">Communication interne CFWT</p>
                </div>
                <div class="flex space-x-3">
                    <button onclick="document.getElementById('modal-new-message').classList.remove('hidden')" 
                            class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-paper-plane mr-2"></i>Nouveau message
                    </button>
                    <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Dashboard
                    </a>
                </div>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-red-900 to-red-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-300 text-sm">Messages non lus</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['unread']; ?></p>
                        </div>
                        <i class="fas fa-envelope text-red-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-300 text-sm">Messages reçus</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_received']; ?></p>
                        </div>
                        <i class="fas fa-inbox text-blue-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Messages envoyés</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_sent']; ?></p>
                        </div>
                        <i class="fas fa-paper-plane text-green-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex space-x-4 border-b border-gray-700 mb-6">
                    <button onclick="showTab('received')" id="tab-received" class="tab-button px-6 py-3 font-semibold text-white border-b-2 border-blue-500">
                        <i class="fas fa-inbox mr-2"></i>Reçus (<?php echo $stats['unread']; ?>)
                    </button>
                    <button onclick="showTab('sent')" id="tab-sent" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-paper-plane mr-2"></i>Envoyés
                    </button>
                    <?php if ($can_view_logs): ?>
                        <button onclick="showTab('logs')" id="tab-logs" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                            <i class="fas fa-history mr-2"></i>Logs
                            <?php if ($current_user_role === 'super_admin'): ?>
                                <span class="text-xs bg-yellow-600 px-2 py-1 rounded ml-2">Admin</span>
                            <?php endif; ?>
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Tab: Messages reçus -->
                <div id="content-received" class="tab-content">
                    <?php if (empty($received_messages)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-inbox text-gray-600 text-6xl mb-4"></i>
                            <p class="text-gray-400 text-xl">Aucun message reçu</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($received_messages as $msg): ?>
                                <div class="bg-gray-700 p-4 rounded-lg <?php echo !$msg['is_read'] ? 'border-l-4 border-blue-500' : ''; ?>">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <?php if (!$msg['is_read']): ?>
                                                    <span class="bg-blue-600 text-white text-xs px-2 py-1 rounded font-bold">NOUVEAU</span>
                                                <?php endif; ?>
                                                <span class="text-gray-400 text-sm">
                                                    De: <span class="text-white font-semibold"><?php echo htmlspecialchars($msg['sender_username']); ?></span>
                                                    <span class="px-2 py-0.5 rounded text-xs ml-2
                                                        <?php 
                                                        echo $msg['sender_role'] == 'super_admin' ? 'bg-red-600 text-white' :
                                                             ($msg['sender_role'] == 'chef' ? 'bg-purple-600 text-white' :
                                                             ($msg['sender_role'] == 'etat_major' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'));
                                                        ?>">
                                                        <?php echo htmlspecialchars($msg['sender_role']); ?>
                                                    </span>
                                                </span>
                                            </div>
                                            <h3 class="text-lg font-bold text-white mb-2">
                                                <i class="fas fa-envelope-open-text mr-2 text-blue-400"></i>
                                                <?php echo htmlspecialchars($msg['title']); ?>
                                            </h3>
                                            <p class="text-gray-300 mb-2"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                                            <p class="text-gray-500 text-sm">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                            </p>
                                        </div>
                                        <div class="flex space-x-2 ml-4">
                                            <?php if (!$msg['is_read']): ?>
                                                <button onclick="markRead(<?php echo $msg['id']; ?>)" 
                                                        class="text-blue-400 hover:text-blue-300 p-2" title="Marquer comme lu">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            <?php endif; ?>
                                            <button onclick="replyMessage('<?php echo htmlspecialchars($msg['sender_username']); ?>', <?php echo $msg['sender_id']; ?>, 'Re: <?php echo htmlspecialchars($msg['title']); ?>')" 
                                                    class="text-green-400 hover:text-green-300 p-2" title="Répondre">
                                                <i class="fas fa-reply"></i>
                                            </button>
                                            <button onclick="archiveMessage(<?php echo $msg['id']; ?>, 0)" 
                                                    class="text-yellow-400 hover:text-yellow-300 p-2" title="Archiver">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Messages envoyés -->
                <div id="content-sent" class="tab-content hidden">
                    <?php if (empty($sent_messages)): ?>
                        <div class="text-center py-12">
                            <i class="fas fa-paper-plane text-gray-600 text-6xl mb-4"></i>
                            <p class="text-gray-400 text-xl">Aucun message envoyé</p>
                        </div>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($sent_messages as $msg): ?>
                                <div class="bg-gray-700 p-4 rounded-lg">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="text-gray-400 text-sm">
                                                    À: <span class="text-white font-semibold"><?php echo htmlspecialchars($msg['recipient_username']); ?></span>
                                                    <span class="px-2 py-0.5 rounded text-xs ml-2
                                                        <?php 
                                                        echo $msg['recipient_role'] == 'super_admin' ? 'bg-red-600 text-white' :
                                                             ($msg['recipient_role'] == 'chef' ? 'bg-purple-600 text-white' :
                                                             ($msg['recipient_role'] == 'etat_major' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'));
                                                        ?>">
                                                        <?php echo htmlspecialchars($msg['recipient_role']); ?>
                                                    </span>
                                                </span>
                                                <?php if ($msg['is_read']): ?>
                                                    <span class="text-green-400 text-xs">
                                                        <i class="fas fa-check-double mr-1"></i>Lu
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-500 text-xs">
                                                        <i class="fas fa-check mr-1"></i>Envoyé
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="text-lg font-bold text-white mb-2">
                                                <i class="fas fa-envelope mr-2 text-green-400"></i>
                                                <?php echo htmlspecialchars($msg['title']); ?>
                                            </h3>
                                            <p class="text-gray-300 mb-2"><?php echo nl2br(htmlspecialchars($msg['content'])); ?></p>
                                            <p class="text-gray-500 text-sm">
                                                <i class="fas fa-clock mr-1"></i>
                                                <?php echo date('d/m/Y H:i', strtotime($msg['created_at'])); ?>
                                                <?php if ($msg['is_read'] && $msg['read_at']): ?>
                                                    · Lu le <?php echo date('d/m/Y H:i', strtotime($msg['read_at'])); ?>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div class="flex space-x-2 ml-4">
                                            <button onclick="archiveMessage(<?php echo $msg['id']; ?>, 1)" 
                                                    class="text-yellow-400 hover:text-yellow-300 p-2" title="Archiver">
                                                <i class="fas fa-archive"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Tab: Logs (modérateurs et admins) -->
                <?php if ($can_view_logs): ?>
                    <div id="content-logs" class="tab-content hidden">
                        <div class="mb-4 bg-yellow-900/20 border border-yellow-500/50 rounded p-4">
                            <p class="text-yellow-300 text-sm">
                                <i class="fas fa-info-circle mr-2"></i>
                                <?php if ($current_user_role === 'super_admin'): ?>
                                    <strong>Admin Principal:</strong> Vous voyez TOUS les logs de messages, y compris ceux entre membres de l'état-major.
                                <?php else: ?>
                                    <strong>Modérateur:</strong> Vous voyez les logs SAUF les messages entre membres de l'état-major.
                                <?php endif; ?>
                            </p>
                        </div>

                        <?php if (empty($message_logs)): ?>
                            <div class="text-center py-12">
                                <i class="fas fa-history text-gray-600 text-6xl mb-4"></i>
                                <p class="text-gray-400 text-xl">Aucun log disponible</p>
                            </div>
                        <?php else: ?>
                            <div class="overflow-x-auto">
                                <table class="w-full">
                                    <thead class="bg-gray-700">
                                        <tr>
                                            <th class="px-4 py-3 text-left text-white">Date/Heure</th>
                                            <th class="px-4 py-3 text-left text-white">Expéditeur</th>
                                            <th class="px-4 py-3 text-left text-white">Destinataire</th>
                                            <th class="px-4 py-3 text-left text-white">Titre</th>
                                            <th class="px-4 py-3 text-left text-white">Contenu</th>
                                            <?php if ($current_user_role === 'super_admin'): ?>
                                                <th class="px-4 py-3 text-center text-white">Actions</th>
                                            <?php endif; ?>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-700">
                                        <?php foreach ($message_logs as $log): ?>
                                            <tr class="hover:bg-gray-700">
                                                <td class="px-4 py-3 text-gray-400 text-sm whitespace-nowrap">
                                                    <?php echo date('d/m/Y H:i', strtotime($log['logged_at'])); ?>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($log['sender_username']); ?></p>
                                                        <span class="text-xs px-2 py-0.5 rounded
                                                            <?php 
                                                            echo $log['sender_role'] == 'super_admin' ? 'bg-red-600 text-white' :
                                                                 ($log['sender_role'] == 'chef' ? 'bg-purple-600 text-white' :
                                                                 ($log['sender_role'] == 'etat_major' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'));
                                                            ?>">
                                                            <?php echo htmlspecialchars($log['sender_role']); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3">
                                                    <div>
                                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($log['recipient_username']); ?></p>
                                                        <span class="text-xs px-2 py-0.5 rounded
                                                            <?php 
                                                            echo $log['recipient_role'] == 'super_admin' ? 'bg-red-600 text-white' :
                                                                 ($log['recipient_role'] == 'chef' ? 'bg-purple-600 text-white' :
                                                                 ($log['recipient_role'] == 'etat_major' ? 'bg-blue-600 text-white' : 'bg-gray-600 text-white'));
                                                            ?>">
                                                            <?php echo htmlspecialchars($log['recipient_role']); ?>
                                                        </span>
                                                    </div>
                                                </td>
                                                <td class="px-4 py-3 text-white font-semibold">
                                                    <?php echo htmlspecialchars($log['title']); ?>
                                                </td>
                                                <td class="px-4 py-3 text-gray-300 text-sm max-w-md">
                                                    <div class="truncate"><?php echo htmlspecialchars(substr($log['content'], 0, 100)); ?>...</div>
                                                </td>
                                                <?php if ($current_user_role === 'super_admin'): ?>
                                                    <td class="px-4 py-3 text-center">
                                                        <button onclick="deleteMessage(<?php echo $log['message_id']; ?>)" 
                                                                class="text-red-400 hover:text-red-300">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Nouveau message -->
    <div id="modal-new-message" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-paper-plane mr-2 text-blue-400"></i>Nouveau message
            </h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="send_message">
                
                <div>
                    <label class="block text-white mb-2">Destinataire *</label>
                    <select name="recipient_id" id="recipient-select" required 
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="">Sélectionnez un destinataire</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?> 
                                (<?php echo htmlspecialchars($user['role']); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Titre *</label>
                    <input type="text" name="title" id="message-title" required maxlength="255"
                           placeholder="Objet du message"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Message *</label>
                    <textarea name="content" required rows="8"
                              placeholder="Contenu de votre message..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                    <p class="text-gray-400 text-sm mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Les messages sont enregistrés dans les logs et peuvent être consultés par les modérateurs.
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-paper-plane mr-2"></i>Envoyer
                    </button>
                    <button type="button" onclick="closeNewMessageModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('border-blue-500', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('border-blue-500', 'text-white');
        tabBtn.classList.remove('text-gray-400');
    }

    function markRead(messageId) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="mark_read">
            <input type="hidden" name="message_id" value="${messageId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function archiveMessage(messageId, isSender) {
        if (confirm('Archiver ce message ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="archive_message">
                <input type="hidden" name="message_id" value="${messageId}">
                <input type="hidden" name="is_sender" value="${isSender}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteMessage(messageId) {
        if (confirm('ATTENTION : Supprimer définitivement ce message ? Cette action est irréversible.')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_message">
                <input type="hidden" name="message_id" value="${messageId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function replyMessage(username, userId, originalTitle) {
        document.getElementById('modal-new-message').classList.remove('hidden');
        document.getElementById('recipient-select').value = userId;
        document.getElementById('message-title').value = originalTitle;
    }

    function closeNewMessageModal() {
        document.getElementById('modal-new-message').classList.add('hidden');
        document.getElementById('recipient-select').value = '';
        document.getElementById('message-title').value = '';
        document.querySelector('textarea[name="content"]').value = '';
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('modal-new-message').addEventListener('click', function(e) {
        if (e.target === this) {
            closeNewMessageModal();
        }
    });
    </script>

    <?php include '/includes/footer.php'; ?>
</body>
</html>
