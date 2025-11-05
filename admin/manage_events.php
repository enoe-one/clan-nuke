<?php
require_once '../config.php';

// Vérifier que l'utilisateur est au moins état-major
if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

// Vérifier si l'utilisateur peut créer des événements importants (seulement chef et Enoe)
if (!function_exists('canCreateImportantEvents')) {
    function canCreateImportantEvents($pdo) {
        if (!isset($_SESSION['user_id'])) return false;

        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();

        // Seulement les chefs et le super admin "Enoe" peuvent créer des événements importants
        return $user && ($user['role'] === 'chef' || $user['role'] === 'super_admin' || $user['username'] === 'Enoe');
    }
}

$success = '';
$error = '';

// Actions POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'create') {
            $stmt = $pdo->prepare("
                INSERT INTO events (title, description, type, date_start, date_end, location, max_participants, required_grade, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['type'],
                $_POST['date_start'],
                $_POST['date_end'],
                $_POST['location'] ?: null,
                $_POST['max_participants'] ?: null,
                $_POST['required_grade'] ?: null,
                $_SESSION['user_id']
            ]);

            logAdminAction($pdo, $_SESSION['user_id'], 'Création événement', $_POST['title']);
            $success = "Événement créé avec succès !";

        } elseif ($action === 'update') {
            $stmt = $pdo->prepare("
                UPDATE events SET 
                title = ?, description = ?, type = ?, date_start = ?, date_end = ?, 
                location = ?, max_participants = ?, required_grade = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['title'],
                $_POST['description'],
                $_POST['type'],
                $_POST['date_start'],
                $_POST['date_end'],
                $_POST['location'] ?: null,
                $_POST['max_participants'] ?: null,
                $_POST['required_grade'] ?: null,
                $_POST['event_id']
            ]);

            logAdminAction($pdo, $_SESSION['user_id'], 'Modification événement', "ID: {$_POST['event_id']}");
            $success = "Événement modifié avec succès !";
        }
    } catch (PDOException $e) {
        $error = "Erreur : " . $e->getMessage();
    }
}

// Actions GET
if (isset($_GET['delete'])) {
    $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$_GET['delete']]);
    logAdminAction($pdo, $_SESSION['user_id'], 'Suppression événement', "ID: {$_GET['delete']}");
    header('Location: manage_events.php?success=deleted');
    exit;
}

if (isset($_GET['cancel'])) {
    $pdo->prepare("UPDATE events SET status = 'cancelled' WHERE id = ?")->execute([$_GET['cancel']]);
    logAdminAction($pdo, $_SESSION['user_id'], 'Annulation événement', "ID: {$_GET['cancel']}");
    header('Location: manage_events.php?success=cancelled');
    exit;
}

// Récupérer un événement à éditer
$edit_event = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_event = $stmt->fetch();
}

// Messages de succès GET
if (isset($_GET['success'])) {
    $success = $_GET['success'] === 'deleted' ? 'Événement supprimé !' : 'Événement annulé !';
}

