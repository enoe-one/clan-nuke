<?php
require_once 'config.php';

// Créer les tables si elles n'existent pas
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS events (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        type ENUM('raid', 'formation', 'reunion', 'competition', 'entrainement', 'autre') DEFAULT 'autre',
        date_start DATETIME NOT NULL,
        date_end DATETIME NOT NULL,
        location VARCHAR(255),
        max_participants INT DEFAULT NULL,
        required_grade VARCHAR(50) DEFAULT NULL,
        status ENUM('planned', 'ongoing', 'completed', 'cancelled') DEFAULT 'planned',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id)
    )");
    
    $pdo->exec("CREATE TABLE IF NOT EXISTS event_participants (
        id INT AUTO_INCREMENT PRIMARY KEY,
        event_id INT NOT NULL,
        member_id INT NOT NULL,
        status ENUM('registered', 'confirmed', 'absent', 'present') DEFAULT 'registered',
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        UNIQUE KEY event_member (event_id, member_id),
        FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE,
        FOREIGN KEY (member_id) REFERENCES members(id) ON DELETE CASCADE
    )");
} catch (PDOException $e) {
    // Tables déjà créées
}

$success = '';
$error = '';

// Traitement des actions (inscription/désinscription)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isMember()) {
    $action = $_POST['action'] ?? '';
    $event_id = $_POST['event_id'] ?? 0;
    
    try {
        if ($action === 'register') {
            // Vérifier si l'événement existe et n'est pas complet
            $stmt = $pdo->prepare("
                SELECT e.*, 
                (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as current_participants
                FROM events e WHERE e.id = ?
            ");
            $stmt->execute([$event_id]);
            $event = $stmt->fetch();
            
            if (!$event) {
                throw new Exception("Événement introuvable");
            }
            
            if ($event['max_participants'] && $event['current_participants'] >= $event['max_participants']) {
                throw new Exception("L'événement est complet");
            }
            
            // Vérifier si déjà inscrit
            $stmt = $pdo->prepare("SELECT id FROM event_participants WHERE event_id = ? AND member_id = ?");
            $stmt->execute([$event_id, $_SESSION['member_id']]);
            if ($stmt->fetch()) {
                throw new Exception("Vous êtes déjà inscrit à cet événement");
            }
            
            // Inscription
            $stmt = $pdo->prepare("INSERT INTO event_participants (event_id, member_id) VALUES (?, ?)");
            $stmt->execute([$event_id, $_SESSION['member_id']]);
            
            $success = "Inscription réussie ! Vous participerez à cet événement.";
            
        } elseif ($action === 'unregister') {
            // Désinscription
            $stmt = $pdo->prepare("DELETE FROM event_participants WHERE event_id = ? AND member_id = ?");
            $stmt->execute([$event_id, $_SESSION['member_id']]);
            
            $success = "Désinscription réussie.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Récupérer le mois/année à afficher
$month = $_GET['month'] ?? date('n');
$year = $_GET['year'] ?? date('Y');

// Navigation mois précédent/suivant
$prev_month = $month - 1;
$prev_year = $year;
if ($prev_month < 1) {
    $prev_month = 12;
    $prev_year--;
}

$next_month = $month + 1;
$next_year = $year;
if ($next_month > 12) {
    $next_month = 1;
    $next_year++;
}

// Récupérer les événements du mois
$start_date = "$year-$month-01 00:00:00";
$end_date = date('Y-m-t 23:59:59', strtotime($start_date));

$stmt = $pdo->prepare("
    SELECT e.*, 
    u.username as creator_name,
    (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.date_start BETWEEN ? AND ?
    ORDER BY e.date_start ASC
");
$stmt->execute([$start_date, $end_date]);
$events = $stmt->fetchAll();

// Organiser les événements par jour
$events_by_day = [];
foreach ($events as $event) {
    $day = date('j', strtotime($event['date_start']));
    $events_by_day[$day][] = $event;
}

// Si membre connecté, récupérer ses inscriptions
$member_registrations = [];
if (isMember()) {
    $stmt = $pdo->prepare("SELECT event_id FROM event_participants WHERE member_id = ?");
    $stmt->execute([$_SESSION['member_id']]);
    while ($row = $stmt->fetch()) {
        $member_registrations[] = $row['event_id'];
    }
}

// Événements à venir (prochains 5)
$upcoming_events = $pdo->query("
    SELECT e.*, 
    u.username as creator_name,
    (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.date_start > NOW() AND e.status = 'planned'
    ORDER BY e.date_start ASC
    LIMIT 5
")->fetchAll();

$type_colors = [
    'raid' => 'red',
    'formation' => 'blue',
    'reunion' => 'purple',
    'competition' => 'yellow',
    'entrainement' => 'green',
    'autre' => 'gray'
];

$type_icons = [
    'raid' => 'fa-crosshairs',
    'formation' => 'fa-graduation-cap',
    'reunion' => 'fa-users',
    'competition' => 'fa-trophy',
    'entrainement' => 'fa-dumbbell',
    'autre' => 'fa-calendar'
];

$type_names = [
    'raid' => 'Raid',
    'formation' => 'Formation',
    'reunion' => 'Réunion',
    'competition' => 'Compétition',
    'entrainement' => 'Entraînement',
    'autre' => 'Autre'
];

// Générer le calendrier
$first_day_of_month = mktime(0, 0, 0, $month, 1, $year);
$days_in_month = date('t', $first_day_of_month);
$day_of_week = date('N', $first_day_of_month); // 1 (lundi) à 7 (dimanche)
$month_name = strftime('%B %Y', $first_day_of_month);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier d'Événements - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .calendar-day {
            min-height: 120px;
            transition: all 0.3s;
        }
        .calendar-day:hover {
            background-color: rgba(59, 130, 246, 0.1);
        }
        .event-badge {
            font-size: 0.7rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .event-badge:hover {
            transform: scale(1.05);
        }
        @keyframes pulse-event {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        .event-today {
            animation: pulse-event 2s infinite;
        }
        @keyframes glow-important {
            0%, 100% { 
                box-shadow: 0 0 10px rgba(249, 115, 22, 0.5);
                transform: scale(1);
            }
            50% { 
                box-shadow: 0 0 20px rgba(249, 115, 22, 0.8);
                transform: scale(1.02);
            }
        }
        .event-important {
            animation: glow-important 2s infinite;
            font-weight: bold;
        }
        .important-banner {
            background: linear-gradient(90deg, #ea580c, #f97316, #ea580c);
            background-size: 200% 100%;
            animation: gradient-shift 3s ease infinite;
        }
        @keyframes gradient-shift {
            0%, 100% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
        }
    </style>
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-4xl font-bold text-white mb-2">
                        <i class="fas fa-calendar-alt text-blue-500 mr-3"></i>
                        Calendrier d'Événements
                    </h1>
                    <p class="text-gray-400">Raids, formations, réunions et compétitions</p>
                </div>
                <?php if (isAdmin()): ?>
                    <a href="admin/manage_events.php" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                        <i class="fas fa-plus mr-2"></i>Créer un événement
                    </a>
                <?php endif; ?>
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

            <?php if (!isMember()): ?>
                <div class="bg-blue-900 bg-opacity-30 border-2 border-blue-500 p-6 rounded-lg text-center mb-8">
                    <i class="fas fa-info-circle text-blue-400 text-4xl mb-3"></i>
                    <p class="text-blue-300 text-lg mb-4">Connectez-vous pour vous inscrire aux événements</p>
                    <a href="member_login.php" class="inline-block bg-blue-600 text-white px-8 py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                        <i class="fas fa-sign-in-alt mr-2"></i>Se connecter
                    </a>
                </div>
            <?php endif; ?>

            <div class="grid lg:grid-cols-3 gap-8">
                <!-- Calendrier principal -->
                <div class="lg:col-span-2">
                    <div class="bg-gray-800 rounded-lg overflow-hidden">
                        <!-- Navigation mois -->
                        <div class="bg-gray-900 p-6 flex justify-between items-center">
                            <a href="?month=<?php echo $prev_month; ?>&year=<?php echo $prev_year; ?>" 
                               class="text-white hover:text-blue-400 transition">
                                <i class="fas fa-chevron-left text-2xl"></i>
                            </a>
                            
                            <h2 class="text-2xl font-bold text-white capitalize">
                                <?php 
$date = new DateTime();
$date->setDate($year, $month, 1);

$formatter = new IntlDateFormatter(
    'fr_FR', 
    IntlDateFormatter::LONG, 
    IntlDateFormatter::NONE
);
echo ucfirst($formatter->format($date)); // ex: Novembre 2025

                                ?>
                            </h2>
                            
                            <a href="?month=<?php echo $next_month; ?>&year=<?php echo $next_year; ?>" 
                               class="text-white hover:text-blue-400 transition">
                                <i class="fas fa-chevron-right text-2xl"></i>
                            </a>
                        </div>

                        <!-- Jours de la semaine -->
                        <div class="grid grid-cols-7 bg-gray-700 text-white font-semibold">
                            <?php foreach (['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'] as $day): ?>
                                <div class="p-3 text-center border-r border-gray-600 last:border-r-0">
                                    <?php echo $day; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grille calendrier -->
                        <div class="grid grid-cols-7">
                            <?php
                            // Cellules vides avant le 1er jour
                            for ($i = 1; $i < $day_of_week; $i++) {
                                echo '<div class="calendar-day bg-gray-900 border border-gray-700 p-2"></div>';
                            }
                            
                            // Jours du mois
                            for ($day = 1; $day <= $days_in_month; $day++) {
                                $is_today = ($day == date('j') && $month == date('n') && $year == date('Y'));
                                $has_events = isset($events_by_day[$day]);
                                
                                echo '<div class="calendar-day bg-gray-800 border border-gray-700 p-2 ' . ($is_today ? 'ring-2 ring-blue-500' : '') . '">';
                                echo '<div class="flex justify-between items-start mb-2">';
                                echo '<span class="text-white font-semibold ' . ($is_today ? 'bg-blue-600 px-2 py-1 rounded' : '') . '">' . $day . '</span>';
                                if ($is_today) {
                                    echo '<span class="text-xs text-blue-400">Aujourd\'hui</span>';
                                }
                                echo '</div>';
                                
                                if ($has_events) {
                                    echo '<div class="space-y-1">';
                                    foreach ($events_by_day[$day] as $event) {
                                        $type_color = $type_colors[$event['type']];
                                        echo '<div onclick="showEventDetails(' . $event['id'] . ')" 
                                              class="event-badge bg-' . $type_color . '-600 text-white px-2 py-1 rounded text-xs truncate ' . ($is_today ? 'event-today' : '') . '">';
                                        echo '<i class="fas ' . $type_icons[$event['type']] . ' mr-1"></i>';
                                        echo htmlspecialchars($event['title']);
                                        echo '</div>';
                                    }
                                    echo '</div>';
                                }
                                
                                echo '</div>';
                            }
                            
                            // Cellules vides après le dernier jour
                            $last_day_of_week = date('N', mktime(0, 0, 0, $month, $days_in_month, $year));
                            for ($i = $last_day_of_week; $i < 7; $i++) {
                                echo '<div class="calendar-day bg-gray-900 border border-gray-700 p-2"></div>';
                            }
                            ?>
                        </div>
                    </div>

                    <!-- Légende -->
                    <div class="bg-gray-800 p-4 rounded-lg mt-6">
                        <h3 class="text-white font-semibold mb-3">
                            <i class="fas fa-info-circle mr-2"></i>Types d'événements
                        </h3>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                            <?php foreach ($type_names as $type => $name): ?>
                                <div class="flex items-center gap-2">
                                    <span class="bg-<?php echo $type_colors[$type]; ?>-600 text-white px-2 py-1 rounded text-xs">
                                        <i class="fas <?php echo $type_icons[$type]; ?>"></i>
                                    </span>
                                    <span class="text-gray-300 text-sm"><?php echo $name; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Sidebar : Événements à venir -->
                <div class="space-y-6">
                    <div class="bg-gray-800 rounded-lg p-6">
                        <h3 class="text-xl font-bold text-white mb-4">
                            <i class="fas fa-clock text-yellow-500 mr-2"></i>
                            Prochains Événements
                        </h3>
                        
                        <?php if (!empty($upcoming_events)): ?>
                            <div class="space-y-4">
                                <?php foreach ($upcoming_events as $event): ?>
                                    <?php
                                    $is_registered = in_array($event['id'], $member_registrations);
                                    $is_full = $event['max_participants'] && $event['participant_count'] >= $event['max_participants'];
                                    $type_color = $type_colors[$event['type']];
                                    ?>
                                    <div class="bg-gray-700 rounded-lg p-4 border-l-4 border-<?php echo $type_color; ?>-500">
                                        <div class="flex items-start justify-between mb-2">
                                            <h4 class="text-white font-semibold flex-1">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </h4>
                                            <span class="bg-<?php echo $type_color; ?>-600 text-white px-2 py-1 rounded text-xs">
                                                <i class="fas <?php echo $type_icons[$event['type']]; ?>"></i>
                                            </span>
                                        </div>
                                        
                                        <div class="text-gray-300 text-sm mb-3">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?php echo strftime('%d %B %Y', strtotime($event['date_start'])); ?>
                                            <br>
                                            <i class="fas fa-clock mr-1"></i>
                                            <?php echo date('H:i', strtotime($event['date_start'])); ?>
                                            -
                                            <?php echo date('H:i', strtotime($event['date_end'])); ?>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <span class="text-gray-400 text-xs">
                                                <i class="fas fa-users mr-1"></i>
                                                <?php echo $event['participant_count']; ?>
                                                <?php if ($event['max_participants']): ?>
                                                    / <?php echo $event['max_participants']; ?>
                                                <?php endif; ?>
                                            </span>
                                            
                                            <?php if (isMember()): ?>
                                                <?php if ($is_registered): ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="unregister">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                        <button type="submit" class="bg-red-600 text-white px-3 py-1 rounded text-xs hover:bg-red-700 transition">
                                                            <i class="fas fa-times mr-1"></i>Se désinscrire
                                                        </button>
                                                    </form>
                                                <?php elseif ($is_full): ?>
                                                    <span class="text-red-400 text-xs">
                                                        <i class="fas fa-lock mr-1"></i>Complet
                                                    </span>
                                                <?php else: ?>
                                                    <form method="POST" class="inline">
                                                        <input type="hidden" name="action" value="register">
                                                        <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                                        <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-xs hover:bg-green-700 transition">
                                                            <i class="fas fa-check mr-1"></i>S'inscrire
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <button onclick="showEventDetails(<?php echo $event['id']; ?>)" 
                                                class="w-full mt-3 text-blue-400 hover:text-blue-300 text-sm">
                                            <i class="fas fa-info-circle mr-1"></i>Voir les détails
                                        </button>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-8 text-gray-500">
                                <i class="fas fa-calendar-times text-4xl mb-2"></i>
                                <p>Aucun événement prévu</p>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Mes inscriptions -->
                    <?php if (isMember() && !empty($member_registrations)): ?>
                        <div class="bg-gray-800 rounded-lg p-6">
                            <h3 class="text-xl font-bold text-white mb-4">
                                <i class="fas fa-user-check text-green-500 mr-2"></i>
                                Mes Inscriptions
                            </h3>
                            
                            <?php
                            $stmt = $pdo->prepare("
                                SELECT e.*, ep.status 
                                FROM events e
                                JOIN event_participants ep ON e.id = ep.event_id
                                WHERE ep.member_id = ? AND e.date_start > NOW()
                                ORDER BY e.date_start ASC
                            ");
                            $stmt->execute([$_SESSION['member_id']]);
                            $my_events = $stmt->fetchAll();
                            ?>
                            
                            <?php if (!empty($my_events)): ?>
                                <div class="space-y-2">
                                    <?php foreach ($my_events as $event): ?>
                                        <div class="bg-gray-700 p-3 rounded">
                                            <p class="text-white font-semibold text-sm mb-1">
                                                <?php echo htmlspecialchars($event['title']); ?>
                                            </p>
                                            <p class="text-gray-400 text-xs">
                                                <i class="fas fa-calendar mr-1"></i>
                                                <?php echo date('d/m/Y à H:i', strtotime($event['date_start'])); ?>
                                            </p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-gray-400 text-center py-4 text-sm">
                                    Vous n'êtes inscrit à aucun événement
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal détails événement -->
    <div id="event-modal" class="hidden fixed inset-0 bg-black bg-opacity-75 flex items-center justify-center z-50 p-4">
        <div class="bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
            <div id="event-modal-content">
                <!-- Contenu chargé dynamiquement -->
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    function showEventDetails(eventId) {
        const modal = document.getElementById('event-modal');
        const content = document.getElementById('event-modal-content');
        
        // Afficher un loader
        content.innerHTML = '<div class="p-12 text-center"><i class="fas fa-spinner fa-spin text-white text-4xl"></i></div>';
        modal.classList.remove('hidden');
        
        // Charger les détails via AJAX
        fetch(`get_event_details.php?id=${eventId}`)
            .then(response => response.text())
            .then(html => {
                content.innerHTML = html;
            })
            .catch(error => {
                content.innerHTML = '<div class="p-8 text-center text-red-400">Erreur de chargement</div>';
            });
    }

    function closeEventModal() {
        document.getElementById('event-modal').classList.add('hidden');
    }

    // Fermer le modal en cliquant à l'extérieur
    document.getElementById('event-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEventModal();
        }
    });
    </script>
</body>
</html>
