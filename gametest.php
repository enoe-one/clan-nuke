<?php
session_start();

// Configuration des images - METTEZ VOS LIENS D'IMAGES ICI
$IMAGES = [
    'soldier' => 'https://exemple.com/soldat.png',      // Image du soldat (64x64px recommandé)
    'tank' => 'https://exemple.com/tank.png',           // Image du tank (64x64px)
    'helicopter' => 'https://exemple.com/heli.png',     // Image de l'hélicoptère (64x64px)
    'base' => 'https://exemple.com/base.png',           // Image de la base (128x128px)
    'enemy_base' => 'https://exemple.com/ennemi.png',   // Image base ennemie (128x128px)
    'battlefield' => 'https://exemple.com/terrain.jpg', // Fond de bataille (1200x800px)
    'explosion' => 'https://exemple.com/explosion.gif'  // Animation explosion (64x64px)
];

// Initialisation du jeu
if (!isset($_SESSION['game'])) {
    $_SESSION['game'] = [
        'player' => [
            'name' => 'Alliance',
            'hp' => 100,
            'gold' => 500,
            'units' => [
                'soldiers' => 10,
                'tanks' => 2,
                'helicopters' => 1
            ],
            'defense' => 50
        ],
        'enemy' => [
            'name' => 'Forces Ennemies',
            'hp' => 100,
            'units' => [
                'soldiers' => 8,
                'tanks' => 2,
                'helicopters' => 1
            ],
            'defense' => 40
        ],
        'turn' => 1,
        'battle_log' => [],
        'game_over' => false,
        'winner' => null
    ];
}

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    switch ($action) {
        case 'recruit_soldier':
            if ($_SESSION['game']['player']['gold'] >= 50) {
                $_SESSION['game']['player']['gold'] -= 50;
                $_SESSION['game']['player']['units']['soldiers']++;
                addLog("Vous avez recruté un soldat !");
            } else {
                addLog("Or insuffisant pour recruter un soldat.");
            }
            break;
            
        case 'recruit_tank':
            if ($_SESSION['game']['player']['gold'] >= 200) {
                $_SESSION['game']['player']['gold'] -= 200;
                $_SESSION['game']['player']['units']['tanks']++;
                addLog("Vous avez construit un tank !");
            } else {
                addLog("Or insuffisant pour construire un tank.");
            }
            break;
            
        case 'recruit_helicopter':
            if ($_SESSION['game']['player']['gold'] >= 300) {
                $_SESSION['game']['player']['gold'] -= 300;
                $_SESSION['game']['player']['units']['helicopters']++;
                addLog("Vous avez acheté un hélicoptère !");
            } else {
                addLog("Or insuffisant pour acheter un hélicoptère.");
            }
            break;
            
        case 'upgrade_defense':
            if ($_SESSION['game']['player']['gold'] >= 150) {
                $_SESSION['game']['player']['gold'] -= 150;
                $_SESSION['game']['player']['defense'] += 10;
                addLog("Défense améliorée de +10 !");
            } else {
                addLog("Or insuffisant pour améliorer la défense.");
            }
            break;
            
        case 'attack':
            performAttack();
            enemyTurn();
            $_SESSION['game']['turn']++;
            break;
            
        case 'defend':
            $_SESSION['game']['player']['defense'] += 15;
            addLog("Vous vous êtes mis en position défensive (+15 défense temporaire).");
            enemyTurn();
            $_SESSION['game']['turn']++;
            break;
            
        case 'reset':
            session_destroy();
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
    }
}

function addLog($message) {
    $_SESSION['game']['battle_log'][] = [
        'turn' => $_SESSION['game']['turn'],
        'message' => $message
    ];
    if (count($_SESSION['game']['battle_log']) > 10) {
        array_shift($_SESSION['game']['battle_log']);
    }
}

