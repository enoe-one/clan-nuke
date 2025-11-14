<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM members WHERE discord_pseudo = ? OR roblox_pseudo = ?");
    $stmt->execute([$username, $username]);
    $member = $stmt->fetch();
    
    if ($member && password_verify($password, $member['password'])) {
        $_SESSION['member_id'] = $member['id'];
        $_SESSION['member_discord'] = $member['discord_pseudo'];
        $_SESSION['member_roblox'] = $member['roblox_pseudo'];
        $_SESSION['member_must_change_password'] = $member['must_change_password'];
        
        header('Location: member/dashboard.php');
        exit;
    } else {
        $error = 'Identifiant ou mot de passe incorrect';
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Membre - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-gray-800 p-8 rounded-lg">
                <div class="text-center mb-8">
                    <i class="fas fa-user text-blue-500 text-6xl mb-4"></i>
                    <h1 class="text-3xl font-bold text-white">Connexion Membre</h1>
                    <p class="text-gray-400 mt-2">Accédez à votre espace personnel</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Pseudo (Discord ou Roblox)</label>
                        <input type="text" name="username" required
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Mot de passe</label>
                        <input type="password" name="password" required
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                        <p class="text-gray-500 text-sm mt-1">Mot de passe par défaut : "Coalition"</p>
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-800 text-white py-3 rounded-lg font-bold hover:from-blue-700 hover:to-blue-900 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <p class="text-gray-400 mb-2">Vous êtes administrateur ?</p>
                    <a href="login.php" class="text-blue-400 hover:text-blue-300">
                        Connexion Admin <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

