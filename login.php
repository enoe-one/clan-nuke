<?php
require_once 'config.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['access_recruitment_player'] = $user['access_recruitment_player'];
        $_SESSION['access_recruitment_faction'] = $user['access_recruitment_faction'];
        $_SESSION['access_edit_members'] = $user['access_edit_members'];
        $_SESSION['access_moderation'] = $user['access_moderation'];
        $_SESSION['access_edit_site'] = $user['access_edit_site'];
        $_SESSION['access_full'] = $user['access_full'];
        $_SESSION['access_create_accounts'] = $user['access_create_accounts'];
        $_SESSION['access_manage_legions'] = $user['access_manage_legions'];
        $_SESSION['access_reset_passwords'] = $user['access_reset_passwords'];
        $_SESSION['must_change_password'] = $user['must_change_password'];
        
        logAdminAction($pdo, $user['id'], 'Connexion');
        header('Location: admin/dashboard.php');
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
    <title>Connexion Admin - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-gray-800 p-8 rounded-lg">
                <div class="text-center mb-8">
                    <i class="fas fa-shield-alt text-red-500 text-6xl mb-4"></i>
                    <h1 class="text-3xl font-bold text-white">Connexion Admin</h1>
                    <p class="text-gray-400 mt-2">Réservé aux membres de l'état-major</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Identifiant</label>
                        <input type="text" name="username" required
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Mot de passe</label>
                        <input type="password" name="password" required
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <button type="submit"
                            class="w-full bg-gradient-to-r from-red-600 to-blue-600 text-white py-3 rounded-lg font-bold hover:from-red-700 hover:to-blue-700 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i> Se connecter
                    </button>
                </form>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

