<?php
// admin/maintenance_manager.php
require_once '../config.php';

// IMPORTANT: Seul Enoe peut accéder à cette page
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Vérifier que c'est bien Enoe
$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || strtolower($user['username']) !== 'enoe') {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Créer la table de maintenance si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS maintenance_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_active BOOLEAN DEFAULT FALSE,
        maintenance_type ENUM('scheduled', 'emergency', 'update', 'technical', 'custom') DEFAULT 'scheduled',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        estimated_duration VARCHAR(100),
        start_time DATETIME,
        end_time DATETIME,
        show_countdown BOOLEAN DEFAULT TRUE,
        show_discord_link BOOLEAN DEFAULT TRUE,
        custom_icon VARCHAR(50) DEFAULT 'fa-cog',
        icon_color VARCHAR(20) DEFAULT 'text-blue-500',
        theme_color VARCHAR(20) DEFAULT 'blue',
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    // Table existe déjà
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'activate_maintenance':
                $type = $_POST['maintenance_type'];
                $title = $_POST['title'];
                $message = $_POST['message'];
                $estimated_duration = $_POST['estimated_duration'];
                $start_time = $_POST['start_time'] ?: date('Y-m-d H:i:s');
                $end_time = $_POST['end_time'] ?: null;
                $show_countdown = isset($_POST['show_countdown']) ? 1 : 0;
                $show_discord = isset($_POST['show_discord_link']) ? 1 : 0;
                $custom_icon = $_POST['custom_icon'];
                $icon_color = $_POST['icon_color'];
                $theme_color = $_POST['theme_color'];
                
                // Désactiver toutes les maintenances actives
                $pdo->query("UPDATE maintenance_settings SET is_active = 0");
                
                // Créer la nouvelle maintenance
                $stmt = $pdo->prepare("INSERT INTO maintenance_settings 
                    (is_active, maintenance_type, title, message, estimated_duration, 
                     start_time, end_time, show_countdown, show_discord_link, 
                     custom_icon, icon_color, theme_color, created_by) 
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $type, $title, $message, $estimated_duration,
                    $start_time, $end_time, $show_countdown, $show_discord,
                    $custom_icon, $icon_color, $theme_color, $_SESSION['user_id']
                ]);
                
                // Mettre à jour site_content pour activer le mode maintenance
                $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                    VALUES ('appearance', 'maintenance_mode', '1', ?, NOW()) 
                    ON DUPLICATE KEY UPDATE content = '1', updated_by = ?, updated_at = NOW()")
                    ->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Activation maintenance', $title);
                $success = "Maintenance activée avec succès !";
                break;
                
            case 'deactivate_maintenance':
                $pdo->query("UPDATE maintenance_settings SET is_active = 0");
                
                // Désactiver dans site_content
                $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                    VALUES ('appearance', 'maintenance_mode', '0', ?, NOW()) 
                    ON DUPLICATE KEY UPDATE content = '0', updated_by = ?, updated_at = NOW()")
                    ->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Désactivation maintenance', '');
                $success = "Maintenance désactivée avec succès !";
                break;
                
            case 'delete_maintenance':
                $id = $_POST['maintenance_id'];
                $stmt = $pdo->prepare("DELETE FROM maintenance_settings WHERE id = ?");
                $stmt->execute([$id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression maintenance', "ID: $id");
                $success = "Configuration de maintenance supprimée !";
                break;
                
            case 'quick_activate':
                $id = $_POST['maintenance_id'];
                
                // Désactiver toutes les maintenances
                $pdo->query("UPDATE maintenance_settings SET is_active = 0");
                
                // Activer celle sélectionnée
                $stmt = $pdo->prepare("UPDATE maintenance_settings SET is_active = 1 WHERE id = ?");
                $stmt->execute([$id]);
                
                // Activer dans site_content
                $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                    VALUES ('appearance', 'maintenance_mode', '1', ?, NOW()) 
                    ON DUPLICATE KEY UPDATE content = '1', updated_by = ?, updated_at = NOW()")
                    ->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Activation rapide maintenance', "ID: $id");
                $success = "Maintenance activée !";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer la maintenance active
$active_maintenance = $pdo->query("SELECT * FROM maintenance_settings WHERE is_active = 1 LIMIT 1")->fetch();

// Récupérer toutes les configurations
$all_maintenances = $pdo->query("SELECT m.*, u.username 
    FROM maintenance_settings m 
    LEFT JOIN users u ON m.created_by = u.id 
    ORDER BY m.created_at DESC")->fetchAll();

// Templates de maintenance prédéfinis
$templates = [
    'scheduled' => [
        'title' => 'Maintenance Programmée',
        'message' => 'Nous effectuons une maintenance programmée pour améliorer nos services. Le site sera de nouveau accessible très prochainement.',
        'icon' => 'fa-calendar-check',
        'color' => 'blue',
        'icon_color' => 'text-blue-500'
    ],
    'emergency' => [
        'title' => 'Maintenance d\'Urgence',
        'message' => 'Une intervention urgente est en cours pour résoudre un problème technique. Nous nous excusons pour la gêne occasionnée.',
        'icon' => 'fa-exclamation-triangle',
        'color' => 'red',
        'icon_color' => 'text-red-500'
    ],
    'update' => [
        'title' => 'Mise à Jour en Cours',
        'message' => 'Nous installons de nouvelles fonctionnalités pour améliorer votre expérience. Le site sera bientôt de retour avec des nouveautés !',
        'icon' => 'fa-download',
        'color' => 'green',
        'icon_color' => 'text-green-500'
    ],
    'technical' => [
        'title' => 'Maintenance Technique',
        'message' => 'Notre équipe technique effectue des optimisations pour garantir les meilleures performances possibles.',
        'icon' => 'fa-tools',
        'color' => 'yellow',
        'icon_color' => 'text-yellow-500'
    ],
    'custom' => [
        'title' => 'Maintenance Personnalisée',
        'message' => 'Le site est temporairement indisponible.',
        'icon' => 'fa-cog',
        'color' => 'purple',
        'icon_color' => 'text-purple-500'
    ]
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Maintenance - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="../css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php 
    // Ne pas afficher le header en mode maintenance pour les non-admins
    if (!isMaintenanceMode($pdo) || isAdmin()) {
        include '../includes/header.php'; 
    }
    ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-tools text-red-500 mr-3"></i>Gestion de la Maintenance
                    </h1>
                    <p class="text-gray-400">
                        <i class="fas fa-crown text-yellow-500 mr-2"></i>
                        Accès réservé à Enoe
                    </p>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
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

            <!-- Statut actuel -->
            <div class="bg-gray-800 rounded-lg p-6 mb-8 border-2 <?php echo $active_maintenance ? 'border-red-500' : 'border-green-500'; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-white mb-2">
                            Statut actuel : 
                            <?php if ($active_maintenance): ?>
                                <span class="text-red-500">
                                    <i class="fas fa-exclamation-triangle mr-2"></i>MAINTENANCE ACTIVE
                                </span>
                            <?php else: ?>
                                <span class="text-green-500">
                                    <i class="fas fa-check-circle mr-2"></i>SITE OPÉRATIONNEL
                                </span>
                            <?php endif; ?>
                        </h2>
                        
                        <?php if ($active_maintenance): ?>
                            <p class="text-gray-400">
                                Type: <span class="text-white font-semibold"><?php echo ucfirst($active_maintenance['maintenance_type']); ?></span>
                            </p>
                            <p class="text-gray-400">
                                Début: <span class="text-white"><?php echo date('d/m/Y H:i', strtotime($active_maintenance['start_time'])); ?></span>
                            </p>
                            <?php if ($active_maintenance['end_time']): ?>
                                <p class="text-gray-400">
                                    Fin prévue: <span class="text-white"><?php echo date('d/m/Y H:i', strtotime($active_maintenance['end_time'])); ?></span>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($active_maintenance): ?>
                        <form method="POST" onsubmit="return confirm('Désactiver la maintenance ?');">
                            <input type="hidden" name="action" value="deactivate_maintenance">
                            <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 transition font-bold">
                                <i class="fas fa-power-off mr-2"></i>Désactiver la Maintenance
                            </button>
                        </form>
                    <?php else: ?>
                        <button onclick="document.getElementById('modal-new-maintenance').classList.remove('hidden')" 
                                class="bg-red-600 text-white px-8 py-4 rounded-lg hover:bg-red-700 transition font-bold">
                            <i class="fas fa-exclamation-triangle mr-2"></i>Activer une Maintenance
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Templates rapides -->
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-bolt text-yellow-500 mr-2"></i>Activation Rapide
                </h2>
                <div class="grid md:grid-cols-5 gap-4">
                    <?php foreach ($templates as $type => $template): ?>
                        <button onclick="fillTemplate('<?php echo $type; ?>')" 
                                class="bg-gray-700 hover:bg-gray-600 p-4 rounded-lg transition text-center">
                            <i class="fas <?php echo $template['icon']; ?> text-4xl mb-3 <?php echo $template['icon_color']; ?>"></i>
                            <p class="text-white font-semibold text-sm"><?php echo $template['title']; ?></p>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Configurations sauvegardées -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">
                        <i class="fas fa-history text-blue-500 mr-2"></i>Configurations Sauvegardées
                    </h2>
                    <button onclick="document.getElementById('modal-new-maintenance').classList.remove('hidden')" 
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Nouvelle Configuration
                    </button>
                </div>

                <div class="space-y-4">
                    <?php foreach ($all_maintenances as $maint): ?>
                        <div class="bg-gray-700 p-6 rounded-lg <?php echo $maint['is_active'] ? 'border-2 border-red-500' : ''; ?>">
                            <div class="flex justify-between items-start">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-3">
                                        <i class="fas <?php echo $maint['custom_icon']; ?> text-3xl <?php echo $maint['icon_color']; ?>"></i>
                                        <div>
                                            <h3 class="text-xl font-bold text-white">
                                                <?php echo htmlspecialchars($maint['title']); ?>
                                            </h3>
                                            <span class="px-3 py-1 rounded-full text-xs font-semibold
                                                <?php 
                                                $colors = [
                                                    'scheduled' => 'bg-blue-600',
                                                    'emergency' => 'bg-red-600',
                                                    'update' => 'bg-green-600',
                                                    'technical' => 'bg-yellow-600',
                                                    'custom' => 'bg-purple-600'
                                                ];
                                                echo $colors[$maint['maintenance_type']] ?? 'bg-gray-600';
                                                ?> text-white">
                                                <?php echo ucfirst($maint['maintenance_type']); ?>
                                            </span>
                                            <?php if ($maint['is_active']): ?>
                                                <span class="ml-2 px-3 py-1 bg-red-600 text-white rounded-full text-xs font-bold">
                                                    ACTIVE
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <p class="text-gray-300 mb-3"><?php echo nl2br(htmlspecialchars($maint['message'])); ?></p>
                                    
                                    <div class="grid md:grid-cols-3 gap-4 text-sm">
                                        <?php if ($maint['estimated_duration']): ?>
                                            <div class="text-gray-400">
                                                <i class="fas fa-clock mr-2"></i>
                                                Durée: <span class="text-white"><?php echo htmlspecialchars($maint['estimated_duration']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-gray-400">
                                            <i class="fas fa-user mr-2"></i>
                                            Par: <span class="text-white"><?php echo htmlspecialchars($maint['username']); ?></span>
                                        </div>
                                        
                                        <div class="text-gray-400">
                                            <i class="fas fa-calendar mr-2"></i>
                                            <?php echo date('d/m/Y H:i', strtotime($maint['created_at'])); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex flex-col space-y-2 ml-4">
                                    <?php if (!$maint['is_active']): ?>
                                        <form method="POST">
                                            <input type="hidden" name="action" value="quick_activate">
                                            <input type="hidden" name="maintenance_id" value="<?php echo $maint['id']; ?>">
                                            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition text-sm whitespace-nowrap">
                                                <i class="fas fa-play mr-2"></i>Activer
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                    
                                    <form method="POST" onsubmit="return confirm('Supprimer cette configuration ?');">
                                        <input type="hidden" name="action" value="delete_maintenance">
                                        <input type="hidden" name="maintenance_id" value="<?php echo $maint['id']; ?>">
                                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition text-sm whitespace-nowrap">
                                            <i class="fas fa-trash mr-2"></i>Supprimer
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($all_maintenances)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-inbox text-6xl mb-4"></i>
                            <p>Aucune configuration de maintenance enregistrée</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nouvelle Maintenance -->
    <div id="modal-new-maintenance" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-gray-800 p-8 rounded-lg max-w-4xl w-full my-8">
            <h2 class="text-3xl font-bold text-white mb-6">
                <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Nouvelle Maintenance
            </h2>
            
            <form method="POST" id="maintenance-form" class="space-y-6">
                <input type="hidden" name="action" value="activate_maintenance">
                
                <!-- Type de maintenance -->
                <div>
                    <label class="block text-white font-semibold mb-3">Type de maintenance *</label>
                    <div class="grid md:grid-cols-5 gap-3">
                        <?php foreach ($templates as $type => $template): ?>
                            <label class="cursor-pointer">
                                <input type="radio" name="maintenance_type" value="<?php echo $type; ?>" 
                                       class="hidden peer" required
                                       onchange="updatePreview()">
                                <div class="bg-gray-700 p-4 rounded-lg text-center border-2 border-gray-600 
                                            peer-checked:border-<?php echo $template['color']; ?>-500 
                                            peer-checked:bg-gray-600 transition hover:bg-gray-650">
                                    <i class="fas <?php echo $template['icon']; ?> text-3xl mb-2 <?php echo $template['icon_color']; ?>"></i>
                                    <p class="text-white text-sm font-semibold"><?php echo ucfirst($type); ?></p>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Titre -->
                    <div>
                        <label class="block text-white font-semibold mb-2">Titre *</label>
                        <input type="text" name="title" id="maint-title" required maxlength="255"
                               placeholder="Ex: Maintenance Programmée"
                               onkeyup="updatePreview()"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>

                    <!-- Durée estimée -->
                    <div>
                        <label class="block text-white font-semibold mb-2">Durée estimée</label>
                        <input type="text" name="estimated_duration" id="maint-duration"
                               placeholder="Ex: 30 minutes, 2 heures, etc."
                               onkeyup="updatePreview()"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>

                <!-- Message -->
                <div>
                    <label class="block text-white font-semibold mb-2">Message *</label>
                    <textarea name="message" id="maint-message" required rows="4"
                              placeholder="Message à afficher aux visiteurs..."
                              onkeyup="updatePreview()"
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <!-- Heure de début -->
                    <div>
                        <label class="block text-white font-semibold mb-2">Heure de début</label>
                        <input type="datetime-local" name="start_time"
                               value="<?php echo date('Y-m-d\TH:i'); ?>"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <p class="text-gray-500 text-sm mt-1">Laisser vide pour maintenant</p>
                    </div>

                    <!-- Heure de fin -->
                    <div>
                        <label class="block text-white font-semibold mb-2">Heure de fin prévue</label>
                        <input type="datetime-local" name="end_time"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <p class="text-gray-500 text-sm mt-1">Optionnel</p>
                    </div>
                </div>

                <!-- Personnalisation -->
                <div class="bg-gray-700 p-6 rounded-lg">
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-palette text-purple-500 mr-2"></i>Personnalisation visuelle
                    </h3>
                    
                    <div class="grid md:grid-cols-3 gap-4">
                        <!-- Icône -->
                        <div>
                            <label class="block text-white mb-2">Icône</label>
                            <select name="custom_icon" id="maint-icon" onchange="updatePreview()"
                                    class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                <option value="fa-cog">⚙️ Engrenage</option>
                                <option value="fa-tools">🔧 Outils</option>
                                <option value="fa-exclamation-triangle">⚠️ Triangle</option>
                                <option value="fa-calendar-check">📅 Calendrier</option>
                                <option value="fa-download">⬇️ Téléchargement</option>
                                <option value="fa-server">🖥️ Serveur</option>
                                <option value="fa-shield-alt">🛡️ Bouclier</option>
                            </select>
                        </div>

                        <!-- Couleur icône -->
                        <div>
                            <label class="block text-white mb-2">Couleur icône</label>
                            <select name="icon_color" id="maint-icon-color" onchange="updatePreview()"
                                    class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                <option value="text-blue-500">🔵 Bleu</option>
                                <option value="text-red-500">🔴 Rouge</option>
                                <option value="text-green-500">🟢 Vert</option>
                                <option value="text-yellow-500">🟡 Jaune</option>
                                <option value="text-purple-500">🟣 Violet</option>
                                <option value="text-orange-500">🟠 Orange</option>
                            </select>
                        </div>

                        <!-- Thème -->
                        <div>
                            <label class="block text-white mb-2">Thème</label>
                            <select name="theme_color"
                                    class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                <option value="blue">Bleu</option>
                                <option value="red">Rouge</option>
                                <option value="green">Vert</option>
                                <option value="yellow">Jaune</option>
                                <option value="purple">Violet</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Options -->
                <div class="space-y-3">
                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="show_countdown" checked
                               class="w-5 h-5 rounded">
                        <span class="text-white">
                            <i class="fas fa-clock mr-2 text-blue-400"></i>
                            Afficher le compte à rebours (si heure de fin définie)
                        </span>
                    </label>

                    <label class="flex items-center space-x-3 cursor-pointer">
                        <input type="checkbox" name="show_discord_link" checked
                               class="w-5 h-5 rounded">
                        <span class="text-white">
                            <i class="fab fa-discord mr-2 text-blue-400"></i>
                            Afficher le lien Discord
                        </span>
                    </label>
                </div>

                <!-- Prévisualisation -->
                <div class="bg-gray-900 p-6 rounded-lg border-2 border-gray-700">
                    <h3 class="text-white font-bold mb-4">
                        <i class="fas fa-eye text-green-500 mr-2"></i>Aperçu
                    </h3>
                    <div id="preview-container" class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 p-8 rounded text-center">
                        <i id="preview-icon" class="fas fa-cog text-9xl text-blue-500 mb-4"></i>
                        <h2 id="preview-title" class="text-3xl font-bold text-white mb-4">Titre de la maintenance</h2>
                        <p id="preview-message" class="text-gray-300 mb-4">Message de la maintenance</p>
                        <p id="preview-duration" class="text-gray-500 text-sm"></p>
                    </div>
                </div>

                <!-- Boutons -->
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-red-600 text-white px-8 py-4 rounded-lg hover:bg-red-700 transition font-bold text-lg">
                        <i class="fas fa-exclamation-triangle mr-2"></i>Activer la Maintenance
                    </button>
                    <button type="button" onclick="document.getElementById('modal-new-maintenance').classList.add('hidden')" 
                            class="bg-gray-600 text-white px-8 py-4 rounded-lg hover:bg-gray-700 transition font-bold">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    const templates = <?php echo json_encode($templates); ?>;

    function fillTemplate(type) {
        const template = templates[type];
        document.querySelector(`input[value="${type}"]`).checked = true;
        document.getElementById('maint-title').value = template.title;
        document.getElementById('maint-message').value = template.message;
        document.getElementById('maint-icon').value = template.icon;
        document.getElementById('maint-icon-color').value = template.icon_color;
        updatePreview();
        document.getElementById('modal-new-maintenance').classList.remove('hidden');
    }

    function updatePreview() {
        const title = document.getElementById('maint-title').value || 'Titre de la maintenance';
        const message = document.getElementById('maint-message').value || 'Message de la maintenance';
        const duration = document.getElementById('maint-duration').value;
        const icon = document.getElementById('maint-icon').value;
        const iconColor = document.getElementById('maint-icon-color').value;

        document.getElementById('preview-title').textContent = title;
        document.getElementById('preview-message').textContent = message;
        document.getElementById('preview-duration').textContent = duration ? `⏱️ Durée estimée : ${duration}` : '';
        
        const previewIcon = document.getElementById('preview-icon');
        previewIcon.className = `fas ${icon} text-9xl ${iconColor} mb-4`;
    }

    // Initialiser l'aperçu
    updatePreview();
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
