<?php
require_once 'config.php';

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
    'terrestre' => '🧥 Terrestre',
    'aeronaval' => '🚁 Aéronaval et Naval',
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
                                <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-<?php echo $level_colors[$diplome['niveau']]; ?>-500 hover:bg-gray-750 transition">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <div class="flex items-center gap-3 mb-2">
                                                <span class="bg-<?php echo $level_colors[$diplome['niveau']]; ?>-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                    Niveau <?php echo $diplome['niveau']; ?>
                                                </span>
                                                <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded text-sm font-mono">
                                                    <?php echo htmlspecialchars($diplome['code']); ?>
                                                </span>
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
                                        <i class="fas fa-graduation-cap text-<?php echo $level_colors[$diplome['niveau']]; ?>-500 text-3xl ml-4"></i>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>