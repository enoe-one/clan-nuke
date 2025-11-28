<?php
require_once '../config.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = intval($_POST['request_id'] ?? 0);
    
    if ($action === 'approve' || $action === 'reject') {
        $admin_response = trim($_POST['admin_response'] ?? '');
        
        try {
            // Récupérer la demande
            $stmt = $pdo->prepare("SELECT * FROM promotion_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if (!$request) {
                throw new Exception("Demande introuvable");
            }
            
            $pdo->beginTransaction();
            
            if ($action === 'approve') {
                // Mettre à jour le grade/rang du membre
                $field = $request['type'] === 'grade' ? 'grade' : 'rang';
                $stmt = $pdo->prepare("UPDATE members SET $field = ? WHERE id = ?");
                $stmt->execute([$request['requested_value'], $request['member_id']]);
                
                $status = 'approved';
                $success = "Promotion approuvée ! Le membre a été promu.";
                
                logAdminAction($pdo, $_SESSION['user_id'], 'approve_promotion', 
                    "Membre ID {$request['member_id']}: {$request['current_value']} → {$request['requested_value']}");
            } else {
                $status = 'rejected';
                $success = "Demande refusée.";
                
                logAdminAction($pdo, $_SESSION['user_id'], 'reject_promotion', 
                    "Membre ID {$request['member_id']}: {$request['requested_value']}");
            }
            
            // Mettre à jour la demande
            $stmt = $pdo->prepare("
                UPDATE promotion_requests 
                SET status = ?, admin_response = ?, reviewed_at = NOW(), reviewed_by = ?
                WHERE id = ?
            ");
            $stmt->execute([$status, $admin_response, $_SESSION['user_id'], $request_id]);
            
            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = $e->getMessage();
        }
    }
}

