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

// Configuration par défaut si aucune maintenance configurée
if (!$maintenance) {
    $maintenance = [
        'title' => 'Maintenance en cours',
        'message' => 'Le site CFWT est actuellement en maintenance pour améliorer votre expérience.',
        'custom_icon' => 'fa-cog',
        'icon_color' => 'text-blue-500',
        'theme_color' => 'blue',
        'estimated_duration' => '',
        'end_time' => null,
        'show_countdown' => false,
        'show_discord_link' => true
    ];
}

// Couleurs de thème
$theme_colors = [
    'blue' => ['primary' => 'bg-blue-600', 'secondary' => 'bg-blue-900', 'border' => 'border-blue-500', 'text' => 'text-blue-400'],
    'red' => ['primary' => 'bg-red-600', 'secondary' => 'bg-red-900', 'border' => 'border-red-500', 'text' => 'text-red-400'],
    'green' => ['primary' => 'bg-green-600', 'secondary' => 'bg-green-900', 'border' => 'border-green-500', 'text' => 'text-green-400'],
    'yellow' => ['primary' => 'bg-yellow-600', 'secondary' => 'bg-yellow-900', 'border' => 'border-yellow-500', 'text' => 'text-yellow-400'],
    'purple' => ['primary' => 'bg-purple-600', 'secondary' => 'bg-purple-900', 'border' => 'border-purple-500', 'text' => 'text-purple-400']
];

