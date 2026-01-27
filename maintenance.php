<?php
// maintenance.php
require_once 'config.php';

// Si l'utilisateur est admin, le laisser passer
if (isAdmin()) {
    header('Location: index.php');
    exit;
}

// Récupérer la configuration de maintenance active
$maintenance = $pdo->query("SELECT * FROM maintenance_settings WHERE is_active = 1 LIMIT 1")->fetch();

// Si pas de maintenance active, rediriger
if (!$maintenance) {
    header('Location: index.php');
    exit;
}

// Configurations visuelles par type
$type_configs = [
    'server_issue' => [
        'bg_gradient' => 'from-red-900 via-red-800 to-orange-900',
        'icon' => 'fa-exclamation-triangle',
        'icon_color' => 'text-red-500',
        'accent_color' => 'red',
        'particles' => true,
        'shake' => true
    ],
    'technical_danger' => [
        'bg_gradient' => 'from-pink-900 via-red-900 to-pink-900',
        'icon' => 'fa-radiation-alt',
        'icon_color' => 'text-pink-500',
        'accent_color' => 'pink',
        'particles' => true,
        'shake' => true
    ],
    'scheduled' => [
        'bg_gradient' => 'from-blue-900 via-indigo-800 to-blue-900',
        'icon' => 'fa-calendar-check',
        'icon_color' => 'text-blue-400',
        'accent_color' => 'blue',
        'particles' => false,
        'shake' => false
    ],
    'emergency_update' => [
        'bg_gradient' => 'from-purple-900 via-violet-800 to-purple-900',
        'icon' => 'fa-rocket',
        'icon_color' => 'text-purple-400',
        'accent_color' => 'purple',
        'particles' => false,
        'shake' => false
    ]
];

$config = $type_configs[$maintenance['maintenance_type']] ?? $type_configs['scheduled'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($maintenance['title']); ?> - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
            background-attachment: fixed;
        }
        
        @keyframes spin-slow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.05); }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            10%, 30%, 50%, 70%, 90% { transform: translateX(-10px); }
            20%, 40%, 60%, 80% { transform: translateX(10px); }
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .spinner { animation: spin-slow 3s linear infinite; }
        .pulse-glow { animation: pulse-glow 2s ease-in-out infinite; }
        .shake { animation: shake 0.5s ease-in-out infinite; }
        .float { animation: float 3s ease-in-out infinite; }
        .fade-in-up { animation: fadeInUp 0.6s ease-out; }
        
        /* Particules animées */
        @keyframes particle-float {
            0% {
                transform: translateY(0) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
        
        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 50%;
            animation: particle-float linear infinite;
        }
        
        .particle:nth-child(1) { left: 10%; animation-duration: 8s; animation-delay: 0s; }
        .particle:nth-child(2) { left: 20%; animation-duration: 10s; animation-delay: 1s; }
        .particle:nth-child(3) { left: 30%; animation-duration: 7s; animation-delay: 2s; }
        .particle:nth-child(4) { left: 40%; animation-duration: 9s; animation-delay: 0.5s; }
        .particle:nth-child(5) { left: 50%; animation-duration: 11s; animation-delay: 1.5s; }
        .particle:nth-child(6) { left: 60%; animation-duration: 8s; animation-delay: 2.5s; }
        .particle:nth-child(7) { left: 70%; animation-duration: 10s; animation-delay: 0.8s; }
        .particle:nth-child(8) { left: 80%; animation-duration: 9s; animation-delay: 1.8s; }
        .particle:nth-child(9) { left: 90%; animation-duration: 7s; animation-delay: 2.2s; }
        .particle:nth-child(10) { left: 95%; animation-duration: 11s; animation-delay: 0.3s; }
        
        /* Grille hexagonale d'arrière-plan */
        .hex-bg {
            background-image: 
                linear-gradient(30deg, rgba(255,255,255,.02) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.02) 87.5%, rgba(255,255,255,.02)),
                linear-gradient(150deg, rgba(255,255,255,.02) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.02) 87.5%, rgba(255,255,255,.02)),
                linear-gradient(30deg, rgba(255,255,255,.02) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.02) 87.5%, rgba(255,255,255,.02)),
                linear-gradient(150deg, rgba(255,255,255,.02) 12%, transparent 12.5%, transparent 87%, rgba(255,255,255,.02) 87.5%, rgba(255,255,255,.02));
            background-size: 80px 140px;
            background-position: 0 0, 0 0, 40px 70px, 40px 70px;
        }
    </style>
