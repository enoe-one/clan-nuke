<?php
require_once '../config.php';

if (!isMember()) {
    header('Location: ../member_login.php');
    exit;
}

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old_password = $_POST['old_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    if ($new_password !== $confirm_password) {
        $error = 'Les nouveaux mots de passe ne correspondent pas';
    } elseif (strlen($new_password) < 6) {
        $error = 'Le mot de passe doit contenir au moins 6 caractères';
    } else {
        $stmt = $pdo->prepare("SELECT password FROM members WHERE id = ?");
        $stmt->execute([$_SESSION['member_id']]);
        $member = $stmt->fetch();
        
        if (password_verify($old_password, $member['password'])) {
            $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE members SET password = ?, must_change_password = 0 WHERE id = ?");
            $stmt->execute([$new_hash, $_SESSION['member_id']]);
            
            $_SESSION['member_must_change_password'] = false;
            $success = true;
        } else {
            $error = 'Ancien mot de passe incorrect';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changer le mot de passe - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-gray-800 p-8 rounded-lg">
                <div class="text-center mb-8">
                    <i class="fas fa-key text-blue-500 text-6xl mb-4"></i>
                    <h1 class="text-3xl font-bold text-white">Changer le mot de passe</h1>
                    <p class="text-gray-400 mt-2">Sécurisez votre compte membre</p>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-check-circle mr-2"></i>
                        Mot de passe changé avec succès !
                        <a href="dashboard.php" class="underline ml-2">Retour au profil</a>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Ancien mot de passe</label>
                        <input type="password" name="old_password" required
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                               placeholder="Votre mot de passe actuel">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Nouveau mot de passe</label>
                        <input type="password" name="new_password" required minlength="6"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                               placeholder="Minimum 6 caractères">
                        <p class="text-gray-500 text-sm mt-1">
                            <i class="fas fa-info-circle mr-1"></i>
                            Utilisez un mot de passe fort et unique
                        </p>
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Confirmer le nouveau mot de passe</label>
                        <input type="password" name="confirm_password" required minlength="6"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                               placeholder="Répétez le nouveau mot de passe">
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white py-3 rounded-lg font-bold hover:from-blue-700 hover:to-blue-900 transition">
                        <i class="fas fa-save mr-2"></i> Enregistrer le nouveau mot de passe
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="dashboard.php" class="text-gray-400 hover:text-gray-300">
                        <i class="fas fa-arrow-left mr-2"></i> Retour au profil
                    </a>
                </div>

                <div class="mt-6 bg-blue-900 bg-opacity-30 p-4 rounded border border-blue-500">
                    <p class="text-blue-300 text-sm">
                        <i class="fas fa-shield-alt mr-2"></i>
                        <strong>Conseil de sécurité :</strong> Ne partagez jamais votre mot de passe. 
                        Les administrateurs ne vous demanderont JAMAIS votre mot de passe.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>