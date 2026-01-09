<?php 
require_once 'config.php';

// Vérifier la connexion à la base de données
$db_ready = false;
$error_message = '';

try {
    // Tester la connexion
    $pdo->query("SELECT 1");
    $db_ready = true;
    
    // Récupérer les paramètres d'apparence
    $appearance = getAppearanceSettings($pdo);

    // Statistiques (si activées)
    $stats = [];
    if ($appearance['show_stats_home'] == '1') {
        $stats = [
            'total_members' => $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
            'total_legions' => $pdo->query("SELECT COUNT(*) FROM legions")->fetchColumn(),
            'total_diplomes' => $pdo->query("SELECT COUNT(*) FROM diplomes")->fetchColumn()
        ];
    }

    // Derniers membres (si activés)
    $latest_members = [];
    if ($appearance['show_latest_members'] == '1') {
        $latest_members = $pdo->query("SELECT discord_pseudo, grade, created_at FROM members ORDER BY created_at DESC LIMIT 5")->fetchAll();
    }

    // Classe de fond selon le style choisi
    $background_class = 'bg-gray-900';
    if ($appearance['background_style'] == 'gradient') {
        $background_class = 'bg-gradient-to-b from-gray-900 to-gray-800';
    } elseif ($appearance['background_style'] == 'pattern') {
        $background_class = 'bg-gray-900';
    }
} catch (Exception $e) {
    $error_message = $e->getMessage();
    // Valeurs par défaut pour l'apparence
    $appearance = [
        'site_title' => 'CFWT',
        'site_description' => 'Coalition Française de Wars Tycoon',
        'primary_color' => '#3b82f6',
        'secondary_color' => '#8b5cf6',
        'accent_color' => '#f59e0b',
        'logo_path' => '',
        'background_style' => 'gradient',
        'show_stats_home' => '0',
        'show_latest_members' => '0'
    ];
    $background_class = 'bg-gradient-to-b from-gray-900 to-gray-800';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($appearance['site_title']); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($appearance['site_description']); ?>">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/all.min.css">
    <style>
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin-slow {
            animation: spin-slow 3s linear infinite;
        }
        @keyframes pulse-glow {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .animate-pulse-glow {
            animation: pulse-glow 2s ease-in-out infinite;
        }
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="<?php echo $background_class; ?>">
    
    <?php if (!$db_ready): ?>
    <!-- Écran de chargement -->
    <div id="loading-screen" class="min-h-screen flex items-center justify-center">
        <div class="text-center max-w-2xl px-4">
            <!-- Logo/Icône animé -->
            <div class="mb-8">
                <div class="relative inline-block">
                    <i class="fas fa-shield-alt text-9xl text-blue-500 animate-pulse-glow"></i>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <i class="fas fa-circle-notch text-6xl text-blue-300 animate-spin-slow"></i>
                    </div>
                </div>
            </div>

            <!-- Titre -->
            <h1 class="text-5xl font-bold text-white mb-4">CFWT</h1>
            <h2 class="text-2xl text-blue-400 mb-8">Coalition Française de Wars Tycoon</h2>

            <!-- Message de chargement -->
            <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm rounded-lg p-8 border border-blue-500 border-opacity-30">
                <div class="mb-6">
                    <i class="fas fa-server text-4xl text-blue-400 mb-4"></i>
                    <p class="text-xl text-white font-semibold mb-2">Démarrage du serveur en cours...</p>
                    <p class="text-gray-400">Veuillez patienter quelques instants</p>
                </div>

                <!-- Barre de progression -->
                <div class="w-full bg-gray-700 rounded-full h-2 mb-6 overflow-hidden">
                    <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-2 rounded-full animate-pulse-glow" 
                         style="width: 100%"></div>
                </div>

                <!-- Timer et infos -->
                <div class="text-sm text-gray-500 mb-4">
                    <p>Temps écoulé: <span id="timer" class="text-blue-400 font-mono">0:00</span></p>
                </div>

                <!-- Message d'aide -->
                <div id="help-message" class="hidden bg-yellow-900 bg-opacity-30 border border-yellow-600 rounded-lg p-4 text-yellow-200">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Si le site ne se charge pas après 2 minutes, 
                    <a href="mailto:admin@cfwt.com" class="underline hover:text-yellow-100">
                        contactez l'administrateur
                    </a>
                </div>
            </div>

            <!-- Informations supplémentaires -->
            <div class="mt-8 text-gray-400 text-sm">
                <p>🛡️ Serveur sécurisé • 🔄 Rechargement automatique</p>
            </div>
        </div>
    </div>

    <script>
        let seconds = 0;
        const timerElement = document.getElementById('timer');
        const helpMessage = document.getElementById('help-message');
        
        // Timer
        const interval = setInterval(() => {
            seconds++;
            const mins = Math.floor(seconds / 60);
            const secs = seconds % 60;
            timerElement.textContent = `${mins}:${secs.toString().padStart(2, '0')}`;
            
            // Afficher le message d'aide après 2 minutes
            if (seconds === 120) {
                helpMessage.classList.remove('hidden');
                helpMessage.classList.add('fade-in');
            }
        }, 1000);

        // Recharger la page toutes les 5 secondes
        const reloadInterval = setInterval(() => {
            location.reload();
        }, 5000);

        // Nettoyer les intervals si la page se charge
        window.addEventListener('beforeunload', () => {
            clearInterval(interval);
            clearInterval(reloadInterval);
        });
    </script>

    <?php else: ?>
    <!-- Contenu normal de la page -->
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen">
        <div class="max-w-4xl mx-auto px-4 py-16 text-center">
            <!-- Logo et titre -->
            <div class="mb-8">
                <?php if ($appearance['logo_path']): ?>
                    <img src="uploads/<?php echo htmlspecialchars($appearance['logo_path']); ?>" 
                         alt="Logo CFWT" class="h-36 mx-auto mb-6">
                <?php else: ?>
                    <i class="fas fa-shield-alt text-9xl mb-6" style="color: <?php echo htmlspecialchars($appearance['primary_color']); ?>"></i>
                <?php endif; ?>
                
                <h1 class="text-6xl font-bold text-white mb-4">CFWT</h1>
                <h2 class="text-3xl mb-8" style="color: <?php echo htmlspecialchars($appearance['primary_color']); ?>">
                    Coalition Française de Wars Tycoon
                </h2>
                <p class="text-xl text-gray-300 mb-12 max-w-2xl mx-auto">
                    <?php echo htmlspecialchars($appearance['site_description']); ?>
                </p>
            </div>

            <!-- Discord -->
            <div class="bg-gray-800 rounded-lg p-6 mb-8 border-2" style="border-color: <?php echo htmlspecialchars($appearance['primary_color']); ?>">
                <p class="text-white text-lg mb-2">Rejoignez notre Discord :</p>
                <a href="<?php echo DISCORD_INVITE; ?>" target="_blank" rel="noopener noreferrer"
                   class="text-xl font-semibold underline hover:no-underline"
                   style="color: <?php echo htmlspecialchars($appearance['secondary_color']); ?>">
                    <?php echo DISCORD_INVITE; ?>
                </a>
            </div>

            <!-- Bouton principal -->
            <a href="recruitment.php" 
               class="inline-block text-white px-12 py-6 rounded-lg text-2xl font-bold transform hover:scale-105 transition shadow-2xl"
               style="background: linear-gradient(135deg, 
                   <?php echo htmlspecialchars($appearance['primary_color']); ?>, 
                   <?php echo htmlspecialchars($appearance['secondary_color']); ?>)">
                Nous Rejoindre
            </a>

            <!-- Statistiques (si activées) -->
            <?php if ($appearance['show_stats_home'] == '1' && !empty($stats)): ?>
                <div class="grid md:grid-cols-3 gap-8 mt-16">
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <i class="fas fa-trophy text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['accent_color']); ?>"></i>
                        <p class="text-4xl font-bold text-white mb-2"><?php echo $stats['total_members']; ?></p>
                        <p class="text-gray-400">Membres Actifs</p>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <i class="fas fa-shield-alt text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['primary_color']); ?>"></i>
                        <p class="text-4xl font-bold text-white mb-2"><?php echo $stats['total_legions']; ?></p>
                        <p class="text-gray-400">Légions</p>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <i class="fas fa-graduation-cap text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['secondary_color']); ?>"></i>
                        <p class="text-4xl font-bold text-white mb-2"><?php echo $stats['total_diplomes']; ?></p>
                        <p class="text-gray-400">Diplômes Disponibles</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Derniers membres (si activés) -->
            <?php if ($appearance['show_latest_members'] == '1' && !empty($latest_members)): ?>
                <div class="mt-16 bg-gray-800 p-8 rounded-lg">
                    <h3 class="text-3xl font-bold text-white mb-6">
                        <i class="fas fa-users mr-2" style="color: <?php echo htmlspecialchars($appearance['accent_color']); ?>"></i>
                        Derniers Membres
                    </h3>
                    <div class="grid md:grid-cols-5 gap-4">
                        <?php foreach ($latest_members as $member): ?>
                            <div class="bg-gray-700 p-4 rounded-lg">
                                <i class="fas fa-user-circle text-4xl mb-2" style="color: <?php echo htmlspecialchars($appearance['secondary_color']); ?>"></i>
                                <p class="text-white font-semibold"><?php echo htmlspecialchars($member['discord_pseudo']); ?></p>
                                <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($member['grade']); ?></p>
                                <p class="text-gray-500 text-xs"><?php echo date('d/m/Y', strtotime($member['created_at'])); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Cartes d'informations -->
            <div class="grid md:grid-cols-3 gap-8 mt-16">
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-trophy text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['accent_color']); ?>"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Excellence</h3>
                    <p class="text-gray-400">Formation tactique et progression garantie</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-users text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['secondary_color']); ?>"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Communauté</h3>
                    <p class="text-gray-400">Une équipe soudée et organisée</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-shield-alt text-6xl mb-4" style="color: <?php echo htmlspecialchars($appearance['primary_color']); ?>"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Stratégie</h3>
                    <p class="text-gray-400">Attaques coordonnées et défenses efficaces</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php endif; ?>
</body>
</html>
