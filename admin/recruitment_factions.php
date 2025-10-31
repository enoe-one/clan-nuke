<?php
require_once '../config.php';

if (!isLoggedIn() || !hasAccess('access_recruitment_faction')) {
    header('Location: dashboard.php');
    exit;
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = $_POST['id'] ?? 0;
    $action = $_POST['action'];
    
    if ($action === 'accept' || $action === 'reject') {
        $status = $action === 'accept' ? 'accepted' : 'rejected';
        $stmt = $pdo->prepare("UPDATE faction_applications SET status = ?, reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
        $stmt->execute([$status, $_SESSION['user_id'], $id]);
        
        logAdminAction($pdo, $_SESSION['user_id'], ucfirst($action) . ' candidature faction', "ID: $id");
    }
}

// Récupérer les candidatures
$filter = $_GET['filter'] ?? 'pending';
$stmt = $pdo->prepare("SELECT * FROM faction_applications WHERE status = ? ORDER BY created_at DESC");
$stmt->execute([$filter]);
$applications = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidatures Factions - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Candidatures Factions</h1>
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <div class="mb-6 flex space-x-4">
                <a href="?filter=pending" class="px-4 py-2 rounded <?php echo $filter === 'pending' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    En attente
                </a>
                <a href="?filter=accepted" class="px-4 py-2 rounded <?php echo $filter === 'accepted' ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Acceptées
                </a>
                <a href="?filter=rejected" class="px-4 py-2 rounded <?php echo $filter === 'rejected' ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Refusées
                </a>
            </div>

            <div class="space-y-4">
                <?php foreach ($applications as $app): 
                    // Récupérer les membres de la faction
                    $stmt_members = $pdo->prepare("SELECT * FROM faction_members WHERE faction_id = ?");
                    $stmt_members->execute([$app['id']]);
                    $members = $stmt_members->fetchAll();
                ?>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <h3 class="text-2xl font-bold text-white mb-4"><?php echo htmlspecialchars($app['nom_faction']); ?></h3>
                        
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-gray-400 text-sm">Chef de faction</p>
                                <p class="text-white font-semibold"><?php echo htmlspecialchars($app['chef_faction']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Nombre de membres</p>
                                <p class="text-white"><?php echo htmlspecialchars($app['nombre_membres']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm">Auto-évaluation</p>
                                <p class="text-white"><?php echo htmlspecialchars($app['evaluation']); ?>/10</p>
                            </div>
                        </div>

                        <?php if (!empty($members)): ?>
                            <div class="mb-4">
                                <p class="text-gray-400 text-sm mb-2">Membres de la faction</p>
                                <div class="bg-gray-700 p-3 rounded max-h-48 overflow-y-auto">
                                    <?php foreach ($members as $member): ?>
                                        <div class="flex justify-between py-1 border-b border-gray-600">
                                            <span class="text-white"><?php echo htmlspecialchars($member['pseudo']); ?></span>
                                            <span class="text-gray-400"><?php echo htmlspecialchars($member['niveau']); ?>/10</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="mb-4">
                            <p class="text-gray-400 text-sm mb-1">Motivation</p>
                            <p class="text-white bg-gray-700 p-3 rounded"><?php echo nl2br(htmlspecialchars($app['raison'])); ?></p>
                        </div>

                        <div class="flex justify-between items-center">
                            <p class="text-gray-500 text-sm">
                                Candidature du <?php echo date('d/m/Y à H:i', strtotime($app['created_at'])); ?>
                            </p>
                            
                            <?php if ($filter === 'pending'): ?>
                                <div class="flex space-x-2">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="action" value="accept">
                                        <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                                            <i class="fas fa-check mr-2"></i> Accepter
                                        </button>
                                    </form>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $app['id']; ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                            <i class="fas fa-times mr-2"></i> Refuser
                                        </button>
                                    </form>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($applications)): ?>
                    <div class="bg-gray-800 p-12 rounded-lg text-center">
                        <i class="fas fa-inbox text-gray-600 text-6xl mb-4"></i>
                        <p class="text-gray-400 text-xl">Aucune candidature dans cette catégorie</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>