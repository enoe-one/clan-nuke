<?php
require_once '../config.php';

// Vérifier que c'est bien Enoe
if (!isLoggedIn() || strtolower($_SESSION['username']) !== 'enoe') {
    header('Location: ../login.php');
    exit;
}

// Créer la table des sessions de jeu si elle n'existe pas
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS game_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) UNIQUE NOT NULL,
            player_name VARCHAR(100),
            difficulty VARCHAR(20) NOT NULL,
            wave INT DEFAULT 1,
            money INT DEFAULT 600,
            base_health INT DEFAULT 100,
            max_base_health INT DEFAULT 100,
            score INT DEFAULT 0,
            kills INT DEFAULT 0,
            towers_count INT DEFAULT 0,
            enemies_alive INT DEFAULT 0,
            game_state LONGTEXT,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_active (is_active),
            INDEX idx_last_update (last_update)
        )
    ");
    
    // Nettoyer les sessions de plus de 10 heures
    $pdo->exec("
        UPDATE game_sessions 
        SET is_active = FALSE 
        WHERE last_update < DATE_SUB(NOW(), INTERVAL 10 HOUR) 
        AND is_active = TRUE
    ");
    
    // Supprimer les anciennes sessions inactives (plus de 24h)
    $pdo->exec("
        DELETE FROM game_sessions 
        WHERE last_update < DATE_SUB(NOW(), INTERVAL 24 HOUR) 
        AND is_active = FALSE
    ");
} catch (PDOException $e) {
    // Erreur silencieuse
}

// Gérer les actions admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $sessionId = $_POST['session_id'] ?? '';
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM game_sessions WHERE session_id = ? AND is_active = TRUE");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if (!$session) {
            echo json_encode(['success' => false, 'message' => 'Session non trouvée']);
            exit;
        }
        
        $gameState = json_decode($session['game_state'], true);
        $updated = false;
        
        switch ($action) {
            case 'add_money':
                $amount = intval($_POST['amount'] ?? 1000);
                $gameState['money'] += $amount;
                $updated = true;
                break;
                
            case 'spawn_enemy':
                $enemyType = $_POST['enemy_type'] ?? 'normal';
                // Ajouter un marqueur pour spawner l'ennemi
                if (!isset($gameState['adminSpawns'])) {
                    $gameState['adminSpawns'] = [];
                }
                $gameState['adminSpawns'][] = [
                    'type' => $enemyType,
                    'timestamp' => time()
                ];
                $updated = true;
                break;
                
            case 'kill_all':
                $gameState['adminKillAll'] = time();
                $updated = true;
                break;
                
            case 'heal_base':
                $gameState['baseHealth'] = $gameState['maxBaseHealth'];
                $updated = true;
                break;
                
            case 'skip_wave':
                $gameState['wave']++;
                $gameState['adminSkipWave'] = time();
                $updated = true;
                break;
                
            case 'end_session':
                $stmt = $pdo->prepare("UPDATE game_sessions SET is_active = FALSE WHERE session_id = ?");
                $stmt->execute([$sessionId]);
                echo json_encode(['success' => true, 'message' => 'Session terminée']);
                exit;
        }
        
        if ($updated) {
            $stmt = $pdo->prepare("
                UPDATE game_sessions 
                SET game_state = ?, last_update = NOW() 
                WHERE session_id = ?
            ");
            $stmt->execute([json_encode($gameState), $sessionId]);
            
            echo json_encode(['success' => true, 'message' => 'Action effectuée', 'gameState' => $gameState]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Action non reconnue']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erreur: ' . $e->getMessage()]);
    }
    exit;
}

