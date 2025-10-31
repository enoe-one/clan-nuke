<?php
require_once '../config.php';

if (!isAdmin() || !hasAccess('access_full')) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Action de déconnexion forcée
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'clear_all_sessions') {
        // Supprimer tous les fichiers de session (sauf la session admin actuelle)
        $session_path = session_save_path();
        if (empty($session_path)) {
            $session_path = sys_get_temp_dir();
        }
        
        $current_session_id = session_id();
        $count = 0;
        
        foreach (glob("$session_path/sess_*") as $file) {
            if (basename($file) !== "sess_$current_session_id") {
                @unlink($file);
                $count++;
            }
        }
        
        logAdminAction($pdo, $_SESSION['user_id'], 'Suppression de toutes les sessions', "$count sessions supprimées");
        $success = "$count session(s) supprimée(s) avec succès !";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Sessions - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-4xl mx-auto px-4">
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-4xl font-bold text-white">Gestion des Sessions</h1>
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

            <div class="bg-red-900 bg-opacity-30 border-2 border-red-500 p-6 rounded-lg mb-8">
                <div class="flex items-start">
                    <i class="fas fa-exclamation-triangle text-red-500 text-3xl mr-4"></i>
                    <div>
                        <h2 class="text-xl font-bold text-red-400 mb-2">Zone Dangereuse</h2>
                        <p class="text-red-200 mb-4">
                            Ces actions sont irréversibles et déconnecteront tous les utilisateurs (sauf vous) du site.
                            Utilisez avec précaution !
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 p-6 rounded-lg">
                <h3 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-users-slash mr-2"></i> Déconnexion Forcée
                </h3>
                
                <div class="space-y-4">
                    <div class="bg-gray-700 p-4 rounded">
                        <h4 class="text-lg font-semibold text-white mb-2">
                            Supprimer toutes les sessions actives
                        </h4>
                        <p class="text-gray-300 mb-4">
                            Cette action va déconnecter tous les utilisateurs (administrateurs et membres) 
                            du site, à l'exception de votre session actuelle. Utile en cas de :
                        </p>
                        <ul class="text-gray-400 mb-4 space-y-1">
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Maintenance du site</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Problème de sécurité</li>
                            <li><i class="fas fa-check text-green-500 mr-2"></i> Réinitialisation des connexions</li>
                        </ul>
                        
                        <form method="POST" onsubmit="return confirm('⚠️ ATTENTION ⚠️\n\nÊtes-vous absolument sûr de vouloir déconnecter tous les utilisateurs du site ?\n\nCette action est irréversible.');">
                            <input type="hidden" name="action" value="clear_all_sessions">
                            <button type="submit" class="bg-red-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-red-700 transition">
                                <i class="fas fa-bomb mr-2"></i> Supprimer Toutes les Sessions
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="mt-8 bg-gray-800 p-6 rounded-lg">
                <h3 class="text-2xl font-bold text-white mb-4">
                    <i class="fas fa-info-circle mr-2"></i> Informations sur les Sessions
                </h3>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div class="bg-gray-700 p-4 rounded">
                        <p class="text-gray-400 text-sm mb-1">Votre Session ID</p>
                        <p class="text-white font-mono text-sm break-all"><?php echo session_id(); ?></p>
                    </div>
                    
                    <div class="bg-gray-700 p-4 rounded">
                        <p class="text-gray-400 text-sm mb-1">Chemin des Sessions</p>
                        <p class="text-white font-mono text-sm break-all">
                            <?php 
                            $path = session_save_path();
                            echo empty($path) ? sys_get_temp_dir() : $path;
                            ?>
                        </p>
                    </div>
                    
                    <div class="bg-gray-700 p-4 rounded">
                        <p class="text-gray-400 text-sm mb-1">Durée de vie</p>
                        <p class="text-white font-semibold">
                            <?php echo ini_get('session.gc_maxlifetime'); ?> secondes
                            (<?php echo round(ini_get('session.gc_maxlifetime') / 60); ?> minutes)
                        </p>
                    </div>
                    
                    <div class="bg-gray-700 p-4 rounded">
                        <p class="text-gray-400 text-sm mb-1">Cookie de session</p>
                        <p class="text-white font-semibold"><?php echo session_name(); ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>