$colors = $theme_colors[$maintenance['theme_color']] ?? $theme_colors['blue'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($maintenance['title']); ?> - CFWT</title>
    <meta http-equiv="refresh" content="300"> <!-- Rafraîchir toutes les 5 minutes -->
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/all.min.css">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 3s linear infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.6s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center">
    
    <div class="max-w-3xl mx-auto px-4 text-center fade-in">
        
        <!-- Icône animée -->
        <div class="mb-8">
            <div class="relative inline-block">
                <i class="fas <?php echo htmlspecialchars($maintenance['custom_icon']); ?> text-9xl <?php echo htmlspecialchars($maintenance['icon_color']); ?> spinner"></i>
                <i class="fas fa-shield-alt text-6xl text-red-500 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pulse"></i>
            </div>
        </div>

        <!-- Titre -->
        <h1 class="text-5xl font-bold text-white mb-6">
            <?php echo htmlspecialchars($maintenance['title']); ?>
        </h1>

        <!-- Message principal -->
        <div class="<?php echo $colors['secondary']; ?> bg-opacity-50 backdrop-blur-sm p-8 rounded-lg border <?php echo $colors['border']; ?> mb-8">
            <i class="fas fa-info-circle <?php echo $colors['text']; ?> text-4xl mb-4"></i>
            <p class="text-xl text-gray-300 mb-6 leading-relaxed">
                <?php echo nl2br(htmlspecialchars($maintenance['message'])); ?>
            </p>

            <!-- Durée estimée -->
            <?php if ($maintenance['estimated_duration']): ?>
                <div class="bg-gray-800 bg-opacity-50 p-4 rounded-lg inline-block mb-4">
                    <i class="fas fa-clock <?php echo $colors['text']; ?> mr-2"></i>
                    <span class="text-white font-semibold">Durée estimée : </span>
                    <span class="<?php echo $colors['text']; ?> font-bold">
                        <?php echo htmlspecialchars($maintenance['estimated_duration']); ?>
                    </span>
                </div>
            <?php endif; ?>

            <!-- Compte à rebours -->
            <?php if ($maintenance['show_countdown'] && $maintenance['end_time']): ?>
                <div id="countdown" class="mt-6">
                    <p class="text-gray-400 mb-3">Retour prévu dans :</p>
                    <div class="flex justify-center space-x-4">
                        <div class="<?php echo $colors['primary']; ?> bg-opacity-30 p-4 rounded-lg min-w-[80px]">
                            <div id="hours" class="text-3xl font-bold text-white">00</div>
                            <div class="text-gray-400 text-sm">Heures</div>
                        </div>
                        <div class="<?php echo $colors['primary']; ?> bg-opacity-30 p-4 rounded-lg min-w-[80px]">
                            <div id="minutes" class="text-3xl font-bold text-white">00</div>
                            <div class="text-gray-400 text-sm">Minutes</div>
                        </div>
                        <div class="<?php echo $colors['primary']; ?> bg-opacity-30 p-4 rounded-lg min-w-[80px]">
                            <div id="seconds" class="text-3xl font-bold text-white">00</div>
                            <div class="text-gray-400 text-sm">Secondes</div>
                        </div>
                    </div>
                </div>

                <script>
                const endTime = new Date('<?php echo $maintenance['end_time']; ?>').getTime();

                function updateCountdown() {
                    const now = new Date().getTime();
                    const distance = endTime - now;

                    if (distance < 0) {
                        document.getElementById('countdown').innerHTML = 
                            '<p class="text-green-400 text-xl font-bold"><i class="fas fa-check-circle mr-2"></i>Maintenance terminée ! Rechargement...</p>';
                        setTimeout(() => location.reload(), 3000);
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
        </div>

        <!-- Informations supplémentaires -->
        <div class="grid md:grid-cols-2 gap-4 mb-8">
            <!-- Statut -->
            <div class="bg-gray-800 bg-opacity-50 p-6 rounded-lg border border-gray-700">
                <i class="fas fa-server text-4xl <?php echo $colors['text']; ?> mb-3"></i>
                <p class="text-white font-semibold mb-2">Statut du serveur</p>
                <div class="flex items-center justify-center">
                    <span class="w-3 h-3 bg-yellow-500 rounded-full mr-2 pulse"></span>
                    <span class="text-yellow-400 font-semibold">En maintenance</span>
                </div>
            </div>

            <!-- Progression -->
            <div class="bg-gray-800 bg-opacity-50 p-6 rounded-lg border border-gray-700">
                <i class="fas fa-tasks text-4xl <?php echo $colors['text']; ?> mb-3"></i>
                <p class="text-white font-semibold mb-2">Progression</p>
                <div class="w-full bg-gray-700 rounded-full h-3 overflow-hidden">
                    <div class="<?php echo $colors['primary']; ?> h-3 rounded-full pulse" 
                         style="width: 100%"></div>
                </div>
                <p class="text-gray-400 text-sm mt-2">Opérations en cours...</p>
            </div>
        </div>

        <!-- Discord -->
        <?php if ($maintenance['show_discord_link']): ?>
            <div class="<?php echo $colors['secondary']; ?> bg-opacity-30 p-6 rounded-lg border <?php echo $colors['border']; ?> mb-8">
                <i class="fab fa-discord text-5xl <?php echo $colors['text']; ?> mb-4"></i>
                <p class="<?php echo $colors['text']; ?> text-lg mb-4 font-semibold">
                    Rejoignez notre Discord pour plus d'informations !
                </p>
                <a href="<?php echo DISCORD_INVITE; ?>" target="_blank" 
                   class="inline-block <?php echo $colors['primary']; ?> text-white px-8 py-4 rounded-lg font-bold hover:opacity-90 transition transform hover:scale-105">
                    <i class="fab fa-discord mr-2"></i>Rejoindre le Discord
                </a>
            </div>
        <?php endif; ?>

        <!-- Actions rapides -->
        <div class="flex flex-wrap justify-center gap-4 mb-8">
            <button onclick="location.reload()" 
                    class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-sync-alt mr-2"></i>Rafraîchir
            </button>
            <a href="login.php" 
               class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition inline-block">
                <i class="fas fa-user-shield mr-2"></i>Connexion Admin
            </a>
        </div>

        <!-- Informations techniques -->
        <div class="bg-gray-800 bg-opacity-30 p-4 rounded-lg border border-gray-700">
            <details class="text-left">
                <summary class="text-gray-400 cursor-pointer hover:text-gray-300 font-semibold">
                    <i class="fas fa-info-circle mr-2"></i>Informations techniques
                </summary>
                <div class="mt-4 space-y-2 text-sm text-gray-500">
                    <p><i class="fas fa-calendar mr-2"></i>Début : <?php echo $maintenance['start_time'] ? date('d/m/Y H:i', strtotime($maintenance['start_time'])) : 'En cours'; ?></p>
                    <?php if ($maintenance['end_time']): ?>
                        <p><i class="fas fa-calendar-check mr-2"></i>Fin prévue : <?php echo date('d/m/Y H:i', strtotime($maintenance['end_time'])); ?></p>
                    <?php endif; ?>
                    <p><i class="fas fa-tag mr-2"></i>Type : <?php echo ucfirst($maintenance['maintenance_type'] ?? 'Standard'); ?></p>
                    <p><i class="fas fa-sync mr-2"></i>Rafraîchissement automatique toutes les 5 minutes</p>
                </div>
            </details>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-gray-600 text-sm">
            <p>&copy; <?php echo date('Y'); ?> CFWT - Coalition Française de Wars Tycoon</p>
            <p class="mt-2 text-gray-700">
                <i class="fas fa-shield-alt mr-1"></i>
                Serveur sécurisé • Merci de votre patience
            </p>
        </div>
    </div>

    <!-- Auto-refresh notification -->
    <div class="fixed bottom-4 right-4 bg-gray-800 bg-opacity-90 text-white px-4 py-2 rounded-lg shadow-lg">
        <i class="fas fa-sync-alt mr-2 text-blue-400"></i>
        <span class="text-sm">Vérification auto : <span id="next-check" class="font-mono">5:00</span></span>
    </div>

    <script>
    // Compte à rebours pour le prochain rafraîchissement
    let timeLeft = 300; // 5 minutes en secondes
    const nextCheckElement = document.getElementById('next-check');

    function updateNextCheck() {
        const mins = Math.floor(timeLeft / 60);
        const secs = timeLeft % 60;
        nextCheckElement.textContent = `${mins}:${String(secs).padStart(2, '0')}`;
        
        if (timeLeft > 0) {
            timeLeft--;
        }
    }

    updateNextCheck();
    setInterval(updateNextCheck, 1000);
    </script>
</body>
</html>
