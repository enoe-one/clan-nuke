<?php
require_once 'config.php';

$stmt = $pdo->query("
    SELECT l.*, COUNT(m.id) as member_count, u.username as chef_username
    FROM legions l 
    LEFT JOIN members m ON l.id = m.legion_id 
    LEFT JOIN users u ON l.chef_id = u.id
    GROUP BY l.id
    ORDER BY l.nom
");
$legions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Légions - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- En-tête avec le dirigeant principal -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-white mb-4">
                    <i class="fas fa-flag mr-3 text-red-500"></i>
                    Coalition Française de War Tycoon
                </h1>
                <div class="bg-gradient-to-r from-yellow-900 to-yellow-800 p-6 rounded-lg inline-block border-2 border-yellow-500">
                    <div class="flex items-center justify-center space-x-4">
                        <i class="fas fa-crown text-yellow-400 text-4xl"></i>
                        <div class="text-left">
                            <p class="text-yellow-300 text-sm font-semibold uppercase tracking-wider">Général d'Armée</p>
                            <p class="text-white text-3xl font-bold">Death Angel</p>
                            <p class="text-yellow-200 text-sm italic">Dirigeant de la Coalition</p>
                        </div>
                        <i class="fas fa-star text-yellow-400 text-4xl"></i>
                    </div>
                </div>
            </div>

            <h2 class="text-3xl font-bold text-white mb-8 text-center">
                <i class="fas fa-shield-alt mr-2 text-red-500"></i>
                Légions de la CFWT
            </h2>
            
            <div class="grid md:grid-cols-2 gap-6">
                <?php foreach ($legions as $legion): 
                    $stmt_members = $pdo->prepare("
                        SELECT discord_pseudo, roblox_pseudo, grade, rang, kdr 
                        FROM members 
                        WHERE legion_id = ? 
                        ORDER BY 
                            CASE grade
                                WHEN 'Général d''armée' THEN 1
                                WHEN 'Général de corps d''armée' THEN 2
                                WHEN 'Général de division' THEN 3
                                WHEN 'Général de brigade' THEN 4
                                WHEN 'Colonel' THEN 5
                                WHEN 'Lieutenant-colonel' THEN 6
                                WHEN 'Commandant' THEN 7
                                WHEN 'Capitaine' THEN 8
                                WHEN 'Lieutenant' THEN 9
                                WHEN 'Sous-lieutenant' THEN 10
                                WHEN 'Adjudant' THEN 11
                                WHEN 'Sergent' THEN 12
                                WHEN 'Caporal' THEN 13
                                ELSE 14
                            END,
                            discord_pseudo
                    ");
                    $stmt_members->execute([$legion['id']]);
                    $members = $stmt_members->fetchAll();
                ?>
                    <div class="bg-gray-800 p-6 rounded-lg border-t-4 border-red-500 hover:shadow-xl hover:shadow-red-500/20 transition">
                        <div class="flex justify-between items-start mb-4">
                            <div class="flex-1">
                                <h3 class="text-2xl font-bold text-white mb-2">
                                    <i class="fas fa-shield-alt mr-2 text-red-500"></i>
                                    <?php echo htmlspecialchars($legion['nom']); ?>
                                </h3>
                                <?php if ($legion['description']): ?>
                                    <p class="text-gray-400 mb-2"><?php echo htmlspecialchars($legion['description']); ?></p>
                                <?php endif; ?>
                                
                                <!-- Chef de légion -->
                                <?php if ($legion['chef_username']): ?>
                                    <div class="bg-purple-900/30 border border-purple-500/50 rounded px-3 py-2 inline-block mt-2">
                                        <i class="fas fa-user-shield text-purple-400 mr-2"></i>
                                        <span class="text-purple-300 text-sm font-semibold">Chef:</span>
                                        <span class="text-white text-sm ml-1"><?php echo htmlspecialchars($legion['chef_username']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <span class="bg-red-600 text-white px-3 py-1 rounded-full text-sm font-bold">
                                <?php echo $legion['member_count']; ?> membres
                            </span>
                        </div>

                        <?php if (!empty($members)): ?>
                            <div class="mt-4 space-y-2 max-h-96 overflow-y-auto">
                                <h4 class="text-lg font-semibold text-white mb-3 sticky top-0 bg-gray-800 py-2 border-b border-gray-700">
                                    <i class="fas fa-users mr-2"></i> Membres
                                </h4>
                                <?php foreach ($members as $member): ?>
                                    <div class="bg-gray-700 p-3 rounded hover:bg-gray-650 transition">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="text-white font-semibold">
                                                    <?php echo htmlspecialchars($member['discord_pseudo']); ?>
                                                </p>
                                                <p class="text-gray-400 text-sm">
                                                    <i class="fab fa-roblox mr-1"></i>
                                                    <?php echo htmlspecialchars($member['roblox_pseudo']); ?>
                                                </p>
                                            </div>
                                            <div class="text-right">
                                                <p class="text-blue-400 text-sm font-semibold">
                                                    <?php echo htmlspecialchars($member['grade']); ?>
                                                </p>
                                                <p class="text-green-400 text-sm">
                                                    <?php echo htmlspecialchars($member['rang']); ?>
                                                </p>
                                                <p class="text-gray-500 text-xs">
                                                    <i class="fas fa-crosshairs mr-1"></i>
                                                    KDR: <?php echo htmlspecialchars($member['kdr']); ?>
                                                </p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 text-center py-8">
                                <i class="fas fa-inbox text-4xl mb-2"></i><br>
                                Aucun membre pour l'instant
                            </p>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <?php if (empty($legions)): ?>
                <div class="bg-gray-800 p-12 rounded-lg text-center">
                    <i class="fas fa-shield-alt text-gray-600 text-6xl mb-4"></i>
                    <p class="text-gray-400 text-xl">Aucune légion n'a encore été créée</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
</body>
</html>

