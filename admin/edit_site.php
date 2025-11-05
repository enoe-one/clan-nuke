<?php
require_once '../config.php';

// Vérifier que l'utilisateur est connecté et a les droits edit_site
if (!isAdmin() || !hasAccess('access_edit_site')) {
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
            case 'add_diplome':
                $stmt = $pdo->prepare("INSERT INTO diplomes (code, nom, description, categorie, niveau, prerequis) 
                    VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $_POST['code'],
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['categorie'],
                    $_POST['niveau'],
                    $_POST['prerequis'] ?: null
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Ajout diplôme', $_POST['nom']);
                $success = "Diplôme ajouté avec succès !";
                break;
                
            case 'edit_diplome':
                $stmt = $pdo->prepare("UPDATE diplomes SET code = ?, nom = ?, description = ?, 
                    categorie = ?, niveau = ?, prerequis = ? WHERE id = ?");
                $stmt->execute([
                    $_POST['code'],
                    $_POST['nom'],
                    $_POST['description'],
                    $_POST['categorie'],
                    $_POST['niveau'],
                    $_POST['prerequis'] ?: null,
                    $_POST['diplome_id']
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification diplôme', $_POST['nom']);
                $success = "Diplôme modifié avec succès !";
                break;
                
            case 'delete_diplome':
                $diplome_id = $_POST['diplome_id'];
                $stmt = $pdo->prepare("SELECT nom FROM diplomes WHERE id = ?");
                $stmt->execute([$diplome_id]);
                $nom = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM diplomes WHERE id = ?");
                $stmt->execute([$diplome_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression diplôme', $nom);
                $success = "Diplôme supprimé avec succès !";
                break;
                
            case 'update_discord':
                // Modifier directement dans config.php (simplifié)
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification lien Discord', $_POST['discord_invite']);
                $success = "Lien Discord enregistré ! Modifiez manuellement config.php pour l'appliquer.";
                break;
                
            case 'create_announcement':
                $stmt = $pdo->prepare("INSERT INTO announcements (title, content, type, active, created_by) 
                    VALUES (?, ?, ?, 1, ?)");
                $stmt->execute([
                    $_POST['title'],
                    $_POST['content'],
                    $_POST['type'],
                    $_SESSION['user_id']
                ]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Création annonce', $_POST['title']);
                $success = "Annonce créée avec succès !";
                break;
                
            case 'delete_announcement':
                $ann_id = $_POST['announcement_id'];
                $stmt = $pdo->prepare("SELECT title FROM announcements WHERE id = ?");
                $stmt->execute([$ann_id]);
                $title = $stmt->fetchColumn();
                
                $stmt = $pdo->prepare("DELETE FROM announcements WHERE id = ?");
                $stmt->execute([$ann_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Suppression annonce', $title);
                $success = "Annonce supprimée avec succès !";
                break;
                
            case 'toggle_announcement':
                $ann_id = $_POST['announcement_id'];
                $stmt = $pdo->prepare("UPDATE announcements SET active = NOT active WHERE id = ?");
                $stmt->execute([$ann_id]);
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Toggle annonce', "ID: $ann_id");
                $success = "Statut de l'annonce modifié !";
                break;
                
            case 'update_appearance':
                // Sauvegarder les paramètres d'apparence
                $settings = [
                    'site_title' => $_POST['site_title'],
                    'site_description' => $_POST['site_description'],
                    'primary_color' => $_POST['primary_color'],
                    'secondary_color' => $_POST['secondary_color'],
                    'accent_color' => $_POST['accent_color'],
                    'background_style' => $_POST['background_style'],
                    'show_stats_home' => isset($_POST['show_stats_home']) ? 1 : 0,
                    'show_latest_members' => isset($_POST['show_latest_members']) ? 1 : 0,
                    'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0
                ];
                
                foreach ($settings as $key => $value) {
                    $stmt = $pdo->prepare("INSERT INTO site_content (page, section, content, updated_by, updated_at) 
                        VALUES ('appearance', ?, ?, ?, NOW()) 
                        ON DUPLICATE KEY UPDATE content = ?, updated_by = ?, updated_at = NOW()");
                    $stmt->execute([$key, $value, $_SESSION['user_id'], $value, $_SESSION['user_id']]);
                }
                
                logAdminAction($pdo, $_SESSION['user_id'], 'Modification apparence', 'Paramètres mis à jour');
                $success = "Apparence mise à jour avec succès !";
                break;
                
case 'upload_logo':
    try {
        // Définir le chemin fixe de l'icône
        $fixed_path = 'admin/f565469d-93e6-4bed-85a6-39cbb3d2d70e.png';

        // Sauvegarder le chemin du logo dans la base
        $stmt = $pdo->prepare("
            INSERT INTO site_content (page, section, content, updated_by, updated_at) 
            VALUES ('appearance', 'logo_path', ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE content = ?, updated_by = ?, updated_at = NOW()
        ");
        $stmt->execute([$fixed_path, $_SESSION['user_id'], $fixed_path, $_SESSION['user_id']]);

        // Journaliser l’action admin
        logAdminAction($pdo, $_SESSION['user_id'], 'Définir logo', $fixed_path);

        $success = "✅ Logo défini sur l’icône par défaut ($fixed_path)";
    } catch (Exception $e) {
        $error = $e->getMessage();
    } catch (PDOException $e) {
        $error = "Erreur de base de données : " . $e->getMessage();
    }
    break;


// Créer les tables si elles n'existent pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
        active BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS site_content (
        id INT AUTO_INCREMENT PRIMARY KEY,
        page VARCHAR(50) NOT NULL,
        section VARCHAR(50) NOT NULL,
        content TEXT,
        updated_by INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY page_section (page, section),
        FOREIGN KEY (updated_by) REFERENCES users(id)
    )");
} catch (PDOException $e) {
    // Tables déjà créées
}

// Récupérer les paramètres d'apparence
$appearance_settings = [];
$stmt = $pdo->query("SELECT section, content FROM site_content WHERE page = 'appearance'");
while ($row = $stmt->fetch()) {
    $appearance_settings[$row['section']] = $row['content'];
}

// Valeurs par défaut
$defaults = [
    'site_title' => 'CFWT - Coalition Française de Wars Tycoon',
    'site_description' => 'Rejoignez la plus grande coalition francophone de Wars Tycoon',
    'primary_color' => '#dc2626',
    'secondary_color' => '#2563eb',
    'accent_color' => '#7c3aed',
    'background_style' => 'gradient',
    'show_stats_home' => '1',
    'show_latest_members' => '1',
    'maintenance_mode' => '0',
    'logo_path' => ''
];

foreach ($defaults as $key => $value) {
    if (!isset($appearance_settings[$key])) {
        $appearance_settings[$key] = $value;
    }
}

// Récupérer les données
$diplomes = $pdo->query("SELECT * FROM diplomes ORDER BY categorie, niveau, code")->fetchAll();
$announcements = $pdo->query("SELECT a.*, u.username 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC")->fetchAll();

$diplomes_by_category = [
    'aerien' => [],
    'terrestre' => [],
    'aeronaval' => [],
    'formateur' => [],
    'elite' => []
];

foreach ($diplomes as $diplome) {
    $diplomes_by_category[$diplome['categorie']][] = $diplome;
}

$category_names = [
    'aerien' => '✈️ Aérien',
    'terrestre' => '🎖️ Terrestre',
    'aeronaval' => '🚢 Aéronaval et Naval',
    'formateur' => '📚 Formateurs',
    'elite' => '⚔️ Forces d\'Élite'
];

// Statistiques
$stats = [
    'total_diplomes' => $pdo->query("SELECT COUNT(*) FROM diplomes")->fetchColumn(),
    'total_announcements' => $pdo->query("SELECT COUNT(*) FROM announcements WHERE active = 1")->fetchColumn(),
    'diplomes_aerien' => count($diplomes_by_category['aerien']),
    'diplomes_terrestre' => count($diplomes_by_category['terrestre']),
    'diplomes_aeronaval' => count($diplomes_by_category['aeronaval'])
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Édition Site - CFWT Admin</title>
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
                        <i class="fas fa-paint-brush text-purple-500 mr-3"></i>Édition du Site
                    </h1>
                    <p class="text-gray-400">Personnalisation et gestion du contenu</p>
                </div>
                <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                    <i class="fas fa-arrow-left mr-2"></i> Retour au Dashboard
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

            <!-- Statistiques -->
            <div class="grid md:grid-cols-4 gap-6 mb-8">
                <div class="bg-gradient-to-br from-blue-900 to-blue-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-blue-300 text-sm">Total Diplômes</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_diplomes']; ?></p>
                        </div>
                        <i class="fas fa-graduation-cap text-blue-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-purple-900 to-purple-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-purple-300 text-sm">Aérien</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['diplomes_aerien']; ?></p>
                        </div>
                        <i class="fas fa-plane text-purple-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-green-900 to-green-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-green-300 text-sm">Terrestre</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['diplomes_terrestre']; ?></p>
                        </div>
                        <i class="fas fa-tank text-green-400 text-4xl"></i>
                    </div>
                </div>
                
                <div class="bg-gradient-to-br from-cyan-900 to-cyan-800 p-6 rounded-lg">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-300 text-sm">Annonces Actives</p>
                            <p class="text-white text-3xl font-bold"><?php echo $stats['total_announcements']; ?></p>
                        </div>
                        <i class="fas fa-bullhorn text-cyan-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <!-- Tabs -->
            <div class="bg-gray-800 rounded-lg p-6">
                <div class="flex space-x-4 border-b border-gray-700 mb-6 overflow-x-auto">
                    <button onclick="showTab('diplomes')" id="tab-diplomes" class="tab-button px-6 py-3 font-semibold text-white border-b-2 border-purple-500 whitespace-nowrap">
                        <i class="fas fa-graduation-cap mr-2"></i>Diplômes
                    </button>
                    <button onclick="showTab('announcements')" id="tab-announcements" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white whitespace-nowrap">
                        <i class="fas fa-bullhorn mr-2"></i>Annonces
                    </button>
                    <button onclick="showTab('config')" id="tab-config" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white whitespace-nowrap">
                        <i class="fas fa-cog mr-2"></i>Configuration
                    </button>
                    <button onclick="showTab('appearance')" id="tab-appearance" class="tab-button px-6 py-3 font-semibold text-gray-400 hover:text-white whitespace-nowrap">
                        <i class="fas fa-palette mr-2"></i>Apparence
                    </button>
                </div>

                <!-- Tab: Diplômes -->
                <div id="content-diplomes" class="tab-content">
                    <div class="mb-6">
                        <button onclick="document.getElementById('modal-add-diplome').classList.remove('hidden')" 
                                class="bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                            <i class="fas fa-plus mr-2"></i>Ajouter un diplôme
                        </button>
                    </div>

                    <?php foreach ($diplomes_by_category as $category => $cat_diplomes): ?>
                        <?php if (!empty($cat_diplomes)): ?>
                            <div class="mb-8">
                                <h2 class="text-2xl font-bold text-white mb-4"><?php echo $category_names[$category]; ?></h2>
                                
                                <div class="space-y-3">
                                    <?php foreach ($cat_diplomes as $diplome): ?>
                                        <div class="bg-gray-700 p-4 rounded-lg flex justify-between items-start">
                                            <div class="flex-1">
                                                <div class="flex items-center gap-3 mb-2">
                                                    <span class="bg-gray-600 text-gray-300 px-3 py-1 rounded text-sm font-mono">
                                                        <?php echo htmlspecialchars($diplome['code']); ?>
                                                    </span>
                                                    <span class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                                        Niveau <?php echo $diplome['niveau']; ?>
                                                    </span>
                                                </div>
                                                <h3 class="text-lg font-bold text-white mb-1">
                                                    <?php echo htmlspecialchars($diplome['nom']); ?>
                                                </h3>
                                                <p class="text-gray-300 text-sm mb-2">
                                                    <?php echo htmlspecialchars($diplome['description']); ?>
                                                </p>
                                                <?php if ($diplome['prerequis']): ?>
                                                    <p class="text-yellow-400 text-sm">
                                                        <i class="fas fa-lock mr-2"></i>
                                                        Prérequis : <?php echo htmlspecialchars($diplome['prerequis']); ?>
                                                    </p>
                                                <?php endif; ?>
                                            </div>
                                            <div class="flex space-x-2 ml-4">
                                                <button onclick="editDiplome(<?php echo htmlspecialchars(json_encode($diplome)); ?>)" 
                                                        class="text-blue-400 hover:text-blue-300 p-2">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button onclick="deleteDiplome(<?php echo $diplome['id']; ?>, '<?php echo htmlspecialchars($diplome['nom']); ?>')" 
                                                        class="text-red-400 hover:text-red-300 p-2">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Tab: Annonces -->
                <div id="content-announcements" class="tab-content hidden">
                    <div class="mb-6">
                        <button onclick="document.getElementById('modal-create-announcement').classList.remove('hidden')" 
                                class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                            <i class="fas fa-plus mr-2"></i>Créer une annonce
                        </button>
                    </div>

                    <div class="space-y-4">
                        <?php foreach ($announcements as $announcement): ?>
                            <div class="bg-gray-700 p-6 rounded-lg">
                                <div class="flex justify-between items-start mb-3">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-3 mb-2">
                                            <h3 class="text-xl font-bold text-white">
                                                <?php echo htmlspecialchars($announcement['title']); ?>
                                            </h3>
                                            <span class="px-3 py-1 rounded text-sm font-semibold
                                                <?php 
                                                echo $announcement['type'] == 'info' ? 'bg-blue-600 text-white' :
                                                     ($announcement['type'] == 'warning' ? 'bg-yellow-600 text-white' :
                                                     ($announcement['type'] == 'success' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'));
                                                ?>">
                                                <?php echo htmlspecialchars($announcement['type']); ?>
                                            </span>
                                            <?php if ($announcement['active']): ?>
                                                <span class="bg-green-600 text-white px-2 py-1 rounded text-xs">Active</span>
                                            <?php else: ?>
                                                <span class="bg-gray-600 text-white px-2 py-1 rounded text-xs">Inactive</span>
                                            <?php endif; ?>
                                        </div>
                                        <p class="text-gray-300 mb-2">
                                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                                        </p>
                                        <p class="text-gray-500 text-sm">
                                            Par <?php echo htmlspecialchars($announcement['username']); ?> 
                                            le <?php echo date('d/m/Y H:i', strtotime($announcement['created_at'])); ?>
                                        </p>
                                    </div>
                                    <div class="flex space-x-2 ml-4">
                                        <button onclick="toggleAnnouncement(<?php echo $announcement['id']; ?>)" 
                                                class="text-yellow-400 hover:text-yellow-300 p-2" title="Activer/Désactiver">
                                            <i class="fas fa-<?php echo $announcement['active'] ? 'eye-slash' : 'eye'; ?>"></i>
                                        </button>
                                        <button onclick="deleteAnnouncement(<?php echo $announcement['id']; ?>, '<?php echo htmlspecialchars($announcement['title']); ?>')" 
                                                class="text-red-400 hover:text-red-300 p-2">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>

                        <?php if (empty($announcements)): ?>
                            <div class="bg-gray-700 p-12 rounded-lg text-center">
                                <i class="fas fa-bullhorn text-gray-600 text-6xl mb-4"></i>
                                <p class="text-gray-400 text-xl">Aucune annonce pour le moment</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tab: Configuration -->
                <div id="content-config" class="tab-content hidden">
                    <div class="grid md:grid-cols-2 gap-6">
                        <!-- Discord -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fab fa-discord text-blue-500 mr-2"></i>Lien Discord
                            </h3>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="action" value="update_discord">
                                <div>
                                    <label class="block text-gray-300 mb-2">URL d'invitation Discord</label>
                                    <input type="url" name="discord_invite" required 
                                           value="<?php echo DISCORD_INVITE; ?>"
                                           class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                </div>
                                <button type="submit" class="w-full bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                                    <i class="fas fa-save mr-2"></i>Mettre à jour
                                </button>
                            </form>
                        </div>

                        <!-- Info Site -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fas fa-info-circle text-green-500 mr-2"></i>Informations Site
                            </h3>
                            <div class="space-y-3 text-gray-300">
                                <div>
                                    <p class="text-sm text-gray-500">URL du site</p>
                                    <p class="font-semibold"><?php echo SITE_URL; ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Version PHP</p>
                                    <p class="font-semibold"><?php echo phpversion(); ?></p>
                                </div>
                                <div>
                                    <p class="text-sm text-gray-500">Base de données</p>
                                    <p class="font-semibold"><?php echo DB_NAME; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab: Apparence -->
                <div id="content-appearance" class="tab-content hidden">
                    <form method="POST" enctype="multipart/form-data" class="space-y-8">
                        <input type="hidden" name="action" value="update_appearance">
                        
                        <!-- Informations générales -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-info-circle text-blue-500 mr-2"></i>Informations du site
                            </h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-white mb-2">Titre du site</label>
                                    <input type="text" name="site_title" 
                                           value="<?php echo htmlspecialchars($appearance_settings['site_title']); ?>"
                                           class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500">
                                </div>
                                <div>
                                    <label class="block text-white mb-2">Description du site</label>
                                    <textarea name="site_description" rows="3"
                                              class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500"><?php echo htmlspecialchars($appearance_settings['site_description']); ?></textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Logo -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-image text-green-500 mr-2"></i>Logo du site
                            </h3>
                            
                            <?php if ($appearance_settings['logo_path']): ?>
                                <div class="mb-4">
                                    <p class="text-gray-400 mb-2">Logo actuel :</p>
                                    <img src="../uploads/<?php echo htmlspecialchars($appearance_settings['logo_path']); ?>" 
                                         alt="Logo" class="max-h-32 bg-white p-4 rounded">
                                </div>
                            <?php endif; ?>
                            
                            <div class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center">
                                <i class="fas fa-cloud-upload-alt text-4xl text-gray-500 mb-3"></i>
                                <p class="text-gray-400 mb-2">Changer le logo (JPG, PNG, SVG, GIF)</p>
                                <input type="file" name="logo" accept="image/*" 
                                       class="hidden" id="logo-input"
                                       onchange="this.form.action.value='upload_logo'; this.form.submit();">
                                <label for="logo-input" 
                                       class="inline-block bg-blue-600 text-white px-6 py-2 rounded cursor-pointer hover:bg-blue-700 transition">
                                    Choisir un fichier
                                </label>
                                <p class="text-gray-500 text-sm mt-2">Max 5MB</p>
                            </div>
                        </div>

                        <!-- Couleurs -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-palette text-purple-500 mr-2"></i>Palette de couleurs
                            </h3>
                            <div class="grid md:grid-cols-3 gap-6">
                                <div>
                                    <label class="block text-white mb-2">Couleur primaire</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" name="primary_color" 
                                               value="<?php echo htmlspecialchars($appearance_settings['primary_color']); ?>"
                                               class="w-16 h-16 rounded cursor-pointer">
                                        <div class="flex-1">
                                            <input type="text" 
                                                   value="<?php echo htmlspecialchars($appearance_settings['primary_color']); ?>"
                                                   class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500 font-mono"
                                                   readonly>
                                            <p class="text-gray-400 text-sm mt-1">Utilisé pour les éléments principaux</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-white mb-2">Couleur secondaire</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" name="secondary_color" 
                                               value="<?php echo htmlspecialchars($appearance_settings['secondary_color']); ?>"
                                               class="w-16 h-16 rounded cursor-pointer">
                                        <div class="flex-1">
                                            <input type="text" 
                                                   value="<?php echo htmlspecialchars($appearance_settings['secondary_color']); ?>"
                                                   class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500 font-mono"
                                                   readonly>
                                            <p class="text-gray-400 text-sm mt-1">Pour les boutons et liens</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="block text-white mb-2">Couleur d'accent</label>
                                    <div class="flex items-center space-x-3">
                                        <input type="color" name="accent_color" 
                                               value="<?php echo htmlspecialchars($appearance_settings['accent_color']); ?>"
                                               class="w-16 h-16 rounded cursor-pointer">
                                        <div class="flex-1">
                                            <input type="text" 
                                                   value="<?php echo htmlspecialchars($appearance_settings['accent_color']); ?>"
                                                   class="w-full p-3 rounded bg-gray-600 text-white border border-gray-500 font-mono"
                                                   readonly>
                                            <p class="text-gray-400 text-sm mt-1">Pour les highlights</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mt-6 p-4 bg-gray-800 rounded">
                                <p class="text-gray-400 mb-3"><i class="fas fa-info-circle mr-2"></i>Aperçu des couleurs :</p>
                                <div class="flex space-x-3">
                                    <div class="flex-1 p-4 rounded text-white font-semibold text-center"
                                         style="background-color: <?php echo htmlspecialchars($appearance_settings['primary_color']); ?>">
                                        Primaire
                                    </div>
                                    <div class="flex-1 p-4 rounded text-white font-semibold text-center"
                                         style="background-color: <?php echo htmlspecialchars($appearance_settings['secondary_color']); ?>">
                                        Secondaire
                                    </div>
                                    <div class="flex-1 p-4 rounded text-white font-semibold text-center"
                                         style="background-color: <?php echo htmlspecialchars($appearance_settings['accent_color']); ?>">
                                        Accent
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Style du fond -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-image text-yellow-500 mr-2"></i>Style d'arrière-plan
                            </h3>
                            <div class="grid md:grid-cols-3 gap-4">
                                <?php 
                                $bg_styles = [
                                    'solid' => ['Couleur unie', 'bg-gray-900'],
                                    'gradient' => ['Dégradé', 'bg-gradient-to-br from-gray-900 to-gray-800'],
                                    'pattern' => ['Motif', 'bg-gray-900']
                                ];
                                
                                foreach ($bg_styles as $value => $data):
                                    $selected = $appearance_settings['background_style'] == $value;
                                ?>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="background_style" value="<?php echo $value; ?>" 
                                               <?php echo $selected ? 'checked' : ''; ?>
                                               class="hidden peer">
                                        <div class="p-6 rounded-lg border-2 transition <?php echo $data[1]; ?>
                                                    peer-checked:border-blue-500 peer-checked:shadow-lg
                                                    border-gray-600 hover:border-gray-500">
                                            <p class="text-white font-semibold text-center mb-2"><?php echo $data[0]; ?></p>
                                            <div class="h-24 rounded <?php echo $data[1]; ?>"></div>
                                        </div>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Options d'affichage -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-toggle-on text-cyan-500 mr-2"></i>Options d'affichage
                            </h3>
                            <div class="space-y-4">
                                <label class="flex items-center justify-between p-4 bg-gray-800 rounded cursor-pointer hover:bg-gray-750 transition">
                                    <div class="flex items-center">
                                        <i class="fas fa-chart-bar text-blue-400 text-xl mr-3"></i>
                                        <div>
                                            <p class="text-white font-semibold">Afficher les statistiques sur l'accueil</p>
                                            <p class="text-gray-400 text-sm">Total membres, diplômes, légions...</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="show_stats_home" value="1" 
                                           <?php echo $appearance_settings['show_stats_home'] ? 'checked' : ''; ?>
                                           class="w-6 h-6 rounded">
                                </label>
                                
                                <label class="flex items-center justify-between p-4 bg-gray-800 rounded cursor-pointer hover:bg-gray-750 transition">
                                    <div class="flex items-center">
                                        <i class="fas fa-users text-green-400 text-xl mr-3"></i>
                                        <div>
                                            <p class="text-white font-semibold">Afficher les derniers membres</p>
                                            <p class="text-gray-400 text-sm">Les 5 membres les plus récents</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="show_latest_members" value="1" 
                                           <?php echo $appearance_settings['show_latest_members'] ? 'checked' : ''; ?>
                                           class="w-6 h-6 rounded">
                                </label>
                                
                                <label class="flex items-center justify-between p-4 bg-red-900 bg-opacity-30 rounded cursor-pointer hover:bg-opacity-40 transition border-2 border-red-500">
                                    <div class="flex items-center">
                                        <i class="fas fa-tools text-red-400 text-xl mr-3"></i>
                                        <div>
                                            <p class="text-white font-semibold">Mode maintenance</p>
                                            <p class="text-red-300 text-sm">⚠️ Désactive l'accès au site pour les visiteurs</p>
                                        </div>
                                    </div>
                                    <input type="checkbox" name="maintenance_mode" value="1" 
                                           <?php echo $appearance_settings['maintenance_mode'] ? 'checked' : ''; ?>
                                           class="w-6 h-6 rounded">
                                </label>
                            </div>
                        </div>

                        <!-- Prévisualisation -->
                        <div class="bg-gray-700 p-6 rounded-lg">
                            <h3 class="text-xl font-bold text-white mb-6">
                                <i class="fas fa-eye text-pink-500 mr-2"></i>Prévisualisation
                            </h3>
                            <div class="bg-gray-900 p-8 rounded-lg">
                                <div class="max-w-4xl mx-auto">
                                    <div class="text-center mb-8">
                                        <?php if ($appearance_settings['logo_path']): ?>
                                            <img src="../uploads/<?php echo htmlspecialchars($appearance_settings['logo_path']); ?>" 
                                                 alt="Logo" class="h-16 mx-auto mb-4">
                                        <?php else: ?>
                                            <i class="fas fa-shield-alt text-6xl mb-4" 
                                               style="color: <?php echo htmlspecialchars($appearance_settings['primary_color']); ?>"></i>
                                        <?php endif; ?>
                                        <h2 class="text-3xl font-bold text-white mb-2">
                                            <?php echo htmlspecialchars($appearance_settings['site_title']); ?>
                                        </h2>
                                        <p class="text-gray-400">
                                            <?php echo htmlspecialchars($appearance_settings['site_description']); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="grid md:grid-cols-3 gap-4 mb-6">
                                        <div class="p-4 rounded text-center"
                                             style="background-color: <?php echo htmlspecialchars($appearance_settings['primary_color']); ?>">
                                            <p class="text-white font-bold text-2xl">125</p>
                                            <p class="text-white text-sm">Membres</p>
                                        </div>
                                        <div class="p-4 rounded text-center"
                                             style="background-color: <?php echo htmlspecialchars($appearance_settings['secondary_color']); ?>">
                                            <p class="text-white font-bold text-2xl">35</p>
                                            <p class="text-white text-sm">Diplômes</p>
                                        </div>
                                        <div class="p-4 rounded text-center"
                                             style="background-color: <?php echo htmlspecialchars($appearance_settings['accent_color']); ?>">
                                            <p class="text-white font-bold text-2xl">5</p>
                                            <p class="text-white text-sm">Légions</p>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <button type="button" class="px-8 py-3 rounded-lg text-white font-bold"
                                                style="background: linear-gradient(135deg, 
                                                    <?php echo htmlspecialchars($appearance_settings['primary_color']); ?>, 
                                                    <?php echo htmlspecialchars($appearance_settings['secondary_color']); ?>)">
                                            Nous Rejoindre
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bouton de sauvegarde -->
                        <div class="flex space-x-4">
                            <button type="submit" class="flex-1 bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-green-700 hover:to-green-800 transition">
                                <i class="fas fa-save mr-2"></i>Enregistrer l'apparence
                            </button>
                            <button type="button" onclick="resetAppearance()" 
                                    class="bg-gray-600 text-white px-8 py-4 rounded-lg font-bold hover:bg-gray-700 transition">
                                <i class="fas fa-undo mr-2"></i>Réinitialiser
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals précédents (diplômes et annonces) -->
    <!-- Modal: Ajouter diplôme -->
    <div id="modal-add-diplome" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Ajouter un diplôme</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="add_diplome">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Code *</label>
                        <input type="text" name="code" required maxlength="20"
                               placeholder="Ex: PAMA"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                    <div>
                        <label class="block text-white mb-2">Catégorie *</label>
                        <select name="categorie" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="aerien">Aérien</option>
                            <option value="terrestre">Terrestre</option>
                            <option value="aeronaval">Aéronaval</option>
                            <option value="formateur">Formateur</option>
                            <option value="elite">Élite</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Nom *</label>
                    <input type="text" name="nom" required maxlength="255"
                           placeholder="Ex: Pilote Aviation Mobile et Armée"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Description *</label>
                    <textarea name="description" required rows="3"
                              placeholder="Description du diplôme..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Niveau *</label>
                        <select name="niveau" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="1">Niveau 1</option>
                            <option value="2">Niveau 2</option>
                            <option value="3">Niveau 3</option>
                            <option value="4">Niveau 4</option>
                            <option value="5">Niveau 5</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">Prérequis (optionnel)</label>
                        <input type="text" name="prerequis" maxlength="255"
                               placeholder="Ex: PAMA"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-lg hover:bg-purple-700 transition">
                        <i class="fas fa-plus mr-2"></i>Ajouter
                    </button>
                    <button type="button" onclick="document.getElementById('modal-add-diplome').classList.add('hidden')" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Éditer diplôme -->
    <div id="modal-edit-diplome" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <h2 class="text-2xl font-bold text-white mb-6">Modifier le diplôme</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="edit_diplome">
                <input type="hidden" name="diplome_id" id="edit-diplome-id">
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Code *</label>
                        <input type="text" name="code" id="edit-code" required maxlength="20"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                    <div>
                        <label class="block text-white mb-2">Catégorie *</label>
                        <select name="categorie" id="edit-categorie" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="aerien">Aérien</option>
                            <option value="terrestre">Terrestre</option>
                            <option value="aeronaval">Aéronaval</option>
                            <option value="formateur">Formateur</option>
                            <option value="elite">Élite</option>
                        </select>
                    </div>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Nom *</label>
                    <input type="text" name="nom" id="edit-nom" required maxlength="255"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Description *</label>
                    <textarea name="description" id="edit-description" required rows="3"
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                </div>
                
                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-white mb-2">Niveau *</label>
                        <select name="niveau" id="edit-niveau" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                            <option value="1">Niveau 1</option>
                            <option value="2">Niveau 2</option>
                            <option value="3">Niveau 3</option>
                            <option value="4">Niveau 4</option>
                            <option value="5">Niveau 5</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-white mb-2">Prérequis (optionnel)</label>
                        <input type="text" name="prerequis" id="edit-prerequis" maxlength="255"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                    </div>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-save mr-2"></i>Enregistrer
                    </button>
                    <button type="button" onclick="document.getElementById('modal-edit-diplome').classList.add('hidden')" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Créer annonce -->
    <div id="modal-create-announcement" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 p-8 rounded-lg max-w-2xl w-full">
            <h2 class="text-2xl font-bold text-white mb-6">Créer une annonce</h2>
            <form method="POST" class="space-y-4">
                <input type="hidden" name="action" value="create_announcement">
                
                <div>
                    <label class="block text-white mb-2">Titre *</label>
                    <input type="text" name="title" required maxlength="255"
                           placeholder="Ex: Nouvelle mise à jour"
                           class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                </div>
                
                <div>
                    <label class="block text-white mb-2">Contenu *</label>
                    <textarea name="content" required rows="4"
                              placeholder="Contenu de l'annonce..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600"></textarea>
                </div>
                
                <div>
                    <label class="block text-white mb-2">Type *</label>
                    <select name="type" required 
                            class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600">
                        <option value="info">Information (Bleu)</option>
                        <option value="warning">Avertissement (Jaune)</option>
                        <option value="success">Succès (Vert)</option>
                        <option value="danger">Important (Rouge)</option>
                    </select>
                </div>
                
                <div class="flex space-x-4">
                    <button type="submit" class="flex-1 bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Créer
                    </button>
                    <button type="button" onclick="document.getElementById('modal-create-announcement').classList.add('hidden')" 
                            class="flex-1 bg-gray-600 text-white px-6 py-3 rounded-lg hover:bg-gray-700 transition">
                        Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function showTab(tab) {
        document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-button').forEach(el => {
            el.classList.remove('border-purple-500', 'text-white');
            el.classList.add('text-gray-400');
        });
        
        document.getElementById('content-' + tab).classList.remove('hidden');
        const tabBtn = document.getElementById('tab-' + tab);
        tabBtn.classList.add('border-purple-500', 'text-white');
        tabBtn.classList.remove('text-gray-400');
    }

    function editDiplome(diplome) {
        document.getElementById('edit-diplome-id').value = diplome.id;
        document.getElementById('edit-code').value = diplome.code;
        document.getElementById('edit-nom').value = diplome.nom;
        document.getElementById('edit-description').value = diplome.description;
        document.getElementById('edit-categorie').value = diplome.categorie;
        document.getElementById('edit-niveau').value = diplome.niveau;
        document.getElementById('edit-prerequis').value = diplome.prerequis || '';
        
        document.getElementById('modal-edit-diplome').classList.remove('hidden');
    }

    function deleteDiplome(id, nom) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer le diplôme "${nom}" ?\n\nAttention : Cette action supprimera également toutes les attributions de ce diplôme aux membres.`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_diplome">
                <input type="hidden" name="diplome_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function deleteAnnouncement(id, title) {
        if (confirm(`Êtes-vous sûr de vouloir supprimer l'annonce "${title}" ?`)) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="action" value="delete_announcement">
                <input type="hidden" name="announcement_id" value="${id}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function toggleAnnouncement(id) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="toggle_announcement">
            <input type="hidden" name="announcement_id" value="${id}">
        `;
        document.body.appendChild(form);
        form.submit();
    }

    function resetAppearance() {
        if (confirm('Voulez-vous vraiment réinitialiser l\'apparence aux paramètres par défaut ?')) {
            location.reload();
        }
    }

    // Mise à jour en temps réel des couleurs
    document.querySelectorAll('input[type="color"]').forEach(input => {
        input.addEventListener('input', function() {
            this.nextElementSibling.querySelector('input[type="text"]').value = this.value;
        });
    });
    </script>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