</head>
<body class="min-h-screen flex items-center justify-center overflow-hidden hex-bg">
    
    <!-- Particules animées (pour dangers) -->
    <?php if ($config['particles']): ?>
        <div class="fixed inset-0 overflow-hidden pointer-events-none">
            <?php for ($i = 1; $i <= 10; $i++): ?>
                <div class="particle"></div>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
    
    <div class="max-w-4xl mx-auto px-4 text-center relative z-10">
        
        <!-- Icône principale animée -->
        <div class="mb-12 fade-in-up">
            <div class="relative inline-block">
                <!-- Cercles pulsants en arrière-plan -->
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-64 h-64 bg-<?php echo $config['accent_color']; ?>-500 opacity-20 rounded-full pulse-glow"></div>
                </div>
                <div class="absolute inset-0 flex items-center justify-center">
                    <div class="w-48 h-48 bg-<?php echo $config['accent_color']; ?>-500 opacity-30 rounded-full pulse-glow" style="animation-delay: 0.5s;"></div>
                </div>
                
                <!-- Icône principale -->
                <div class="relative <?php echo $config['shake'] ? 'shake' : 'float'; ?>">
                    <i class="fas <?php echo $config['icon']; ?> text-9xl <?php echo $config['icon_color']; ?> drop-shadow-2xl"></i>
                </div>
                
                <!-- Bouclier CFWT -->
                <div class="absolute bottom-0 right-0">
                    <i class="fas fa-shield-alt text-5xl text-white opacity-30 pulse-glow"></i>
                </div>
            </div>
        </div>

        <!-- Titre principal -->
        <div class="mb-8 fade-in-up" style="animation-delay: 0.2s;">
            <h1 class="text-6xl font-black text-white mb-4 drop-shadow-2xl <?php echo $config['shake'] ? 'shake' : ''; ?>">
                <?php echo htmlspecialchars($maintenance['title']); ?>
            </h1>
            <div class="h-2 w-64 mx-auto bg-gradient-to-r from-<?php echo $config['accent_color']; ?>-500 to-<?php echo $config['accent_color']; ?>-700 rounded-full"></div>
        </div>

        <!-- Message principal -->
        <div class="mb-10 fade-in-up" style="animation-delay: 0.4s;">
            <div class="bg-gradient-to-br <?php echo $config['bg_gradient']; ?> backdrop-blur-md rounded-2xl p-8 border-2 border-<?php echo $config['accent_color']; ?>-500 shadow-2xl">
                <p class="text-2xl text-white mb-6 leading-relaxed whitespace-pre-line font-semibold">
                    <?php echo nl2br(htmlspecialchars($maintenance['message'])); ?>
                </p>

                <!-- Durée estimée -->
                <?php if ($maintenance['estimated_duration']): ?>
                    <div class="bg-black bg-opacity-40 rounded-xl p-5 inline-block">
                        <i class="fas fa-clock text-<?php echo $config['accent_color']; ?>-400 text-3xl mr-3"></i>
                        <span class="text-white text-xl font-semibold">Durée estimée : </span>
                        <span class="text-<?php echo $config['accent_color']; ?>-300 text-2xl font-bold">
                            <?php echo htmlspecialchars($maintenance['estimated_duration']); ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Compte à rebours -->
        <?php if ($maintenance['show_countdown'] && $maintenance['end_time']): ?>
            <div id="countdown" class="mb-10 fade-in-up" style="animation-delay: 0.6s;">
                <p class="text-gray-300 text-xl mb-6 font-semibold">
                    <i class="fas fa-hourglass-half mr-2"></i>Retour prévu dans :
                </p>
                <div class="flex justify-center space-x-6">
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl min-w-[120px] border-2 border-<?php echo $config['accent_color']; ?>-500 shadow-xl transform hover:scale-110 transition">
                        <div id="hours" class="text-5xl font-black text-<?php echo $config['accent_color']; ?>-400 mb-2">00</div>
                        <div class="text-gray-400 text-sm font-semibold uppercase tracking-wider">Heures</div>
                    </div>
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl min-w-[120px] border-2 border-<?php echo $config['accent_color']; ?>-500 shadow-xl transform hover:scale-110 transition">
                        <div id="minutes" class="text-5xl font-black text-<?php echo $config['accent_color']; ?>-400 mb-2">00</div>
                        <div class="text-gray-400 text-sm font-semibold uppercase tracking-wider">Minutes</div>
                    </div>
                    <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl min-w-[120px] border-2 border-<?php echo $config['accent_color']; ?>-500 shadow-xl transform hover:scale-110 transition">
                        <div id="seconds" class="text-5xl font-black text-<?php echo $config['accent_color']; ?>-400 mb-2">00</div>
                        <div class="text-gray-400 text-sm font-semibold uppercase tracking-wider">Secondes</div>
                    </div>
                </div>
            </div>

            <script>
            const endTime = new Date('<?php echo $maintenance['end_time']; ?>').getTime();

            function updateCountdown() {
                const now = new Date().getTime();
                const distance = endTime - now;

                if (distance < 0) {
                    // Vérifier si la maintenance est toujours active via AJAX
                    checkMaintenanceStatus();
                    return;
                }

                const hours = Math.floor(distance / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);

                document.getElementById('hours').textContent = String(hours).padStart(2, '0');
                document.getElementById('minutes').textContent = String(minutes).padStart(2, '0');
                document.getElementById('seconds').textContent = String(seconds).padStart(2, '0');
            }

            updateCountdown();
            setInterval(updateCountdown, 1000);
            </script>
        <?php endif; ?>

        <!-- Stats visuelles -->
        <div class="grid md:grid-cols-2 gap-6 mb-10 fade-in-up" style="animation-delay: 0.8s;">
            <!-- Statut serveur -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl border border-<?php echo $config['accent_color']; ?>-500 border-opacity-30 shadow-xl">
                <i class="fas fa-server text-5xl text-<?php echo $config['accent_color']; ?>-400 mb-4"></i>
                <p class="text-white font-bold text-lg mb-3">Statut du serveur</p>
                <div class="flex items-center justify-center">
                    <span class="w-4 h-4 bg-yellow-500 rounded-full mr-3 pulse-glow"></span>
                    <span class="text-yellow-400 font-bold text-lg">En maintenance</span>
                </div>
            </div>

            <!-- Progression -->
            <div class="bg-gradient-to-br from-gray-800 to-gray-900 p-6 rounded-2xl border border-<?php echo $config['accent_color']; ?>-500 border-opacity-30 shadow-xl">
                <i class="fas fa-tasks text-5xl text-<?php echo $config['accent_color']; ?>-400 mb-4"></i>
                <p class="text-white font-bold text-lg mb-3">Progression</p>
                <div class="w-full bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="bg-gradient-to-r from-<?php echo $config['accent_color']; ?>-500 to-<?php echo $config['accent_color']; ?>-700 h-4 rounded-full pulse-glow" 
                         style="width: 100%"></div>
                </div>
                <p class="text-gray-400 text-sm mt-3">Opérations en cours...</p>
            </div>
        </div>

        <!-- Événements à venir -->
        <?php
        $upcoming_events = $pdo->query("SELECT * FROM events 
            WHERE event_date >= NOW() 
            AND event_date <= DATE_ADD(NOW(), INTERVAL 24 HOUR) 
            ORDER BY event_date ASC 
            LIMIT 3")->fetchAll();
        ?>
        
        <?php if (!empty($upcoming_events)): ?>
            <div class="mb-10 fade-in-up" style="animation-delay: 0.9s;">
                <div class="bg-gradient-to-br from-yellow-900 to-orange-900 rounded-2xl p-6 sm:p-8 border-2 border-yellow-500 shadow-2xl">
                    <h3 class="text-2xl sm:text-3xl font-bold text-yellow-300 mb-6 flex items-center">
                        <i class="fas fa-calendar-exclamation mr-3 text-3xl sm:text-4xl"></i>
                        Événements à venir
                    </h3>
                    <div class="space-y-3 sm:space-y-4">
                        <?php foreach ($upcoming_events as $event): ?>
                            <div class="bg-black bg-opacity-40 rounded-xl p-4 sm:p-5 transform hover:scale-105 transition">
                                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <?php if ($event['is_important']): ?>
                                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs font-bold">
                                                    <i class="fas fa-star mr-1"></i>IMPORTANT
                                                </span>
                                            <?php endif; ?>
                                            <span class="bg-yellow-600 text-white px-2 py-1 rounded text-xs font-bold">
                                                <?php echo strtoupper($event['type']); ?>
                                            </span>
                                        </div>
                                        <h4 class="text-lg sm:text-xl font-bold text-white mb-2">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </h4>
                                        <p class="text-yellow-200 text-sm sm:text-base">
                                            <i class="fas fa-clock mr-2"></i>
                                            <?php echo date('d/m/Y à H:i', strtotime($event['event_date'])); ?>
                                        </p>
                                        <?php if ($event['description']): ?>
                                            <p class="text-gray-300 text-sm mt-2 line-clamp-2">
                                                <?php echo htmlspecialchars($event['description']); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <div class="text-center sm:text-right">
                                        <div class="bg-yellow-600 bg-opacity-30 rounded-lg px-4 py-2 inline-block">
                                            <p class="text-yellow-200 text-xs">Dans</p>
                                            <p class="text-white text-lg sm:text-xl font-bold">
                                                <?php
                                                $diff = (strtotime($event['event_date']) - time()) / 3600;
                                                if ($diff < 1) {
                                                    echo round($diff * 60) . ' min';
                                                } else {
                                                    echo round($diff) . 'h';
                                                }
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Discord -->
        <?php if ($maintenance['show_discord_link']): ?>
            <div class="mb-10 fade-in-up" style="animation-delay: 1s;">
                <div class="bg-gradient-to-br from-blue-900 to-indigo-900 rounded-2xl p-6 sm:p-8 border-2 border-blue-500 shadow-2xl transform hover:scale-105 transition">
                    <i class="fab fa-discord text-5xl sm:text-6xl text-blue-300 mb-4 float"></i>
                    <p class="text-blue-200 text-lg sm:text-xl mb-6 font-bold">
                        Rejoignez notre Discord pour les dernières infos !
                    </p>
                    <a href="<?php echo DISCORD_INVITE; ?>" target="_blank" 
                       class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-8 sm:px-10 py-3 sm:py-4 rounded-xl font-black text-base sm:text-lg shadow-xl transform hover:scale-110 transition">
                        <i class="fab fa-discord mr-2 sm:mr-3"></i>Rejoindre Discord
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <!-- Actions -->
        <div class="flex flex-wrap justify-center gap-4 mb-8 fade-in-up" style="animation-delay: 1.2s;">
            <button onclick="checkMaintenanceStatus()" 
                    class="bg-gradient-to-r from-gray-700 to-gray-800 text-white px-8 py-4 rounded-xl hover:from-gray-600 hover:to-gray-700 transition font-bold shadow-lg transform hover:scale-105">
                <i class="fas fa-sync-alt mr-2"></i>Vérifier le statut
            </button>
            <a href="login.php" 
               class="bg-gradient-to-r from-gray-700 to-gray-800 text-white px-8 py-4 rounded-xl hover:from-gray-600 hover:to-gray-700 transition font-bold shadow-lg transform hover:scale-105 inline-block">
                <i class="fas fa-user-shield mr-2"></i>Connexion Admin
            </a>
        </div>

        <!-- Footer -->
        <div class="text-gray-500 text-sm fade-in-up" style="animation-delay: 1.4s;">
            <p class="mb-2">&copy; <?php echo date('Y'); ?> CFWT - Coalition Française de Wars Tycoon</p>
            <p>
                <i class="fas fa-shield-alt mr-2"></i>
                Serveur sécurisé • Merci de votre patience
            </p>
        </div>
    </div>

    <!-- Notification de vérification -->
    <div id="status-notification" class="hidden fixed bottom-8 right-8 bg-gray-800 bg-opacity-95 text-white px-6 py-4 rounded-xl shadow-2xl border-2 border-blue-500 z-50 transform transition-all">
        <div class="flex items-center space-x-3">
            <i class="fas fa-spinner fa-spin text-blue-400 text-xl"></i>
            <span class="font-semibold">Vérification du statut...</span>
        </div>
    </div>

    <script>
    // Vérification AJAX du statut de maintenance (toutes les 30 secondes)
    function checkMaintenanceStatus() {
        const notification = document.getElementById('status-notification');
        notification.classList.remove('hidden');
        
        fetch('check_maintenance.php')
            .then(response => response.json())
            .then(data => {
                if (!data.maintenance_active) {
                    // Maintenance terminée, recharger la page
                    notification.innerHTML = '<div class="flex items-center space-x-3"><i class="fas fa-check-circle text-green-400 text-xl"></i><span class="font-semibold">Site de nouveau accessible !</span></div>';
                    setTimeout(() => {
                        window.location.href = 'index.php';
                    }, 2000);
                } else {
                    notification.innerHTML = '<div class="flex items-center space-x-3"><i class="fas fa-info-circle text-yellow-400 text-xl"></i><span class="font-semibold">Maintenance toujours en cours</span></div>';
                    setTimeout(() => {
                        notification.classList.add('hidden');
                    }, 3000);
                }
            })
            .catch(error => {
                notification.classList.add('hidden');
                console.error('Erreur:', error);
            });
    }

    // Vérification automatique toutes les 30 secondes
    setInterval(checkMaintenanceStatus, 30000);
    </script>
</body>
</html>