function performAttack() {
    $player = &$_SESSION['game']['player'];
    $enemy = &$_SESSION['game']['enemy'];
    
    $playerPower = calculatePower($player['units']);
    $enemyDefense = $enemy['defense'];
    
    $damage = max(0, $playerPower - ($enemyDefense / 2));
    $enemy['hp'] -= $damage;
    
    addLog("⚔️ Vous attaquez ! Dégâts infligés : " . round($damage, 1));
    
    // Pertes aléatoires des unités joueur
    if (rand(1, 100) > 70 && $player['units']['soldiers'] > 0) {
        $player['units']['soldiers']--;
        addLog("💀 Vous avez perdu un soldat au combat !");
    }
    
    // Gain d'or
    $goldGained = rand(30, 80);
    $player['gold'] += $goldGained;
    addLog("💰 Vous gagnez " . $goldGained . " d'or !");
    
    checkGameOver();
}

function enemyTurn() {
    $player = &$_SESSION['game']['player'];
    $enemy = &$_SESSION['game']['enemy'];
    
    // L'ennemi recrute parfois des unités
    if (rand(1, 100) > 60) {
        $enemy['units']['soldiers'] += rand(1, 2);
        addLog("🚨 L'ennemi a recruté des renforts !");
    }
    
    // Attaque ennemie
    $enemyPower = calculatePower($enemy['units']);
    $playerDefense = $player['defense'];
    
    $damage = max(0, $enemyPower - ($playerDefense / 2));
    $player['hp'] -= $damage;
    
    addLog("🔥 L'ennemi vous attaque ! Dégâts subis : " . round($damage, 1));
    
    // Pertes ennemies
    if (rand(1, 100) > 75 && $enemy['units']['soldiers'] > 0) {
        $enemy['units']['soldiers']--;
    }
    
    // Reset défense temporaire
    if ($player['defense'] > 50) {
        $player['defense'] = 50;
    }
    
    checkGameOver();
}

function calculatePower($units) {
    return ($units['soldiers'] * 5) + 
           ($units['tanks'] * 25) + 
           ($units['helicopters'] * 40);
}

function checkGameOver() {
    $player = &$_SESSION['game']['player'];
    $enemy = &$_SESSION['game']['enemy'];
    
    if ($player['hp'] <= 0) {
        $_SESSION['game']['game_over'] = true;
        $_SESSION['game']['winner'] = 'enemy';
        addLog("💀 DÉFAITE ! Votre base a été détruite !");
    } elseif ($enemy['hp'] <= 0) {
        $_SESSION['game']['game_over'] = true;
        $_SESSION['game']['winner'] = 'player';
        addLog("🎉 VICTOIRE ! Vous avez détruit la base ennemie !");
    }
}

