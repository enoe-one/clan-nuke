<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Statistiques
$stmt = $pdo->query("SELECT COUNT(*) FROM member_applications WHERE status = 'pending'");
$pending_members = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM faction_applications WHERE status = 'pending'");
$pending_factions = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM member_applications WHERE status = 'accepted'");
$accepted_members = $stmt->fetchColumn();

$stmt = $pdo->query("SELECT COUNT(*) FROM faction_applications WHERE status = 'accepted'");
$accepted_factions = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord Admin - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">Tableau de bord Admin</h1>
                    <p class="text-gray-400">Connecté en tant que: <span class="text-blue-400 font-semibold"><?php echo htmlspecialchars($_SESSION['username']); ?></span></p>
                </div>
                <a href="logout.php" class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 transition">
                    <i class="fas fa-sign-out-alt mr-2"></i> Déconnexion
                </a>
            </div>

            <?php if ($_SESSION['must_change_password']): ?>
                <div class="bg-yellow-900 bg-opacity-50 border border-yellow-500 p-4 rounded-lg mb-6">
                    <p class="text-yellow-300 font-semibold">
                        <i class="fas fa-exclamation-triangle mr-2"></i>
                        Vous devez changer votre mot de passe lors de votre première connexion
                    </p>
                    <a href="change_password.php" class="text-yellow-200 underline hover:text-yellow-100">
                        Changer mon mot de passe maintenant
                    </a>
                </div>
            <?php endif; ?>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
                <?php if (hasAccess('access_recruitment_player')): ?>
                    <a href="recruitment_members.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-blue-500 hover:bg-gray-700 transition">
                        <i class="fas fa-user-plus text-blue-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Recrutement Joueurs</h3>
                        <p class="text-gray-400 mb-4">Voir les candidatures individuelles</p>
                        <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm">
                            <?php echo $pending_members; ?> en attente
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (hasAccess('access_recruitment_faction')): ?>
                    <a href="recruitment_factions.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-red-500 hover:bg-gray-700 transition">
                        <i class="fas fa-users text-red-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Recrutement Factions</h3>
                        <p class="text-gray-400 mb-4">Voir les candidatures de factions</p>
                        <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm">
                            <?php echo $pending_factions; ?> en attente
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (hasAccess('access_edit_members')): ?>
                    <a href="manage_members.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-green-500 hover:bg-gray-700 transition">
                        <i class="fas fa-users-cog text-green-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Gestion Membres</h3>
                        <p class="text-gray-400 mb-4">Modifier les membres de la coalition</p>
                        <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm">
                            <?php echo $accepted_members; ?> membres
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (hasAccess('access_moderation')): ?>
                    <a href="moderation.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-yellow-500 hover:bg-gray-700 transition">
                        <i class="fas fa-shield-alt text-yellow-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Modération</h3>
                        <p class="text-gray-400 mb-4">Gérer les signalements et problèmes</p>
                        <span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-sm">
                            0 signalements
                        </span>
                    </a>
                <?php endif; ?>

                <?php if (hasAccess('access_edit_site')): ?>
                    <a href="edit_site.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-purple-500 hover:bg-gray-700 transition">
                        <i class="fas fa-edit text-purple-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Édition Site</h3>
                        <p class="text-gray-400 mb-4">Modifier le contenu du site</p>
                    </a>
                <?php endif; ?>

                <?php if (hasAccess('access_full')): ?>
                    <a href="full_access.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-red-500 hover:bg-gray-700 transition">
                        <i class="fas fa-database text-red-500 text-5xl mb-4"></i>
                        <h3 class="text-xl font-bold text-white mb-2">Accès Complet</h3>
                        <p class="text-gray-400 mb-4">Toutes les données et logs</p>
                    </a>
                <?php endif; ?>

                <a href="logs.php" class="bg-gray-800 p-6 rounded-lg border-t-4 border-gray-500 hover:bg-gray-700 transition">
                    <i class="fas fa-history text-gray-500 text-5xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Logs</h3>
                    <p class="text-gray-400 mb-4">Historique des actions</p>
                </a>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg">
                <h3 class="text-2xl font-bold text-white mb-4">Statistiques</h3>
                <div class="grid md:grid-cols-4 gap-4">
                    <div class="bg-gray-700 p-4 rounded text-center">
                        <p class="text-3xl font-bold text-blue-400"><?php echo $pending_members + $pending_factions; ?></p>
                        <p class="text-gray-400">Candidatures en attente</p>
                    </div>
                    <div class="bg-gray-700 p-4 rounded text-center">
                        <p class="text-3xl font-bold text-green-400"><?php echo $accepted_members; ?></p>
                        <p class="text-gray-400">Membres acceptés</p>
                    </div>
                    <div class="bg-gray-700 p-4 rounded text-center">
                        <p class="text-3xl font-bold text-yellow-400"><?php echo $accepted_factions; ?></p>
                        <p class="text-gray-400">Factions alliées</p>
                    </div>
                    <div class="bg-gray-700 p-4 rounded text-center">
                        <p class="text-3xl font-bold text-red-400">0</p>
                        <p class="text-gray-400">Signalements</p>
                    </div>
                    <a href="manage_events.php" class="bg-gray-800 p-6 rounded-lg...">
                    <i class="fas fa-calendar-plus text-blue-500 text-5xl mb-4"></i>
                    <h3 class="text-xl font-bold text-white mb-2">Gérer les Événements</h3>
                    <p class="text-gray-400 mb-4">Créer raids, formations et réunions</p>
                   </a>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

