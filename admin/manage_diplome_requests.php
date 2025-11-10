<?php
require_once '../config.php';

// Vérifier que l'utilisateur est état-major ou supérieur
if (!isAdmin() || !in_array($_SESSION['role'], ['etat_major', 'chef', 'super_admin'])) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $request_id = $_POST['request_id'] ?? 0;
    
    try {
        if ($action === 'approve') {
            // Récupérer les infos de la demande
            $stmt = $pdo->prepare("SELECT * FROM diplome_requests WHERE id = ?");
            $stmt->execute([$request_id]);
            $request = $stmt->fetch();
            
            if (!$request) {
                throw new Exception("Demande introuvable");
            }
            
            // Attribuer le diplôme au membre
            $stmt = $pdo->prepare("INSERT INTO member_diplomes (member_id, diplome_id) VALUES (?, ?)");
            $stmt->execute([$request['member_id'], $request['diplome_id']]);
            
            // Mettre à jour la demande
            $stmt = $pdo->prepare("UPDATE diplome_requests SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $request_id]);
            
            logAdminAction($pdo, $_SESSION['user_id'], 'Validation diplôme', "Request ID: $request_id");
            $success = "Diplôme validé et attribué avec succès !";
            
        } elseif ($action === 'reject') {
            $rejection_reason = $_POST['rejection_reason'] ?? '';
            
            $stmt = $pdo->prepare("UPDATE diplome_requests SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? WHERE id = ?");
            $stmt->execute([$_SESSION['user_id'], $rejection_reason, $request_id]);
            
            logAdminAction($pdo, $_SESSION['user_id'], 'Refus diplôme', "Request ID: $request_id");
            $success = "Demande refusée.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer toutes les demandes
$stmt = $pdo->query("
    SELECT dr.*, 
           m.discord_pseudo, m.roblox_pseudo, m.grade, m.rang,
           d.code, d.nom as diplome_nom, d.categorie, d.niveau,
           u.username as reviewer_name
    FROM diplome_requests dr
    JOIN members m ON dr.member_id = m.id
    JOIN diplomes d ON dr.diplome_id = d.id
    LEFT JOIN users u ON dr.reviewed_by = u.id
    ORDER BY 
        CASE dr.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'rejected' THEN 3 
        END,
        dr.created_at DESC
");
$requests = $stmt->fetchAll();

// Organiser par statut
$requests_by_status = [
    'pending' => [],
    'approved' => [],
    'rejected' => []
];

foreach ($requests as $request) {
    $requests_by_status[$request['status']][] = $request;
}

$status_colors = [
    'pending' => 'yellow',
    'approved' => 'green',
    'rejected' => 'red'
];

$status_names = [
    'pending' => 'En attente',
    'approved' => 'Validées',
    'rejected' => 'Refusées'
];

$level_colors = [
    1 => 'blue',
    2 => 'green',
    3 => 'purple',
    4 => 'red',
    5 => 'yellow'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demandes de Diplômes - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-graduation-cap text-cyan-500 mr-3"></i>
                        Demandes de Diplômes
                    </h1>
                    <p class="text-gray-400">Examen et validation des candidatures</p>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Dashboard
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
                <div class="bg-gradient-to-br from-yellow-900 to-yellow-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-300 text-sm">En attente</p>
                            <p class="text-white text-3xl font-bold"><?php echo count($requests_by_status['pending']); ?></p>
                        </div>
                        <i class="fas fa-clock text-yellow-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Validées</p>
                            <p class="text-white text-3xl font-bold"><?php echo count($requests_by_status['approved']); ?></p>
                        </div>
                        <i class="fas fa-check text-green-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-red-900 to-red-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-red-300 text-sm">Refusées</p>
                            <p class="text-white text-3xl font-bold"><?php echo count($requests_by_status['rejected']); ?></p>
                        </div>
                        <i class="fas fa-times text-red-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex space-x-4 border-b border-gray-700 mb-6">
                    <button onclick="showTab('pending')" id="tab-pending" class="tab-button px-6 py-3 font-semibold text-white border-b-2 border-yellow-500">
                        <i class="fas fa-clock mr-2"></i>En attente (<?php echo count($requests_by_status['pending']); ?>)
                    </button>
                    <button onclick="showTab('approved')" id="tab-approved" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-check mr-2"></i>Validées
                    </button>
                    <button onclick="showTab('rejected')" id="tab-rejected" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-times mr-2"></i>Refusées
                    </button>
                </div>

                <?php foreach (['pending', 'approved', 'rejected'] as $status): ?>
                    <div id="content-<?php echo $status; ?>" class="tab-content <?php echo $status !== 'pending' ? 'hidden' : ''; ?>">
                        <?php if (empty($requests_by_status[$status])): ?>
                            <div class="text-center py-12 text-gray-500">
                                <i class="fas fa-inbox text-6xl mb-4"></i>
                                <p class="text-xl">Aucune demande <?php echo $status_names[$status]; ?></p>
                            </div>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($requests_by_status[$status] as $request): ?>
                                    <div class="bg-gray-700 p-6 rounded-lg border-l-4 border-<?php echo $level_colors[$request['niveau']]; ?>-500">
                                        <div class="grid md:grid-cols-3 gap-6">
                                            <!-- Infos membre -->
                                            <div>
                                                <h4 class="text-white font-bold text-lg mb-3">
                                                    <i class="fas fa-user mr-2 text-blue-400"></i>Membre
                                                </h4>
                                                <div class="space-y-2 text-sm">
                                                    <p class="text-gray-300">
                                                        <span class="text-gray-500">Discord:</span>
                                                        <span class="text-white font-semibold ml-2"><?php echo htmlspecialchars($request['discord_pseudo']); ?></span>
                                                    </p>
                                                    <p class="text-gray-300">
                                                        <span class="text-gray-500">Roblox:</span>
                                                        <span class="text-white font-semibold ml-2"><?php echo htmlspecialchars($request['roblox_pseudo']); ?></span>
                                                    </p>
                                                    <p class="text-gray-300">
                                                        <span class="text-gray-500">Grade:</span>
                                                        <span class="text-blue-400 ml-2"><?php echo htmlspecialchars($request['grade']); ?></span>
                                                    </p>
                                                    <p class="text-gray-300">
                                                        <span class="text-gray-500">Rang:</span>
                                                        <span class="text-green-400 ml-2"><?php echo htmlspecialchars($request['rang']); ?></span>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Infos diplôme -->
                                            <div>
                                                <h4 class="text-white font-bold text-lg mb-3">
                                                    <i class="fas fa-graduation-cap mr-2 text-cyan-400"></i>Diplôme demandé
                                                </h4>
                                                <div class="space-y-2">
                                                    <div class="flex items-center gap-2">
                                                        <span class="bg-<?php echo $level_colors[$request['niveau']]; ?>-600 text-white px-2 py-1 rounded text-xs font-bold">
                                                            Niveau <?php echo $request['niveau']; ?>
                                                        </span>
                                                        <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs font-mono">
                                                            <?php echo htmlspecialchars($request['code']); ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-white font-semibold">
                                                        <?php echo htmlspecialchars($request['diplome_nom']); ?>
                                                    </p>
                                                    <p class="text-gray-400 text-sm capitalize">
                                                        <i class="fas fa-tag mr-1"></i>
                                                        <?php echo htmlspecialchars($request['categorie']); ?>
                                                    </p>
                                                    <p class="text-gray-500 text-xs">
                                                        <i class="fas fa-calendar mr-1"></i>
                                                        Demandé le <?php echo date('d/m/Y à H:i', strtotime($request['created_at'])); ?>
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Motivation et actions -->
                                            <div>
                                                <h4 class="text-white font-bold text-lg mb-3">
                                                    <i class="fas fa-comment-dots mr-2 text-yellow-400"></i>Motivation
                                                </h4>
                                                <div class="bg-gray-800 p-3 rounded mb-4 max-h-32 overflow-y-auto">
                                                    <p class="text-gray-300 text-sm whitespace-pre-wrap"><?php echo htmlspecialchars($request['motivation']); ?></p>
                                                </div>

                                                <?php if ($status === 'pending'): ?>
                                                    <div class="flex space-x-2">
                                                        <button onclick="approveRequest(<?php echo $request['id']; ?>)" 
                                                                class="flex-1 bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition font-semibold">
                                                            <i class="fas fa-check mr-1"></i>Valider
                                                        </button>
                                                        <button onclick="rejectRequest(<?php echo $request['id']; ?>)" 
                                                                class="flex-1 bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition font-semibold">
                                                            <i class="fas fa-times mr-1"></i>Refuser
                                                        </button>
                                                    </div>
                                                <?php elseif ($status === 'approved'): ?>
                                                    <div class="bg-green-900 bg-opacity-30 border border-green-500 rounded p-3">
                                                        <p class="text-green-300 text-sm">
                                                            <i class="fas fa-check-circle mr-2"></i>
                                                            Validé par <strong><?php echo htmlspecialchars($request['reviewer_name']); ?></strong>
                                                        </p>
                                                        <p class="text-green-400 text-xs mt-1">
                                                            Le <?php echo date('d/m/Y à H:i', strtotime($request['reviewed_at'])); ?>
                                                        </p>
                                                    </div>
                                                <?php elseif ($status === 'rejected'): ?>
                                                    <div class="bg-red-900 bg-opacity-30 border border-red-500 rounded p-3">
                                                        <p class="text-red-300 text-sm mb-2">
                                                            <i class="fas fa-times-circle mr-2"></i>
                                                            Refusé par <strong><?php echo htmlspecialchars($request['reviewer_name']); ?></strong>
                                                        </p>
                                                        <?php if ($request['rejection_reason']): ?>
                                                            <p class="text-red-400 text-xs bg-red-900 bg-opacity-30 p-2 rounded">
                                                                <strong>Raison:</strong> <?php echo htmlspecialchars($request['rejection_reason']); ?>
                                                            </p>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Modal refus -->
    <div id="modal-reject" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full p-8">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-times-circle text-red-500 mr-2"></i>
                Refuser la demande
            </h2>
            
            <form method="POST">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="request_id" id="reject-request-id">
                
                <div class="mb-6">
                    <label class="block text-white mb-2 font-semibold">
                        Raison du refus (optionnel)
                    </label>
                    <textarea name="rejection_reason" rows="4"
                              placeholder="Expliquez pourquoi cette demande est refusée..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-red-500 focus:outline-none"></textarea>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-lg hover:bg-red-700 transition font-bold">
                        <i class="fas fa-times mr-2"></i>Confirmer le refus
                    </button>
                    <button type="button" onclick="closeRejectModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('border-yellow-500', 'border-green-500', 'border-red-500', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('text-white');
        
        if (tab === 'pending') tabBtn.classList.add('border-yellow-500');
        if (tab === 'approved') tabBtn.classList.add('border-green-500');
        if (tab === 'rejected') tabBtn.classList.add('border-red-500');
    }

    function approveRequest(requestId) {
        if (confirm('Valider cette demande et attribuer le diplôme au membre ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="request_id" value="${requestId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function rejectRequest(requestId) {
        document.getElementById('reject-request-id').value = requestId;
        document.getElementById('modal-reject').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('modal-reject').classList.add('hidden');
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('modal-reject').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRejectModal();
        }
    });
    </script>
</body>
</html>