// Récupérer les demandes en attente
$stmt = $pdo->query("
    SELECT pr.*, 
           m.discord_pseudo, m.roblox_pseudo, m.grade as current_grade, m.rang as current_rang,
           l.nom as legion_nom
    FROM promotion_requests pr
    JOIN members m ON pr.member_id = m.id
    LEFT JOIN legions l ON m.legion_id = l.id
    WHERE pr.status = 'pending'
    ORDER BY pr.created_at ASC
");
$pending_requests = $stmt->fetchAll();

// Récupérer l'historique récent
$stmt = $pdo->query("
    SELECT pr.*, 
           m.discord_pseudo, m.roblox_pseudo,
           u.username as reviewer_name
    FROM promotion_requests pr
    JOIN members m ON pr.member_id = m.id
    LEFT JOIN users u ON pr.reviewed_by = u.id
    WHERE pr.status != 'pending'
    ORDER BY pr.reviewed_at DESC
    LIMIT 20
");
$history = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Promotions - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">
                    <i class="fas fa-arrow-up text-green-500 mr-3"></i>
                    Gestion des Promotions
                </h1>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i>Retour
                </a>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Statistiques -->
            <div class="grid md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-yellow-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 mb-1">En attente</p>
                            <p class="text-3xl font-bold text-white"><?php echo count($pending_requests); ?></p>
                        </div>
                        <i class="fas fa-clock text-yellow-500 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-green-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 mb-1">Approuvées (24h)</p>
                            <p class="text-3xl font-bold text-white">
                                <?php 
                                $stmt = $pdo->query("SELECT COUNT(*) FROM promotion_requests WHERE status = 'approved' AND reviewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                                echo $stmt->fetchColumn();
                                ?>
                            </p>
                        </div>
                        <i class="fas fa-check-circle text-green-500 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-red-500">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-gray-400 mb-1">Refusées (24h)</p>
                            <p class="text-3xl font-bold text-white">
                                <?php 
                                $stmt = $pdo->query("SELECT COUNT(*) FROM promotion_requests WHERE status = 'rejected' AND reviewed_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
                                echo $stmt->fetchColumn();
                                ?>
                            </p>
                        </div>
                        <i class="fas fa-times-circle text-red-500 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Demandes en attente -->
            <div class="bg-gray-800 rounded-lg p-6 mb-8">
                <h2 class="text-2xl font-bold text-white mb-6">
                    <i class="fas fa-inbox text-yellow-500 mr-2"></i>
                    Demandes en attente (<?php echo count($pending_requests); ?>)
                </h2>
                
                <?php if (empty($pending_requests)): ?>
                    <div class="text-center py-12 text-gray-500">
                        <i class="fas fa-check-double text-6xl mb-4"></i>
                        <p class="text-xl">Aucune demande en attente</p>
                    </div>
                <?php else: ?>
                    <div class="space-y-6">
                        <?php foreach ($pending_requests as $request): ?>
                            <div class="bg-gray-700 rounded-lg p-6 border-l-4 border-yellow-500">
                                <!-- En-tête -->
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h3 class="text-xl font-bold text-white mb-2">
                                            <?php echo $request['type'] === 'grade' ? 'Promotion de grade' : 'Montée de rang'; ?>
                                        </h3>
                                        <p class="text-gray-400 text-sm">
                                            Demandé le <?php echo date('d/m/Y à H:i', strtotime($request['created_at'])); ?>
                                        </p>
                                    </div>
                                    <span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-sm font-semibold">
                                        <i class="fas fa-clock mr-1"></i>En attente
                                    </span>
                                </div>

                                <!-- Infos membre -->
                                <div class="bg-gray-800 p-4 rounded mb-4">
                                    <div class="grid md:grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-gray-400 text-sm mb-1">Discord</p>
                                            <p class="text-white font-semibold"><?php echo htmlspecialchars($request['discord_pseudo']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm mb-1">Roblox</p>
                                            <p class="text-white font-semibold"><?php echo htmlspecialchars($request['roblox_pseudo']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm mb-1">Légion</p>
                                            <p class="text-white font-semibold">
                                                <?php echo $request['legion_nom'] ? htmlspecialchars($request['legion_nom']) : 'Aucune'; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-gray-400 text-sm mb-1">État actuel</p>
                                            <p class="text-white font-semibold">
                                                Grade: <?php echo htmlspecialchars($request['current_grade']); ?><br>
                                                Rang: <?php echo htmlspecialchars($request['current_rang']); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Demande -->
                                <div class="bg-gray-800 p-4 rounded mb-4">
                                    <div class="flex items-center justify-center mb-4">
                                        <span class="text-2xl font-bold text-blue-400">
                                            <?php echo htmlspecialchars($request['current_value']); ?>
                                        </span>
                                        <i class="fas fa-arrow-right text-yellow-500 text-2xl mx-4"></i>
                                        <span class="text-2xl font-bold text-green-400">
                                            <?php echo htmlspecialchars($request['requested_value']); ?>
                                        </span>
                                    </div>
                                    <div>
                                        <p class="text-gray-400 text-sm mb-2 font-semibold">Justification :</p>
                                        <div class="text-gray-200 bg-gray-900 p-3 rounded">
                                            <?php echo nl2br(htmlspecialchars($request['reason'])); ?>
                                        </div>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <form method="POST" class="space-y-4">
                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                    
                                    <div>
                                        <label class="block text-white font-semibold mb-2">Réponse à l'utilisateur</label>
                                        <textarea name="admin_response" rows="3"
                                                  class="w-full p-3 rounded bg-gray-800 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                                                  placeholder="Ajoutez un commentaire ou des instructions (optionnel)"></textarea>
                                    </div>

                                    <div class="flex gap-4">
                                        <button type="submit" name="action" value="approve"
                                                onclick="return confirm('Confirmer l\'approbation de cette promotion ?')"
                                                class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                                            <i class="fas fa-check mr-2"></i>Approuver
                                        </button>
                                        <button type="submit" name="action" value="reject"
                                                onclick="return confirm('Confirmer le refus de cette demande ?')"
                                                class="flex-1 bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition">
                                            <i class="fas fa-times mr-2"></i>Refuser
                                        </button>
                                    </div>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Historique -->
            <?php if (!empty($history)): ?>
                <div class="bg-gray-800 rounded-lg p-6">
                    <h2 class="text-2xl font-bold text-white mb-6">
                        <i class="fas fa-history text-blue-500 mr-2"></i>
                        Historique récent
                    </h2>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead>
                                <tr class="border-b border-gray-700">
                                    <th class="text-left text-gray-400 font-semibold p-3">Membre</th>
                                    <th class="text-left text-gray-400 font-semibold p-3">Type</th>
                                    <th class="text-left text-gray-400 font-semibold p-3">Changement</th>
                                    <th class="text-left text-gray-400 font-semibold p-3">Statut</th>
                                    <th class="text-left text-gray-400 font-semibold p-3">Traité par</th>
                                    <th class="text-left text-gray-400 font-semibold p-3">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($history as $item): ?>
                                    <tr class="border-b border-gray-700 hover:bg-gray-700 transition">
                                        <td class="p-3">
                                            <p class="text-white font-semibold"><?php echo htmlspecialchars($item['discord_pseudo']); ?></p>
                                            <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($item['roblox_pseudo']); ?></p>
                                        </td>
                                        <td class="p-3 text-gray-300">
                                            <?php echo $item['type'] === 'grade' ? 'Grade' : 'Rang'; ?>
                                        </td>
                                        <td class="p-3">
                                            <span class="text-gray-400"><?php echo htmlspecialchars($item['current_value']); ?></span>
                                            <i class="fas fa-arrow-right mx-1 text-gray-500"></i>
                                            <span class="text-white font-semibold"><?php echo htmlspecialchars($item['requested_value']); ?></span>
                                        </td>
                                        <td class="p-3">
                                            <?php if ($item['status'] === 'approved'): ?>
                                                <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">
                                                    <i class="fas fa-check mr-1"></i>Approuvée
                                                </span>
                                            <?php else: ?>
                                                <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">
                                                    <i class="fas fa-times mr-1"></i>Refusée
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="p-3 text-gray-300">
                                            <?php echo htmlspecialchars($item['reviewer_name']); ?>
                                        </td>
                                        <td class="p-3 text-gray-400 text-sm">
                                            <?php echo date('d/m/Y H:i', strtotime($item['reviewed_at'])); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
