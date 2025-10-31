<?php
require_once '../config.php';

if (!isAdmin() || !hasAccess('access_manage_legions')) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Créer une nouvelle légion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create') {
    try {
        $stmt = $pdo->prepare("INSERT INTO legions (nom, description) VALUES (?, ?)");
        $stmt->execute([$_POST['nom'], $_POST['description']]);
        logAdminAction($pdo, $_SESSION['user_id'], 'Création de légion', $_POST['nom']);
        $success = "Légion créée avec succès !";
    } catch(PDOException $e) {
        $error = "Erreur lors de la création de la légion.";
    }
}

// Supprimer une légion
if (isset($_GET['delete'])) {
    try {
        $stmt = $pdo->prepare("DELETE FROM legions WHERE id = ?");
        $stmt->execute([$_GET['delete']]);
        logAdminAction($pdo, $_SESSION['user_id'], 'Suppression de légion', "ID: " . $_GET['delete']);
        $success = "Légion supprimée avec succès !";
    } catch(PDOException $e) {
        $error = "Erreur lors de la suppression (vérifiez qu'aucun membre n'est assigné).";
    }
}

// Récupérer toutes les légions
$stmt = $pdo->query("
    SELECT l.*, COUNT(m.id) as member_count 
    FROM legions l 
    LEFT JOIN members m ON l.id = m.legion_id 
    GROUP BY l.id
    ORDER BY l.nom
");
$legions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Légions - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-6xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Gestion des Légions</h1>
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

            <!-- Formulaire de création -->
            <div class="bg-gray-800 p-6 rounded-lg mb-8">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-plus-circle mr-2"></i> Créer une Nouvelle Légion
                </h2>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="action" value="create">
                    <div class="grid md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-white mb-2 font-semibold">Nom de la légion *</label>
                            <input type="text" name="nom" required maxlength="100"
                                   class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                        <div>
                            <label class="block text-white mb-2 font-semibold">Description</label>
                            <input type="text" name="description" maxlength="255"
                                   class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        </div>
                    </div>
                    <button type="submit"
                            class="bg-green-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-green-700 transition">
                        <i class="fas fa-plus mr-2"></i> Créer la Légion
                    </button>
                </form>
            </div>

            <!-- Liste des légions -->
            <div class="bg-gray-800 p-6 rounded-lg">
                <h2 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-list mr-2"></i> Légions Existantes
                </h2>
                <div class="space-y-4">
                    <?php foreach ($legions as $legion): ?>
                        <div class="bg-gray-700 p-4 rounded-lg flex justify-between items-center">
                            <div>
                                <h3 class="text-xl font-bold text-white">
                                    <?php echo htmlspecialchars($legion['nom']); ?>
                                </h3>
                                <?php if ($legion['description']): ?>
                                    <p class="text-gray-400"><?php echo htmlspecialchars($legion['description']); ?></p>
                                <?php endif; ?>
                                <p class="text-blue-400 text-sm mt-1">
                                    <i class="fas fa-users mr-1"></i>
                                    <?php echo $legion['member_count']; ?> membre(s)
                                </p>
                            </div>
                            <a href="?delete=<?php echo $legion['id']; ?>" 
                               onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette légion ?')"
                               class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                <i class="fas fa-trash"></i>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>