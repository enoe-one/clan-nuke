<?php
require_once '../config.php';

if (!isMember()) {
    header('Location: ../member_login.php');
    exit;
}

// Créer la table si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS promotion_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        type ENUM('grade', 'rang') NOT NULL,
        current_value VARCHAR(100) NOT NULL,
        requested_value VARCHAR(100) NOT NULL,
        reason TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        admin_response TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        reviewed_at TIMESTAMP NULL,
        reviewed_by INT,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    // Table déjà créée
}

$success = '';
$error = '';

// Récupérer les infos du membre
$stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
$stmt->execute([$_SESSION['member_id']]);
$member = $stmt->fetch();

// Vérifier s'il y a une demande en attente
$stmt = $pdo->prepare("SELECT * FROM promotion_requests WHERE member_id = ? AND status = 'pending'");
$stmt->execute([$_SESSION['member_id']]);
$pending_request = $stmt->fetch();

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$pending_request) {
    $type = $_POST['type'] ?? '';
    $requested_value = $_POST['requested_value'] ?? '';
    $reason = trim($_POST['reason'] ?? '');
    
    if (empty($type) || empty($requested_value) || empty($reason)) {
        $error = 'Tous les champs sont obligatoires';
    } elseif (strlen($reason) < 50) {
        $error = 'La justification doit contenir au moins 50 caractères';
    } else {
        try {
            $current_value = $type === 'grade' ? $member['grade'] : $member['rang'];
            
            $stmt = $pdo->prepare("
                INSERT INTO promotion_requests (member_id, type, current_value, requested_value, reason) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['member_id'],
                $type,
                $current_value,
                $requested_value,
                $reason
            ]);
            
            $success = 'Votre demande de promotion a été envoyée ! Un administrateur l\'examinera bientôt.';
            
            // Recharger la page pour afficher la demande en attente
            header('Location: request_promotion.php?success=1');
            exit;
        } catch (PDOException $e) {
            $error = 'Erreur lors de l\'envoi de la demande';
        }
    }
}

