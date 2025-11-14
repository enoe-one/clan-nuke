<?php
require_once 'config.php';

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO reports (reporter_name, subject, description, ip_address)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $_POST['reporter_name'] ?? null,
            $_POST['subject'],
            $_POST['description'],
            $_SERVER['REMOTE_ADDR']
        ]);
        $success = true;
    } catch(PDOException $e) {
        $error = "Erreur lors de l'envoi du signalement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signaler un Problème - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-3xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-white mb-8 text-center">Signaler un Problème</h1>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    Votre signalement a été envoyé avec succès ! L'équipe de modération le traitera rapidement.
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="bg-gray-800 p-8 rounded-lg space-y-6">
                <div>
                    <label class="block text-white mb-2 font-semibold">Votre nom (optionnel)</label>
                    <input type="text" name="reporter_name" maxlength="100"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                           placeholder="Discord ou Roblox">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Sujet du signalement *</label>
                    <input type="text" name="subject" required maxlength="255"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                           placeholder="Ex: Problème technique, comportement inapproprié...">
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Description détaillée *</label>
                    <textarea name="description" required rows="8"
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                              placeholder="Décrivez le problème en détail..."></textarea>
                </div>

                <button type="submit"
                        class="w-full bg-gradient-to-r from-yellow-600 to-orange-600 text-white py-4 rounded-lg font-bold text-lg hover:from-yellow-700 hover:to-orange-700 transition">
                    <i class="fas fa-flag mr-2"></i> Envoyer le Signalement
                </button>
            </form>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>

</html>
