<?php
require_once 'config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Insérer la faction
        $stmt = $pdo->prepare("
            INSERT INTO faction_applications 
            (nom_faction, chef_faction, nombre_membres, evaluation, raison, ip_address)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $_POST['nom_faction'],
            $_POST['chef_faction'],
            $_POST['nombre_membres'],
            $_POST['evaluation'],
            $_POST['raison'],
            $_SERVER['REMOTE_ADDR']
        ]);
        
        $faction_id = $pdo->lastInsertId();
        
        // Insérer les membres
        if (isset($_POST['membres_pseudo']) && is_array($_POST['membres_pseudo'])) {
            $stmt = $pdo->prepare("INSERT INTO faction_members (faction_id, pseudo, niveau) VALUES (?, ?, ?)");
            
            foreach ($_POST['membres_pseudo'] as $index => $pseudo) {
                if (!empty($pseudo)) {
                    $niveau = $_POST['membres_niveau'][$index] ?? 5;
                    $stmt->execute([$faction_id, $pseudo, $niveau]);
                }
            }
        }
        
        $pdo->commit();
        $success = true;
    } catch(PDOException $e) {
        $pdo->rollBack();
        $error = "Erreur lors de l'envoi de la candidature. Veuillez réessayer.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recrutement Faction - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-3xl mx-auto px-4">
            <a href="recruitment.php" class="text-blue-400 hover:text-blue-300 mb-6 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour au recrutement
            </a>
            
            <h1 class="text-4xl font-bold text-white mb-8">Formulaire de Recrutement Faction</h1>
            
            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    La candidature de votre faction a été envoyée avec succès !
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
                    <label class="block text-white mb-2 font-semibold">Nom de votre faction *</label>
                    <input type="text" name="nom_faction" required maxlength="100"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Chef de faction *</label>
                    <input type="text" name="chef_faction" required maxlength="100"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Combien êtes-vous ? *</label>
                    <input type="number" name="nombre_membres" required min="1"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">À combien évaluez-vous votre faction ? (1-10)</label>
                    <input type="range" name="evaluation" min="1" max="10" value="5" id="evaluation-slider"
                           class="w-full">
                    <div class="text-center text-white text-xl font-bold mt-2"><span id="evaluation-value">5</span>/10</div>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Liste de vos membres et leur niveau</label>
                    <div id="membres-container">
                        <div class="flex gap-4 mb-3 membre-row">
                            <input type="text" name="membres_pseudo[]" placeholder="Pseudo du membre"
                                   class="flex-1 p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <div class="w-48">
                                <input type="range" name="membres_niveau[]" min="1" max="10" value="5" class="w-full membre-niveau-slider">
                                <div class="text-center text-white text-sm membre-niveau-value">5/10</div>
                            </div>
                        </div>
                    </div>
                    <button type="button" id="add-membre-btn"
                            class="mt-2 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i> Ajouter un membre
                    </button>
                    <p class="text-gray-400 text-sm mt-2">Maximum 100 membres. Au-delà, veuillez contacter un modérateur.</p>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Pourquoi voudriez-vous entrer dans la CFWT ? *</label>
                    <textarea name="raison" required
                              placeholder="Expliquez les motivations de votre faction..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none h-32"></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-red-600 to-red-800 text-white py-4 rounded-lg font-bold text-lg hover:from-red-700 hover:to-red-900 transition transform hover:scale-105">
                    <i class="fas fa-paper-plane mr-2"></i> Envoyer la candidature de faction
                </button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    // Slider evaluation
    document.getElementById('evaluation-slider').addEventListener('input', function() {
        document.getElementById('evaluation-value').textContent = this.value;
    });
    
    // Ajouter membre
    let membreCount = 1;
    document.getElementById('add-membre-btn').addEventListener('click', function() {
        if (membreCount >= 100) {
            alert('Vous avez atteint le maximum de 100 membres. Veuillez contacter un modérateur.');
            return;
        }
        
        const container = document.getElementById('membres-container');
        const newRow = document.createElement('div');
        newRow.className = 'flex gap-4 mb-3 membre-row';
        newRow.innerHTML = `
            <input type="text" name="membres_pseudo[]" placeholder="Pseudo du membre"
                   class="flex-1 p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
            <div class="w-48">
                <input type="range" name="membres_niveau[]" min="1" max="10" value="5" class="w-full membre-niveau-slider">
                <div class="text-center text-white text-sm membre-niveau-value">5/10</div>
            </div>
        `;
        container.appendChild(newRow);
        membreCount++;
        
        // Ajouter l'événement au nouveau slider
        const slider = newRow.querySelector('.membre-niveau-slider');
        const value = newRow.querySelector('.membre-niveau-value');
        slider.addEventListener('input', function() {
            value.textContent = this.value + '/10';
        });
    });
    
    // Événements pour les sliders de membres existants
    document.querySelectorAll('.membre-niveau-slider').forEach(slider => {
        slider.addEventListener('input', function() {
            this.parentElement.querySelector('.membre-niveau-value').textContent = this.value + '/10';
        });
    });
    </script>
</body>
</html>