// Récupérer l'historique des demandes
$stmt = $pdo->prepare("
    SELECT pr.*, u.username as reviewer_name 
    FROM promotion_requests pr
    LEFT JOIN users u ON pr.reviewed_by = u.id
    WHERE pr.member_id = ?
    ORDER BY pr.created_at DESC
");
$stmt->execute([$_SESSION['member_id']]);
$history = $stmt->fetchAll();

$grades = getGrades();
$rangs = getRangs();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Demande de Promotion - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex items-center justify-between mb-8">
                <h1 class="text-4xl font-bold text-white">
                    <i class="fas fa-arrow-up text-green-500 mr-3"></i>
                    Demande de Promotion
                </h1>
                <a href="dashboard.php" class="text-gray-400 hover:text-white transition">
                    <i class="fas fa-arrow-left mr-2"></i>Retour au profil
                </a>
            </div>

            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Votre demande de promotion a été envoyée avec succès !
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- État actuel -->
            <div class="grid md:grid-cols-2 gap-6 mb-8">
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-blue-500">
                    <h3 class="text-xl font-bold text-white mb-2">
                        <i class="fas fa-medal mr-2"></i>Grade actuel
                    </h3>
                    <p class="text-3xl font-bold text-blue-400"><?php echo htmlspecialchars($member['grade']); ?></p>
                </div>
                
                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-green-500">
                    <h3 class="text-xl font-bold text-white mb-2">
                        <i class="fas fa-star mr-2"></i>Rang actuel
                    </h3>
                    <p class="text-3xl font-bold text-green-400"><?php echo htmlspecialchars($member['rang']); ?></p>
                </div>
            </div>

            <!-- Demande en attente -->
            <?php if ($pending_request): ?>
                <div class="bg-yellow-900 bg-opacity-30 border-2 border-yellow-500 p-6 rounded-lg mb-8">
                    <h3 class="text-2xl font-bold text-yellow-300 mb-4">
                        <i class="fas fa-clock mr-2"></i>Demande en attente
                    </h3>
                    <div class="bg-gray-800 p-4 rounded">
                        <div class="grid md:grid-cols-2 gap-4 mb-4">
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Type</p>
                                <p class="text-white font-semibold">
                                    <?php echo $pending_request['type'] === 'grade' ? 'Promotion de grade' : 'Montée de rang'; ?>
                                </p>
                            </div>
                            <div>
                                <p class="text-gray-400 text-sm mb-1">Demande</p>
                                <p class="text-white font-semibold">
                                    <?php echo htmlspecialchars($pending_request['current_value']); ?>
                                    <i class="fas fa-arrow-right mx-2 text-yellow-500"></i>
                                    <?php echo htmlspecialchars($pending_request['requested_value']); ?>
                                </p>
                            </div>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm mb-1">Justification</p>
                            <p class="text-gray-300"><?php echo nl2br(htmlspecialchars($pending_request['reason'])); ?></p>
                        </div>
                        <p class="text-gray-500 text-xs mt-4">
                            <i class="fas fa-calendar mr-1"></i>
                            Envoyée le <?php echo date('d/m/Y à H:i', strtotime($pending_request['created_at'])); ?>
                        </p>
                    </div>
                </div>
            <?php else: ?>
                <!-- Formulaire de demande -->
                <div class="bg-gray-800 p-6 rounded-lg mb-8">
                    <h3 class="text-2xl font-bold text-white mb-6">
                        <i class="fas fa-paper-plane mr-2"></i>Nouvelle demande
                    </h3>
                    
                    <form method="POST" class="space-y-6">
                        <div>
                            <label class="block text-white font-semibold mb-2">Type de promotion</label>
                            <select name="type" id="promotion-type" required
                                    class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <option value="">-- Choisissez --</option>
                                <option value="grade">Promotion de grade</option>
                                <option value="rang">Montée de rang</option>
                            </select>
                        </div>

                        <div id="grade-select" style="display: none;">
                            <label class="block text-white font-semibold mb-2">Grade souhaité</label>
                            <select name="requested_value" id="grade-value"
                                    class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <option value="">-- Choisissez --</option>
                                <?php foreach ($grades as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>"
                                            <?php echo $grade === $member['grade'] ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($grade); ?>
                                        <?php echo $grade === $member['grade'] ? '(actuel)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-gray-400 text-sm mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                <a href="../grades.php" target="_blank" class="text-blue-400 hover:underline">
                                    Consulter la liste des grades et leurs prérequis
                                </a>
                            </p>
                        </div>

                        <div id="rang-select" style="display: none;">
                            <label class="block text-white font-semibold mb-2">Rang souhaité</label>
                            <select name="requested_value" id="rang-value"
                                    class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <option value="">-- Choisissez --</option>
                                <?php foreach ($rangs as $rang): ?>
                                    <option value="<?php echo htmlspecialchars($rang); ?>"
                                            <?php echo $rang === $member['rang'] ? 'disabled' : ''; ?>>
                                        <?php echo htmlspecialchars($rang); ?>
                                        <?php echo $rang === $member['rang'] ? '(actuel)' : ''; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <p class="text-gray-400 text-sm mt-2">
                                <i class="fas fa-info-circle mr-1"></i>
                                <a href="../grades.php" target="_blank" class="text-blue-400 hover:underline">
                                    Consulter la liste des rangs et leurs prérequis
                                </a>
                            </p>
                        </div>

                        <div>
                            <label class="block text-white font-semibold mb-2">Justification de la demande</label>
                            <textarea name="reason" required minlength="50" rows="8"
                                      class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                                      placeholder="Expliquez pourquoi vous méritez cette promotion. Détaillez vos compétences, vos accomplissements, et comment vous remplissez les prérequis (minimum 50 caractères)."></textarea>
                            <p class="text-gray-500 text-sm mt-2">
                                <i class="fas fa-lightbulb mr-1"></i>
                                Soyez précis : mentionnez vos RIB, véhicules, participations aux raids, compétences tactiques, etc.
                            </p>
                        </div>

                        <div class="bg-blue-900 bg-opacity-30 p-4 rounded border border-blue-500">
                            <h4 class="text-blue-300 font-semibold mb-2">
                                <i class="fas fa-exclamation-circle mr-2"></i>Important
                            </h4>
                            <ul class="text-blue-200 text-sm space-y-1 list-disc list-inside">
                                <li>Une seule demande à la fois peut être en attente</li>
                                <li>Les demandes abusives peuvent être sanctionnées</li>
                                <li>Le délai de traitement est généralement de 48-72h</li>
                                <li>Assurez-vous de remplir tous les prérequis avant de demander</li>
                            </ul>
                        </div>

                        <button type="submit"
                                class="w-full bg-gradient-to-r from-green-600 to-green-800 text-white py-4 rounded-lg font-bold text-lg hover:from-green-700 hover:to-green-900 transition">
                            <i class="fas fa-paper-plane mr-2"></i>Envoyer la demande
                        </button>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Historique des demandes -->
            <?php if (!empty($history)): ?>
                <div class="bg-gray-800 p-6 rounded-lg">
                    <h3 class="text-2xl font-bold text-white mb-6">
                        <i class="fas fa-history mr-2"></i>Historique des demandes
                    </h3>
                    
                    <div class="space-y-4">
                        <?php foreach ($history as $request): ?>
                            <?php
                            $status_colors = [
                                'pending' => 'yellow',
                                'approved' => 'green',
                                'rejected' => 'red'
                            ];
                            $status_icons = [
                                'pending' => 'clock',
                                'approved' => 'check-circle',
                                'rejected' => 'times-circle'
                            ];
                            $status_labels = [
                                'pending' => 'En attente',
                                'approved' => 'Approuvée',
                                'rejected' => 'Refusée'
                            ];
                            $color = $status_colors[$request['status']];
                            ?>
                            <div class="bg-gray-700 p-4 rounded-lg border-l-4 border-<?php echo $color; ?>-500">
                                <div class="flex justify-between items-start mb-3">
                                    <div>
                                        <p class="text-white font-semibold">
                                            <?php echo $request['type'] === 'grade' ? 'Promotion de grade' : 'Montée de rang'; ?>
                                        </p>
                                        <p class="text-gray-300 text-sm">
                                            <?php echo htmlspecialchars($request['current_value']); ?>
                                            <i class="fas fa-arrow-right mx-2"></i>
                                            <?php echo htmlspecialchars($request['requested_value']); ?>
                                        </p>
                                    </div>
                                    <span class="bg-<?php echo $color; ?>-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                        <i class="fas fa-<?php echo $status_icons[$request['status']]; ?> mr-1"></i>
                                        <?php echo $status_labels[$request['status']]; ?>
                                    </span>
                                </div>
                                
                                <?php if ($request['status'] !== 'pending'): ?>
                                    <div class="bg-gray-800 p-3 rounded mb-2">
                                        <p class="text-gray-400 text-sm mb-1">Réponse de l'administration :</p>
                                        <p class="text-gray-200 text-sm">
                                            <?php echo $request['admin_response'] ? nl2br(htmlspecialchars($request['admin_response'])) : 'Aucune réponse'; ?>
                                        </p>
                                        <?php if ($request['reviewer_name']): ?>
                                            <p class="text-gray-500 text-xs mt-2">
                                                Par <?php echo htmlspecialchars($request['reviewer_name']); ?>
                                                le <?php echo date('d/m/Y à H:i', strtotime($request['reviewed_at'])); ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <p class="text-gray-500 text-xs">
                                    <i class="fas fa-calendar mr-1"></i>
                                    Demandé le <?php echo date('d/m/Y à H:i', strtotime($request['created_at'])); ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
        document.getElementById('promotion-type').addEventListener('change', function() {
            const gradeSelect = document.getElementById('grade-select');
            const rangSelect = document.getElementById('rang-select');
            const gradeValue = document.getElementById('grade-value');
            const rangValue = document.getElementById('rang-value');
            
            if (this.value === 'grade') {
                gradeSelect.style.display = 'block';
                rangSelect.style.display = 'none';
                gradeValue.required = true;
                rangValue.required = false;
                rangValue.value = '';
            } else if (this.value === 'rang') {
                rangSelect.style.display = 'block';
                gradeSelect.style.display = 'none';
                rangValue.required = true;
                gradeValue.required = false;
                gradeValue.value = '';
            } else {
                gradeSelect.style.display = 'none';
                rangSelect.style.display = 'none';
                gradeValue.required = false;
                rangValue.required = false;
            }
        });
    </script>
</body>
</html>
