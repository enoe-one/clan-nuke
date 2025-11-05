<?php
require_once '../config.php';

// Vérifier que l'utilisateur est connecté et a les droits full_access
if (!isAdmin() || !hasAccess('access_full')) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_user':
                $stmt = $pdo->prepare("INSERT INTO users (username, password, role, must_change_password, 
                    access_recruitment_player, access_recruitment_faction, access_edit_members, 
                    access_moderation, access_edit_site, access_full, access_create_accounts, 
                    access_manage_legions, access_reset_passwords) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt->execute([
                    $_POST['username'],
                    $password,
                    $_POST['role'],
                    $_POST['must_change_password'] ?? 1,
                    $_POST['access_recruitment_player'] ?? 0,
                    $_POST['access_recruitment_faction'] ?? 0,
                    $_POST['access_edit_members'] ?? 0,
                    $_POST['access_moderation'] ?? 0,
                    $_POST['access_edit_site'] ?? 0,
                    $_POST['access_full'] ?? 0,
                    $_POST['access_create_accounts'] ?? 0,
                    $_POST['access_manage_legions'] ?? 0,
                    $_POST['access_reset_passwords'] ?? 0
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Création utilisateur', $_POST['username']);
                $success = "Utilisateur {$_POST['username']} créé avec succès !";
                break;
                
            case 'delete_user':
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $username = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND id != ?");
                $stmt->execute([$user_id, $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression utilisateur', $username);
                $success = "Utilisateur supprimé avec succès !";
                break;
                
            case 'reset_password':
                $new_password = $_POST['new_password'];
                $user_id = $_POST['user_id'];
                
                $stmt = $pdo->prepare("UPDATE users SET password = ?, must_change_password = 1 WHERE id = ?");
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $user_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Réinitialisation mot de passe', "User ID: $user_id");
                $success = "Mot de passe réinitialisé avec succès !";
                break;
                
            case 'update_permissions':
                $user_id = $_POST['user_id'];
                $stmt = $pdo->prepare("UPDATE users SET 
                    role = ?,
                    access_recruitment_player = ?,
                    access_recruitment_faction = ?,
                    access_edit_members = ?,
                    access_moderation = ?,
                    access_edit_site = ?,
                    access_full = ?,
                    access_create_accounts = ?,
                    access_manage_legions = ?,
                    access_reset_passwords = ?
                    WHERE id = ?");
                    
                $stmt->execute([
                    $_POST['role'],
                    $_POST['access_recruitment_player'] ?? 0,
                    $_POST['access_recruitment_faction'] ?? 0,
                    $_POST['access_edit_members'] ?? 0,
                    $_POST['access_moderation'] ?? 0,
                    $_POST['access_edit_site'] ?? 0,
                    $_POST['access_full'] ?? 0,
                    $_POST['access_create_accounts'] ?? 0,
                    $_POST['access_manage_legions'] ?? 0,
                    $_POST['access_reset_passwords'] ?? 0,
                    $user_id
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification permissions', "User ID: $user_id");
                $success = "Permissions mises à jour avec succès !";
                break;
                
            case 'create_legion':
                $stmt = $pdo->prepare("INSERT INTO legions (nom, description, chef_id) VALUES (?, ?, ?)");
                $stmt->execute([
                    $_POST['nom_legion'],
                    $_POST['description_legion'],
                    $_POST['chef_id'] ?: null
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Création légion', $_POST['nom_legion']);
                $success = "Légion créée avec succès !";
                break;
                
            case 'delete_legion':
                $legion_id = $_POST['legion_id'];
                $stmt = $pdo->prepare("SELECT nom FROM legions WHERE id = ?");
                $stmt->execute([$legion_id]);
                $nom = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM legions WHERE id = ?");
                $stmt->execute([$legion_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression légion', $nom);
                $success = "Légion supprimée avec succès !";
                break;
                    case 'update_legion':
        $stmt = $pdo->prepare("UPDATE legions SET nom = ?, description = ?, chef_id = ? WHERE id = ?");
        $stmt->execute([
            $_POST['nom_legion'],
            $_POST['description_legion'],
            $_POST['chef_id'] ?: null,
            $_POST['legion_id']
        ]);

        logAdminAction($pdo, $_SESSION['user_id'], 'Modification légion', $_POST['nom_legion']);
        $success = "Légion mise à jour avec succès !";
        break;

            case 'backup_database':
                $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
                $backup_path = '../backups/';
                
                if (!is_dir($backup_path)) {
                    mkdir($backup_path, 0755, true);
                }
                
                $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
                $sql_content = "-- Backup CFWT - " . date('Y-m-d H:i:s') . "\n\n";
                
                foreach ($tables as $table) {
                    $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
                    
                    $create = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
                    $sql_content .= $create['Create Table'] . ";\n\n";
                    
                    $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (!empty($rows)) {
                        foreach ($rows as $row) {
                            $values = array_map(function($val) use ($pdo) {
                                return $val === null ? 'NULL' : $pdo->quote($val);
                            }, array_values($row));
                            
                            $sql_content .= "INSERT INTO `$table` VALUES (" . implode(', ', $values) . ");\n";
                        }
                        $sql_content .= "\n";
                    }
                }
                
                file_put_contents($backup_path . $filename, $sql_content);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Backup base de données', $filename);
                $success = "Backup créé : $filename";
                break;
                
            case 'clear_logs':
                $days = intval($_POST['days']);
                $stmt = $pdo->prepare("DELETE FROM admin_logs WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
                $stmt->execute([$days]);
                $deleted = $stmt->rowCount();
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Nettoyage logs', "$deleted entrées supprimées");
                $success = "$deleted entrées de logs supprimées !";
                break;
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Récupérer les données
$users = $pdo->query("SELECT * FROM users ORDER BY role, username")->fetchAll();
$legions = $pdo->query("SELECT l.*, u.username as chef_username, 
    (SELECT COUNT(*) FROM members WHERE legion_id = l.id) as member_count 
    FROM legions l 
    LEFT JOIN users u ON l.chef_id = u.id 
    ORDER BY l.nom")->fetchAll();
$logs = $pdo->query("SELECT al.*, u.username 
    FROM admin_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC LIMIT 50")->fetchAll();

// Statistiques
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_members' => $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'total_legions' => $pdo->query("SELECT COUNT(*) FROM legions")->fetchColumn(),
    'pending_applications' => $pdo->query("SELECT COUNT(*) FROM member_applications WHERE status = 'pending'")->fetchColumn(),
    'pending_factions' => $pdo->query("SELECT COUNT(*) FROM faction_applications WHERE status = 'pending'")->fetchColumn(),
    'pending_reports' => $pdo->query("SELECT COUNT(*) FROM reports WHERE status = 'pending'")->fetchColumn(),
    'total_diplomes' => $pdo->query("SELECT COUNT(*) FROM diplomes")->fetchColumn()
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accès Complet - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-crown text-yellow-500 mr-3"></i>Accès Complet
                    </h1>
                    <p class="text-gray-400">Gestion avancée du système CFWT</p>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour au Dashboard
                </a>
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

            <!-- Statistiques globales -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-300 text-sm">Administrateurs</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_users']; ?></p>
                        </div>
                        <i class="fas fa-user-shield text-blue-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Membres</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_members']; ?></p>
                        </div>
                        <i class="fas fa-users text-green-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-900 to-purple-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-300 text-sm">Légions</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_legions']; ?></p>
                        </div>
                        <i class="fas fa-shield-alt text-purple-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-900 to-yellow-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-300 text-sm">En attente</p>
                            <p class="text-white text-3xl font-bold">
                                <?php echo $stats['pending_applications'] + $stats['pending_factions'] + $stats['pending_reports']; ?>
                            </p>
                        </div>
                        <i class="fas fa-clock text-yellow-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <div class="flex space-x-4 border-b border-gray-700 mb-6">
                    <button onclick="showTab('users')" id="tab-users" class="tab-button px-6 py-3 font-semibold text-white border-b-2 border-blue-500">
                        <i class="fas fa-users mr-2"></i>Utilisateurs
                    </button>
                    <button onclick="showTab('legions')" id="tab-legions" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-shield-alt mr-2"></i>Légions
                    </button>
                    <button onclick="showTab('system')" id="tab-system" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-cog mr-2"></i>Système
                    </button>
                    <button onclick="showTab('logs')" id="tab-logs" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-history mr-2"></i>Logs
                    </button>
                </div>

                <!-- Tab: Utilisateurs -->
                <div id="content-users" class="tab-content">
                    <div class="mb-6">
                        <button onclick="document.getElementById('modal-create-user').classList.remove('hidden')" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Créer un utilisateur
                        </button>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-white">Utilisateur</th>
                                    <th class="px-4 py-3 text-left text-white">Rôle</th>
                                    <th class="px-4 py-3 text-left text-white">Permissions</th>
                                    <th class="px-4 py-3 text-left text-white">Créé le</th>
                                    <th class="px-4 py-3 text-center text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($users as $user): ?>
                                    <tr class="hover:bg-gray-700">
                                        <td class="px-4 py-3 text-white font-semibold">
                                            <?php echo htmlspecialchars($user['username']); ?>
                                            <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="text-xs bg-blue-600 px-2 py-1 rounded ml-2">Vous</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-3 py-1 rounded text-sm font-semibold
                                                <?php 
                                                echo $user['role'] == 'super_admin' ? 'bg-red-600 text-white' :
                                                     ($user['role'] == 'chef' ? 'bg-purple-600 text-white' :
                                                     ($user['role'] == 'etat_major' ? 'bg-blue-600 text-white' :
                                                     ($user['role'] == 'recruteur' ? 'bg-green-600 text-white' : 'bg-gray-600 text-white')));
                                                ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-gray-300 text-sm">
                                            <?php
                                            $perms = [];
                                            if ($user['access_full']) $perms[] = 'Full';
                                            if ($user['access_recruitment_player']) $perms[] = 'Recrutement';
                                            if ($user['access_edit_members']) $perms[] = 'Membres';
                                            if ($user['access_moderation']) $perms[] = 'Modération';
                                            echo implode(', ', $perms) ?: 'Aucune';
                                            ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-400 text-sm">
                                            <?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="editUser(<?php echo $user['id']; ?>)" 
                                                    class="text-blue-400 hover:text-blue-300 mx-1">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="resetPassword(<?php echo $user['id']; ?>)" 
                                                    class="text-yellow-400 hover:text-yellow-300 mx-1">
                                                <i class="fas fa-key"></i>
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                                        class="text-red-400 hover:text-red-300 mx-1">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Légions -->
                <div id="content-legions" class="tab-content hidden">
                    <div class="mb-6">
                        <button onclick="document.getElementById('modal-create-legion').classList.remove('hidden')" 
                                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                            <i class="fas fa-plus mr-2"></i>Créer une légion
                        </button>
                    </div>

                    <div class="grid md:grid-cols-2 gap-6">
                        <?php foreach ($legions as $legion): ?>
                            <div class="bg-gray-700 p-6 rounded-lg">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-white mb-2">
                                            <i class="fas fa-shield-alt text-purple-500 mr-2"></i>
                                            <?php echo htmlspecialchars($legion['nom']); ?>
                                        </h3>
                                        <p class="text-gray-400 text-sm">
                                            <?php echo htmlspecialchars($legion['description']); ?>
                                        </p>
                                    </div>
                                    <span class="bg-purple-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                        <?php echo $legion['member_count']; ?> membres
                                    </span>
                                </div>
                                
                                <?php if ($legion['chef_username']): ?>
                                    <p class="text-gray-400 text-sm mb-4">
                                        <i class="fas fa-crown text-yellow-500 mr-2"></i>
                                        Chef: <?php echo htmlspecialchars($legion['chef_username']); ?>
                                    </p>
                                <?php endif; ?>
                                
                                <div class="flex space-x-2">
                                    <button onclick="editLegion(<?php echo $legion['id']; ?>)" 
                                            class="flex-1 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                        <i class="fas fa-edit mr-2"></i>Modifier
                                    </button>
                                    <button onclick="deleteLegion(<?php echo $legion['id']; ?>, '<?php echo htmlspecialchars($legion['nom']); ?>')" 
                                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Tab: Système -->
                <div id="content-system" class="tab-content hidden">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Backup -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fas fa-database text-blue-500 mr-2"></i>Backup Base de données
                            </h3>
                            <p class="text-gray-400 mb-4">Créer une sauvegarde complète de la base de données</p>
                            <form method="POST">
                                <input type="hidden" name="action" value="backup_database">
                                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-download mr-2"></i>Créer un Backup
                                </button>
                            </form>
                        </div>

                        <!-- Clear Logs -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fas fa-broom text-yellow-500 mr-2"></i>Nettoyer les Logs
                            </h3>
                            <p class="text-gray-400 mb-4">Supprimer les logs de plus de X jours</p>
                            <form method="POST" class="space-y-3">
                                <input type="hidden" name="action" value="clear_logs">
                                <select name="days" class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                    <option value="30">Plus de 30 jours</option>
                                    <option value="60">Plus de 60 jours</option>
                                    <option value="90">Plus de 90 jours</option>
                                </select>
                                <button type="submit" class="w-full bg-yellow-600 text-white px-6 py-3 rounded-lg hover:bg-yellow-700 transition">
                                    <i class="fas fa-trash mr-2"></i>Nettoyer
                                </button>
                            </form>
                        </div>

                        <!-- Informations système -->
                        <div class="bg-gray-700 p-6 rounded-lg md:col-span-2">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fas fa-info-circle text-green-500 mr-2"></i>Informations Système
                            </h3>
                            <div class="grid md:grid-cols-3 gap-4 text-gray-300">
                                <div>
                                    <p class="text-sm text-gray-500">Version PHP</p>
                                    <p class="font-semibold"><?php echo phpversion(); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Base de données</p>
                                    <p class="font-semibold"><?php echo $pdo->query("SELECT VERSION()")->fetchColumn(); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Serveur</p>
                                    <p class="font-semibold"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Logs -->
                <div id="content-logs" class="tab-content hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-white">Date/Heure</th>
                                    <th class="px-4 py-3 text-left text-white">Utilisateur</th>
                                    <th class="px-4 py-3 text-left text-white">Action</th>
                                    <th class="px-4 py-3 text-left text-white">Détails</th>
                                    <th class="px-4 py-3 text-left text-white">IP</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($logs as $log): ?>
                                    <tr class="hover:bg-gray-700">
                                        <td class="px-4 py-3 text-gray-400 text-sm">
                                            <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                        </td>
                                        <td class="px-4 py-3 text-white">
                                            <?php echo htmlspecialchars($log['username']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-blue-400">
                                            <?php echo htmlspecialchars($log['action']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-300 text-sm">
                                            <?php echo htmlspecialchars($log['details'] ?: '-'); ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-400 text-sm font-mono">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Créer utilisateur -->
    <div id="modal-create-user" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Créer un utilisateur</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_user">
                
                <div>
                    <label class="block text-white mb-2">Mot de passe *</label>
                    <input type="password" name="password" required 
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Rôle *</label>
                    <select name="role" required 
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="recruteur">Recruteur</option>
                        <option value="moderateur">Modérateur</option>
                        <option value="etat_major">État-major</option>
                        <option value="chef">Chef</option>
                        <option value="super_admin">Super Admin</option>
                    </select>
                </div>
                
                <div class="border-t border-gray-700 pt-4">
                    <h3 class="text-white font-semibold mb-3">Permissions</h3>
                    <div class="grid md:grid-cols-2 gap-3">
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_recruitment_player" value="1" class="mr-2">
                            Recrutement joueur
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_recruitment_faction" value="1" class="mr-2">
                            Recrutement faction
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_edit_members" value="1" class="mr-2">
                            Modifier membres
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_moderation" value="1" class="mr-2">
                            Modération
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_edit_site" value="1" class="mr-2">
                            Éditer site
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_manage_legions" value="1" class="mr-2">
                            Gérer légions
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_create_accounts" value="1" class="mr-2">
                            Créer comptes
                        </label>
                        <label class="flex items-center text-gray-300">
                            <input type="checkbox" name="access_reset_passwords" value="1" class="mr-2">
                            Reset mots de passe
                        </label>
                        <label class="flex items-center text-yellow-300">
                            <input type="checkbox" name="access_full" value="1" class="mr-2">
                            <strong>Accès complet</strong>
                        </label>
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Créer
                    </button>
                    <button type="button" onclick="document.getElementById('modal-create-user').classList.add('hidden')" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Créer légion -->
    <div id="modal-create-legion" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full mx-4">
            <h2 class="text-2xl font-bold text-white mb-6">Créer une légion</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_legion">
                
                <div>
                    <label class="block text-white mb-2">Nom de la légion *</label>
                    <input type="text" name="nom_legion" required 
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Description</label>
                    <textarea name="description_legion" rows="3"
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Chef de légion (optionnel)</label>
                    <select name="chef_id" class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="">Aucun</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo $user['id']; ?>">
                                <?php echo htmlspecialchars($user['username']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-plus mr-2"></i>Créer
                    </button>
                    <button type="button" onclick="document.getElementById('modal-create-legion').classList.add('hidden')" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        // Masquer tous les contenus
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('border-blue-500', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        // Afficher le contenu sélectionné
        document.getElementById('content-' + tab).classList.remove('hidden');
        document.getElementById('tab-' + tab).classList.add('border-blue-500', 'text-white');
        document.getElementById('tab-' + tab).classList.remove('text-gray-400');
    }

    function deleteUser(id, username) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'utilisateur "${username}" ?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_user">
                <input type="hidden" name="user_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetPassword(id) {
        const newPassword = prompt('Entrez le nouveau mot de passe :');
        if (newPassword && newPassword.length >= 5) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="user_id" value="${id}">
                <input type="hidden" name="new_password" value="${newPassword}">
            `;
            document.body.appendChild(form);
            form.submit();
        } else {
            alert('Le mot de passe doit contenir au moins 5 caractères.');
        }
    }

    function deleteLegion(id, nom) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer la légion "${nom}" ?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_legion">
                <input type="hidden" name="legion_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>Nom d'utilisateur *</label>
                    <input type="text" name="username" required 
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>

                    <label class="block text-white mb-2">
