<?php
require_once '../config.php';

if (!isAdmin() || !hasAccess('access_edit_members')) {
    header('Location: dashboard.php');
    exit;
}

$member_id = $_GET['id'] ?? 0;
$success = '';
$error = '';

// Récupérer les infos du membre
$stmt = $pdo->prepare("SELECT m.*, l.nom as legion_nom FROM members m LEFT JOIN legions l ON m.legion_id = l.id WHERE m.id = ?");
$stmt->execute([$member_id]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: dashboard.php');
    exit;
}

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        if ($action === 'reset_password' && hasAccess('access_reset_passwords')) {
            $new_password = password_hash('Coalition', PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE members SET password = ?, must_change_password = 1 WHERE id = ?");
            $stmt->execute([$new_password, $member_id]);
            logAdminAction($pdo, $_SESSION['user_id'], 'Réinitialisation MDP membre', "Member ID: $member_id");
            $success = 'Mot de passe réinitialisé à "Coalition"';
        }
        
        if ($action === 'delete_account' && hasAccess('access_full')) {
            $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            logAdminAction($pdo, $_SESSION['user_id'], 'Suppression compte membre', "Member ID: $member_id, Discord: {$member['discord_pseudo']}");
            header('Location: dashboard.php?deleted=1');
            exit;
        }
        
        if ($action === 'update_info') {
            $stmt = $pdo->prepare("
                UPDATE members 
                SET discord_pseudo = ?, roblox_pseudo = ?, kdr = ?, grade = ?, rang = ?, legion_id = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['discord_pseudo'],
                $_POST['roblox_pseudo'],
                $_POST['kdr'],
                $_POST['grade'],
                $_POST['rang'],
                $_POST['legion_id'] ?: null,
                $member_id
            ]);
            logAdminAction($pdo, $_SESSION['user_id'], 'Modification membre', "Member ID: $member_id");
            $success = 'Informations mises à jour avec succès';
            
            // Recharger les infos
            $stmt = $pdo->prepare("SELECT m.*, l.nom as legion_nom FROM members m LEFT JOIN legions l ON m.legion_id = l.id WHERE m.id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
        }
    }
}

// Récupérer les légions
$stmt = $pdo->query("SELECT * FROM legions ORDER BY nom");
$legions = $stmt->fetchAll();

// Récupérer les diplômes du membre
$stmt = $pdo->prepare("
    SELECT d.* 
    FROM diplomes d
    JOIN member_diplomes md ON d.id = md.diplome_id
    WHERE md.member_id = ?
    ORDER BY d.categorie, d.niveau
");
$stmt->execute([$member_id]);
$member_diplomes = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gérer Membre - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-5xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Gérer le Membre</h1>
                <a href="dashboard.php" class="text-blue-400 hover:text-blue-300">
                    <i class="fas fa-arrow-left mr-2"></i> Retour
                </a>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Formulaire de modification -->
            <div class="bg-gray-800 p-6 rounded-lg mb-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-edit mr-2"></i> Informations du Membre
                </h2>
                
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="update_info">
                    
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white mb-2 font-semibold">Pseudo Discord</label>
                            <input type="text" name="discord_pseudo" required value="<?php echo htmlspecialchars($member['discord_pseudo']); ?>"
                                   class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-white mb-2 font-semibold">Pseudo Roblox</label>
                            <input type="text" name="roblox_pseudo" required value="<?php echo htmlspecialchars($member['roblox_pseudo']); ?>"
                                   class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>

                    <div class="grid md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-white mb-2 font-semibold">KDR</label>
                            <input type="number" name="kdr" step="0.01" value="<?php echo htmlspecialchars($member['kdr']); ?>"
                                   class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-white mb-2 font-semibold">Grade</label>
                            <select name="grade" required
                                    class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <?php foreach (getGrades() as $grade): ?>
                                    <option value="<?php echo htmlspecialchars($grade); ?>" <?php echo $member['grade'] === $grade ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($grade); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-white mb-2 font-semibold">Rang</label>
                            <select name="rang" required
                                    class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                                <?php foreach (getRangs() as $rang): ?>
                                    <option value="<?php echo htmlspecialchars($rang); ?>" <?php echo $member['rang'] === $rang ? 'selected' : ''; ?>>
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
                                <option value="<?php echo $legion['id']; ?>" <?php echo $member['legion_id'] == $legion['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($legion['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <button type="submit" class="bg-blue-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i> Enregistrer les Modifications
                    </button>
                </form>
            </div>

            <!-- Diplômes -->
            <div class="bg-gray-800 p-6 rounded-lg mb-6">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-graduation-cap mr-2"></i> Diplômes Obtenus
                    <span class="text-lg text-gray-400">(<?php echo count($member_diplomes); ?>)</span>
                </h2>
                <?php if (!empty($member_diplomes)): ?>
                    <div class="grid md:grid-cols-2 gap-3">
                        <?php foreach ($member_diplomes as $diplome): ?>
                            <div class="bg-gray-700 p-3 rounded">
                                <div class="flex items-center justify-between">
                                    <div>
                                        <p class="text-white font-semibold"><?php echo htmlspecialchars($diplome['code']); ?></p>
                                        <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($diplome['nom']); ?></p>
                                    </div>
                                    <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">
                                        Niv. <?php echo $diplome['niveau']; ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-gray-500 text-center py-4">Aucun diplôme obtenu</p>
                <?php endif; ?>
            </div>

            <!-- Actions dangereuses -->
            <div class="bg-red-900 bg-opacity-30 border-2 border-red-500 p-6 rounded-lg">
                <h2 class="text-2xl font-bold text-red-400 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i> Actions Dangereuses
                </h2>
                
                <div class="space-y-4">
                    <?php if (hasAccess('access_reset_passwords')): ?>
                        <div class="flex justify-between items-center">
                            <div>
                                <p class="text-white font-semibold">Réinitialiser le mot de passe</p>
                                <p class="text-gray-400 text-sm">Le mot de passe sera réinitialisé à "Coalition"</p>
                            </div>
                            <form method="POST" onsubmit="return confirm('Voulez-vous vraiment réinitialiser le mot de passe de ce membre ?');">
                                <input type="hidden" name="action" value="reset_password">
                                <button type="submit" class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                                    <i class="fas fa-key mr-2"></i> Réinitialiser
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>

                    <?php if (hasAccess('access_full')): ?>
                        <div class="flex justify-between items-center pt-4 border-t border-red-700">
                            <div>
                                <p class="text-white font-semibold">Supprimer le compte</p>
                                <p class="text-gray-400 text-sm">Action irréversible ! Toutes les données seront perdues.</p>
                            </div>
                            <form method="POST" onsubmit="return confirm('⚠️ ATTENTION ⚠️\n\nÊtes-vous absolument sûr de vouloir supprimer ce compte ?\n\nCette action est IRRÉVERSIBLE et supprimera toutes les données du membre.');">
                                <input type="hidden" name="action" value="delete_account">
                                <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                    <i class="fas fa-trash mr-2"></i> Supprimer le Compte
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>

</html>
