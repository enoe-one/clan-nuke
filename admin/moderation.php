<?php
require_once '../config.php';

if (!isAdmin() || !hasAccess('access_moderation')) {
    header('Location: dashboard.php');
    exit;
}

// Traiter les actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $id = $_POST['id'] ?? 0;
    $action = $_POST['action'];
    
    if ($action === 'resolve') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'resolved', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        logAdminAction($pdo, $_SESSION['user_id'], 'Résolution signalement', "ID: $id");
    } elseif ($action === 'close') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'closed', resolved_by = ?, resolved_at = NOW() WHERE id = ?");
        $stmt->execute([$_SESSION['user_id'], $id]);
        logAdminAction($pdo, $_SESSION['user_id'], 'Fermeture signalement', "ID: $id");
    } elseif ($action === 'in_progress') {
        $stmt = $pdo->prepare("UPDATE reports SET status = 'in_progress' WHERE id = ?");
        $stmt->execute([$id]);
        logAdminAction($pdo, $_SESSION['user_id'], 'Prise en charge signalement', "ID: $id");
    }
}

// Récupérer les signalements
$filter = $_GET['filter'] ?? 'pending';
$stmt = $pdo->prepare("
    SELECT r.*, u.username as resolved_by_name 
    FROM reports r 
    LEFT JOIN users u ON r.resolved_by = u.id 
    WHERE r.status = ? 
    ORDER BY r.created_at DESC
");
$stmt->execute([$filter]);
$reports = $stmt->fetchAll();

// Compter les signalements par statut
$stmt = $pdo->query("SELECT status, COUNT(*) as count FROM reports GROUP BY status");
$counts = [];
while ($row = $stmt->fetch()) {
    $counts[$row['status']] = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signalements - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Gestion des Signalements</h1>
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <!-- Filtres -->
            <div class="mb-6 flex flex-wrap gap-4">
                <a href="?filter=pending" class="px-4 py-2 rounded flex items-center gap-2 <?php echo $filter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    <i class="fas fa-clock"></i>
                    En attente
                    <?php if (isset($counts['pending'])): ?>
                        <span class="bg-yellow-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            <?php echo $counts['pending']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?filter=in_progress" class="px-4 py-2 rounded flex items-center gap-2 <?php echo $filter === 'in_progress' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    <i class="fas fa-spinner"></i>
                    En cours
                    <?php if (isset($counts['in_progress'])): ?>
                        <span class="bg-blue-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            <?php echo $counts['in_progress']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?filter=resolved" class="px-4 py-2 rounded flex items-center gap-2 <?php echo $filter === 'resolved' ? 'bg-green-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    <i class="fas fa-check-circle"></i>
                    Résolus
                    <?php if (isset($counts['resolved'])): ?>
                        <span class="bg-green-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            <?php echo $counts['resolved']; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <a href="?filter=closed" class="px-4 py-2 rounded flex items-center gap-2 <?php echo $filter === 'closed' ? 'bg-gray-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    <i class="fas fa-times-circle"></i>
                    Fermés
                    <?php if (isset($counts['closed'])): ?>
                        <span class="bg-gray-500 text-white px-2 py-1 rounded-full text-xs font-bold">
                            <?php echo $counts['closed']; ?>
                        </span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Liste des signalements -->
            <div class="space-y-4">
                <?php foreach ($reports as $report): ?>
                    <div class="bg-gray-800 p-6 rounded-lg hover:bg-gray-750 transition">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-xl font-bold text-white mb-2">
                                    <?php echo htmlspecialchars($report['subject']); ?>
                                </h3>
                                <div class="flex items-center gap-4 text-sm text-gray-400">
                                    <?php if ($report['reporter_name']): ?>
                                        <span>
                                            <i class="fas fa-user mr-1"></i>
                                            <?php echo htmlspecialchars($report['reporter_name']); ?>
                                        </span>
                                    <?php endif; ?>
                                    <span>
                                        <i class="fas fa-calendar mr-1"></i>
                                        <?php echo date('d/m/Y à H:i', strtotime($report['created_at'])); ?>
                                    </span>
                                    <span>
                                        <i class="fas fa-network-wired mr-1"></i>
                                        <?php echo htmlspecialchars($report['ip_address']); ?>
                                    </span>
                                </div>
                            </div>
                            <span class="px-3 py-1 rounded text-sm font-bold whitespace-nowrap
                                <?php 
                                    echo $report['status'] === 'pending' ? 'bg-yellow-600 text-white' : 
                                         ($report['status'] === 'in_progress' ? 'bg-blue-600 text-white' : 
                                         ($report['status'] === 'resolved' ? 'bg-green-600 text-white' : 'bg-gray-600 text-white')); 
                                ?>">
                                <?php 
                                    $status_labels = [
                                        'pending' => 'En attente',
                                        'in_progress' => 'En cours',
                                        'resolved' => 'Résolu',
                                        'closed' => 'Fermé'
                                    ];
                                    echo $status_labels[$report['status']];
                                ?>
                            </span>
                        </div>

                        <div class="bg-gray-700 p-4 rounded mb-4">
                            <p class="text-white whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($report['description'])); ?></p>
                        </div>

                        <?php if ($report['resolved_by_name']): ?>
                            <p class="text-green-400 text-sm mb-4">
                                <i class="fas fa-user-check mr-1"></i>
                                Traité par <strong><?php echo htmlspecialchars($report['resolved_by_name']); ?></strong>
                                le <?php echo date('d/m/Y à H:i', strtotime($report['resolved_at'])); ?>
                            </p>
                        <?php endif; ?>

                        <?php if ($filter === 'pending' || $filter === 'in_progress'): ?>
                            <div class="flex flex-wrap gap-2">
                                <?php if ($filter === 'pending'): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                        <input type="hidden" name="action" value="in_progress">
                                        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                            <i class="fas fa-play mr-2"></i> Prendre en charge
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="action" value="resolve">
                                    <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition">
                                        <i class="fas fa-check mr-2"></i> Marquer comme résolu
                                    </button>
                                </form>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $report['id']; ?>">
                                    <input type="hidden" name="action" value="close">
                                    <button type="submit" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                                        <i class="fas fa-times mr-2"></i> Fermer sans résoudre
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>

                <?php if (empty($reports)): ?>
                    <div class="bg-gray-800 p-12 rounded-lg text-center">
                        <i class="fas fa-inbox text-gray-600 text-6xl mb-4"></i>
                        <p class="text-gray-400 text-xl">Aucun signalement dans cette catégorie</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