$game = $_SESSION['game'];
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guerre Stratégique - Tour <?= $game['turn'] ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial Black', Arial, sans-serif;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            color: #ecf0f1;
            min-height: 100vh;
            padding: 20px;
        }
        
        .game-container {
            max-width: 1400px;
            margin: 0 auto;
        }
        
        h1 {
            text-align: center;
            color: #e74c3c;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
            margin-bottom: 20px;
            font-size: 3em;
            letter-spacing: 3px;
        }
        
        .battlefield {
            background: linear-gradient(180deg, #8B4513, #654321);
            border: 5px solid #2c3e50;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .bases {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
        }
        
        .base {
            background: rgba(44, 62, 80, 0.8);
            border: 3px solid;
            border-radius: 10px;
            padding: 20px;
            width: 45%;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .player-base {
            border-color: #3498db;
        }
        
        .enemy-base {
            border-color: #e74c3c;
        }
        
        .base h2 {
            margin-bottom: 15px;
            font-size: 1.8em;
        }
        
        .hp-bar {
            background: #2c3e50;
            height: 30px;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
            border: 2px solid #000;
        }
        
        .hp-fill {
            height: 100%;
            transition: width 0.5s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.8);
        }
        
        .player-hp {
            background: linear-gradient(90deg, #27ae60, #2ecc71);
        }
        
        .enemy-hp {
            background: linear-gradient(90deg, #c0392b, #e74c3c);
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-top: 15px;
        }
        
        .stat {
            background: rgba(0,0,0,0.3);
            padding: 10px;
            border-radius: 5px;
            font-weight: bold;
        }
        
        .units-display {
            display: flex;
            gap: 20px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .unit-card {
            background: rgba(0,0,0,0.4);
            padding: 15px;
            border-radius: 8px;
            text-align: center;
            min-width: 100px;
            border: 2px solid rgba(255,255,255,0.2);
        }
        
        .unit-icon {
            font-size: 2em;
            margin-bottom: 5px;
        }
        
        .control-panel {
            background: rgba(44, 62, 80, 0.9);
            border: 3px solid #3498db;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        
        button {
            background: linear-gradient(135deg, #3498db, #2980b9);
            color: white;
            border: none;
            padding: 15px 25px;
            font-size: 1em;
            font-weight: bold;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
            text-transform: uppercase;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 12px rgba(0,0,0,0.4);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .attack-btn {
            background: linear-gradient(135deg, #e74c3c, #c0392b);
        }
        
        .defend-btn {
            background: linear-gradient(135deg, #f39c12, #d68910);
        }
        
        .recruit-btn {
            background: linear-gradient(135deg, #27ae60, #229954);
        }
        
        .upgrade-btn {
            background: linear-gradient(135deg, #9b59b6, #8e44ad);
        }
        
        .reset-btn {
            background: linear-gradient(135deg, #95a5a6, #7f8c8d);
        }
        
        .battle-log {
            background: rgba(0,0,0,0.5);
            border: 3px solid #e74c3c;
            border-radius: 10px;
            padding: 20px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .battle-log h3 {
            margin-bottom: 15px;
            color: #e74c3c;
        }
        
        .log-entry {
            background: rgba(255,255,255,0.1);
            padding: 10px;
            margin: 5px 0;
            border-radius: 5px;
            border-left: 4px solid #3498db;
        }
        
        .game-over {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background: rgba(0,0,0,0.95);
            border: 5px solid;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            z-index: 1000;
            box-shadow: 0 0 50px rgba(0,0,0,0.8);
        }
        
        .victory {
            border-color: #27ae60;
        }
        
        .defeat {
            border-color: #e74c3c;
        }
        
        .game-over h2 {
            font-size: 3em;
            margin-bottom: 20px;
        }
        
        .cost {
            color: #f39c12;
            font-size: 0.9em;
        }
        
        .turn-counter {
            text-align: center;
            font-size: 1.5em;
            margin-bottom: 20px;
            color: #3498db;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .attack-btn:hover {
            animation: pulse 0.5s infinite;
        }
    </style>
</head>
<body>
    <div class="game-container">
        <h1>⚔️ GUERRE STRATÉGIQUE ⚔️</h1>
        
        <div class="turn-counter">
            🎯 TOUR <?= $game['turn'] ?>
        </div>
        
        <div class="battlefield">
            <div class="bases">
                <div class="base player-base">
                    <h2>🛡️ <?= htmlspecialchars($game['player']['name']) ?></h2>
                    <div class="hp-bar">
                        <div class="hp-fill player-hp" style="width: <?= max(0, $game['player']['hp']) ?>%">
                            <?= max(0, round($game['player']['hp'])) ?> HP
                        </div>
                    </div>
                    <div class="stats">
                        <div class="stat">💰 Or: <?= $game['player']['gold'] ?></div>
                        <div class="stat">🛡️ Défense: <?= $game['player']['defense'] ?></div>
                        <div class="stat">⚡ Puissance: <?= calculatePower($game['player']['units']) ?></div>
                    </div>
                    <div class="units-display">
                        <div class="unit-card">
                            <div class="unit-icon">🪖</div>
                            <div>Soldats: <?= $game['player']['units']['soldiers'] ?></div>
                        </div>
                        <div class="unit-card">
                            <div class="unit-icon">🚜</div>
                            <div>Tanks: <?= $game['player']['units']['tanks'] ?></div>
                        </div>
                        <div class="unit-card">
                            <div class="unit-icon">🚁</div>
                            <div>Hélicos: <?= $game['player']['units']['helicopters'] ?></div>
                        </div>
                    </div>
                </div>
                
                <div class="base enemy-base">
                    <h2>💀 <?= htmlspecialchars($game['enemy']['name']) ?></h2>
                    <div class="hp-bar">
                        <div class="hp-fill enemy-hp" style="width: <?= max(0, $game['enemy']['hp']) ?>%">
                            <?= max(0, round($game['enemy']['hp'])) ?> HP
                        </div>
                    </div>
                    <div class="stats">
                        <div class="stat">🛡️ Défense: <?= $game['enemy']['defense'] ?></div>
                        <div class="stat">⚡ Puissance: <?= calculatePower($game['enemy']['units']) ?></div>
                    </div>
                    <div class="units-display">
                        <div class="unit-card">
                            <div class="unit-icon">🪖</div>
                            <div>Soldats: <?= $game['enemy']['units']['soldiers'] ?></div>
                        </div>
                        <div class="unit-card">
                            <div class="unit-icon">🚜</div>
                            <div>Tanks: <?= $game['enemy']['units']['tanks'] ?></div>
                        </div>
                        <div class="unit-card">
                            <div class="unit-icon">🚁</div>
                            <div>Hélicos: <?= $game['enemy']['units']['helicopters'] ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <?php if (!$game['game_over']): ?>
        <div class="control-panel">
            <h3 style="margin-bottom: 15px;">🎮 ACTIONS</h3>
            <form method="POST">
                <div class="actions">
                    <button type="submit" name="action" value="attack" class="attack-btn">
                        ⚔️ ATTAQUER
                    </button>
                    <button type="submit" name="action" value="defend" class="defend-btn">
                        🛡️ DÉFENDRE<br><span class="cost">(+15 Défense)</span>
                    </button>
                    <button type="submit" name="action" value="recruit_soldier" class="recruit-btn">
                        🪖 RECRUTER SOLDAT<br><span class="cost">(-50 Or)</span>
                    </button>
                    <button type="submit" name="action" value="recruit_tank" class="recruit-btn">
                        🚜 CONSTRUIRE TANK<br><span class="cost">(-200 Or)</span>
                    </button>
                    <button type="submit" name="action" value="recruit_helicopter" class="recruit-btn">
                        🚁 ACHETER HÉLICO<br><span class="cost">(-300 Or)</span>
                    </button>
                    <button type="submit" name="action" value="upgrade_defense" class="upgrade-btn">
                        ⬆️ AMÉLIORER DÉFENSE<br><span class="cost">(-150 Or)</span>
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
        
        <div class="battle-log">
            <h3>📜 JOURNAL DE COMBAT</h3>
            <?php foreach (array_reverse($game['battle_log']) as $log): ?>
                <div class="log-entry">
                    <strong>Tour <?= $log['turn'] ?>:</strong> <?= htmlspecialchars($log['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if ($game['game_over']): ?>
        <div class="game-over <?= $game['winner'] === 'player' ? 'victory' : 'defeat' ?>">
            <?php if ($game['winner'] === 'player'): ?>
                <h2>🎉 VICTOIRE ! 🎉</h2>
                <p style="font-size: 1.5em; margin: 20px 0;">
                    Vous avez détruit la base ennemie en <?= $game['turn'] ?> tours !
                </p>
            <?php else: ?>
                <h2>💀 DÉFAITE 💀</h2>
                <p style="font-size: 1.5em; margin: 20px 0;">
                    Votre base a été détruite au tour <?= $game['turn'] ?>...
                </p>
            <?php endif; ?>
            <form method="POST">
                <button type="submit" name="action" value="reset" style="font-size: 1.2em; padding: 20px 40px;">
                    🔄 NOUVELLE PARTIE
                </button>
            </form>
        </div>
        <?php endif; ?>
        
        <form method="POST" style="text-align: center; margin-top: 20px;">
            <button type="submit" name="action" value="reset" class="reset-btn">
                🔄 RECOMMENCER LA PARTIE
            </button>
        </form>
    </div>
</body>
</html>