<?php
require_once '../config.php';

if (!isAdmin() || !hasAccess('access_create_accounts')) {
    header('Location: dashboard.php');
    exit;
}

$success = false;
$error = '';

// Récupérer les légions
$stmt = $pdo->query("SELECT * FROM legions ORDER BY nom");
$legions = $stmt->fetchAll();

// Récupérer les diplômes
$stmt = $pdo->query("SELECT * FROM diplomes ORDER BY categorie, niveau");
$diplomes = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Créer le membre
        $default_password = password_hash('Coalition', PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO members 
            (discord_pseudo, roblox_pseudo, password, kdr, grade, rang, legion_id, must_change_password)
            VALUES (?, ?, ?, ?, ?, ?, ?, 1)
        ");
        
        $stmt->execute([
            $_POST['discord_pseudo'],
            $_POST['roblox_pseudo'],
            $default_password,
            $_POST['kdr'] ?? 0,
            $_POST['grade'],
            $_POST['rang'],
            $_POST['legion_id'] ?? null
        ]);
        
        $member_id = $pdo->lastInsertId();
        
        // Ajouter les diplômes sélectionnés
        if (isset($_POST['diplomes']) && is_array($_POST['diplomes'])) {
            $stmt = $pdo->prepare("INSERT INTO member_diplomes (member_id, diplome_id) VALUES (?, ?)");
            foreach ($_POST['diplomes'] as $diplome_id) {
                $stmt->execute([$member_id, $diplome_id]);
            }
        }
        
        $pdo->commit();
        logAdminAction($pdo, $_SESSION['user_id'], 'Création de compte membre', "Discord: {$_POST['discord_pseudo']}");
        $success = true;
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de la création du compte: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Créer un Membre - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Créer un Compte Membre</h1>
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Compte créé avec succès ! Mot de passe par défaut : <strong>Coalition</strong>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-gray-800 p-8 rounded-lg space-y-6">
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Pseudo Discord *</label>
                        <input type="text" name="discord_pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Pseudo Roblox *</label>
                        <input type="text" name="roblox_pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">KDR</label>
                        <input type="number" name="kdr" step="0.01" min="0" value="0"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Grade *</label>
                        <select name="grade" required
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <?php foreach (getGrades() as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade); ?>">
                                    <?php echo htmlspecialchars($grade); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Rang *</label>
                        <select name="rang" required
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <?php foreach (getRangs() as $rang): ?>
                                <option value="<?php echo htmlspecialchars($rang); ?>">
                                    <?php echo htmlspecialchars($rang); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Légion</label>
                    <select name="legion_id"
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="">-- Aucune légion --</option>
                        <?php foreach ($legions as $legion): ?>
                            <option value="<?php echo $legion['id']; ?>">
                                <?php echo htmlspecialchars($legion['nom']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Diplômes obtenus</label>
                    <div class="bg-gray-700 p-4 rounded max-h-96 overflow-y-auto">
                        <p class="text-gray-400 text-sm mb-3">Sélectionnez les diplômes déjà obtenus par le membre</p>
                        <?php 
                        $current_category = '';
                        foreach ($diplomes as $diplome): 
                            if ($current_category != $diplome['categorie']):
                                if ($current_category != '') echo '</div>';
                                $current_category = $diplome['categorie'];
                                $category_names = [
                                    'aerien' => '✈️ Aérien',
                                    'terrestre' => '🧥 Terrestre',
                                    'aeronaval' => '🚁 Aéronaval',
                                    'formateur' => '📚 Formateurs',
                                    'elite' => '⚔️ Élite'
                                ];
                                echo '<div class="mb-4"><h4 class="text-white font-bold mb-2">' . $category_names[$current_category] . '</h4>';
                            endif;
                        ?>
                            <label class="flex items-center py-2 hover:bg-gray-600 px-2 rounded cursor-pointer">
                                <input type="checkbox" name="diplomes[]" value="<?php echo $diplome['id']; ?>"
                                       class="mr-3 w-4 h-4">
                                <span class="text-white flex-1">
                                    <span class="font-semibold"><?php echo htmlspecialchars($diplome['code']); ?></span>
                                    - <?php echo htmlspecialchars($diplome['nom']); ?>
                                    <span class="text-gray-400 text-sm">(Niveau <?php echo $diplome['niveau']; ?>)</span>
                                </span>
                            </label>
                        <?php 
                        endforeach; 
                        if ($current_category != '') echo '</div>';
                        ?>
                    </div>
                </div>

                <div class="bg-blue-900 bg-opacity-30 p-4 rounded border border-blue-500">
                    <p class="text-blue-300">
                        <i class="fas fa-info-circle mr-2"></i>
                        Le mot de passe par défaut sera <strong>"Coalition"</strong>. 
                        Le membre devra le changer lors de sa première connexion.
                    </p>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-green-600 to-green-800 text-white py-4 rounded-lg font-bold text-lg hover:from-green-700 hover:to-green-900 transition">
                    <i class="fas fa-user-plus mr-2"></i> Créer le Compte
                </button>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>