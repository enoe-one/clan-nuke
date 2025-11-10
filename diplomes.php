<?php
require_once 'config.php';

$success = '';
$error = '';

// Créer la table des demandes si elle n'existe pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS diplome_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        member_id INT NOT NULL,
        diplome_id INT NOT NULL,
        motivation TEXT NOT NULL,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        reviewed_by INT NULL,
        reviewed_at TIMESTAMP NULL,
        rejection_reason TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE,
        FOREIGN KEY (diplome_id) REFERENCES diplomes(id) ON DELETE CASCADE,
        FOREIGN KEY (reviewed_by) REFERENCES users(id),
        INDEX idx_status (status),
        INDEX idx_member (member_id),
        INDEX idx_created (created_at)
    )");
} catch (PDOException $e) {
    // Table déjà créée
}

// Traitement de la demande de diplôme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isMember()) {
    $diplome_id = $_POST['diplome_id'] ?? 0;
    $motivation = trim($_POST['motivation'] ?? '');
    
    try {
        if (empty($motivation)) {
            throw new Exception("La motivation est obligatoire");
        }
        
        // Vérifier que le diplôme existe
        $stmt = $pdo->prepare("SELECT * FROM diplomes WHERE id = ?");
        $stmt->execute([$diplome_id]);
        $diplome = $stmt->fetch();
        
        if (!$diplome) {
            throw new Exception("Diplôme introuvable");
        }
        
        // Vérifier si le membre a déjà ce diplôme
        $stmt = $pdo->prepare("SELECT id FROM member_diplomes WHERE member_id = ? AND diplome_id = ?");
        $stmt->execute([$_SESSION['member_id'], $diplome_id]);
        if ($stmt->fetch()) {
            throw new Exception("Vous possédez déjà ce diplôme");
        }
        
        // Vérifier si une demande est déjà en cours
        $stmt = $pdo->prepare("SELECT id FROM diplome_requests WHERE member_id = ? AND diplome_id = ? AND status = 'pending'");
        $stmt->execute([$_SESSION['member_id'], $diplome_id]);
        if ($stmt->fetch()) {
            throw new Exception("Vous avez déjà une demande en cours pour ce diplôme");
        }
        
        // Créer la demande
        $stmt = $pdo->prepare("INSERT INTO diplome_requests (member_id, diplome_id, motivation) VALUES (?, ?, ?)");
        $stmt->execute([$_SESSION['member_id'], $diplome_id, $motivation]);
        
        $success = "Votre demande de diplôme a été envoyée avec succès ! L'état-major examinera votre candidature.";
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

$stmt = $pdo->query("SELECT * FROM diplomes ORDER BY categorie, niveau, code");
$all_diplomes = $stmt->fetchAll();

$diplomes_by_category = [
    'aerien' => [],
    'terrestre' => [],
    'aeronaval' => [],
    'formateur' => [],
    'elite' => []
];

foreach ($all_diplomes as $diplome) {
    $diplomes_by_category[$diplome['categorie']][] = $diplome;
}

$category_names = [
    'aerien' => '✈️ Aérien',
    'terrestre' => '🎖️ Terrestre',
    'aeronaval' => '🚢 Aéronaval et Naval',
    'formateur' => '📚 Formateurs',
    'elite' => '⚔️ Forces d\'Élite'
];

$level_colors = [
    1 => 'blue',
    2 => 'green',
    3 => 'purple',
    4 => 'red',
    5 => 'yellow'
];

// Si membre connecté, récupérer ses diplômes et demandes
$member_diplomes_ids = [];
$member_requests = [];
if (isMember()) {
    $stmt = $pdo->prepare("SELECT diplome_id FROM member_diplomes WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    while ($row = $stmt->fetch()) {
        $member_diplomes_ids[] = $row['diplome_id'];
    }
    
    $stmt = $pdo->prepare("SELECT diplome_id, status FROM diplome_requests WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    while ($row = $stmt->fetch()) {
        $member_requests[$row['diplome_id']] = $row['status'];
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Diplômes - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-white mb-4 text-center">Diplômes et Formations</h1>
            <p class="text-center text-gray-400 mb-8 max-w-3xl mx-auto">
                Les diplômes permettent d'augmenter votre grade et vos rangs. Le niveau 1 est obligatoire pour accéder au niveau 2, et ainsi de suite. 
                La difficulté augmente progressivement avec chaque niveau.
            </p>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6 max-w-3xl mx-auto">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6 max-w-3xl mx-auto">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if (!isMember()): ?>
                <div class="bg-blue-900 bg-opacity-30 border-2 border-blue-500 p-6 rounded-lg text-center mb-8 max-w-3xl mx-auto">
                    <i class="fas fa-info-circle text-blue-400 text-4xl mb-3"></i>
                    <p class="text-blue-300 text-lg mb-4">Connectez-vous pour demander des diplômes</p>
                    <a href="member_login.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                    </a>
                </div>
            <?php endif; ?>

            <div class="bg-yellow-900 bg-opacity-30 p-4 rounded-lg border border-yellow-500 mb-8 max-w-3xl mx-auto">
                <p class="text-yellow-300 font-semibold text-center">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Les diplômes doivent être obtenus dans l'ordre des niveaux (1 → 2 → 3 → 4 → 5)
                </p>
            </div>

            <?php foreach ($diplomes_by_category as $category => $diplomes): ?>
                <?php if (!empty($diplomes)): ?>
                    <div class="mb-12">
                        <h2 class="text-3xl font-bold text-white mb-6"><?php echo $category_names[$category]; ?></h2>
                        
                        <div class="space-y-4">
                            <?php foreach ($diplomes as $diplome): ?>
                                <?php
                                $has_diplome = in_array($diplome['id'], $member_diplomes_ids);
                                $request_status = $member_requests[$diplome['id']] ?? null;
                                ?>
                                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-<?php echo $level_colors[$diplome['niveau']]; ?>-500 hover:bg-gray-750 transition">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2 flex-wrap">
                                                <span class="bg-<?php echo $level_colors[$diplome['niveau']]; ?>-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                    Niveau <?php echo $diplome['niveau']; ?>
                                                </span>
                                                <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded text-sm font-mono">
                                                    <?php echo htmlspecialchars($diplome['code']); ?>
                                                </span>
                                                
                                                <?php if ($has_diplome): ?>
                                                    <span class="bg-green-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        <i class="fas fa-check mr-1"></i>Obtenu
                                                    </span>
                                                <?php elseif ($request_status === 'pending'): ?>
                                                    <span class="bg-yellow-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        <i class="fas fa-clock mr-1"></i>En attente
                                                    </span>
                                                <?php elseif ($request_status === 'rejected'): ?>
                                                    <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        <i class="fas fa-times mr-1"></i>Refusé
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <h3 class="text-xl font-bold text-white mb-2">
                                                <?php echo htmlspecialchars($diplome['nom']); ?>
                                            </h3>
                                            <p class="text-gray-300 mb-2">
                                                <?php echo htmlspecialchars($diplome['description']); ?>
                                            </p>
                                            <?php if ($diplome['prerequis']): ?>
                                                <p class="text-yellow-400 text-sm">
                                                    <i class="fas fa-lock mr-2"></i>
                                                    Prérequis : <?php echo htmlspecialchars($diplome['prerequis']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                        <div class="ml-4 text-right">
                                            <i class="fas fa-graduation-cap text-<?php echo $level_colors[$diplome['niveau']]; ?>-500 text-3xl mb-3 block"></i>
                                            
                                            <?php if (isMember() && !$has_diplome && !$request_status): ?>
                                                <button onclick="requestDiplome(<?php echo $diplome['id']; ?>, '<?php echo htmlspecialchars(addslashes($diplome['nom'])); ?>')" 
                                                        class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition text-sm">
                                                    <i class="fas fa-paper-plane mr-1"></i>Demander
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Modal demande de diplôme -->
    <div id="modal-request-diplome" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full p-8">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-graduation-cap text-blue-500 mr-2"></i>
                Demander un diplôme
            </h2>
            <p class="text-gray-400 mb-6" id="diplome-name"></p>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="diplome_id" id="diplome-id">
                
                <div>
                    <label class="block text-white mb-2 font-semibold">
                        Motivation *
                        <span class="text-gray-400 font-normal text-sm ml-2">Expliquez pourquoi vous souhaitez ce diplôme</span>
                    </label>
                    <textarea name="motivation" required rows="6"
                              placeholder="Décrivez votre expérience, vos compétences et votre motivation pour obtenir ce diplôme..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"></textarea>
                    <p class="text-gray-500 text-sm mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Votre demande sera examinée par l'état-major
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition font-bold">
                        <i class="fas fa-paper-plane mr-2"></i>Envoyer la demande
                    </button>
                    <button type="button" onclick="closeRequestModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function requestDiplome(diplomeId, diplomeName) {
        document.getElementById('diplome-id').value = diplomeId;
        document.getElementById('diplome-name').textContent = diplomeName;
        document.getElementById('modal-request-diplome').classList.remove('hidden');
    }

    function closeRequestModal() {
        document.getElementById('modal-request-diplome').classList.add('hidden');
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('modal-request-diplome').addEventListener('click', function(e) {
        if (e.target === this) {
            closeRequestModal();
        }
    });
    </script>
</body>
</html>
