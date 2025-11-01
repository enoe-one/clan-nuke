<?php
require_once '../config.php';

if (!isMember()) {
    header('Location: ../member_login.php');
    exit;
}

// Récupérer les infos du membre
$stmt = $pdo->prepare("
    SELECT m.*, l.nom as legion_nom 
    FROM members m 
    LEFT JOIN legions l ON m.legion_id = l.id 
    WHERE m.id = ?
");
$stmt->execute([$_SESSION['member_id']]);
$member = $stmt->fetch();

// Récupérer les diplômes du membre
$stmt = $pdo->prepare("
    SELECT d.* 
    FROM diplomes d
    JOIN member_diplomes md ON d.id = md.diplome_id
    WHERE md.member_id = ?
    ORDER BY d.categorie, d.niveau
");
$stmt->execute([$_SESSION['member_id']]);
$member_diplomes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Mon Profil</h1>
                <a href="logout.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                </a>
            </div>

            <?php if ($_SESSION['member_must_change_password']): ?>
                <div class="bg-yellow-900 bg-opacity-50 border border-yellow-500 p-4 rounded-lg mb-6">
                    <p class="text-yellow-300 font-semibold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Vous devez changer votre mot de passe
                    </p>
                    <a href="change_password.php" class="text-yellow-200 underline hover:text-yellow-100">
                        Changer mon mot de passe maintenant
                    </a>
                </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-blue-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400">Grade</span>
                        <i class="fas fa-medal text-blue-500 text-2xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-white"><?php echo htmlspecialchars($member['grade']); ?></p>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-green-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400">Rang</span>
                        <i class="fas fa-star text-green-500 text-2xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-white"><?php echo htmlspecialchars($member['rang']); ?></p>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-gray-400">KDR</span>
                        <i class="fas fa-crosshairs text-yellow-500 text-2xl"></i>
                    </div>
                    <p class="text-2xl font-bold text-white"><?php echo htmlspecialchars($member['kdr']); ?></p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-2xl font-bold text-white mb-4">
                        <i class="fas fa-user mr-2"></i> Informations
                    </h2>
                    <div class="space-y-3">
                        <div>
                            <p class="text-gray-400 text-sm">Discord</p>
                            <p class="text-white font-semibold"><?php echo htmlspecialchars($member['discord_pseudo']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Roblox</p>
                            <p class="text-white font-semibold"><?php echo htmlspecialchars($member['roblox_pseudo']); ?></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Légion</p>
                            <p class="text-white font-semibold">
                                <?php echo $member['legion_nom'] ? htmlspecialchars($member['legion_nom']) : 'Aucune légion'; ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg">
                    <h2 class="text-2xl font-bold text-white mb-4">
                        <i class="fas fa-graduation-cap mr-2"></i> Mes Diplômes
                        <span class="text-lg text-gray-400">(<?php echo count($member_diplomes); ?>)</span>
                    </h2>
                    <?php if (!empty($member_diplomes)): ?>
                        <div class="space-y-2 max-h-64 overflow-y-auto">
                            <?php foreach ($member_diplomes as $diplome): ?>
                                <div class="bg-gray-700 p-3 rounded">
           