// Récupérer toutes les sessions actives
$stmt = $pdo->query("
    SELECT * FROM game_sessions 
    WHERE is_active = TRUE 
    ORDER BY last_update DESC
");
$activeSessions = $stmt->fetchAll();

logAdminAction($pdo, $_SESSION['user_id'], 'Consultation sessions de jeu');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Sessions de Jeu - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 20px rgba(34, 197, 94, 0.5); }
            50% { box-shadow: 0 0 40px rgba(34, 197, 94, 0.8); }
        }
        
        .active-session {
            animation: pulse-glow 2s infinite;
        }
        
        .session-card:hover {
            transform: translateY(-5px);
            transition: all 0.3s;
        }
    </style>
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- En-tête -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2 flex items-center">
                        <i class="fas fa-crown text-yellow-400 mr-3"></i>
                        Gestion Sessions de Jeu
                    </h1>
                    <p class="text-gray-400">Contrôle administrateur - Réservé à Enoe</p>
                </div>
                <div class="flex gap-3">
                    <button onclick="refreshSessions()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-sync-alt mr-2"></i>Actualiser
                    </button>
                    <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i>Retour
                    </a>
                </div>
            </div>

            <!-- Statistiques globales -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-green-600 to-green-700 p-6 rounded-lg text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-gamepad text-4xl opacity-50"></i>
                        <span class="text-3xl font-bold"><?php echo count($activeSessions); ?></span>
                    </div>
                    <p class="text-sm opacity-90">Sessions Actives</p>
                </div>
                
                <div class="bg-gradient-to-br from-blue-600 to-blue-700 p-6 rounded-lg text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-users text-4xl opacity-50"></i>
                        <span class="text-3xl font-bold"><?php echo count(array_unique(array_column($activeSessions, 'player_name'))); ?></span>
                    </div>
                    <p class="text-sm opacity-90">Joueurs Uniques</p>
                </div>
                
                <div class="bg-gradient-to-br from-purple-600 to-purple-700 p-6 rounded-lg text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-trophy text-4xl opacity-50"></i>
                        <span class="text-3xl font-bold"><?php echo !empty($activeSessions) ? max(array_column($activeSessions, 'score')) : 0; ?></span>
                    </div>
                    <p class="text-sm opacity-90">Meilleur Score</p>
                </div>
                
                <div class="bg-gradient-to-br from-red-600 to-red-700 p-6 rounded-lg text-white">
                    <div class="flex items-center justify-between mb-2">
                        <i class="fas fa-wave-square text-4xl opacity-50"></i>
                        <span class="text-3xl font-bold"><?php echo !empty($activeSessions) ? max(array_column($activeSessions, 'wave')) : 0; ?></span>
                    </div>
                    <p class="text-sm opacity-90">Vague Max</p>
                </div>
            </div>

            <!-- Liste des sessions -->
            <?php if (empty($activeSessions)): ?>
                <div class="bg-gray-800 p-12 rounded-lg text-center">
                    <i class="fas fa-ghost text-gray-600 text-8xl mb-6"></i>
                    <h2 class="text-3xl font-bold text-white mb-4">Aucune session en cours</h2>
                    <p class="text-gray-400 text-lg">Aucun joueur n'est actuellement en train de jouer à Base Defense</p>
                </div>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($activeSessions as $session): 
                        $gameState = json_decode($session['game_state'], true);
                        $timeSinceUpdate = time() - strtotime($session['last_update']);
                        $isRecent = $timeSinceUpdate < 30; // Actif si mis à jour il y a moins de 30 secondes
                        
                        // Déterminer la couleur selon la difficulté
                        $difficultyColors = [
                            'easy' => 'border-green-500',
                            'medium' => 'border-yellow-500',
                            'hard' => 'border-red-500'
                        ];
                        $borderColor = $difficultyColors[$session['difficulty']] ?? 'border-gray-500';
                    ?>
                        <div class="session-card bg-gray-800 rounded-lg border-t-4 <?php echo $borderColor; ?> <?php echo $isRecent ? 'active-session' : ''; ?> overflow-hidden">
                            <div class="p-6">
                                <!-- En-tête de session -->
                                <div class="flex justify-between items-start mb-6">
                                    <div class="flex items-center gap-4">
                                        <div class="bg-gray-700 p-4 rounded-lg">
                                            <i class="fas fa-user text-blue-400 text-3xl"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-2xl font-bold text-white flex items-center gap-2">
                                                <?php echo htmlspecialchars($session['player_name'] ?: 'Joueur Anonyme'); ?>
                                                <?php if ($isRecent): ?>
                                                    <span class="flex items-center gap-1 text-sm bg-green-600 px-2 py-1 rounded-full">
                                                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                                                        EN LIGNE
                                                    </span>
                                                <?php endif; ?>
                                            </h3>
                                            <p class="text-gray-400">
                                                Session: <?php echo substr($session['session_id'], 0, 8); ?>...
                                            </p>
                                            <p class="text-gray-500 text-sm">
                                                Démarré: <?php echo date('d/m/Y H:i', strtotime($session['started_at'])); ?>
                                                - Dernière activité: <?php echo $timeSinceUpdate < 60 ? $timeSinceUpdate . 's' : round($timeSinceUpdate/60) . 'min'; ?>
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex flex-col gap-2">
                                        <span class="px-3 py-1 rounded-full text-sm font-bold text-white <?php 
                                            echo $session['difficulty'] === 'easy' ? 'bg-green-600' : 
                                                ($session['difficulty'] === 'medium' ? 'bg-yellow-600' : 'bg-red-600'); 
                                        ?>">
                                            <?php echo strtoupper($session['difficulty']); ?>
                                        </span>
                                    </div>
                                </div>

                                <!-- Statistiques de jeu -->
                                <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-blue-400"><?php echo $session['wave']; ?></p>
                                        <p class="text-xs text-gray-400">Vague</p>
                                    </div>
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-yellow-400">💰 <?php echo $session['money']; ?></p>
                                        <p class="text-xs text-gray-400">Argent</p>
                                    </div>
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-green-400"><?php echo $session['base_health']; ?></p>
                                        <p class="text-xs text-gray-400">PV Base</p>
                                    </div>
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-purple-400"><?php echo $session['score']; ?></p>
                                        <p class="text-xs text-gray-400">Score</p>
                                    </div>
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-red-400"><?php echo $session['kills']; ?></p>
                                        <p class="text-xs text-gray-400">Kills</p>
                                    </div>
                                    <div class="bg-gray-700 p-3 rounded text-center">
                                        <p class="text-2xl font-bold text-cyan-400"><?php echo $session['towers_count']; ?></p>
                                        <p class="text-xs text-gray-400">Tours</p>
                                    </div>
                                </div>

                                <!-- Barre de vie -->
                                <div class="mb-6">
                                    <div class="flex justify-between text-sm text-gray-400 mb-2">
                                        <span>Vie de la Base</span>
                                        <span><?php echo $session['base_health']; ?> / <?php echo $session['max_base_health']; ?></span>
                                    </div>
                                    <div class="w-full h-4 bg-gray-700 rounded-full overflow-hidden">
                                        <?php 
                                            $healthPercent = ($session['base_health'] / $session['max_base_health']) * 100;
                                            $healthColor = $healthPercent > 60 ? 'bg-green-500' : ($healthPercent > 30 ? 'bg-yellow-500' : 'bg-red-500');
                                        ?>
                                        <div class="h-full <?php echo $healthColor; ?> transition-all duration-300" style="width: <?php echo $healthPercent; ?>%"></div>
                                    </div>
                                </div>

                                <!-- Actions Admin -->
                                <div class="border-t border-gray-700 pt-4">
                                    <p class="text-gray-400 text-sm mb-3 font-bold">
                                        <i class="fas fa-crown text-yellow-400 mr-2"></i>Actions Administrateur
                                    </p>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
                                        <button onclick="adminAction('<?php echo $session['session_id']; ?>', 'add_money', {amount: 1000})" 
                                                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm transition">
                                            <i class="fas fa-money-bill-wave mr-1"></i>+1000 💰
                                        </button>
                                        
                                        <button onclick="showSpawnMenu('<?php echo $session['session_id']; ?>')" 
                                                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition">
                                            <i class="fas fa-user-ninja mr-1"></i>Spawn Ennemi
                                        </button>
                                        
                                        <button onclick="adminAction('<?php echo $session['session_id']; ?>', 'kill_all')" 
                                                class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded text-sm transition">
                                            <i class="fas fa-skull mr-1"></i>Kill All
                                        </button>
                                        
                                        <button onclick="adminAction('<?php echo $session['session_id']; ?>', 'heal_base')" 
                                                class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded text-sm transition">
                                            <i class="fas fa-heart mr-1"></i>Heal Base
                                        </button>
                                        
                                        <button onclick="adminAction('<?php echo $session['session_id']; ?>', 'skip_wave')" 
                                                class="bg-yellow-600 hover:bg-yellow-700 text-white px-4 py-2 rounded text-sm transition">
                                            <i class="fas fa-forward mr-1"></i>Skip Vague
                                        </button>
                                        
                                        <button onclick="if(confirm('Terminer cette session ?')) adminAction('<?php echo $session['session_id']; ?>', 'end_session')" 
                                                class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm transition col-span-2 md:col-span-1">
                                            <i class="fas fa-times-circle mr-1"></i>Terminer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal Spawn Ennemi -->
    <div id="spawn-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
        <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
            <h3 class="text-2xl font-bold text-white mb-4">Spawner un Ennemi</h3>
            <div class="grid grid-cols-2 gap-3 mb-4">
                <button onclick="spawnEnemy('normal')" class="bg-red-700 hover:bg-red-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-user-ninja text-2xl mb-2"></i>
                    <p class="font-bold">Normal</p>
                </button>
                <button onclick="spawnEnemy('runner')" class="bg-yellow-700 hover:bg-yellow-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-running text-2xl mb-2"></i>
                    <p class="font-bold">Runner</p>
                </button>
                <button onclick="spawnEnemy('tank')" class="bg-blue-700 hover:bg-blue-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-shield text-2xl mb-2"></i>
                    <p class="font-bold">Tank</p>
                </button>
                <button onclick="spawnEnemy('aerial')" class="bg-cyan-700 hover:bg-cyan-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-plane text-2xl mb-2"></i>
                    <p class="font-bold">Aérien</p>
                </button>
                <button onclick="spawnEnemy('boss')" class="bg-purple-700 hover:bg-purple-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-dragon text-2xl mb-2"></i>
                    <p class="font-bold">Boss</p>
                </button>
                <button onclick="spawnEnemy('airboss')" class="bg-indigo-700 hover:bg-indigo-600 text-white p-4 rounded-lg transition">
                    <i class="fas fa-plane-departure text-2xl mb-2"></i>
                    <p class="font-bold">Air Boss</p>
                </button>
                <button onclick="spawnEnemy('panda')" class="bg-pink-700 hover:bg-pink-600 text-white p-4 rounded-lg transition col-span-2">
                    <i class="fas fa-paw text-2xl mb-2"></i>
                    <p class="font-bold">Boss Panda</p>
                </button>
            </div>
            <button onclick="closeSpawnMenu()" class="w-full bg-gray-700 hover:bg-gray-600 text-white py-2 rounded-lg transition">
                Annuler
            </button>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notifications" class="fixed top-4 right-4 z-50 space-y-2"></div>

    <?php include '../includes/footer.php'; ?>

    <script>
        let currentSessionId = null;

        // Rafraîchir automatiquement toutes les 5 secondes
        setInterval(refreshSessions, 5000);

        function refreshSessions() {
            window.location.reload();
        }

        function showNotification(message, type = 'info') {
            const colors = {
                success: 'bg-green-600',
                error: 'bg-red-600',
                info: 'bg-blue-600',
                warning: 'bg-yellow-600'
            };
            
            const notif = document.createElement('div');
            notif.className = `${colors[type]} text-white px-6 py-4 rounded-lg shadow-2xl font-bold transform transition-all duration-300`;
            notif.textContent = message;
            
            document.getElementById('notifications').appendChild(notif);
            
            setTimeout(() => {
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(400px)';
                setTimeout(() => notif.remove(), 300);
            }, 3000);
        }

        function adminAction(sessionId, action, extraData = {}) {
            const formData = new FormData();
            formData.append('action', action);
            formData.append('session_id', sessionId);
            
            for (let key in extraData) {
                formData.append(key, extraData[key]);
            }
            
            fetch('manage_game_sessions.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    setTimeout(() => refreshSessions(), 1000);
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                showNotification('Erreur de communication', 'error');
                console.error('Error:', error);
            });
        }

        function showSpawnMenu(sessionId) {
            currentSessionId = sessionId;
            document.getElementById('spawn-modal').classList.remove('hidden');
        }

        function closeSpawnMenu() {
            document.getElementById('spawn-modal').classList.add('hidden');
            currentSessionId = null;
        }

        function spawnEnemy(enemyType) {
            if (!currentSessionId) return;
            
            adminAction(currentSessionId, 'spawn_enemy', { enemy_type: enemyType });
            closeSpawnMenu();
        }

        // Fermer le modal en cliquant en dehors
        document.getElementById('spawn-modal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeSpawnMenu();
            }
        });
    </script>
</body>
</html>
