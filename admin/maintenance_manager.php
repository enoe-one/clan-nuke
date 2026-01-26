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
        maintenance_type ENUM('server_issue', 'technical_danger', 'scheduled', 'emergency_update') DEFAULT 'scheduled',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        estimated_duration VARCHAR(100),
        end_time DATETIME,
        show_countdown BOOLEAN DEFAULT TRUE,
        show_discord_link BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    // Table existe déjà
}

// Insérer les 4 maintenances par défaut si elles n'existent pas
try {
    $count = $pdo->query("SELECT COUNT(*) FROM maintenance_settings")->fetchColumn();
    
    if ($count == 0) {
        $default_maintenances = [
            [
                'type' => 'server_issue',
                'title' => '⚠️ PROBLÈME SERVEUR CRITIQUE',
                'message' => "Nous rencontrons actuellement des problèmes techniques majeurs affectant la stabilité du serveur.\n\nNos équipes travaillent activement pour résoudre ce problème dans les plus brefs délais.\n\nMerci de votre patience et de votre compréhension.",
                'duration' => 'Jusqu\'à résolution complète'
            ],
            [
                'type' => 'technical_danger',
                'title' => '🔴 DANGER TECHNIQUE - INTERVENTION D\'URGENCE',
                'message' => "Une faille de sécurité critique a été détectée et nécessite une intervention immédiate.\n\nPour protéger vos données et garantir la sécurité de tous, le site est temporairement inaccessible.\n\nLa situation est sous contrôle et sera résolue très rapidement.",
                'duration' => '15-30 minutes'
            ],
            [
                'type' => 'scheduled',
                'title' => '📅 Maintenance Programmée',
                'message' => "Une maintenance planifiée est en cours pour améliorer les performances et la sécurité du site.\n\nCette intervention était prévue et permettra d'installer de nouvelles fonctionnalités.\n\nMerci de votre compréhension.",
                'duration' => '1-2 heures'
            ],
            [
                'type' => 'emergency_update',
                'title' => '🚀 MISE À JOUR MAJEURE EN COURS',
                'message' => "Une mise à jour importante est en cours d'installation.\n\nDe nouvelles fonctionnalités et améliorations arrivent très bientôt !\n\nLe site sera de retour avec des nouveautés excitantes.",
                'duration' => '30-45 minutes'
            ]
        ];
        
        foreach ($default_maintenances as $maint) {
            $stmt = $pdo->prepare("INSERT INTO maintenance_settings 
                (maintenance_type, title, message, estimated_duration, show_countdown, show_discord_link, created_by) 
                VALUES (?, ?, ?, ?, 1, 1, ?)");
            $stmt->execute([
                $maint['type'],
                $maint['title'],
                $maint['message'],
                $maint['duration'],
                $_SESSION['user_id']
            ]);
        }
    }
} catch (PDOException $e) {
    // Erreur silencieuse
}

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'quick_activate':
                $id = $_POST['maintenance_id'];
                $end_time = $_POST['end_time'] ?? null;
                
                // Désactiver toutes les maintenances
                $pdo->query("UPDATE maintenance_settings SET is_active = 0");
                
                // Activer celle sélectionnée avec l'heure de fin
                $stmt = $pdo->prepare("UPDATE maintenance_settings SET is_active = 1, end_time = ? WHERE id = ?");
                $stmt->execute([$end_time, $id]);
                
                // Activer dans site_content
                $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                    VALUES ('appearance', 'maintenance_mode', '1', ?, NOW()) 
                    ON DUPLICATE KEY UPDATE content = '1', updated_by = ?, updated_at = NOW()")
                    ->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Activation maintenance', "ID: $id");
                $success = "Maintenance activée avec succès !";
                break;
                
            case 'deactivate_maintenance':
                $pdo->query("UPDATE maintenance_settings SET is_active = 0, end_time = NULL");
                
                // Désactiver dans site_content
                $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                    VALUES ('appearance', 'maintenance_mode', '0', ?, NOW()) 
                    ON DUPLICATE KEY UPDATE content = '0', updated_by = ?, updated_at = NOW()")
                    ->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Désactivation maintenance', '');
                $success = "Maintenance désactivée avec succès !";
                break;
                
            case 'update_maintenance':
                $id = $_POST['maintenance_id'];
                $title = $_POST['title'];
                $message = $_POST['message'];
                $duration = $_POST['estimated_duration'];
                
                $stmt = $pdo->prepare("UPDATE maintenance_settings 
                    SET title = ?, message = ?, estimated_duration = ? 
                    WHERE id = ?");
                $stmt->execute([$title, $message, $duration, $id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification maintenance', "ID: $id");
                $success = "Maintenance modifiée avec succès !";
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer la maintenance active
$active_maintenance = $pdo->query("SELECT * FROM maintenance_settings WHERE is_active = 1 LIMIT 1")->fetch();

// Récupérer toutes les configurations
$all_maintenances = $pdo->query("SELECT * FROM maintenance_settings ORDER BY 
    FIELD(maintenance_type, 'server_issue', 'technical_danger', 'scheduled', 'emergency_update')")->fetchAll();

// Configurations visuelles par type
$type_configs = [
    'server_issue' => [
        'name' => 'Problème Serveur',
        'icon' => 'fa-exclamation-triangle',
        'bg' => 'from-red-900 via-red-800 to-orange-900',
        'border' => 'border-red-500',
        'text' => 'text-red-400',
        'badge' => 'bg-red-600',
        'pulse' => true
    ],
    'technical_danger' => [
        'name' => 'Danger Technique',
        'icon' => 'fa-radiation-alt',
        'bg' => 'from-red-900 via-pink-900 to-red-900',
        'border' => 'border-pink-500',
        'text' => 'text-pink-400',
        'badge' => 'bg-pink-600',
        'pulse' => true
    ],
    'scheduled' => [
        'name' => 'Maintenance Prévue',
        'icon' => 'fa-calendar-check',
        'bg' => 'from-blue-900 via-blue-800 to-indigo-900',
        'border' => 'border-blue-500',
        'text' => 'text-blue-400',
        'badge' => 'bg-blue-600',
        'pulse' => false
    ],
    'emergency_update' => [
        'name' => 'Mise à Jour Urgente',
        'icon' => 'fa-rocket',
        'bg' => 'from-purple-900 via-violet-800 to-purple-900',
        'border' => 'border-purple-500',
        'text' => 'text-purple-400',
        'badge' => 'bg-purple-600',
        'pulse' => false
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
    <style>
        @keyframes pulse-ring {
            0% { transform: scale(0.8); opacity: 1; }
            50% { transform: scale(1.2); opacity: 0.5; }
            100% { transform: scale(0.8); opacity: 1; }
        }
        .pulse-ring {
            animation: pulse-ring 2s ease-in-out infinite;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-5px); }
            75% { transform: translateX(5px); }
        }
        .shake {
            animation: shake 0.5s ease-in-out infinite;
        }
    </style>
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
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
                        Accès réservé à Enoe - Contrôle total du site
                    </p>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6 animate-pulse">
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
                    <div class="flex items-center space-x-4">
                        <?php if ($active_maintenance): ?>
                            <div class="relative">
                                <div class="w-4 h-4 bg-red-500 rounded-full pulse-ring"></div>
                                <div class="w-4 h-4 bg-red-500 rounded-full absolute top-0 left-0"></div>
                            </div>
                        <?php else: ?>
                            <div class="w-4 h-4 bg-green-500 rounded-full"></div>
                        <?php endif; ?>
                        
                        <div>
                            <h2 class="text-2xl font-bold text-white mb-2">
                                <?php if ($active_maintenance): ?>
                                    <span class="text-red-500">
                                        <i class="fas fa-exclamation-triangle mr-2 shake"></i>MAINTENANCE ACTIVE
                                    </span>
                                <?php else: ?>
                                    <span class="text-green-500">
                                        <i class="fas fa-check-circle mr-2"></i>SITE OPÉRATIONNEL
                                    </span>
                                <?php endif; ?>
                            </h2>
                            
                            <?php if ($active_maintenance): ?>
                                <div class="space-y-1">
                                    <p class="text-gray-300">
                                        <i class="fas fa-tag mr-2"></i>
                                        <span class="font-semibold"><?php echo htmlspecialchars($active_maintenance['title']); ?></span>
                                    </p>
                                    <?php if ($active_maintenance['end_time']): ?>
                                        <p class="text-gray-400 text-sm">
                                            <i class="fas fa-clock mr-2"></i>
                                            Fin prévue: <?php echo date('d/m/Y à H:i', strtotime($active_maintenance['end_time'])); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <?php if ($active_maintenance): ?>
                        <form method="POST" onsubmit="return confirm('Désactiver la maintenance et rendre le site accessible ?');">
                            <input type="hidden" name="action" value="deactivate_maintenance">
                            <button type="submit" class="bg-green-600 text-white px-8 py-4 rounded-lg hover:bg-green-700 transition font-bold transform hover:scale-105">
                                <i class="fas fa-power-off mr-2"></i>Désactiver la Maintenance
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Grid des 4 maintenances -->
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach ($all_maintenances as $maint): 
                    $config = $type_configs[$maint['maintenance_type']];
                ?>
                    <div class="bg-gradient-to-br <?php echo $config['bg']; ?> rounded-lg p-6 border-2 <?php echo $config['border']; ?> <?php echo $maint['is_active'] ? 'ring-4 ring-yellow-500' : ''; ?> relative overflow-hidden">
                        
                        <!-- Badge ACTIF -->
                        <?php if ($maint['is_active']): ?>
                            <div class="absolute top-4 right-4">
                                <span class="bg-yellow-500 text-black px-4 py-2 rounded-full text-sm font-bold pulse-ring">
                                    ● EN DIRECT
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Icône et titre -->
                        <div class="mb-6">
                            <div class="flex items-start space-x-4">
                                <div class="<?php echo $config['pulse'] ? 'pulse-ring' : ''; ?>">
                                    <i class="fas <?php echo $config['icon']; ?> text-6xl <?php echo $config['text']; ?>"></i>
                                </div>
                                <div class="flex-1">
                                    <span class="<?php echo $config['badge']; ?> text-white px-3 py-1 rounded-full text-xs font-bold uppercase">
                                        <?php echo $config['name']; ?>
                                    </span>
                                    <h3 class="text-2xl font-bold text-white mt-3 mb-2">
                                        <?php echo htmlspecialchars($maint['title']); ?>
                                    </h3>
                                </div>
                            </div>
                        </div>

                        <!-- Message -->
                        <div class="bg-black bg-opacity-30 rounded-lg p-4 mb-4">
                            <p class="text-gray-200 text-sm whitespace-pre-line leading-relaxed">
                                <?php echo htmlspecialchars($maint['message']); ?>
                            </p>
                        </div>

                        <!-- Durée estimée -->
                        <div class="bg-black bg-opacity-20 rounded-lg p-3 mb-4">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-400">
                                    <i class="fas fa-clock mr-2"></i>Durée estimée:
                                </span>
                                <span class="<?php echo $config['text']; ?> font-bold">
                                    <?php echo htmlspecialchars($maint['estimated_duration']); ?>
                                </span>
                            </div>
                        </div>

                        <!-- Actions -->
                        <div class="flex space-x-3">
                            <?php if (!$maint['is_active']): ?>
                                <!-- Formulaire d'activation -->
                                <button onclick="openActivationModal(<?php echo $maint['id']; ?>, '<?php echo addslashes($maint['title']); ?>')" 
                                        class="flex-1 bg-white text-gray-900 px-6 py-3 rounded-lg hover:bg-gray-100 transition font-bold transform hover:scale-105">
                                    <i class="fas fa-play mr-2"></i>Activer
                                </button>
                            <?php endif; ?>
                            
                            <button onclick="openEditModal(<?php echo htmlspecialchars(json_encode($maint)); ?>)" 
                                    class="bg-gray-700 bg-opacity-50 text-white px-4 py-3 rounded-lg hover:bg-opacity-70 transition">
                                <i class="fas fa-edit"></i>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <!-- Informations -->
            <div class="mt-8 bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h3 class="text-xl font-bold text-white mb-4">
                    <i class="fas fa-info-circle text-blue-400 mr-2"></i>Informations importantes
                </h3>
                <ul class="space-y-2 text-gray-300">
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Les visiteurs verront une page de maintenance pendant qu'elle est active</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Les administrateurs peuvent toujours accéder au site normalement</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Le timer est optionnel - laissez vide si vous ne connaissez pas la durée exacte</li>
                    <li><i class="fas fa-check text-green-500 mr-2"></i>Les modifications sont appliquées immédiatement</li>
                    <li><i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>Une seule maintenance peut être active à la fois</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal: Activation avec timer -->
    <div id="modal-activation" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-md w-full border-2 border-red-500">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-exclamation-triangle text-red-500 mr-2"></i>Activer la maintenance
            </h2>
            
            <form method="POST" id="activation-form">
                <input type="hidden" name="action" value="quick_activate">
                <input type="hidden" name="maintenance_id" id="activation-id">
                
                <div class="bg-gray-900 p-4 rounded-lg mb-6">
                    <p class="text-white font-semibold mb-2" id="activation-title"></p>
                    <p class="text-gray-400 text-sm">
                        <i class="fas fa-users mr-2"></i>
                        Les visiteurs ne pourront plus accéder au site
                    </p>
                </div>

                <div class="mb-6">
                    <label class="block text-white font-semibold mb-2">
                        <i class="fas fa-clock mr-2"></i>Fin prévue (optionnel)
                    </label>
                    <input type="datetime-local" name="end_time" id="activation-endtime"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    <p class="text-gray-500 text-sm mt-2">
                        Si défini, un compte à rebours s'affichera sur la page de maintenance
                    </p>
                </div>

                <div class="space-y-3">
                    <button type="button" onclick="setQuickTime(30)" 
                            class="w-full bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition text-sm">
                        <i class="fas fa-clock mr-2"></i>+30 minutes
                    </button>
                    <button type="button" onclick="setQuickTime(60)" 
                            class="w-full bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition text-sm">
                        <i class="fas fa-clock mr-2"></i>+1 heure
                    </button>
                    <button type="button" onclick="setQuickTime(120)" 
                            class="w-full bg-gray-700 text-white px-4 py-2 rounded hover:bg-gray-600 transition text-sm">
                        <i class="fas fa-clock mr-2"></i>+2 heures
                    </button>
                </div>

                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-4 rounded-lg hover:bg-red-700 transition font-bold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>ACTIVER
                    </button>
                    <button type="button" onclick="closeActivationModal()" 
                            class="bg-gray-600 text-white px-6 py-4 rounded-lg hover:bg-gray-700 transition font-bold">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Édition -->
    <div id="modal-edit" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4 overflow-y-auto">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full my-8">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-edit text-blue-500 mr-2"></i>Modifier la maintenance
            </h2>
            
            <form method="POST" id="edit-form">
                <input type="hidden" name="action" value="update_maintenance">
                <input type="hidden" name="maintenance_id" id="edit-id">
                
                <div class="space-y-4">
                    <div>
                        <label class="block text-white font-semibold mb-2">Titre *</label>
                        <input type="text" name="title" id="edit-title" required maxlength="255"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>

                    <div>
                        <label class="block text-white font-semibold mb-2">Message *</label>
                        <textarea name="message" id="edit-message" required rows="6"
                                  class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                    </div>

                    <div>
                        <label class="block text-white font-semibold mb-2">Durée estimée *</label>
                        <input type="text" name="estimated_duration" id="edit-duration" required
                               placeholder="Ex: 30 minutes, 1-2 heures, etc."
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>

                <div class="flex space-x-4 mt-6">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-bold">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeEditModal()" 
                            class="bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition font-bold">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function openActivationModal(id, title) {
        document.getElementById('activation-id').value = id;
        document.getElementById('activation-title').textContent = title;
        document.getElementById('modal-activation').classList.remove('hidden');
    }

    function closeActivationModal() {
        document.getElementById('modal-activation').classList.add('hidden');
        document.getElementById('activation-form').reset();
    }

    function setQuickTime(minutes) {
        const now = new Date();
        now.setMinutes(now.getMinutes() + minutes);
        const formatted = now.toISOString().slice(0, 16);
        document.getElementById('activation-endtime').value = formatted;
    }

    function openEditModal(maint) {
        document.getElementById('edit-id').value = maint.id;
        document.getElementById('edit-title').value = maint.title;
        document.getElementById('edit-message').value = maint.message;
        document.getElementById('edit-duration').value = maint.estimated_duration;
        document.getElementById('modal-edit').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('modal-edit').classList.add('hidden');
        document.getElementById('edit-form').reset();
    }

    // Fermer les modals avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeActivationModal();
            closeEditModal();
        }
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
