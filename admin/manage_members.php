<?php
require_once '../config.php';

// Vérifier que l'utilisateur est connecté et a les droits edit_members
if (!isAdmin() || !hasAccess('access_edit_members')) {
    header('Location: ../login.php');
    exit;
}

$success = '';
$error = '';

// Gestion des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    try {
        switch ($action) {
            case 'create_member':
                // Validation des champs requis
                if (empty($_POST['discord_pseudo']) || empty($_POST['roblox_pseudo']) || 
                    empty($_POST['grade']) || empty($_POST['rang'])) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis.");
                }
                
                // Vérifier si le pseudo existe déjà
                $stmt = $pdo->prepare("SELECT id FROM members WHERE discord_pseudo = ? OR roblox_pseudo = ?");
                $stmt->execute([$_POST['discord_pseudo'], $_POST['roblox_pseudo']]);
                if ($stmt->fetch()) {
                    throw new Exception("Un membre avec ce pseudo Discord ou Roblox existe déjà.");
                }
                
                $password = password_hash($_POST['password'] ?: 'Coalition', PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("INSERT INTO members 
                    (discord_pseudo, roblox_pseudo, password, kdr, grade, rang, legion_id, must_change_password) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    trim($_POST['discord_pseudo']),
                    trim($_POST['roblox_pseudo']),
                    $password,
                    floatval($_POST['kdr'] ?: 0),
                    trim($_POST['grade']),
                    trim($_POST['rang']),
                    !empty($_POST['legion_id']) ? intval($_POST['legion_id']) : null,
                    isset($_POST['must_change_password']) ? 1 : 0
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Création membre', $_POST['discord_pseudo']);
                $success = "Membre {$_POST['discord_pseudo']} créé avec succès !";
                break;
                
            case 'edit_member':
                // Validation des champs requis
                if (empty($_POST['member_id']) || empty($_POST['discord_pseudo']) || 
                    empty($_POST['roblox_pseudo']) || empty($_POST['grade']) || empty($_POST['rang'])) {
                    throw new Exception("Tous les champs obligatoires doivent être remplis.");
                }
                
                $member_id = intval($_POST['member_id']);
                
                // Vérifier que le membre existe
                $stmt = $pdo->prepare("SELECT id FROM members WHERE id = ?");
                $stmt->execute([$member_id]);
                if (!$stmt->fetch()) {
                    throw new Exception("Membre introuvable.");
                }
                
                $stmt = $pdo->prepare("UPDATE members SET 
                    discord_pseudo = ?, roblox_pseudo = ?, kdr = ?, grade = ?, rang = ?, legion_id = ?
                    WHERE id = ?");
                $stmt->execute([
                    trim($_POST['discord_pseudo']),
                    trim($_POST['roblox_pseudo']),
                    floatval($_POST['kdr']),
                    trim($_POST['grade']),
                    trim($_POST['rang']),
                    !empty($_POST['legion_id']) ? intval($_POST['legion_id']) : null,
                    $member_id
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification membre', "ID: $member_id");
                $success = "Membre modifié avec succès !";
                break;
                
            case 'delete_member':
                if (empty($_POST['member_id'])) {
                    throw new Exception("ID membre manquant.");
                }
                
                $member_id = intval($_POST['member_id']);
                $stmt = $pdo->prepare("SELECT discord_pseudo FROM members WHERE id = ?");
                $stmt->execute([$member_id]);
                $pseudo = $stmt->fetchColumn();
                
                if (!$pseudo) {
                    throw new Exception("Membre introuvable.");
                }
                
                $stmt = $pdo->prepare("DELETE FROM members WHERE id = ?");
                $stmt->execute([$member_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression membre', $pseudo);
                $success = "Membre supprimé avec succès !";
                break;
                
            case 'reset_password':
                if (empty($_POST['member_id'])) {
                    throw new Exception("ID membre manquant.");
                }
                
                $member_id = intval($_POST['member_id']);
                $new_password = $_POST['new_password'] ?: 'Coalition';
                
                $stmt = $pdo->prepare("UPDATE members SET password = ?, must_change_password = 1 WHERE id = ?");
                $stmt->execute([password_hash($new_password, PASSWORD_DEFAULT), $member_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Reset password membre', "ID: $member_id");
                $success = "Mot de passe réinitialisé avec succès !";
                break;
                
            case 'add_diplome':
                if (empty($_POST['member_id']) || empty($_POST['diplome_id'])) {
                    throw new Exception("Données manquantes.");
                }
                
                $member_id = intval($_POST['member_id']);
                $diplome_id = intval($_POST['diplome_id']);
                
                // Vérifier si le diplôme n'est pas déjà attribué
                $stmt = $pdo->prepare("SELECT id FROM member_diplomes WHERE member_id = ? AND diplome_id = ?");
                $stmt->execute([$member_id, $diplome_id]);
                if ($stmt->fetch()) {
                    throw new Exception("Ce diplôme est déjà attribué à ce membre.");
                }
                
                $stmt = $pdo->prepare("INSERT INTO member_diplomes (member_id, diplome_id) VALUES (?, ?)");
                $stmt->execute([$member_id, $diplome_id]);
                
                // Récupérer le nom du diplôme pour le log
                $stmt = $pdo->prepare("SELECT nom FROM diplomes WHERE id = ?");
                $stmt->execute([$diplome_id]);
                $diplome_nom = $stmt->fetchColumn();
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Attribution diplôme', "Member ID: $member_id - $diplome_nom");
                $success = "Diplôme attribué avec succès !";
                break;
                
            case 'remove_diplome':
                if (empty($_POST['member_diplome_id'])) {
                    throw new Exception("ID manquant.");
                }
                
                $member_diplome_id = intval($_POST['member_diplome_id']);
                
                $stmt = $pdo->prepare("DELETE FROM member_diplomes WHERE id = ?");
                $stmt->execute([$member_diplome_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Retrait diplôme', "Member Diplome ID: $member_diplome_id");
                $success = "Diplôme retiré avec succès !";
                break;
                
            case 'bulk_grade_update':
                $member_ids = $_POST['member_ids'] ?? [];
                $new_grade = $_POST['bulk_grade'];
                
                if (!empty($member_ids) && !empty($new_grade)) {
                    $placeholders = str_repeat('?,', count($member_ids) - 1) . '?';
                    $stmt = $pdo->prepare("UPDATE members SET grade = ? WHERE id IN ($placeholders)");
                    $stmt->execute(array_merge([$new_grade], $member_ids));
                    
                    logAdminAction($pdo, $_SESSION['user_id'], 'Modification grade en masse', count($member_ids) . " membres");
                    $success = count($member_ids) . " membre(s) mis à jour !";
                }
                break;
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Erreur de base de données : " . $e->getMessage();
        error_log("PDO Error in manage_members.php: " . $e->getMessage());
    }
}

// Récupérer les filtres
$search = $_GET['search'] ?? '';
$filter_legion = $_GET['legion'] ?? '';
$filter_grade = $_GET['grade'] ?? '';
$filter_rang = $_GET['rang'] ?? '';

// Construire la requête avec filtres
$query = "SELECT m.*, l.nom as legion_nom,
    (SELECT COUNT(*) FROM member_diplomes WHERE member_id = m.id) as diplome_count
    FROM members m 
    LEFT JOIN legions l ON m.legion_id = l.id 
    WHERE 1=1";

$params = [];

if ($search) {
    $query .= " AND (m.discord_pseudo LIKE ? OR m.roblox_pseudo LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($filter_legion) {
    $query .= " AND m.legion_id = ?";
    $params[] = $filter_legion;
}

if ($filter_grade) {
    $query .= " AND m.grade = ?";
    $params[] = $filter_grade;
}

if ($filter_rang) {
    $query .= " AND m.rang = ?";
    $params[] = $filter_rang;
}

$query .= " ORDER BY m.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Récupérer les données pour les selects
$legions = $pdo->query("SELECT * FROM legions ORDER BY nom")->fetchAll();
$diplomes = $pdo->query("SELECT * FROM diplomes ORDER BY categorie, niveau, nom")->fetchAll();
$diplomes_by_category = [];
foreach ($diplomes as $diplome) {
    $diplomes_by_category[$diplome['categorie']][] = $diplome;
}

// Statistiques
$stats = [
    'total_members' => $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn(),
    'total_diplomes_attribues' => $pdo->query("SELECT COUNT(*) FROM member_diplomes")->fetchColumn(),
    'members_with_legion' => $pdo->query("SELECT COUNT(*) FROM members WHERE legion_id IS NOT NULL")->fetchColumn(),
    'average_kdr' => $pdo->query("SELECT AVG(kdr) FROM members")->fetchColumn()
];

$grades = getGrades();
$rangs = getRangs();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Membres - CFWT Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href=".../css/all.min.css">
    <style>
        .member-card:hover { transform: translateY(-2px); transition: all 0.2s; }
    </style>
</head>
<body class="bg-gray-900">
    <?php include '../includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-users-cog text-blue-500 mr-3"></i>Gestion des Membres
                    </h1>
                    <p class="text-gray-400">Gérer tous les membres de la coalition</p>
                </div>
                <div class="flex space-x-4">
                    <button onclick="openCreateMemberModal()" 
                            class="bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-user-plus mr-2"></i>Nouveau Membre
                    </button>
                    <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                        <i class="fas fa-arrow-left mr-2"></i> Dashboard
                    </a>
                </div>
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

            <!-- Statistiques -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-300 text-sm">Total Membres</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_members']; ?></p>
                        </div>
                        <i class="fas fa-users text-blue-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Diplômes attribués</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_diplomes_attribues']; ?></p>
                        </div>
                        <i class="fas fa-graduation-cap text-green-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-900 to-purple-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-300 text-sm">Dans une légion</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['members_with_legion']; ?></p>
                        </div>
                        <i class="fas fa-shield-alt text-purple-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-yellow-900 to-yellow-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-yellow-300 text-sm">KDR Moyen</p>
                            <p class="text-white text-3xl font-bold"><?php echo number_format($stats['average_kdr'], 2); ?></p>
                        </div>
                        <i class="fas fa-crosshairs text-yellow-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Filtres et recherche -->
            <div class="bg-gray-800 p-6 rounded-lg mb-8">
                <form method="GET" class="grid md:grid-cols-5 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-gray-400 text-sm mb-2">Rechercher</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Pseudo Discord ou Roblox..."
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Légion</label>
                        <select name="legion" class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="">Toutes</option>
                            <?php foreach ($legions as $legion): ?>
                                <option value="<?php echo $legion['id']; ?>" <?php echo $filter_legion == $legion['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($legion['nom']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-gray-400 text-sm mb-2">Grade</label>
                        <select name="grade" class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="">Tous</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo $grade; ?>" <?php echo $filter_grade == $grade ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($grade); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="flex items-end space-x-2">
                        <button type="submit" class="flex-1 bg-blue-600 text-white px-4 py-3 rounded hover:bg-blue-700 transition">
                            <i class="fas fa-search mr-2"></i>Filtrer
                        </button>
                        <a href="manage_members.php" class="bg-gray-600 text-white px-4 py-3 rounded hover:bg-gray-700 transition">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                </form>
            </div>

            <!-- Liste des membres -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-2xl font-bold text-white">
                        <i class="fas fa-list mr-2"></i>Liste des membres (<?php echo count($members); ?>)
                    </h2>
                    <div class="flex space-x-2">
                        <button onclick="toggleView('grid')" id="btn-grid" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                            <i class="fas fa-th"></i>
                        </button>
                        <button onclick="toggleView('list')" id="btn-list" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 transition">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>

                <!-- Vue Grille -->
                <div id="view-grid" class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php foreach ($members as $member): ?>
                        <div class="member-card bg-gray-700 p-6 rounded-lg">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h3 class="text-xl font-bold text-white mb-1">
                                        <i class="fab fa-discord text-blue-400 mr-2"></i>
                                        <?php echo htmlspecialchars($member['discord_pseudo']); ?>
                                    </h3>
                                    <p class="text-gray-400 text-sm mb-2">
                                        <i class="fas fa-gamepad text-green-400 mr-2"></i>
                                        <?php echo htmlspecialchars($member['roblox_pseudo']); ?>
                                    </p>
                                </div>
                            </div>

                            <div class="space-y-2 mb-4">
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">Grade:</span>
                                    <span class="bg-blue-600 text-white px-3 py-1 rounded text-sm font-semibold">
                                        <?php echo htmlspecialchars($member['grade']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">Rang:</span>
                                    <span class="bg-green-600 text-white px-3 py-1 rounded text-sm font-semibold">
                                        <?php echo htmlspecialchars($member['rang']); ?>
                                    </span>
                                </div>
                                <div class="flex justify-between items-center">
                                    <span class="text-gray-400 text-sm">KDR:</span>
                                    <span class="text-yellow-400 font-bold">
                                        <?php echo number_format($member['kdr'], 2); ?>
                                    </span>
                                </div>
                                <?php if ($member['legion_nom']): ?>
                                    <div class="flex justify-between items-center">
                                        <span class="text-gray-400 text-sm">Légion:</span>
                                        <span class="text-purple-400 font-semibold text-sm">
                                            <i class="fas fa-shield-alt mr-1"></i>
                                            <?php echo htmlspecialchars($member['legion_nom']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="border-t border-gray-600 pt-3">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-gray-400">
                                        <i class="fas fa-graduation-cap mr-1"></i>
                                        <?php echo $member['diplome_count']; ?> diplôme(s)
                                    </span>
                                    <span class="text-gray-500 text-xs">
                                        <?php echo date('d/m/Y', strtotime($member['created_at'])); ?>
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Boutons d'action visibles -->
                            <div class="border-t border-gray-600 mt-3 pt-3 flex justify-between gap-2">
                                <button onclick='editMember(<?php echo htmlspecialchars(json_encode($member), ENT_QUOTES, 'UTF-8'); ?>)' 
                                        class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition"
                                        title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button onclick="manageDiplomes(<?php echo $member['id']; ?>, <?php echo htmlspecialchars(json_encode($member['discord_pseudo']), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        class="flex-1 bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm transition"
                                        title="Diplômes">
                                    <i class="fas fa-graduation-cap"></i>
                                </button>
                                <button onclick="resetMemberPassword(<?php echo $member['id']; ?>)" 
                                        class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white px-3 py-2 rounded text-sm transition"
                                        title="Reset MDP">
                                    <i class="fas fa-key"></i>
                                </button>
                                <button onclick="deleteMember(<?php echo $member['id']; ?>, <?php echo htmlspecialchars(json_encode($member['discord_pseudo']), ENT_QUOTES, 'UTF-8'); ?>)" 
                                        class="flex-1 bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded text-sm transition"
                                        title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Vue Liste -->
                <div id="view-list" class="hidden overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-700">
                            <tr>
                                <th class="px-4 py-3 text-left text-white">Membre</th>
                                <th class="px-4 py-3 text-left text-white">Grade</th>
                                <th class="px-4 py-3 text-left text-white">Rang</th>
                                <th class="px-4 py-3 text-center text-white">KDR</th>
                                <th class="px-4 py-3 text-left text-white">Légion</th>
                                <th class="px-4 py-3 text-center text-white">Diplômes</th>
                                <th class="px-4 py-3 text-center text-white">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-700">
                            <?php foreach ($members as $member): ?>
                                <tr class="hover:bg-gray-700">
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="text-white font-semibold"><?php echo htmlspecialchars($member['discord_pseudo']); ?></p>
                                            <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($member['roblox_pseudo']); ?></p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="bg-blue-600 text-white px-3 py-1 rounded text-sm">
                                            <?php echo htmlspecialchars($member['grade']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="bg-green-600 text-white px-3 py-1 rounded text-sm">
                                            <?php echo htmlspecialchars($member['rang']); ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-yellow-400 font-bold">
                                        <?php echo number_format($member['kdr'], 2); ?>
                                    </td>
                                    <td class="px-4 py-3 text-purple-400">
                                        <?php echo $member['legion_nom'] ? htmlspecialchars($member['legion_nom']) : '-'; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center text-gray-300">
                                        <?php echo $member['diplome_count']; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick='editMember(<?php echo htmlspecialchars(json_encode($member), ENT_QUOTES, 'UTF-8'); ?>)' 
                                                class="text-blue-400 hover:text-blue-300 mx-1" title="Modifier">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="manageDiplomes(<?php echo $member['id']; ?>, <?php echo htmlspecialchars(json_encode($member['discord_pseudo']), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="text-green-400 hover:text-green-300 mx-1" title="Diplômes">
                                            <i class="fas fa-graduation-cap"></i>
                                        </button>
                                        <button onclick="resetMemberPassword(<?php echo $member['id']; ?>)" 
                                                class="text-yellow-400 hover:text-yellow-300 mx-1" title="Reset MDP">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <button onclick="deleteMember(<?php echo $member['id']; ?>, <?php echo htmlspecialchars(json_encode($member['discord_pseudo']), ENT_QUOTES, 'UTF-8'); ?>)" 
                                                class="text-red-400 hover:text-red-300 mx-1" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($members)): ?>
                    <div class="text-center py-12">
                        <i class="fas fa-users text-gray-600 text-6xl mb-4"></i>
                        <p class="text-gray-400 text-xl">Aucun membre trouvé</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal: Créer membre -->
    <div id="modal-create-member" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Créer un nouveau membre</h2>
            <form method="POST" class="space-y-4" onsubmit="return validateCreateForm()">
                <input type="hidden" name="action" value="create_member">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Pseudo Discord *</label>
                        <input type="text" name="discord_pseudo" id="create-discord-pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                    <div>
                        <label class="block text-white mb-2">Pseudo Roblox *</label>
                        <input type="text" name="roblox_pseudo" id="create-roblox-pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Mot de passe (laisser vide = "Coalition")</label>
                    <input type="password" name="password" id="create-password" maxlength="255"
                           placeholder="Laisser vide pour mot de passe par défaut"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-white mb-2">Grade *</label>
                        <select name="grade" id="create-grade" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">Rang *</label>
                        <select name="rang" id="create-rang" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <?php foreach ($rangs as $rang): ?>
                                <option value="<?php echo htmlspecialchars($rang); ?>"><?php echo htmlspecialchars($rang); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">KDR</label>
                        <input type="number" name="kdr" id="create-kdr" step="0.01" min="0" value="0"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Légion (optionnel)</label>
                    <select name="legion_id" id="create-legion-id" class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="">Aucune légion</option>
                        <?php foreach ($legions as $legion): ?>
                            <option value="<?php echo $legion['id']; ?>"><?php echo htmlspecialchars($legion['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="flex items-center text-gray-300">
                        <input type="checkbox" name="must_change_password" value="1" checked class="mr-2">
                        Forcer le changement de mot de passe à la première connexion
                    </label>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-user-plus mr-2"></i>Créer le membre
                    </button>
                    <button type="button" onclick="closeCreateMemberModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Éditer membre -->
    <div id="modal-edit-member" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Modifier le membre</h2>
            <form method="POST" class="space-y-4" onsubmit="return validateEditForm()">
                <input type="hidden" name="action" value="edit_member">
                <input type="hidden" name="member_id" id="edit-member-id">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Pseudo Discord *</label>
                        <input type="text" name="discord_pseudo" id="edit-discord-pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                    <div>
                        <label class="block text-white mb-2">Pseudo Roblox *</label>
                        <input type="text" name="roblox_pseudo" id="edit-roblox-pseudo" required maxlength="100"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div class="grid md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-white mb-2">Grade *</label>
                        <select name="grade" id="edit-grade" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade); ?>"><?php echo htmlspecialchars($grade); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">Rang *</label>
                        <select name="rang" id="edit-rang" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <?php foreach ($rangs as $rang): ?>
                                <option value="<?php echo htmlspecialchars($rang); ?>"><?php echo htmlspecialchars($rang); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">KDR</label>
                        <input type="number" name="kdr" id="edit-kdr" step="0.01" min="0"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Légion</label>
                    <select name="legion_id" id="edit-legion-id" class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="">Aucune légion</option>
                        <?php foreach ($legions as $legion): ?>
                            <option value="<?php echo $legion['id']; ?>"><?php echo htmlspecialchars($legion['nom']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="closeEditMemberModal()" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Gérer diplômes -->
    <div id="modal-manage-diplomes" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-4xl w-full max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-2">Gérer les diplômes</h2>
            <p class="text-gray-400 mb-6" id="diplomes-member-name"></p>
            
            <div class="grid md:grid-cols-2 gap-6">
                <!-- Diplômes attribués -->
                <div>
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>Diplômes obtenus
                    </h3>
                    <div id="diplomes-attribues" class="space-y-2 max-h-96 overflow-y-auto">
                        <p class="text-gray-400 text-center py-4">Chargement...</p>
                    </div>
                </div>
                
                <!-- Ajouter diplôme -->
                <div>
                    <h3 class="text-xl font-bold text-white mb-4">
                        <i class="fas fa-plus-circle text-blue-500 mr-2"></i>Attribuer un diplôme
                    </h3>
                    <div class="space-y-4 max-h-96 overflow-y-auto">
                        <input type="hidden" id="diplomes-member-id">
                        <?php foreach ($diplomes_by_category as $category => $cat_diplomes): ?>
                            <div>
                                <h4 class="text-white font-semibold mb-2">
                                    <?php 
                                    echo $category == 'aerien' ? '✈️ Aérien' : 
                                         ($category == 'terrestre' ? '🎖️ Terrestre' : 
                                         ($category == 'aeronaval' ? '🚢 Aéronaval' : 
                                         ($category == 'formateur' ? '📚 Formateurs' : '⚔️ Élite')));
                                    ?>
                                </h4>
                                <div class="space-y-2">
                                    <?php foreach ($cat_diplomes as $diplome): ?>
                                        <button type="button" 
                                                onclick="addDiplomeToMember(<?php echo $diplome['id']; ?>)"
                                                class="w-full text-left p-3 bg-gray-700 hover:bg-gray-600 rounded transition">
                                            <div class="flex items-center justify-between">
                                                <div>
                                                    <p class="text-white font-semibold"><?php echo htmlspecialchars($diplome['nom']); ?></p>
                                                    <p class="text-gray-400 text-sm"><?php echo htmlspecialchars($diplome['code']); ?> - Niveau <?php echo $diplome['niveau']; ?></p>
                                                </div>
                                                <i class="fas fa-plus text-blue-400"></i>
                                            </div>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            
            <div class="mt-6">
                <button type="button" onclick="closeDiplomesModal()" 
                        class="w-full bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                    Fermer
                </button>
            </div>
        </div>
    </div>

    <script>
    let currentMemberId = null;

    // Fonctions de gestion des modals
    function openCreateMemberModal() {
        document.getElementById('modal-create-member').classList.remove('hidden');
    }

    function closeCreateMemberModal() {
        document.getElementById('modal-create-member').classList.add('hidden');
        // Reset form
        document.getElementById('create-discord-pseudo').value = '';
        document.getElementById('create-roblox-pseudo').value = '';
        document.getElementById('create-password').value = '';
        document.getElementById('create-kdr').value = '0';
    }

    function closeEditMemberModal() {
        document.getElementById('modal-edit-member').classList.add('hidden');
    }

    function closeDiplomesModal() {
        document.getElementById('modal-manage-diplomes').classList.add('hidden');
        currentMemberId = null;
    }

    // Validation des formulaires
    function validateCreateForm() {
        const discordPseudo = document.getElementById('create-discord-pseudo').value.trim();
        const robloxPseudo = document.getElementById('create-roblox-pseudo').value.trim();
        const grade = document.getElementById('create-grade').value;
        const rang = document.getElementById('create-rang').value;

        if (!discordPseudo || !robloxPseudo || !grade || !rang) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }
        return true;
    }

    function validateEditForm() {
        const discordPseudo = document.getElementById('edit-discord-pseudo').value.trim();
        const robloxPseudo = document.getElementById('edit-roblox-pseudo').value.trim();
        const grade = document.getElementById('edit-grade').value;
        const rang = document.getElementById('edit-rang').value;

        if (!discordPseudo || !robloxPseudo || !grade || !rang) {
            alert('Veuillez remplir tous les champs obligatoires.');
            return false;
        }
        return true;
    }

    function toggleView(view) {
        const gridView = document.getElementById('view-grid');
        const listView = document.getElementById('view-list');
        const btnGrid = document.getElementById('btn-grid');
        const btnList = document.getElementById('btn-list');
        
        if (view === 'grid') {
            gridView.classList.remove('hidden');
            listView.classList.add('hidden');
            btnGrid.classList.remove('bg-gray-600');
            btnGrid.classList.add('bg-blue-600');
            btnList.classList.remove('bg-blue-600');
            btnList.classList.add('bg-gray-600');
        } else {
            gridView.classList.add('hidden');
            listView.classList.remove('hidden');
            btnList.classList.remove('bg-gray-600');
            btnList.classList.add('bg-blue-600');
            btnGrid.classList.remove('bg-blue-600');
            btnGrid.classList.add('bg-gray-600');
        }
    }

    function toggleMenu(menuId) {
        // Fermer tous les autres menus
        document.querySelectorAll('[id^="menu-"]').forEach(menu => {
            if (menu.id !== menuId) menu.classList.add('hidden');
        });
        
        // Toggle le menu cliqué
        const menu = document.getElementById(menuId);
        menu.classList.toggle('hidden');
    }

    // Fermer les menus en cliquant ailleurs
    document.addEventListener('click', function(e) {
        if (!e.target.closest('button')) {
            document.querySelectorAll('[id^="menu-"]').forEach(menu => menu.classList.add('hidden'));
        }
    });

    function editMember(member) {
        document.getElementById('edit-member-id').value = member.id;
        document.getElementById('edit-discord-pseudo').value = member.discord_pseudo;
        document.getElementById('edit-roblox-pseudo').value = member.roblox_pseudo;
        document.getElementById('edit-grade').value = member.grade;
        document.getElementById('edit-rang').value = member.rang;
        document.getElementById('edit-kdr').value = member.kdr;
        document.getElementById('edit-legion-id').value = member.legion_id || '';
        
        document.getElementById('modal-edit-member').classList.remove('hidden');
    }

    function deleteMember(id, pseudo) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le membre "${pseudo}" ?\n\nCette action supprimera également tous ses diplômes.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_member">
                <input type="hidden" name="member_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function resetMemberPassword(id) {
        const newPassword = prompt('Entrez le nouveau mot de passe (laisser vide pour "Coalition") :');
        if (newPassword !== null) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="reset_password">
                <input type="hidden" name="member_id" value="${id}">
                <input type="hidden" name="new_password" value="${newPassword}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function manageDiplomes(memberId, memberName) {
        currentMemberId = memberId;
        document.getElementById('diplomes-member-id').value = memberId;
        document.getElementById('diplomes-member-name').textContent = 'Membre : ' + memberName;
        
        // Charger les diplômes du membre
        const container = document.getElementById('diplomes-attribues');
        container.innerHTML = '<p class="text-gray-400 text-center py-4">Chargement...</p>';
        
        fetch(`get_member_diplomes.php?member_id=${memberId}`)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur réseau');
                }
                return response.json();
            })
            .then(data => {
                if (data.length === 0) {
                    container.innerHTML = '<p class="text-gray-400 text-center py-4">Aucun diplôme obtenu</p>';
                } else {
                    container.innerHTML = data.map(diplome => `
                        <div class="bg-gray-700 p-3 rounded flex justify-between items-center">
                            <div>
                                <p class="text-white font-semibold">${diplome.nom}</p>
                                <p class="text-gray-400 text-sm">${diplome.code} - Niveau ${diplome.niveau}</p>
                                <p class="text-gray-500 text-xs">Obtenu le ${diplome.obtained_at}</p>
                            </div>
                            <button onclick="removeDiplomeFromMember(${diplome.member_diplome_id})" 
                                    class="text-red-400 hover:text-red-300 p-2" title="Retirer">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    `).join('');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                container.innerHTML = '<p class="text-red-400 text-center py-4">Erreur lors du chargement des diplômes</p>';
            });
        
        document.getElementById('modal-manage-diplomes').classList.remove('hidden');
    }

    function addDiplomeToMember(diplomeId) {
        if (!currentMemberId) {
            alert('Erreur: Aucun membre sélectionné');
            return;
        }
        
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="add_diplome">
            <input type="hidden" name="member_id" value="${currentMemberId}">
            <input type="hidden" name="diplome_id" value="${diplomeId}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function removeDiplomeFromMember(memberDiplomeId) {
        if (confirm('Retirer ce diplôme au membre ?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="remove_diplome">
                <input type="hidden" name="member_diplome_id" value="${memberDiplomeId}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Fermer les modals avec Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeCreateMemberModal();
            closeEditMemberModal();
            closeDiplomesModal();
        }
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

