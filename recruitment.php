<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recrutement - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-white mb-8 text-center">Recrutement</h1>
            
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <a href="recruitment_member.php" 
                   class="bg-gradient-to-br from-blue-600 to-blue-800 text-white p-8 rounded-lg hover:from-blue-700 hover:to-blue-900 transition transform hover:scale-105 shadow-xl text-center">
                    <i class="fas fa-user-plus text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Recrutement Individuel</h2>
                    <p class="text-blue-200">Rejoignez-nous en tant que membre</p>
                </a>

                <a href="recruitment_faction.php" 
                   class="bg-gradient-to-br from-red-600 to-red-800 text-white p-8 rounded-lg hover:from-red-700 hover:to-red-900 transition transform hover:scale-105 shadow-xl text-center">
                    <i class="fas fa-users text-6xl mb-4"></i>
                    <h2 class="text-2xl font-bold mb-2">Recrutement Faction</h2>
                    <p class="text-red-200">Rejoignez avec votre faction complète</p>
                </a>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg border-2 border-yellow-500">
                <p class="text-yellow-400 font-semibold mb-2">⚠️ Note importante</p>
                <p class="text-gray-300">Les formulaires servent à évaluer approximativement votre profil. Le niveau indiqué n'est pas pris en compte dans la décision finale.</p>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>