<?php
require_once '../config.php';

// UNIQUEMENT pour Enoe (super_admin)
if (!isAdmin() || (strtolower($_SESSION['username']) !== 'enoe' && $_SESSION['role'] !== 'super_admin')) {
    header('Location: dashboard.php');
    exit;
}

$success = '';
$error = '';

// Traitement de la promotion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        if ($action === 'promote_to_staff') {
            $member_id = $_POST['member_id'];
            $username = $_POST['username'];
            $password = $_POST['password'];
            $role = $_POST['role'];
            
            // Vérifier que le membre existe
            $stmt = $pdo->prepare("SELECT * FROM members WHERE id = ?");
            $stmt->execute([$member_id]);
            $member = $stmt->fetch();
            
            if (!$member) {
                throw new Exception("Membre introuvable");
            }
            
            // Vérifier que le username n'existe pas déjà
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                throw new Exception("Ce nom d'utilisateur existe déjà dans l'administration");
            }
            
            // Créer le compte admin
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (username, password, role, must_change_password,
                    access_recruitment_player, access_recruitment_faction, access_edit_members,
                    access_moderation, access_edit_site, access_full,
                    access_create_accounts, access_manage_legions, access_reset_passwords)
                VALUES (?, ?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Définir les permissions selon le rôle
            $perms = [
                'etat_major' => [1, 1, 1, 0, 0, 0, 0, 0, 0],
                'chef' => [1, 1, 1, 1, 1, 0, 0, 1, 0],
                'moderateur' => [0, 0, 0, 1, 1, 0, 0, 0, 0],
                'recruteur' => [1, 0, 0, 0, 0, 0, 0, 0, 0]
            ];
            
            $role_perms = $perms[$role] ?? [0, 0, 0, 0, 0, 0, 0, 0, 0];
            
            $stmt->execute([
                $username,
                $password_hash,
                $role,
                ...$role_perms
            ]);
            
            logAdminAction($pdo, $_SESSION['user_id'], 'Promotion membre vers staff', 
                "Membre: {$member['discord_pseudo']} → Admin: $username ($role)");
            
            $success = "Le membre {$member['discord_pseudo']} a été promu au rang de $role avec le compte admin: $username";
            
        } elseif ($action === 'demote_staff') {
            $user_id = $_POST['user_id'];
            
            // Protéger Enoe
            $stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (strtolower($user['username']) === 'enoe') {
                throw new Exception("Impossible de rétrograder Enoe");
            }
            
            // Supprimer le compte admin
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            
            logAdminAction($pdo, $_SESSION['user_id'], 'Rétrogradation staff', "User: {$user['username']}");
            $success = "L'administrateur {$user['username']} a été rétrogradé";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer tous les membres (pas encore dans l'administration)
$members = $pdo->query("
    SELECT m.* 
    FROM members m
    ORDER BY m.grade DESC, m.discord_pseudo
")->fetchAll();

// Récupérer tous les admins
$admins = $pdo->query("
    SELECT * FROM users 
    WHERE role != 'super_admin'
    ORDER BY 
        CASE role
            WHEN 'chef' THEN 1
            WHEN 'etat_major' THEN 2
            WHEN 'moderateur' THEN 3
            WHEN 'recruteur' THEN 4
        END,
        username
")->fetchAll();

$role_colors = [
    'chef' => 'purple',
    'etat_major' => 'blue',
    'moderateur' => 'yellow',
    'recruteur' => 'green'
];

$role_names = [
    'chef' => 'Chef',
    'etat_major' => 'État-Major',
    'moderateur' => 'Modérateur',
    'recruteur' => 'Recruteur'
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion État-Major - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-crown text-orange-500 mr-3"></i>
                        Gestion État-Major
                    </h1>
                    <p class="text-gray-400">Promouvoir des membres vers l'administration</p>
                    <span class="bg-orange-600 text-white px-3 py-1 rounded text-sm font-bold mt-2 inline-block">
                        <i class="fas fa-shield-alt mr-1"></i>ACCÈS ENOE UNIQUEMENT
                    </span>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Dashboard
                </a>
            </div>

            <?php if ($success): ?>
                <div class="bg-green-900 border border-green-500 text-green-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="bg-red-900 border border-red-500 text-red-200 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <div class="bg-yellow-900 bg-opacity-30 border-2 border-yellow-500 p-6 rounded-lg mb-8">
                <div class="flex items-start gap-4">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-3xl"></i>
                    <div>
                        <h3 class="text-yellow-300 font-bold text-lg mb-2">ATTENTION - Fonctionnalité sensible</h3>
                        <p class="text-yellow-200 mb-2">
                            Cette page permet de promouvoir des membres réguliers vers des postes d'administration.
                        </p>
                        <ul class="text-yellow-200 text-sm space-y-1 list-disc list-inside">
                            <li>La promotion crée un compte administrateur séparé</li>
                            <li>Le membre garde son compte membre ET obtient un compte admin</li>
                            <li>Les permissions sont attribuées selon le rôle choisi</li>
                            <li>Toutes les actions sont loggées</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Statistiques -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-purple-900 to-purple-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-300 text-sm">Chefs</p>
                            <p class="text-white text-3xl font-bold">
                                <?php echo count(array_filter($admins, fn($a) => $a['role'] === 'chef')); ?>
                            </p>
                        </div>
                        <i class="fas fa-crown text-purple-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-300 text-sm">État-Major</p>
                            <p class="text-white text-3xl font-bold">
                                <?php echo count(array_filter($admins, fn($a) => $a['role'] === 'etat_major')); ?>
                            </p>
                        </div>
                        <i class="fas fa-star text-blue-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-900 to-yellow-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-300 text-sm">Modérateurs</p>
                            <p class="text-white text-3xl font-bold">
                                <?php echo count(array_filter($admins, fn($a) => $a['role'] === 'moderateur')); ?>
                            </p>
                        </div>
                        <i class="fas fa-shield-alt text-yellow-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Recruteurs</p>
                            <p class="text-white text-3xl font-bold">
                                <?php echo count(array_filter($admins, fn($a) => $a['role'] === 'recruteur')); ?>
                            </p>
                        </div>
                        <i class="fas fa-user-plus text-green-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex space-x-4 border-b border-gray-700 mb-6">
                    <button onclick="showTab('members')" id="tab-members" class="tab-button px-6 py-3 font-semibold text-white border-b-2 border-blue-500">
                        <i class="fas fa-users mr-2"></i>Membres à promouvoir
                    </button>
                    <button onclick="showTab('admins')" id="tab-admins" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white">
                        <i class="fas fa-user-shield mr-2"></i>Administrateurs actuels
                    </button>
                </div>

                <!-- Tab: Membres -->
                <div id="content-members" class="tab-content">
                    <div class="mb-4">
                        <input type="text" id="search-members" placeholder="Rechercher un membre..." 
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"
                               onkeyup="filterMembers()">
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full" id="members-table">
                            <thead class="bg-gray-700">
                                <tr>
                                    <th class="px-4 py-3 text-left text-white">Discord</th>
                                    <th class="px-4 py-3 text-left text-white">Roblox</th>
                                    <th class="px-4 py-3 text-left text-white">Grade</th>
                                    <th class="px-4 py-3 text-left text-white">Rang</th>
                                    <th class="px-4 py-3 text-center text-white">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-700">
                                <?php foreach ($members as $member): ?>
                                    <tr class="hover:bg-gray-700 member-row">
                                        <td class="px-4 py-3 text-white font-semibold member-discord">
                                            <?php echo htmlspecialchars($member['discord_pseudo']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-gray-300 member-roblox">
                                            <?php echo htmlspecialchars($member['roblox_pseudo']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-blue-400">
                                            <?php echo htmlspecialchars($member['grade']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-green-400">
                                            <?php echo htmlspecialchars($member['rang']); ?>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button onclick="promoteToStaff(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['discord_pseudo']); ?>')" 
                                                    class="bg-orange-600 text-white px-4 py-2 rounded hover:bg-orange-700 transition">
                                                <i class="fas fa-arrow-up mr-1"></i>Promouvoir
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab: Admins -->
                <div id="content-admins" class="tab-content hidden">
                    <div class="space-y-4">
                        <?php foreach ($admins as $admin): ?>
                            <div class="bg-gray-700 p-6 rounded-lg">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-xl font-bold text-white">
                                                <?php echo htmlspecialchars($admin['username']); ?>
                                            </h3>
                                            <span class="px-3 py-1 rounded text-sm font-semibold bg-<?php echo $role_colors[$admin['role']]; ?>-600 text-white">
                                                <?php echo $role_names[$admin['role']]; ?>
                                            </span>
                                        </div>
                                        <p class="text-gray-400 text-sm">
                                            <i class="fas fa-calendar mr-1"></i>
                                            Créé le <?php echo date('d/m/Y', strtotime($admin['created_at'])); ?>
                                        </p>
                                        
                                        <!-- Permissions -->
                                        <div class="mt-3 flex flex-wrap gap-2">
                                            <?php if ($admin['access_recruitment_player']): ?>
                                                <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">Recrutement joueur</span>
                                            <?php endif; ?>
                                            <?php if ($admin['access_recruitment_faction']): ?>
                                                <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">Recrutement faction</span>
                                            <?php endif; ?>
                                            <?php if ($admin['access_edit_members']): ?>
                                                <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">Modifier membres</span>
                                            <?php endif; ?>
                                            <?php if ($admin['access_moderation']): ?>
                                                <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">Modération</span>
                                            <?php endif; ?>
                                            <?php if ($admin['access_manage_legions']): ?>
                                                <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">Gérer légions</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <button onclick="demoteStaff(<?php echo $admin['id']; ?>, '<?php echo htmlspecialchars($admin['username']); ?>')" 
                                            class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition ml-4">
                                        <i class="fas fa-arrow-down mr-1"></i>Rétrograder
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal promotion -->
    <div id="modal-promote" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full p-8">
            <h2 class="text-2xl font-bold text-white mb-4">
                <i class="fas fa-arrow-up text-orange-500 mr-2"></i>
                Promouvoir un membre
            </h2>
            <p class="text-gray-400 mb-6" id="promote-member-name"></p>
            
            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="promote_to_staff">
                <input type="hidden" name="member_id" id="promote-member-id">
                
                <div>
                    <label class="block text-white mb-2 font-semibold">
                        Nom d'utilisateur admin *
                    </label>
                    <input type="text" name="username" required maxlength="50"
                           placeholder="Ex: pseudoAdmin"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-orange-500 focus:outline-none">
                    <p class="text-gray-500 text-sm mt-1">
                        Ce sera l'identifiant de connexion à l'administration
                    </p>
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-semibold">
                        Mot de passe temporaire *
                    </label>
                    <input type="password" name="password" required minlength="5"
                           placeholder="Minimum 5 caractères"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-orange-500 focus:outline-none">
                    <p class="text-gray-500 text-sm mt-1">
                        L'utilisateur devra le changer à sa première connexion
                    </p>
                </div>
                
                <div>
                    <label class="block text-white mb-2 font-semibold">
                        Rôle administratif *
                    </label>
                    <select name="role" required 
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-orange-500 focus:outline-none">
                        <option value="">Choisir un rôle...</option>
                        <option value="recruteur">Recruteur (Recrutement joueur uniquement)</option>
                        <option value="moderateur">Modérateur (Modération + Édition site)</option>
                        <option value="etat_major">État-Major (Recrutement complet + Membres)</option>
                        <option value="chef">Chef (État-Major + Modération + Légions)</option>
                    </select>
                </div>
                
                <div class="bg-blue-900 bg-opacity-30 border border-blue-500 rounded p-4">
                    <p class="text-blue-300 text-sm">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Note:</strong> Les permissions seront attribuées automatiquement selon le rôle choisi.
                    </p>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-orange-600 text-white px-6 py-3 rounded-lg hover:bg-orange-700 transition font-bold">
                        <i class="fas fa-arrow-up mr-2"></i>Promouvoir
                    </button>
                    <button type="button" onclick="closePromoteModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('border-blue-500', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('border-blue-500', 'text-white');
        tabBtn.classList.remove('text-gray-400');
    }

    function promoteToStaff(memberId, memberName) {
        document.getElementById('promote-member-id').value = memberId;
        document.getElementById('promote-member-name').textContent = `Membre: ${memberName}`;
        document.getElementById('modal-promote').classList.remove('hidden');
    }

    function closePromoteModal() {
        document.getElementById('modal-promote').classList.add('hidden');
    }

    function demoteStaff(userId, username) {
        if (confirm(`ATTENTION: Rétrograder l'administrateur "${username}" ?\n\nCette action supprimera définitivement son compte administrateur.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="demote_staff">
                <input type="hidden" name="user_id" value="${userId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function filterMembers() {
        const input = document.getElementById('search-members').value.toLowerCase();
        const rows = document.querySelectorAll('.member-row');
        
        rows.forEach(row => {
            const discord = row.querySelector('.member-discord').textContent.toLowerCase();
            const roblox = row.querySelector('.member-roblox').textContent.toLowerCase();
            
            if (discord.includes(input) || roblox.includes(input)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('modal-promote').addEventListener('click', function(e) {
        if (e.target === this) {
            closePromoteModal();
        }
    });
    </script>
</body>
</html>
