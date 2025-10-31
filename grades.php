<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-5xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-white mb-8 text-center">Système de Grades</h1>
            
            <div class="bg-gray-800 p-6 rounded-lg mb-8 border-l-4 border-yellow-500">
                <h2 class="text-2xl font-bold text-yellow-400 mb-2">Grade par défaut : Soldat</h2>
                <p class="text-gray-300">Tous les nouveaux membres commencent avec ce grade.</p>
            </div>

            <div class="space-y-4 mb-12">
                <h2 class="text-3xl font-bold text-white mb-6">Grades Hiérarchiques</h2>
                
                <?php
                $grades = [
                    ['grade' => 'Caporal', 'requis' => 'Au moins 9 RIB et la plupart des véhicules utiles pour le combat', 'color' => 'blue'],
                    ['grade' => 'Sergent', 'requis' => 'Promotion', 'color' => 'blue'],
                    ['grade' => 'Adjudant', 'requis' => 'Promotion', 'color' => 'blue'],
                    ['grade' => 'Sous-lieutenant', 'requis' => 'Promotion', 'color' => 'blue'],
                    ['grade' => 'Lieutenant', 'requis' => 'Promotion', 'color' => 'blue'],
                    ['grade' => 'Capitaine', 'requis' => 'Promotion', 'color' => 'blue'],
                    ['grade' => 'Commandant', 'requis' => 'Connaître la plupart des tactiques de combat de ce jeu / savoir commander une escouade', 'color' => 'purple'],
                    ['grade' => 'Lieutenant-colonel', 'requis' => 'Promotion', 'color' => 'purple'],
                    ['grade' => 'Colonel', 'requis' => 'Promotion', 'color' => 'purple'],
                    ['grade' => 'Général de brigade', 'requis' => 'Promotion', 'color' => 'red'],
                    ['grade' => 'Général de division', 'requis' => 'Doit être chef de faction (a une place dans l\'état-major). Ne peut commander que les membres de sa division', 'color' => 'red', 'special' => true],
                    ['grade' => 'Général de corps d\'armée', 'requis' => 'Peut commander plusieurs divisions ainsi que les généraux de division (état-major)', 'color' => 'red', 'special' => true, 'holders' => 'Enoe_one et elotokyo'],
                    ['grade' => 'Général d\'armée', 'requis' => 'Une seule personne. Permet de commander l\'intégralité des Armées. En son absence, les généraux de division doivent prendre des décisions par et avec l\'état-major', 'color' => 'red', 'special' => true, 'holders' => 'death_angel (fondateur)']
                ];
                
                foreach ($grades as $item): ?>
                    <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-<?php echo $item['color']; ?>-500">
                        <div class="flex justify-between items-start">
                            <div>
                                <h3 class="text-2xl font-bold text-white mb-2"><?php echo htmlspecialchars($item['grade']); ?></h3>
                                <p class="text-gray-300"><?php echo htmlspecialchars($item['requis']); ?></p>
                                <?php if (isset($item['holders'])): ?>
                                    <p class="text-yellow-400 mt-2 font-semibold">Titulaire(s) : <?php echo htmlspecialchars($item['holders']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php if (isset($item['special']) && $item['special']): ?>
                                <i class="fas fa-award text-yellow-500 text-3xl"></i>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="space-y-4">
                <h2 class="text-3xl font-bold text-white mb-6">Rangs Supplémentaires (Atouts)</h2>
                <div class="bg-yellow-900 bg-opacity-30 p-4 rounded-lg border border-yellow-500 mb-6">
                    <p class="text-yellow-300 font-semibold">⚠️ Important : Ces rangs doivent être attribués par ordre. Si vous êtes assez fort pour réussir le stage Commando mais n'avez pas les requis pour le grade 1ère classe, le grade Commando ne peut pas être attribué.</p>
                </div>

                <?php
                $rangs = [
                    ['rang' => 'Recrue', 'requis' => 'Rang de base', 'color' => 'gray'],
                    ['rang' => '2ème classe', 'requis' => 'Au moins 9 RIB et la plupart des véhicules utiles pour le combat', 'color' => 'green'],
                    ['rang' => '1ère classe', 'requis' => 'Passer le stage 1ère classe (savoir se battre avec des véhicules aérien, terrestre et aquatique de tous types)', 'color' => 'green'],
                    ['rang' => '2ème classe (avancé)', 'requis' => 'Stage 2ème classe (savoir raid et se défendre contre un raid)', 'color' => 'blue'],
                    ['rang' => 'Commando', 'requis' => 'Stage Commando (tenir tête à des ennemis plus nombreux ou beaucoup plus forts, aka les tryhards)', 'color' => 'purple'],
                    ['rang' => 'Vétéran', 'requis' => 'Ne nécessite pas de stage particulier. S\'obtient en étant incroyablement fort dans tous les domaines du jeu (pouvoir tenir tête à des joueurs extrêmement forts à ce jeu)', 'color' => 'red']
                ];
                
                foreach ($rangs as $item): ?>
                    <div class="bg-gray-800 p-6 rounded-lg border-l-4 border-<?php echo $item['color']; ?>-500">
                        <h3 class="text-xl font-bold text-white mb-2"><?php echo htmlspecialchars($item['rang']); ?></h3>
                        <p class="text-gray-300"><?php echo htmlspecialchars($item['requis']); ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>
