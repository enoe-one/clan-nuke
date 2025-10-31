<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

$stmt = $pdo->query("
    SELECT l.*, u.username 
    FROM admin_logs l 
    JOIN users u ON l.user_id = u.id 
    ORDER BY l.created_at DESC 
    LIMIT 100
");
$logs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logs Admin - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Logs des Actions</h1>
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <div class="bg-gray-800 rounded-lg overflow-hidden">
                <table class="w-full">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-white">Date</th>
                            <th class="px-6 py-3 text-left text-white">Utilisateur</th>
                            <th class="px-6 py-3 text-left text-white">Action</th>
                            <th class="px-6 py-3 text-left text-white">Détails</th>
                            <th class="px-6 py-3 text-left text-white">IP</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs as $log): ?>
                            <tr class="border-b border-gray-700 hover:bg-gray-750">
                                <td class="px-6 py-4 text-gray-300">
                                    <?php echo date('d/m/Y H:i:s', strtotime($log['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 text-white font-semibold">
                                    <?php echo htmlspecialchars($log['username']); ?>
                                </td>
                                <td class="px-6 py-4 text-blue-400">
                                    <?php echo htmlspecialchars($log['action']); ?>
                                </td>
                                <td class="px-6 py-4 text-gray-400">
                                    <?php echo htmlspecialchars($log['details'] ?? '-'); ?>
                                </td>
                                <td class="px-6 py-4 text-gray-500 text-sm">
                                    <?php echo htmlspecialchars($log['ip_address']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
