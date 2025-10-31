<?php
require_once 'config.php';

echo '<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création Comptes Admin - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <div class="min-h-screen flex items-center justify-center px-4">
        <div class="max-w-5xl w-full">';

try {
    // Supprimer tous les utilisateurs existants
    $pdo->exec("DELETE FROM users");
    
    // Définir les 6 utilisateurs
    $users = [
        [
            'username' => 'Enoe',
            'password' => 'E!wp96vpd8',
            'role' => 'super_admin',
            'must_change' => 0,
            'description' => 'Super Administrateur - TOUS LES DROITS',
            'access' => [
                'access_recruitment_player' => 1,
                'access_recruitment_faction' => 1,
                'access_edit_members' => 1,
                'access_moderation' => 1,
                'access_edit_site' => 1,
                'access_full' => 1,
                'access_create_accounts' => 1,
                'access_manage_legions' => 1,
                'access_reset_passwords' => 1
            ]
        ],
        [
            'username' => 'elotokyo',
            'password' => '60908',
            'role' => 'etat_major',
            'must_change' => 1,
            'description' => 'État-major - Recrutement joueur, Modification membres',
            'access' => [
                'access_recruitment_player' => 1,
                'access_edit_members' => 1
            ]
        ],
        [
            'username' => 'Death_angel',
            'password' => '49356',
            'role' => 'chef',
            'must_change' => 1,
            'description' => 'Chef - Recrutement complet, Modification membres, Accès chef',
            'access' => [
                'access_recruitment_player' => 1,
                'access_recruitment_faction' => 1,
                'access_edit_members' => 1,
                'access_full' => 1
            ]
        ],
        [
            'username' => 'Enoe_one',
            'password' => '32053',
            'role' => 'etat_major',
            'must_change' => 1,
            'description' => 'État-major - Recrutement complet, Modification membres',
            'access' => [
                'access_recruitment_player' => 1,
                'access_recruitment_faction' => 1,
                'access_edit_members' => 1
            ]
        ],
        [
            'username' => 'tankman',
            'password' => '19411',
            'role' => 'recruteur',
            'must_change' => 1,
            'description' => 'Recruteur - Recrutement joueur uniquement',
            'access' => [
                'access_recruitment_player' => 1
            ]
        ],
        [
            'username' => 'adamael_huh',
            'password' => '23942',
            'role' => 'moderateur',
            'must_change' => 0,
            'description' => 'Modérateur - Modération et édition site (PAS recrutement)',
            'access' => [
                'access_moderation' => 1,
                'access_edit_site' => 1
            ]
        ]
    ];
    
    echo '<div class="bg-gray-800 p-8 rounded-lg mb-6">
            <h1 class="text-3xl font-bold text-white mb-6 text-center">
                <i class="fas fa-users-cog mr-3"></i>Création des Comptes Administrateurs
            </h1>
            <div class="space-y-4">';
    
    foreach ($users as $user) {
        $password_hash = password_hash($user['password'], PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            INSERT INTO users 
            (username, password, role, must_change_password, 
             access_recruitment_player, access_recruitment_faction, access_edit_members, 
             access_moderation, access_edit_site, access_full, 
             access_create_accounts, access_manage_legions, access_reset_passwords)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $user['username'],
            $password_hash,
            $user['role'],
            $user['must_change'],
            $user['access']['access_recruitment_player'] ?? 0,
            $user['access']['access_recruitment_faction'] ?? 0,
            $user['access']['access_edit_members'] ?? 0,
            $user['access']['access_moderation'] ?? 0,
            $user['access']['access_edit_site'] ?? 0,
            $user['access']['access_full'] ?? 0,
            $user['access']['access_create_accounts'] ?? 0,
            $user['access']['access_manage_legions'] ?? 0,
            $user['access']['access_reset_passwords'] ?? 0
        ]);
        
        $badge_color = $user['role'] === 'super_admin' ? 'red' : ($user['role'] === 'chef' ? 'purple' : 'blue');
        
        echo '<div class="bg-gray-700 p-4 rounded-lg">
                <div class="flex justify-between items-start">
                    <div>
                        <div class="flex items-center gap-3 mb-2">
                            <span class="bg-' . $badge_color . '-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                ' . htmlspecialchars($user['username']) . '
                            </span>
                            <span class="text-gray-400 font-mono text-sm">MDP: ' . htmlspecialchars($user['password']) . '</span>
                        </div>
                        <p class="text-gray-300 text-sm">' . htmlspecialchars($user['description']) . '</p>
                    </div>
                    <i class="fas fa-check-circle text-green-500 text-2xl"></i>
                </div>
              </div>';
    }
    
    echo '</div></div>';
    
    echo '<div class="bg-green-900 border-2 border-green-500 p-6 rounded-lg mb-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-check-circle text-green-400 text-4xl mr-4"></i>
                <h2 class="text-2xl font-bold text-white">Tous les comptes ont été créés avec succès !</h2>
            </div>
            <div class="bg-green-950 p-4 rounded">
                <p class="text-green-200 mb-2">✓ 6 comptes administrateurs créés</p>
                <p class="text-green-200 mb-2">✓ Base de données configurée</p>
                <p class="text-green-200">✓ Système prêt à l\'emploi</p>
            </div>
          </div>';
    
    echo '<div class="bg-red-900 border-2 border-red-500 p-6 rounded-lg mb-6">
            <div class="flex items-center mb-4">
                <i class="fas fa-exclamation-triangle text-red-400 text-4xl mr-4"></i>
                <h2 class="text-2xl font-bold text-white">SÉCURITÉ CRITIQUE</h2>
            </div>
            <div class="bg-red-950 p-4 rounded">
                <p class="text-red-200 text-lg font-bold mb-3">
                    ⚠️ VOUS DEVEZ SUPPRIMER CE FICHIER MAINTENANT !
                </p>
                <p class="text-red-100 mb-3">
                    Supprimez le fichier <code class="bg-gray-900 px-2 py-1 rounded">create_users.php</code> 
                    de votre serveur immédiatement pour des raisons de sécurité.
                </p>
                <div class="bg-gray-900 p-3 rounded mb-3">
                    <p class="text-gray-400 text-sm mb-1">Commande SSH/Terminal :</p>
                    <code class="text-yellow-400 font-mono">rm create_users.php</code>
                </div>
                <p class="text-red-200 text-sm">
                    <i class="fas fa-shield-alt mr-2"></i>
                    Ne laissez JAMAIS ce fichier sur un serveur en production !
                </p>
            </div>
          </div>';
    
    echo '<div class="bg-gray-800 p-6 rounded-lg text-center">
            <p class="text-gray-300 mb-4">Prêt à vous connecter ?</p>
            <a href="login.php" class="inline-block bg-gradient-to-r from-blue-600 to-blue-800 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-blue-900 transition">
                <i class="fas fa-sign-in-alt mr-2"></i> Aller à la Page de Connexion
            </a>
          </div>';
    
} catch(PDOException $e) {
    echo '<div class="bg-red-900 border-2 border-red-500 p-8 rounded-lg">
            <div class="flex items-center mb-4">
                <i class="fas fa-times-circle text-red-400 text-4xl mr-4"></i>
                <h1 class="text-3xl font-bold text-white">Erreur</h1>
            </div>
            <div class="bg-red-950 p-6 rounded-lg">
                <p class="text-red-200 text-lg mb-4">
                    Une erreur est survenue lors de la création des comptes :
                </p>
                <div class="bg-gray-900 p-4 rounded mb-4">
                    <code class="text-red-400 text-sm">' . htmlspecialchars($e->getMessage()) . '</code>
                </div>
                <p class="text-red-100 mb-2">Vérifiez :</p>
                <ul class="text-red-200 text-sm space-y-1">
                    <li>• La configuration dans config.php</li>
                    <li>• Que la base de données existe</li>
                    <li>• Que database_complete.sql a été importé</li>
                </ul>
            </div>
          </div>';
}

echo '</div></div></body></html>';
?>
