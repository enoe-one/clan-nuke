<?php
require_once 'config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO member_applications 
            (age, discord_pseudo, roblox_pseudo, rebirths, niveau, kdr, recruteur, recruteur_pseudo, 
             vehicule_confiant, vehicule_progresser, motivation, motivation_autre, message, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['age'],
            $_POST['discord_pseudo'],
            $_POST['roblox_pseudo'],
            $_POST['rebirths'],
            $_POST['niveau'],
            $_POST['kdr'],
            $_POST['recruteur'],
            $_POST['recruteur_pseudo'] ?? null,
            $_POST['vehicule_confiant'],
            $_POST['vehicule_progresser'],
            $_POST['motivation'],
            $_POST['motivation_autre'] ?? null,
            $_POST['message'] ?? null,
            $_SERVER['REMOTE_ADDR']
        ]);
        
        $success = true;
    } catch(PDOException $e) {
        $error = "Erreur lors de l'envoi de la candidature. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recrutement Individuel - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-3xl mx-auto px-4">
            <a href="recruitment.php" class="text-blue-400 hover:text-blue-300 mb-6 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour au recrutement
            </a>
            
            <h1 class="text-4xl font-bold text-white mb-8">Formulaire de Recrutement Individuel</h1>
            
            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Votre candidature a été envoyée avec succès ! Nous vous contacterons bientôt sur Discord.
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="space-y-6 bg-gray-800 p-8 rounded-lg">
                <div>
                    <label class="block text-white mb-2 font-semibold">Votre âge *</label>
                    <input type="number" name="age" required min="1" max="99"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div class="grid md:grid-cols-2 gap-4">
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

                <div>
                    <label class="block text-white mb-2 font-semibold">Combien de rebirths ? *</label>
                    <input type="number" name="rebirths" required min="0"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Comment évaluez-vous votre niveau ? (1-10)</label>
                    <input type="range" name="niveau" min="1" max="10" value="5" id="niveau-slider"
                           class="w-full">
                    <div class="text-center text-white text-xl font-bold mt-2"><span id="niveau-value">5</span>/10</div>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Votre KDR (Kill/Death Ratio) *</label>
                    <input type="number" name="kdr" step="0.01" min="0" required placeholder="Ex: 1.50"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Qui vous a recruté ? *</label>
                    <select name="recruteur" id="recruteur" required
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="internet">Sur internet</option>
                        <option value="ami">Un ami</option>
                        <option value="membre">Un membre du clan</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <div id="recruteur-pseudo-div" style="display:none;">
                    <label class="block text-white mb-2 font-semibold">Précisez (pseudo ou détails)</label>
                    <input type="text" name="recruteur_pseudo" maxlength="100"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Dans quel véhicule vous sentez-vous le plus confiant ? *</label>
                    <select name="vehicule_confiant" required
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="">-- Sélectionnez --</option>
                        <option value="terrestre">Terrestre</option>
                        <option value="aerienne_avion">Aérienne - Avions</option>
                        <option value="aerienne_helicoptere">Aérienne - Hélicoptère</option>
                        <option value="marine_navire">Marine - Navire</option>
                        <option value="marine_sousmarin">Marine - Sous-marin</option>
                        <option value="marine_aeroglisseur">Marine - Aéroglisseur</option>
                        <option value="aucun">Aucun</option>
                    </select>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Dans quel véhicule voudriez-vous progresser ? *</label>
                    <select name="vehicule_progresser" required
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="">-- Sélectionnez --</option>
                        <option value="terrestre">Terrestre</option>
                        <option value="aerienne_avion">Aérienne - Avions</option>
                        <option value="aerienne_helicoptere">Aérienne - Hélicoptère</option>
                        <option value="marine_navire">Marine - Navire</option>
                        <option value="marine_sousmarin">Marine - Sous-marin</option>
                        <option value="marine_aeroglisseur">Marine - Aéroglisseur</option>
                        <option value="aucun">Aucun</option>
                    </select>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Pourquoi voulez-vous vous engager dans le clan "CFWT" ? *</label>
                    <select name="motivation" id="motivation" required
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <option value="devenir_fort">Je veux devenir plus fort</option>
                        <option value="augmenter_rebirths">Je veux augmenter mes rebirths</option>
                        <option value="monter_grade">Je veux monter en grade</option>
                        <option value="attaques_dirigees">Je veux participer à des attaques dirigées</option>
                        <option value="autre">Autre</option>
                    </select>
                </div>

                <div id="motivation-autre-div" style="display:none;">
                    <label class="block text-white mb-2 font-semibold">Précisez votre motivation</label>
                    <textarea name="motivation_autre"
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none h-24"></textarea>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Message pour l'État-Major</label>
                    <textarea name="message"
                              placeholder="Présentez-vous et expliquez pourquoi vous souhaitez rejoindre la CFWT..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none h-32"></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-blue-900 transition transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer ma candidature
                </button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Slider niveau
    document.getElementById('niveau-slider').addEventListener('input', function() {
        document.getElementById('niveau-value').textContent = this.value;
    });
    
    // Afficher champ recruteur pseudo
    document.getElementById('recruteur').addEventListener('change', function() {
        const div = document.getElementById('recruteur-pseudo-div');
        div.style.display = (this.value === 'ami' || this.value === 'membre' || this.value === 'autre') ? 'block' : 'none';
    });
    
    // Afficher champ motivation autre
    document.getElementById('motivation').addEventListener('change', function() {
        const div = document.getElementById('motivation-autre-div');
        div.style.display = (this.value === 'autre') ? 'block' : 'none';
    });
    </script>
</body>
</html>