// Récupérer tous les événements
$filter = $_GET['filter'] ?? 'all';
$query = "SELECT e.*, u.username as creator_name,
    (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id";

if ($filter !== 'all') {
    $query .= " WHERE e.status = '$filter'";
}

$query .= " ORDER BY e.date_start DESC";
$events = $pdo->query($query)->fetchAll();

$grades = getGrades();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Événements - CFWT Admin</title>
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
                    <i class="fas fa-calendar-plus text-blue-500 mr-3"></i>
                    Gestion des Événements
                </h1>
                <p class="text-gray-400">Créer et gérer les événements de la coalition</p>
            </div>
            <a href="dashboard.php" class="bg-gray-700 text-white px-6 py-3 rounded-lg hover:bg-gray-600 transition">
                <i class="fas fa-arrow-left mr-2"></i>Retour
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

        <!-- Formulaire de création/modification -->
        <div class="bg-gray-800 rounded-lg p-8 mb-8">
            <h2 class="text-2xl font-bold text-white mb-6">
                <i class="fas fa-<?php echo $edit_event ? 'edit' : 'plus'; ?> mr-2"></i>
                <?php echo $edit_event ? 'Modifier l\'événement' : 'Créer un événement'; ?>
            </h2>

            <form method="POST" class="space-y-6">
                <input type="hidden" name="action" value="<?php echo $edit_event ? 'update' : 'create'; ?>">
                <?php if ($edit_event): ?>
                    <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
                <?php endif; ?>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Titre de l'événement *</label>
                        <input type="text" name="title" required maxlength="255"
                               value="<?php echo $edit_event ? htmlspecialchars($edit_event['title']) : ''; ?>"
                               placeholder="Ex: Raid contre l'Alliance Ennemie"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Type d'événement *</label>
                        <select name="type" required 
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <option value="raid" <?php echo $edit_event && $edit_event['type'] === 'raid' ? 'selected' : ''; ?>>🎯 Raid</option>
                            <option value="formation" <?php echo $edit_event && $edit_event['type'] === 'formation' ? 'selected' : ''; ?>>🎓 Formation</option>
                            <option value="reunion" <?php echo $edit_event && $edit_event['type'] === 'reunion' ? 'selected' : ''; ?>>👥 Réunion</option>
                            <option value="competition" <?php echo $edit_event && $edit_event['type'] === 'competition' ? 'selected' : ''; ?>>🏆 Compétition</option>
                            <option value="entrainement" <?php echo $edit_event && $edit_event['type'] === 'entrainement' ? 'selected' : ''; ?>>💪 Entraînement</option>
                            <option value="autre" <?php echo $edit_event && $edit_event['type'] === 'autre' ? 'selected' : ''; ?>>📅 Autre</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-white mb-2 font-semibold">Description *</label>
                    <textarea name="description" required rows="4"
                              placeholder="Décrivez l'événement en détail..."
                              class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Date et heure de début *</label>
                        <input type="datetime-local" name="date_start" required
                               value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['date_start'])) : ''; ?>"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Date et heure de fin *</label>
                        <input type="datetime-local" name="date_end" required
                               value="<?php echo $edit_event ? date('Y-m-d\TH:i', strtotime($edit_event['date_end'])) : ''; ?>"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>
                </div>

                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-white mb-2 font-semibold">Lieu (optionnel)</label>
                        <input type="text" name="location" maxlength="255"
                               value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>"
                               placeholder="Ex: Serveur EU #1, Base principale"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Participants max (optionnel)</label>
                        <input type="number" name="max_participants" min="1" max="200"
                               value="<?php echo $edit_event ? $edit_event['max_participants'] : ''; ?>"
                               placeholder="Illimité si vide"
                               class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                    </div>

                    <div>
                        <label class="block text-white mb-2 font-semibold">Grade requis (optionnel)</label>
                        <select name="required_grade"
                                class="w-full p-3 rounded bg-gray-700 text-white border border-gray-600 focus:border-blue-500 focus:outline-none">
                            <option value="">Tous les grades</option>
                            <?php foreach ($grades as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade); ?>"
                                        <?php echo $edit_event && $edit_event['required_grade'] === $grade ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($grade); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="flex space-x-4">
                    <button type="submit" 
                            class="flex-1 bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                        <i class="fas fa-<?php echo $edit_event ? 'save' : 'plus'; ?> mr-2"></i>
                        <?php echo $edit_event ? 'Enregistrer les modifications' : 'Créer l\'événement'; ?>
                    </button>

                    <?php if ($edit_event): ?>
                        <a href="manage_events.php" 
                           class="flex-1 bg-gray-600 text-white py-3 rounded-lg font-bold hover:bg-gray-700 transition text-center">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Filtres -->
        <div class="bg-gray-800 rounded-lg p-4 mb-6">
            <div class="flex gap-2 flex-wrap">
                <a href="?filter=all" 
                   class="px-4 py-2 rounded <?php echo $filter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Tous
                </a>
                <a href="?filter=planned" 
                   class="px-4 py-2 rounded <?php echo $filter === 'planned' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Prévus
                </a>
                <a href="?filter=ongoing" 
                   class="px-4 py-2 rounded <?php echo $filter === 'ongoing' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    En cours
                </a>
                <a href="?filter=completed" 
                   class="px-4 py-2 rounded <?php echo $filter === 'completed' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Terminés
                </a>
                <a href="?filter=cancelled" 
                   class="px-4 py-2 rounded <?php echo $filter === 'cancelled' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'; ?>">
                    Annulés
                </a>
            </div>
        </div>

        <!-- Liste des événements -->
        <div class="bg-gray-800 rounded-lg overflow-hidden">
            <div class="p-6 bg-gray-900">
                <h2 class="text-2xl font-bold text-white">
                    <i class="fas fa-list mr-2"></i>
                    Liste des événements (<?php echo count($events); ?>)
                </h2>
            </div>

            <?php if (!empty($events)): ?>
                <div class="divide-y divide-gray-700">
                    <?php foreach ($events as $event): ?>
                        <div class="p-6 hover:bg-gray-750 transition">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-xl font-bold text-white">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </h3>
                                        <span class="px-3 py-1 rounded text-sm font-semibold
                                            <?php 
                                            echo $event['status'] === 'planned' ? 'bg-blue-600 text-white' :
                                                 ($event['status'] === 'ongoing' ? 'bg-green-600 text-white' :
                                                 ($event['status'] === 'completed' ? 'bg-gray-600 text-white' : 'bg-red-600 text-white'));
                                            ?>">
                                            <?php 
                                            echo $event['status'] === 'planned' ? 'Prévu' :
                                                 ($event['status'] === 'ongoing' ? 'En cours' :
                                                 ($event['status'] === 'completed' ? 'Terminé' : 'Annulé'));
                                            ?>
                                        </span>
                                    </div>

                                    <div class="flex items-center gap-4 text-sm text-gray-400 mb-2">
                                        <span>
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo date('d/m/Y', strtotime($event['date_start'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo date('H:i', strtotime($event['date_start'])); ?>
                                            -
                                            <?php echo date('H:i', strtotime($event['date_end'])); ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-users mr-1"></i>
                                            <?php echo $event['participant_count']; ?>
                                            <?php if ($event['max_participants']): ?>
                                                / <?php echo $event['max_participants']; ?>
                                            <?php endif; ?>
                                        </span>
                                        <span>
                                            <i class="fas fa-user mr-1"></i>
                                            <?php echo htmlspecialchars($event['creator_name']); ?>
                                        </span>
                                    </div>

                                    <p class="text-gray-300 text-sm">
                                        <?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 150))); ?>
                                        <?php if (strlen($event['description']) > 150) echo '...'; ?>
                                    </p>
                                </div>

                                <div class="flex gap-2 ml-4">
                                    <a href="?edit=<?php echo $event['id']; ?>" 
                                       class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <?php if ($event['status'] === 'planned'): ?>
                                        <a href="?cancel=<?php echo $event['id']; ?>" 
                                           onclick="return confirm('Annuler cet événement ?')"
                                           class="bg-yellow-600 text-white px-4 py-2 rounded hover:bg-yellow-700 transition">
                                            <i class="fas fa-ban"></i>
                                        </a>
                                    <?php endif; ?>

                                    <a href="?delete=<?php echo $event['id']; ?>" 
                                       onclick="return confirm('Supprimer définitivement cet événement et toutes ses inscriptions ?')"
                                       class="bg-red-600 text-white px-4 py-2 rounded hover:bg-red-700 transition">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="p-12 text-center">
                    <i class="fas fa-calendar-times text-gray-600 text-6xl mb-4"></i>
                    <p class="text-gray-400 text-xl">Aucun événement trouvé</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
</body>
</html>
