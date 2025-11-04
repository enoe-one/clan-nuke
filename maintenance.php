<?php
require_once 'config.php';

// Si l'utilisateur est admin, le laisser passer
if (isAdmin()) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Maintenance - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .spinner {
            animation: spin 2s linear infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        .pulse {
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 min-h-screen flex items-center justify-center">
    <div class="max-w-2xl mx-auto px-4 text-center">
        <!-- Icône animée -->
        <div class="mb-8">
            <div class="relative inline-block">
                <i class="fas fa-cog text-9xl text-blue-500 spinner"></i>
                <i class="fas fa-shield-alt text-6xl text-red-500 absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 pulse"></i>
            </div>
        </div>

        <!-- Titre -->
        <h1 class="text-5xl font-bold text-white mb-4">
            Maintenance en cours
        </h1>

        <!-- Description -->
        <p class="text-xl text-gray-300 mb-8">
            Le site CFWT est actuellement en maintenance pour améliorer votre expérience.
        </p>

        <!-- Message -->
        <div class="bg-gray-800 bg-opacity-50 backdrop-blur-sm p-8 rounded-lg border border-gray-700 mb-8">
            <i class="fas fa-tools text-yellow-500 text-4xl mb-4"></i>
            <p class="text-gray-400 mb-4">
                Nous effectuons des mises à jour importantes pour vous offrir la meilleure expérience possible.
            </p>
            <p class="text-gray-500 text-sm">
                <i class="fas fa-clock mr-2"></i>
                Cette maintenance devrait se terminer très prochainement.
            </p>
        </div>

        <!-- Discord -->
        <div class="bg-blue-900 bg-opacity-30 p-6 rounded-lg border border-blue-500 mb-8">
            <p class="text-blue-300 mb-4">
                <i class="fab fa-discord text-2xl mr-2"></i>
                Rejoignez notre Discord pour plus d'informations !
            </p>
            <a href="<?php echo DISCORD_INVITE; ?>" target="_blank" 
               class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                Rejoindre le Discord
            </a>
        </div>

        <!-- Connexion admin -->
        <div class="text-center">
            <a href="login.php" class="text-gray-500 hover:text-gray-400 text-sm">
                <i class="fas fa-user-shield mr-1"></i>
                Connexion Administrateur
            </a>
        </div>

        <!-- Footer -->
        <div class="mt-12 text-gray-600 text-sm">
            <p>&copy; <?php echo date('Y'); ?> CFWT - Coalition Française de Wars Tycoon</p>
        </div>
    </div>
</body>
</html>