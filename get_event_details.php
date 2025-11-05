<?php
require_once 'config.php';

$event_id = $_GET['id'] ?? 0;

// Récupérer l'événement
$stmt = $pdo->prepare("
    SELECT e.*, u.username as creator_name,
    (SELECT COUNT(*) FROM event_participants WHERE event_id = e.id) as participant_count
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.id = ?
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

if (!$event) {
    echo '<div class="p-8 text-center text-red-400">Événement introuvable</div>';
    exit;
}

// Récupérer la liste des participants
$stmt = $pdo->prepare("
    SELECT m.discord_pseudo, m.roblox_pseudo, m.grade, ep.status, ep.registered_at
    FROM event_participants ep
    JOIN members m ON ep.member_id = m.id
    WHERE ep.event_id = ?
    ORDER BY ep.registered_at ASC
");
$stmt->execute([$event_id]);
$participants = $stmt->fetchAll();

// Vérifier si le membre est inscrit
$is_registered = false;
if (isMember()) {
    $stmt = $pdo->prepare("SELECT id FROM event_participants WHERE event_id = ? AND member_id = ?");
    $stmt->execute([$event_id, $_SESSION['member_id']]);
    $is_registered = $stmt->fetch() ? true : false;
}

$is_full = $event['max_participants'] && $event['participant_count'] >= $event['max_participants'];

$type_colors = [
    'raid' => 'red',
    'formation' => 'blue',
    'reunion' => 'purple',
    'competition' => 'yellow',
    'entrainement' => 'green',
    'important' => 'orange',
    'autre' => 'gray'
];

$type_icons = [
    'raid' => 'fa-crosshairs',
    'formation' => 'fa-graduation-cap',
    'reunion' => 'fa-users',
    'competition' => 'fa-trophy',
    'entrainement' => 'fa-dumbbell',
    'important' => 'fa-exclamation-triangle',
    'autre' => 'fa-calendar'
];

$type_names = [
    'raid' => 'Raid',
    'formation' => 'Formation',
    'reunion' => 'Réunion',
    'competition' => 'Compétition',
    'entrainement' => 'Entraînement',
    'important' => 'IMPORTANT',
    'autre' => 'Autre'
];

$type_color = $type_colors[$event['type']];
?>

<div class="p-8">
    <!-- Header -->
    <div class="flex justify-between items-start mb-6">
        <div class="flex-1">
            <div class="flex items-center gap-3 mb-3">
                <span class="bg-<?php echo $type_color; ?>-600 text-white px-4 py-2 rounded-lg">
                    <i class="fas <?php echo $type_icons[$event['type']]; ?> mr-2"></i>
                    <?php echo $type_names[$event['type']]; ?>
                </span>
                <span class="bg-gray-700 text-gray-300 px-3 py-1 rounded">
                    <?php 
                    $status_labels = [
                        'planned' => '📅 Prévu',
                        'ongoing' => '▶️ En cours',
                        'completed' => '✅ Terminé',
                        'cancelled' => '❌ Annulé'
                    ];
                    echo $status_labels[$event['status']];
                    ?>
                </span>
            </div>
            <h2 class="text-3xl font-bold text-white mb-2">
                <?php echo htmlspecialchars($event['title']); ?>
            </h2>
        </div>
        <button onclick="closeEventModal()" class="text-gray-400 hover:text-white text-2xl">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <!-- Description -->
    <div class="bg-gray-700 rounded-lg p-4 mb-6">
        <p class="text-gray-300 whitespace-pre-wrap"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
    </div>

    <!-- Informations -->
    <div class="grid md:grid-cols-2 gap-6 mb-6">
        <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-white font-semibold mb-3">
                <i class="fas fa-info-circle text-blue-400 mr-2"></i>Informations
            </h3>
            <div class="space-y-2 text-gray-300">
                <div>
                    <i class="fas fa-calendar text-blue-400 mr-2 w-5"></i>
                    <strong>Date :</strong>
                    <?php echo strftime('%A %d %B %Y', strtotime($event['date_start'])); ?>
                </div>
                <div>
                    <i class="fas fa-clock text-green-400 mr-2 w-5"></i>
                    <strong>Horaires :</strong>
                    <?php echo date('H:i', strtotime($event['date_start'])); ?>
                    -
                    <?php echo date('H:i', strtotime($event['date_end'])); ?>
                    (<?php 
                    $duration = (strtotime($event['date_end']) - strtotime($event['date_start'])) / 3600;
                    echo $duration . 'h';
                    ?>)
                </div>
                <?php if ($event['location']): ?>
                    <div>
                        <i class="fas fa-map-marker-alt text-red-400 mr-2 w-5"></i>
                        <strong>Lieu :</strong>
                        <?php echo htmlspecialchars($event['location']); ?>
                    </div>
                <?php endif; ?>
                <?php if ($event['required_grade']): ?>
                    <div>
                        <i class="fas fa-star text-yellow-400 mr-2 w-5"></i>
                        <strong>Grade requis :</strong>
                        <?php echo htmlspecialchars($event['required_grade']); ?>
                    </div>
                <?php endif; ?>
                <div>
                    <i class="fas fa-user text-purple-400 mr-2 w-5"></i>
                    <strong>Organisateur :</strong>
                    <?php echo htmlspecialchars($event['creator_name']); ?>
                </div>
            </div>
        </div>

        <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-white font-semibold mb-3">
                <i class="fas fa-users text-green-400 mr-2"></i>Participants
            </h3>
            <div class="mb-4">
                <div class="flex justify-between items-center mb-2">
                    <span class="text-gray-300">
                        <?php echo $event['participant_count']; ?>
                        <?php if ($event['max_participants']): ?>
                            / <?php echo $event['max_participants']; ?>
                        <?php endif; ?>
                        inscrits
                    </span>
                    <?php if ($is_full): ?>
                        <span class="bg-red-600 text-white px-2 py-1 rounded text-xs">
                            <i class="fas fa-lock mr-1"></i>Complet
                        </span>
                    <?php elseif ($event['max_participants']): ?>
                        <?php 
                        $places_left = $event['max_participants'] - $event['participant_count'];
                        $percentage = ($event['participant_count'] / $event['max_participants']) * 100;
                        ?>
                        <span class="text-sm text-gray-400">
                            <?php echo $places_left; ?> place(s) restante(s)
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if ($event['max_participants']): ?>
                    <div class="w-full bg-gray-600 rounded-full h-2 overflow-hidden">
                        <div class="bg-green-500 h-full transition-all" 
                             style="width: <?php echo min(100, $percentage); ?>%"></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Boutons d'action -->
            <?php if (isMember() && $event['status'] === 'planned'): ?>
                <div class="mb-4">
                    <?php if ($is_registered): ?>
                        <form method="POST" action="events.php">
                            <input type="hidden" name="action" value="unregister">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" class="w-full bg-red-600 text-white py-3 rounded-lg font-bold hover:bg-red-700 transition">
                                <i class="fas fa-times mr-2"></i>Se désinscrire
                            </button>
                        </form>
                        <p class="text-green-400 text-center mt-2 text-sm">
                            <i class="fas fa-check-circle mr-1"></i>Vous êtes inscrit à cet événement
                        </p>
                    <?php elseif ($is_full): ?>
                        <button disabled class="w-full bg-gray-600 text-gray-400 py-3 rounded-lg font-bold cursor-not-allowed">
                            <i class="fas fa-lock mr-2"></i>Événement complet
                        </button>
                    <?php else: ?>
                        <form method="POST" action="events.php">
                            <input type="hidden" name="action" value="register">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" class="w-full bg-green-600 text-white py-3 rounded-lg font-bold hover:bg-green-700 transition">
                                <i class="fas fa-check mr-2"></i>S'inscrire à l'événement
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            <?php elseif (!isMember()): ?>
                <div class="bg-blue-900 bg-opacity-30 border border-blue-500 rounded-lg p-4 text-center">
                    <p class="text-blue-300 text-sm mb-2">Connectez-vous pour vous inscrire</p>
                    <a href="member_login.php" class="text-blue-400 hover:text-blue-300 text-sm underline">
                        Se connecter
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Liste des participants -->
    <?php if (!empty($participants)): ?>
        <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-white font-semibold mb-4">
                <i class="fas fa-list text-blue-400 mr-2"></i>
                Liste des participants (<?php echo count($participants); ?>)
            </h3>
            
            <div class="grid md:grid-cols-2 gap-3 max-h-64 overflow-y-auto">
                <?php foreach ($participants as $participant): ?>
                    <div class="bg-gray-800 rounded p-3 flex items-center justify-between">
                        <div>
                            <p class="text-white font-semibold">
                                <i class="fab fa-discord text-blue-400 mr-1"></i>
                                <?php echo htmlspecialchars($participant['discord_pseudo']); ?>
                            </p>
                            <p class="text-gray-400 text-sm">
                                <?php echo htmlspecialchars($participant['grade']); ?>
                            </p>
                        </div>
                        <?php
                        $status_badges = [
                            'registered' => ['Inscrit', 'blue'],
                            'confirmed' => ['Confirmé', 'green'],
                            'absent' => ['Absent', 'red'],
                            'present' => ['Présent', 'purple']
                        ];
                        $badge = $status_badges[$participant['status']];
                        ?>
                        <span class="bg-<?php echo $badge[1]; ?>-600 text-white px-2 py-1 rounded text-xs">
                            <?php echo $badge[0]; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-gray-700 rounded-lg p-8 text-center">
            <i class="fas fa-user-slash text-gray-600 text-4xl mb-3"></i>
            <p class="text-gray-400">Aucun participant pour le moment</p>
            <?php if (isMember() && !$is_registered && !$is_full): ?>
                <p class="text-gray-500 text-sm mt-2">Soyez le premier à vous inscrire !</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- Actions admin -->
    <?php if (isAdmin()): ?>
        <div class="mt-6 flex gap-3">
            <a href="admin/manage_events.php?edit=<?php echo $event['id']; ?>" 
               class="flex-1 bg-blue-600 text-white py-2 rounded-lg text-center hover:bg-blue-700 transition">
                <i class="fas fa-edit mr-2"></i>Modifier
            </a>
            <button onclick="if(confirm('Annuler cet événement ?')) window.location='admin/manage_events.php?cancel=<?php echo $event['id']; ?>'" 
                    class="flex-1 bg-yellow-600 text-white py-2 rounded-lg hover:bg-yellow-700 transition">
                <i class="fas fa-ban mr-2"></i>Annuler
            </button>
            <button onclick="if(confirm('Supprimer définitivement cet événement ?')) window.location='admin/manage_events.php?delete=<?php echo $event['id']; ?>'" 
                    class="flex-1 bg-red-600 text-white py-2 rounded-lg hover:bg-red-700 transition">
                <i class="fas fa-trash mr-2"></i>Supprimer
            </button>
        </div>
    <?php endif; ?>
</div>
