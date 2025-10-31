<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CFWT - Coalition Française de Wars Tycoon</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen bg-gradient-to-b from-gray-900 to-gray-800">
        <div class="max-w-4xl mx-auto px-4 py-16 text-center">
            <div class="mb-8">
                <i class="fas fa-shield-alt text-red-500 text-9xl mb-6"></i>
                <h1 class="text-6xl font-bold text-white mb-4">CFWT</h1>
                <h2 class="text-3xl text-red-400 mb-8">Coalition Française de Wars Tycoon</h2>
                <p class="text-xl text-gray-300 mb-12 max-w-2xl mx-auto">
                    Rejoignez la plus grande coalition francophone de Wars Tycoon sur Roblox. 
                    Excellence tactique, camaraderie et domination sur le champ de bataille.
                </p>
            </div>

            <div class="bg-gray-800 rounded-lg p-6 mb-8 border-2 border-red-500">
                <p class="text-white text-lg mb-2">Rejoignez notre Discord :</p>
                <a href="<?php echo DISCORD_INVITE; ?>" target="_blank" rel="noopener noreferrer"
                   class="text-blue-400 hover:text-blue-300 text-xl font-semibold underline">
                    <?php echo DISCORD_INVITE; ?>
                </a>
            </div>

            <a href="recruitment.php" 
               class="inline-block bg-gradient-to-r from-red-600 to-blue-600 text-white px-12 py-6 rounded-lg text-2xl font-bold hover:from-red-700 hover:to-blue-700 transform hover:scale-105 transition shadow-2xl">
                Nous Rejoindre
            </a>

            <div class="grid md:grid-cols-3 gap-8 mt-16">
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-trophy text-yellow-500 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Excellence</h3>
                    <p class="text-gray-400">Formation tactique et progression garantie</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-users text-blue-500 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Communauté</h3>
                    <p class="text-gray-400">Une équipe soudée et organisée</p>
                </div>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <i class="fas fa-shield-alt text-red-500 text-6xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Stratégie</h3>
                    <p class="text-gray-400">Attaques coordonnées et défenses efficaces</p>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>