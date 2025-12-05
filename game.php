<?php 
require_once 'config.php';

$appearance = getAppearanceSettings($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base Defense - CFWT Enhanced</title>
    <script src="https://cdn.tailwindcss.com"></script>
   <link rel="stylesheet" href="css/all.min.css"> 
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            overflow-x: hidden;
        }
        
        .tower {
            transition: all 0.3s;
            cursor: pointer;
            position: relative;
        }
        
        .tower:hover {
            transform: scale(1.1);
            filter: brightness(1.3);
        }
        
        .tower.boosted::after {
            content: '⚡';
            position: absolute;
            top: -10px;
            right: -10px;
            font-size: 20px;
            animation: pulse 1s infinite;
        }
        
        .enemy-unit {
            transition: all 0.1s linear;
            position: relative;
        }
        
        .enemy-health-bar {
            position: absolute;
            top: -8px;
            left: 50%;
            transform: translateX(-50%);
            width: 30px;
            height: 4px;
            background: rgba(0,0,0,0.5);
            border-radius: 2px;
        }
        
        .enemy-health-fill {
            height: 100%;
            background: linear-gradient(90deg, #ef4444, #dc2626);
            border-radius: 2px;
            transition: width 0.2s;
        }
        
        .frozen {
            filter: brightness(0.7) sepia(1) hue-rotate(180deg) saturate(5);
        }
        
        .projectile {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #fbbf24;
            border-radius: 50%;
            box-shadow: 0 0 15px #fbbf24;
            transition: all 0.1s linear;
            z-index: 10;
        }
        
        @keyframes explosion {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
        
        .explosion {
            animation: explosion 0.5s ease-out;
            z-index: 15;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.2); }
        }
        
        .game-cell {
            border: 1px solid rgba(255, 255, 255, 0.05);
            transition: all 0.2s;
        }
        
        .game-cell:hover {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .game-cell.path {
            background: rgba(139, 92, 46, 0.3);
        }
        
        .game-cell.base {
            background: radial-gradient(circle, rgba(34, 197, 94, 0.3), transparent);
        }
        
        .health-bar {
            transition: width 0.3s;
        }
        
        .wave-alert {
            animation: slideDown 0.5s ease-out;
        }
        
        @keyframes slideDown {
            from { transform: translateX(-50%) translateY(-100%); opacity: 0; }
            to { transform: translateX(-50%) translateY(0); opacity: 1; }
        }
        
        .tower-menu, .ability-btn {
            backdrop-filter: blur(10px);
        }
        
        .ability-cooldown {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            font-weight: bold;
            border-radius: 0.5rem;
        }
        
        @keyframes bossShake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-2px); }
            75% { transform: translateX(2px); }
        }
        
        .boss {
            animation: bossShake 0.5s infinite;
            filter: drop-shadow(0 0 10px rgba(255, 0, 0, 0.8));
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    <div class="max-w-7xl mx-auto px-4">
        <!-- En-tête -->
        <div class="text-center mb-8">
            <h1 class="text-5xl font-bold text-white mb-4">
                <i class="fas fa-fort-awesome text-green-500 mr-3"></i>
                Base Defense Enhanced
            </h1>
            <p class="text-gray-400 text-xl">Protégez votre base contre les vagues d'ennemis !</p>
        </div>

        <!-- Écran d'accueil -->
        <div id="start-screen" class="text-center">
            <div class="bg-gray-900 bg-opacity-90 p-12 rounded-lg max-w-4xl mx-auto">
                <i class="fas fa-shield-alt text-green-500 text-8xl mb-6"></i>
                <h2 class="text-4xl font-bold text-white mb-6">Sélectionnez la Difficulté</h2>
                
                <div class="grid md:grid-cols-3 gap-6 mb-8">
                    <button onclick="startGame('easy')" class="bg-green-700 hover:bg-green-600 p-8 rounded-lg transition transform hover:scale-105">
                        <i class="fas fa-smile text-6xl mb-4 text-green-300"></i>
                        <h3 class="text-white font-bold text-2xl mb-2">Facile</h3>
                        <p class="text-gray-300">Argent: 800</p>
                        <p class="text-gray-300">Vie base: 150</p>
                    </button>
                    <button onclick="startGame('medium')" class="bg-yellow-700 hover:bg-yellow-600 p-8 rounded-lg transition transform hover:scale-105">
                        <i class="fas fa-meh text-6xl mb-4 text-yellow-300"></i>
                        <h3 class="text-white font-bold text-2xl mb-2">Normal</h3>
                        <p class="text-gray-300">Argent: 600</p>
                        <p class="text-gray-300">Vie base: 100</p>
                    </button>
                    <button onclick="startGame('hard')" class="bg-red-700 hover:bg-red-600 p-8 rounded-lg transition transform hover:scale-105">
                        <i class="fas fa-skull text-6xl mb-4 text-red-300"></i>
                        <h3 class="text-white font-bold text-2xl mb-2">Difficile</h3>
                        <p class="text-gray-300">Argent: 400</p>
                        <p class="text-gray-300">Vie base: 75</p>
                        <p class="text-red-300 font-bold mt-2">Boss Panda Final!</p>
                    </button>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg mb-6">
                    <h3 class="text-white font-bold mb-4 text-xl">Types d'Ennemis</h3>
                    <div class="grid grid-cols-2 md:grid-cols-5 gap-3 text-sm">
                        <div class="bg-gray-700 p-3 rounded">
                            <i class="fas fa-user-ninja text-red-400 text-2xl mb-2"></i>
                            <p class="text-white font-bold">Normal</p>
                            <p class="text-gray-400">Équilibré</p>
                        </div>
                        <div class="bg-gray-700 p-3 rounded">
                            <i class="fas fa-running text-yellow-400 text-2xl mb-2"></i>
                            <p class="text-white font-bold">Runner</p>
                            <p class="text-gray-400">Très rapide</p>
                        </div>
                        <div class="bg-gray-700 p-3 rounded">
                            <i class="fas fa-shield text-blue-400 text-2xl mb-2"></i>
                            <p class="text-white font-bold">Tank</p>
                            <p class="text-gray-400">Beaucoup de PV</p>
                        </div>
                        <div class="bg-gray-700 p-3 rounded">
                            <i class="fas fa-plane text-cyan-400 text-2xl mb-2"></i>
                            <p class="text-white font-bold">Aérien</p>
                            <p class="text-gray-400">Vole au-dessus</p>
                        </div>
                        <div class="bg-gray-700 p-3 rounded">
                            <i class="fas fa-dragon text-purple-400 text-2xl mb-2"></i>
                            <p class="text-white font-bold">Boss</p>
                            <p class="text-gray-400">Très puissant</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-800 p-6 rounded-lg">
                    <h3 class="text-white font-bold mb-4 text-xl">Tours et Capacités Spéciales</h3>
                    <p class="text-gray-400 mb-3">Utilisez les capacités spéciales pour vous sortir des situations difficiles !</p>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div class="bg-gray-700 p-2 rounded">
                            <i class="fas fa-bomb text-orange-400 text-xl mb-1"></i>
                            <p class="text-white font-bold">Frappe Aérienne</p>
                        </div>
                        <div class="bg-gray-700 p-2 rounded">
                            <i class="fas fa-snowflake text-cyan-400 text-xl mb-1"></i>
                            <p class="text-white font-bold">Gel</p>
                        </div>
                        <div class="bg-gray-700 p-2 rounded">
                            <i class="fas fa-bolt text-yellow-400 text-xl mb-1"></i>
                            <p class="text-white font-bold">Boost</p>
                        </div>
                        <div class="bg-gray-700 p-2 rounded">
                            <i class="fas fa-helicopter text-green-400 text-xl mb-1"></i>
                            <p class="text-white font-bold">Renfort</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Zone de jeu -->
        <div id="game-area" class="hidden">
            <!-- HUD -->
            <div class="bg-gray-900 bg-opacity-90 p-6 rounded-lg mb-4">
                <div class="flex justify-between items-center flex-wrap gap-4">
                    <div>
                        <p class="text-gray-400 text-sm">Vague</p>
                        <p class="text-white text-3xl font-bold"><span id="wave">1</span>/25</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Argent</p>
                        <p class="text-yellow-400 text-3xl font-bold">💰 <span id="money">600</span></p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Vie de la Base</p>
                        <div class="w-48 h-6 bg-gray-700 rounded-full overflow-hidden mt-2">
                            <div id="base-health" class="health-bar h-full bg-gradient-to-r from-green-500 to-green-400" style="width: 100%"></div>
                        </div>
                        <p class="text-center text-white text-sm mt-1"><span id="base-health-text">100</span>/100</p>
                    </div>
                    <div>
                        <p class="text-gray-400 text-sm">Score</p>
                        <p class="text-purple-400 text-3xl font-bold" id="score">0</p>
                    </div>
                    <button onclick="startWave()" id="start-wave-btn"
                            class="bg-gradient-to-r from-red-600 to-red-700 text-white px-6 py-3 rounded-lg font-bold hover:from-red-700 hover:to-red-800 transition">
                        <i class="fas fa-play mr-2"></i>Lancer la Vague
                    </button>
                </div>
            </div>

            <!-- Capacités Spéciales -->
            <div class="bg-gray-900 bg-opacity-90 p-4 rounded-lg mb-4">
                <p class="text-gray-400 text-sm mb-3">Capacités Spéciales :</p>
                <div class="flex flex-wrap gap-3">
                    <button onclick="useAbility('airstrike')" id="ability-airstrike" class="ability-btn relative flex-1 min-w-[140px] bg-orange-700 hover:bg-orange-600 p-4 rounded-lg text-white transition">
                        <i class="fas fa-bomb text-3xl mb-2"></i>
                        <p class="font-bold">Frappe Aérienne</p>
                        <p class="text-yellow-400 text-sm">💰 150</p>
                        <p class="text-xs mt-1">Cooldown: 45s</p>
                    </button>
                    <button onclick="useAbility('freeze')" id="ability-freeze" class="ability-btn relative flex-1 min-w-[140px] bg-cyan-700 hover:bg-cyan-600 p-4 rounded-lg text-white transition">
                        <i class="fas fa-snowflake text-3xl mb-2"></i>
                        <p class="font-bold">Gel</p>
                        <p class="text-yellow-400 text-sm">💰 100</p>
                        <p class="text-xs mt-1">Cooldown: 30s</p>
                    </button>
                    <button onclick="useAbility('boost')" id="ability-boost" class="ability-btn relative flex-1 min-w-[140px] bg-yellow-700 hover:bg-yellow-600 p-4 rounded-lg text-white transition">
                        <i class="fas fa-bolt text-3xl mb-2"></i>
                        <p class="font-bold">Boost Tours</p>
                        <p class="text-yellow-400 text-sm">💰 120</p>
                        <p class="text-xs mt-1">Cooldown: 60s</p>
                    </button>
                    <button onclick="useAbility('reinforcement')" id="ability-reinforcement" class="ability-btn relative flex-1 min-w-[140px] bg-green-700 hover:bg-green-600 p-4 rounded-lg text-white transition">
                        <i class="fas fa-helicopter text-3xl mb-2"></i>
                        <p class="font-bold">Renforts</p>
                        <p class="text-yellow-400 text-sm">💰 200</p>
                        <p class="text-xs mt-1">Cooldown: 90s</p>
                    </button>
                </div>
            </div>

            <!-- Alerte de vague -->
            <div id="wave-alert" class="hidden fixed top-1/4 left-1/2 transform -translate-x-1/2 z-50">
                <div class="wave-alert bg-red-600 text-white px-12 py-6 rounded-lg text-center shadow-2xl">
                    <p class="text-5xl font-bold mb-2">VAGUE <span id="wave-number">1</span></p>
                    <p class="text-xl"><span id="enemy-count">10</span> ennemis en approche !</p>
                    <p id="boss-warning" class="text-2xl font-bold mt-2 hidden">⚠️ BOSS INCOMING ⚠️</p>
                </div>
            </div>

            <!-- Menu des tours -->
            <div class="bg-gray-900 bg-opacity-90 p-4 rounded-lg mb-4">
                <p class="text-gray-400 text-sm mb-3">Sélectionnez une tour à construire :</p>
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3">
                    <button onclick="selectTower('basic')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-basic">
                        <i class="fas fa-gun text-gray-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Basique</p>
                        <p class="text-yellow-400 text-xs">💰 40</p>
                        <p class="text-gray-400 text-xs">DMG: 8 | RNG: 100</p>
                    </button>
                    <button onclick="selectTower('sniper')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-sniper">
                        <i class="fas fa-crosshairs text-red-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Sniper</p>
                        <p class="text-yellow-400 text-xs">💰 120</p>
                        <p class="text-gray-400 text-xs">DMG: 40 | RNG: 250</p>
                    </button>
                    <button onclick="selectTower('cannon')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-cannon">
                        <i class="fas fa-bomb text-orange-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Canon</p>
                        <p class="text-yellow-400 text-xs">💰 180</p>
                        <p class="text-gray-400 text-xs">DMG: 25 (Zone) | RNG: 130</p>
                    </button>
                    <button onclick="selectTower('missile')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-missile">
                        <i class="fas fa-rocket text-purple-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Missile</p>
                        <p class="text-yellow-400 text-xs">💰 250</p>
                        <p class="text-gray-400 text-xs">DMG: 80 | RNG: 180</p>
                    </button>
                    <button onclick="selectTower('laser')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-laser">
                        <i class="fas fa-radiation text-green-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Laser</p>
                        <p class="text-yellow-400 text-xs">💰 300</p>
                        <p class="text-gray-400 text-xs">DMG: 15/s | RNG: 140</p>
                    </button>
                    <button onclick="selectTower('antiair')" class="tower-menu bg-gray-800 hover:bg-gray-700 p-3 rounded-lg text-white transition border-2 border-transparent" id="tower-antiair">
                        <i class="fas fa-plane-slash text-cyan-400 text-2xl mb-2"></i>
                        <p class="font-bold text-sm">Anti-Aérien</p>
                        <p class="text-yellow-400 text-xs">💰 150</p>
                        <p class="text-gray-400 text-xs">DMG: 50 | Air Only</p>
                    </button>
                </div>
            </div>

            <!-- Terrain de jeu -->
            <div id="game-grid" class="bg-gray-800 rounded-lg p-2 relative" style="width: 100%; height: 700px;">
                <!-- Grille générée dynamiquement -->
            </div>
        </div>

        <!-- Écran Game Over -->
        <div id="game-over-screen" class="hidden text-center">
            <div class="bg-gray-900 bg-opacity-95 p-12 rounded-lg max-w-2xl mx-auto">
                <i id="game-over-icon" class="fas fa-skull-crossbones text-red-500 text-8xl mb-6"></i>
                <h2 id="game-over-title" class="text-4xl font-bold text-white mb-4">Mission Échouée</h2>
                
                <div class="grid grid-cols-2 gap-6 mb-8">
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <p class="text-gray-400 mb-2">Score Final</p>
                        <p class="text-yellow-400 text-4xl font-bold" id="final-score">0</p>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <p class="text-gray-400 mb-2">Vague Atteinte</p>
                        <p class="text-blue-400 text-4xl font-bold" id="final-wave">0</p>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <p class="text-gray-400 mb-2">Ennemis Éliminés</p>
                        <p class="text-red-400 text-4xl font-bold" id="total-kills">0</p>
                    </div>
                    <div class="bg-gray-800 p-6 rounded-lg">
                        <p class="text-gray-400 mb-2">Tours Construites</p>
                        <p class="text-green-400 text-4xl font-bold" id="total-towers">0</p>
                    </div>
                </div>

                <div class="flex justify-center space-x-4">
                    <button onclick="restartGame()" 
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-4 rounded-lg font-bold hover:from-green-700 hover:to-green-800 transition">
                        <i class="fas fa-redo mr-2"></i>Rejouer
                    </button>
                    <button onclick="backToStart()" 
                            class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-4 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition">
                        <i class="fas fa-home mr-2"></i>Menu Principal
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php include 'includes/footer.php'; ?>
    <script>
    // Configuration du jeu
    const GRID_SIZE = 18;
    const CELL_SIZE = 38;
    const MAX_WAVES = 25;
    
    const TOWER_TYPES = {
        basic: { cost: 40, damage: 8, range: 100, fireRate: 800, icon: 'fa-gun', color: 'text-gray-400', projectileColor: '#9ca3af' },
        sniper: { cost: 120, damage: 40, range: 250, fireRate: 2500, icon: 'fa-crosshairs', color: 'text-red-400', projectileColor: '#f87171' },
        cannon: { cost: 180, damage: 25, range: 130, fireRate: 2000, icon: 'fa-bomb', color: 'text-orange-400', splash: 60, projectileColor: '#fb923c' },
        missile: { cost: 250, damage: 80, range: 180, fireRate: 3000, icon: 'fa-rocket', color: 'text-purple-400', projectileColor: '#c084fc' },
        laser: { cost: 300, damage: 15, range: 140, fireRate: 200, icon: 'fa-radiation', color: 'text-green-400', continuous: true, projectileColor: '#4ade80' },
        antiair: { cost: 150, damage: 50, range: 200, fireRate: 1200, icon: 'fa-plane-slash', color: 'text-cyan-400', airOnly: true, projectileColor: '#22d3ee' }
    };

    const ENEMY_TYPES = {
        normal: { health: 40, speed: 1.2, reward: 15, icon: 'fa-user-ninja', color: 'text-red-400' },
        runner: { health: 20, speed: 2.5, reward: 20, icon: 'fa-running', color: 'text-yellow-400' },
        tank: { health: 150, speed: 0.6, reward: 40, icon: 'fa-shield', color: 'text-blue-400' },
        aerial: { health: 30, speed: 1.5, reward: 25, icon: 'fa-plane', color: 'text-cyan-400', flying: true },
        boss: { health: 500, speed: 0.8, reward: 200, icon: 'fa-dragon', color: 'text-purple-400', size: 2 },
        panda: { health: 2000, speed: 0.5, reward: 1000, icon: 'fa-paw', color: 'text-pink-400', size: 3, boss: true }
    };

    const ABILITIES = {
        airstrike: { cost: 150, cooldown: 45000, damage: 150, radius: 100 },
        freeze: { cost: 100, cooldown: 30000, duration: 5000 },
        boost: { cost: 120, cooldown: 60000, duration: 10000, multiplier: 2 },
        reinforcement: { cost: 200, cooldown: 90000, duration: 15000 }
    };

    // Variables du jeu
    let gameState = {
        wave: 1,
        money: 600,
        baseHealth: 100,
        maxBaseHealth: 100,
        score: 0,
        selectedTower: null,
        towers: [],
        enemies: [],
        projectiles: [],
        kills: 0,
        waveActive: false,
        path: [],
        difficulty: 'medium',
        abilityCooldowns: {},
        reinforcements: [],
        boostedTowers: new Set()
    };

    let gameLoop = null;
    let lastUpdate = Date.now();

    function startGame(difficulty) {
        gameState.difficulty = difficulty;
        
        // Ajuster les paramètres selon la difficulté
        if (difficulty === 'easy') {
            gameState.money = 800;
            gameState.baseHealth = 150;
            gameState.maxBaseHealth = 150;
        } else if (difficulty === 'medium') {
            gameState.money = 600;
            gameState.baseHealth = 100;
            gameState.maxBaseHealth = 100;
        } else if (difficulty === 'hard') {
            gameState.money = 400;
            gameState.baseHealth = 75;
            gameState.maxBaseHealth = 75;
        }
        
        document.getElementById('start-screen').classList.add('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        
        initializeGrid();
        generatePath();
        updateHUD();
        
        gameLoop = setInterval(update, 50);
    }

    function initializeGrid() {
        const grid = document.getElementById('game-grid');
        grid.innerHTML = '';
        grid.style.display = 'grid';
        grid.style.gridTemplateColumns = `repeat(${GRID_SIZE}, ${CELL_SIZE}px)`;
        grid.style.gridTemplateRows = `repeat(${GRID_SIZE}, ${CELL_SIZE}px)`;
        
        for (let y = 0; y < GRID_SIZE; y++) {
            for (let x = 0; x < GRID_SIZE; x++) {
                const cell = document.createElement('div');
                cell.className = 'game-cell';
                cell.dataset.x = x;
                cell.dataset.y = y;
                cell.style.width = CELL_SIZE + 'px';
                cell.style.height = CELL_SIZE + 'px';
                cell.onclick = () => placeTower(x, y);
                grid.appendChild(cell);
            }
        }
    }

    function generatePath() {
        gameState.path = [];
        let x = 0, y = Math.floor(GRID_SIZE / 2);
        
        while (x < GRID_SIZE) {
            gameState.path.push({ x, y });
            
            const cell = document.querySelector(`[data-x="${x}"][data-y="${y}"]`);
            if (cell) cell.classList.add('path');
            
            if (Math.random() < 0.15 && x < GRID_SIZE - 5 && x > 2) {
                const direction = Math.random() < 0.5 ? -1 : 1;
                if (y + direction >= 2 && y + direction < GRID_SIZE - 2) {
                    for (let i = 0; i < 3; i++) {
                        y += direction;
                        gameState.path.push({ x, y });
                        const turnCell = document.querySelector(`[data-x="${x}"][data-y="${y}"]`);
                        if (turnCell) turnCell.classList.add('path');
                    }
                }
            }
            x++;
        }
        
        const baseCell = document.querySelector(`[data-x="${GRID_SIZE-1}"][data-y="${y}"]`);
        if (baseCell) {
            baseCell.classList.add('base');
            baseCell.innerHTML = '<i class="fas fa-flag text-green-500 text-2xl"></i>';
        }
    }

    function selectTower(type) {
        document.querySelectorAll('.tower-menu').forEach(btn => {
            btn.classList.remove('border-blue-500');
        });
        
        const btn = document.getElementById(`tower-${type}`);
        if (btn) {
            btn.classList.add('border-blue-500');
            gameState.selectedTower = type;
        }
    }

    function placeTower(x, y) {
        if (!gameState.selectedTower) {
            alert('Sélectionnez d\'abord un type de tour !');
            return;
        }
        
        const towerType = TOWER_TYPES[gameState.selectedTower];
        
        if (gameState.money < towerType.cost) {
            alert('Pas assez d\'argent !');
            return;
        }
        
        const cell = document.querySelector(`[data-x="${x}"][data-y="${y}"]`);
        if (cell.classList.contains('path') || cell.querySelector('.tower')) {
            alert('Impossible de placer une tour ici !');
            return;
        }
        
        gameState.money -= towerType.cost;
        
        const tower = {
            x, y,
            type: gameState.selectedTower,
            ...towerType,
            lastFire: 0,
            id: Date.now() + Math.random()
        };
        
        gameState.towers.push(tower);
        
        const towerEl = document.createElement('div');
        towerEl.className = `tower absolute ${towerType.color}`;
        towerEl.style.left = x * CELL_SIZE + 'px';
        towerEl.style.top = y * CELL_SIZE + 'px';
        towerEl.style.width = CELL_SIZE + 'px';
        towerEl.style.height = CELL_SIZE + 'px';
        towerEl.style.display = 'flex';
        towerEl.style.alignItems = 'center';
        towerEl.style.justifyContent = 'center';
        towerEl.style.zIndex = '5';
        towerEl.innerHTML = `<i class="fas ${towerType.icon} text-2xl"></i>`;
        towerEl.dataset.towerId = tower.id;
        
        document.getElementById('game-grid').appendChild(towerEl);
        
        updateHUD();
    }

    function startWave() {
        if (gameState.waveActive) return;
        
        gameState.waveActive = true;
        document.getElementById('start-wave-btn').disabled = true;
        document.getElementById('start-wave-btn').classList.add('opacity-50');
        
        const wave = gameState.wave;
        const enemyCount = 8 + (wave * 3);
        
        document.getElementById('wave-number').textContent = wave;
        document.getElementById('enemy-count').textContent = enemyCount;
        
        const isBossWave = wave % 5 === 0;
        const isFinalBoss = wave === 25 && gameState.difficulty === 'hard';
        
        if (isBossWave || isFinalBoss) {
            document.getElementById('boss-warning').classList.remove('hidden');
        } else {
            document.getElementById('boss-warning').classList.add('hidden');
        }
        
        document.getElementById('wave-alert').classList.remove('hidden');
        
        setTimeout(() => {
            document.getElementById('wave-alert').classList.add('hidden');
        }, 2500);
        
        for (let i = 0; i < enemyCount; i++) {
            setTimeout(() => spawnEnemy(wave), i * 800);
        }
        
        if (isFinalBoss) {
            setTimeout(() => spawnEnemy(wave, 'panda'), enemyCount * 800 + 2000);
        } else if (isBossWave) {
            setTimeout(() => spawnEnemy(wave, 'boss'), enemyCount * 800 + 1000);
        }
    }

    function spawnEnemy(wave, forceType = null) {
        const startPos = gameState.path[0];
        
        let type;
        if (forceType) {
            type = forceType;
        } else {
            const rand = Math.random();
            if (wave < 5) {
                type = rand < 0.7 ? 'normal' : (rand < 0.9 ? 'runner' : 'tank');
            } else if (wave < 10) {
                type = rand < 0.4 ? 'normal' : (rand < 0.6 ? 'runner' : (rand < 0.8 ? 'tank' : 'aerial'));
            } else {
                type = rand < 0.25 ? 'normal' : (rand < 0.45 ? 'runner' : (rand < 0.65 ? 'tank' : (rand < 0.85 ? 'aerial' : 'boss')));
            }
        }
        
        const enemyTemplate = ENEMY_TYPES[type];
        const healthMultiplier = 1 + (wave * 0.15);
        
        const enemy = {
            x: startPos.x * CELL_SIZE + CELL_SIZE / 2,
            y: startPos.y * CELL_SIZE + CELL_SIZE / 2,
            pathIndex: 0,
            health: enemyTemplate.health * healthMultiplier,
            maxHealth: enemyTemplate.health * healthMultiplier,
            speed: enemyTemplate.speed,
            reward: enemyTemplate.reward + Math.floor(wave * 2),
            type: type,
            icon: enemyTemplate.icon,
            color: enemyTemplate.color,
            flying: enemyTemplate.flying || false,
            size: enemyTemplate.size || 1,
            frozen: false,
            frozenUntil: 0,
            id: Date.now() + Math.random()
        };
        
        gameState.enemies.push(enemy);
        
        const enemyEl = document.createElement('div');
        enemyEl.className = `enemy-unit absolute ${enemy.color}`;
        if (enemy.type === 'boss' || enemy.type === 'panda') {
            enemyEl.classList.add('boss');
        }
        enemyEl.style.left = (enemy.x - 15) + 'px';
        enemyEl.style.top = (enemy.y - 15) + 'px';
        enemyEl.style.zIndex = enemy.flying ? '8' : '3';
        const iconSize = enemy.size > 1 ? (enemy.size * 1.5) + 'em' : '1.5em';
        enemyEl.innerHTML = `
            <div class="enemy-health-bar">
                <div class="enemy-health-fill" style="width: 100%"></div>
            </div>
            <i class="fas ${enemy.icon}" style="font-size: ${iconSize}"></i>
        `;
        enemyEl.dataset.enemyId = enemy.id;
        
        document.getElementById('game-grid').appendChild(enemyEl);
    }

    function update() {
        const now = Date.now();
        const deltaTime = now - lastUpdate;
        lastUpdate = now;
        
        updateEnemies(now);
        updateTowers(now);
        updateProjectiles();
        updateReinforcements(now);
        updateAbilityCooldowns(now);
    }

    function updateEnemies(now) {
        let allDead = true;
        
        gameState.enemies.forEach((enemy, index) => {
            if (enemy.health <= 0) return;
            
            allDead = false;
            
            if (enemy.frozen && now < enemy.frozenUntil) {
                const enemyEl = document.querySelector(`[data-enemy-id="${enemy.id}"]`);
                if (enemyEl && !enemyEl.classList.contains('frozen')) {
                    enemyEl.classList.add('frozen');
                }
                return;
            } else if (enemy.frozen && now >= enemy.frozenUntil) {
                enemy.frozen = false;
                const enemyEl = document.querySelector(`[data-enemy-id="${enemy.id}"]`);
                if (enemyEl) enemyEl.classList.remove('frozen');
            }
            
            if (enemy.pathIndex < gameState.path.length - 1) {
                const target = gameState.path[enemy.pathIndex + 1];
                const targetX = target.x * CELL_SIZE + CELL_SIZE / 2;
                const targetY = target.y * CELL_SIZE + CELL_SIZE / 2;
                
                const dx = targetX - enemy.x;
                const dy = targetY - enemy.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < enemy.speed) {
                    enemy.pathIndex++;
                } else {
                    enemy.x += (dx / distance) * enemy.speed;
                    enemy.y += (dy / distance) * enemy.speed;
                }
                
                const enemyEl = document.querySelector(`[data-enemy-id="${enemy.id}"]`);
                if (enemyEl) {
                    enemyEl.style.left = (enemy.x - 15) + 'px';
                    enemyEl.style.top = (enemy.y - 15) + 'px';
                    
                    const healthBar = enemyEl.querySelector('.enemy-health-fill');
                    if (healthBar) {
                        const healthPercent = (enemy.health / enemy.maxHealth) * 100;
                        healthBar.style.width = healthPercent + '%';
                    }
                }
            } else {
                const damage = enemy.type === 'boss' ? 20 : (enemy.type === 'panda' ? 40 : 10);
                gameState.baseHealth -= damage;
                killEnemy(enemy.id, false);
                
                if (gameState.baseHealth <= 0) {
                    endGame(false);
                }
            }
        });
        
        if (gameState.waveActive && allDead) {
            gameState.waveActive = false;
            gameState.wave++;
            const bonus = 80 + (gameState.wave * 10);
            gameState.money += bonus;
            gameState.score += bonus * 5;
            
            document.getElementById('start-wave-btn').disabled = false;
            document.getElementById('start-wave-btn').classList.remove('opacity-50');
            
            if (gameState.wave > MAX_WAVES) {
                endGame(true);
            }
        }
        
        updateHUD();
    }

    function updateTowers(now) {
        gameState.towers.forEach(tower => {
            const isBoosted = gameState.boostedTowers.has(tower.id);
            const fireRate = isBoosted ? tower.fireRate / 2 : tower.fireRate;
            
            if (now - tower.lastFire < fireRate) return;
            
            let target = null;
            let minDist = Infinity;
            
            gameState.enemies.forEach((enemy) => {
                if (enemy.health <= 0) return;
                
                if (tower.airOnly && !enemy.flying) return;
                if (!tower.airOnly && enemy.flying && tower.type !== 'missile') return;
                
                const dx = enemy.x - (tower.x * CELL_SIZE + CELL_SIZE / 2);
                const dy = enemy.y - (tower.y * CELL_SIZE + CELL_SIZE / 2);
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < tower.range && dist < minDist) {
                    minDist = dist;
                    target = enemy;
                }
            });
            
            if (target) {
                tower.lastFire = now;
                const damage = isBoosted ? tower.damage * 2 : tower.damage;
                fireProjectile(tower, target, damage);
            }
        });
    }

    function fireProjectile(tower, enemy, damage) {
        const projectile = {
            x: tower.x * CELL_SIZE + CELL_SIZE / 2,
            y: tower.y * CELL_SIZE + CELL_SIZE / 2,
            targetX: enemy.x,
            targetY: enemy.y,
            damage: damage,
            speed: 7,
            enemyId: enemy.id,
            splash: tower.splash || 0,
            color: tower.projectileColor,
            id: Date.now() + Math.random()
        };
        
        gameState.projectiles.push(projectile);
        
        const projEl = document.createElement('div');
        projEl.className = 'projectile';
        projEl.style.left = projectile.x + 'px';
        projEl.style.top = projectile.y + 'px';
        projEl.style.background = projectile.color;
        projEl.style.boxShadow = `0 0 15px ${projectile.color}`;
        projEl.dataset.projId = projectile.id;
        
        document.getElementById('game-grid').appendChild(projEl);
    }

    function updateProjectiles() {
        gameState.projectiles = gameState.projectiles.filter(proj => {
            const dx = proj.targetX - proj.x;
            const dy = proj.targetY - proj.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < proj.speed) {
                hitEnemy(proj.enemyId, proj.damage, proj.splash, proj.targetX, proj.targetY);
                
                const projEl = document.querySelector(`[data-proj-id="${proj.id}"]`);
                if (projEl) projEl.remove();
                return false;
            } else {
                proj.x += (dx / distance) * proj.speed;
                proj.y += (dy / distance) * proj.speed;
                
                const projEl = document.querySelector(`[data-proj-id="${proj.id}"]`);
                if (projEl) {
                    projEl.style.left = proj.x + 'px';
                    projEl.style.top = proj.y + 'px';
                }
                return true;
            }
        });
    }

    function hitEnemy(enemyId, damage, splash, x, y) {
        if (splash > 0) {
            let hitCount = 0;
            gameState.enemies.forEach((enemy) => {
                if (enemy.health <= 0) return;
                
                const dx = enemy.x - x;
                const dy = enemy.y - y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < splash) {
                    enemy.health -= damage;
                    hitCount++;
                    if (enemy.health <= 0) {
                        killEnemy(enemy.id, true);
                    }
                }
            });
            
            if (hitCount > 0) {
                createExplosion(x, y, true);
            }
        } else {
            const enemy = gameState.enemies.find(e => e.id === enemyId);
            if (enemy && enemy.health > 0) {
                enemy.health -= damage;
                if (enemy.health <= 0) {
                    killEnemy(enemy.id, true);
                }
            }
        }
    }

    function killEnemy(enemyId, giveReward) {
        const enemyIndex = gameState.enemies.findIndex(e => e.id === enemyId);
        if (enemyIndex === -1) return;
        
        const enemy = gameState.enemies[enemyIndex];
        
        if (giveReward && enemy.health <= 0) {
            gameState.money += enemy.reward;
            gameState.score += enemy.reward * 10;
            gameState.kills++;
        }
        
        enemy.health = 0;
        
        const enemyEl = document.querySelector(`[data-enemy-id="${enemyId}"]`);
        if (enemyEl) {
            createExplosion(enemy.x, enemy.y, false);
            enemyEl.remove();
        }
    }

    function createExplosion(x, y, isSplash) {
        const explosion = document.createElement('div');
        explosion.className = 'explosion absolute text-4xl text-orange-500 pointer-events-none';
        explosion.innerHTML = isSplash ? '<i class="fas fa-burst"></i>' : '<i class="fas fa-fire"></i>';
        explosion.style.left = x + 'px';
        explosion.style.top = y + 'px';
        explosion.style.transform = 'translate(-50%, -50%)';
        
        document.getElementById('game-grid').appendChild(explosion);
        setTimeout(() => explosion.remove(), 500);
    }

    function useAbility(abilityType) {
        const ability = ABILITIES[abilityType];
        const now = Date.now();
        
        if (gameState.abilityCooldowns[abilityType] && now < gameState.abilityCooldowns[abilityType]) {
            alert('Capacité en cooldown !');
            return;
        }
        
        if (gameState.money < ability.cost) {
            alert('Pas assez d\'argent !');
            return;
        }
        
        gameState.money -= ability.cost;
        gameState.abilityCooldowns[abilityType] = now + ability.cooldown;
        
        if (abilityType === 'airstrike') {
            executeAirstrike();
        } else if (abilityType === 'freeze') {
            executeFreeze(now);
        } else if (abilityType === 'boost') {
            executeBoost(now);
        } else if (abilityType === 'reinforcement') {
            executeReinforcement(now);
        }
        
        updateHUD();
    }

    function executeAirstrike() {
        const targetPath = gameState.path[Math.floor(gameState.path.length / 2)];
        const targetX = targetPath.x * CELL_SIZE + CELL_SIZE / 2;
        const targetY = targetPath.y * CELL_SIZE + CELL_SIZE / 2;
        
        for (let i = 0; i < 3; i++) {
            setTimeout(() => {
                const offsetX = (Math.random() - 0.5) * 100;
                const offsetY = (Math.random() - 0.5) * 100;
                
                gameState.enemies.forEach(enemy => {
                    if (enemy.health <= 0) return;
                    
                    const dx = enemy.x - (targetX + offsetX);
                    const dy = enemy.y - (targetY + offsetY);
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    
                    if (dist < ABILITIES.airstrike.radius) {
                        enemy.health -= ABILITIES.airstrike.damage;
                        if (enemy.health <= 0) {
                            killEnemy(enemy.id, true);
                        }
                    }
                });
                
                createExplosion(targetX + offsetX, targetY + offsetY, true);
            }, i * 300);
        }
    }

    function executeFreeze(now) {
        gameState.enemies.forEach(enemy => {
            if (enemy.health > 0 && !enemy.frozen) {
                enemy.frozen = true;
                enemy.frozenUntil = now + ABILITIES.freeze.duration;
            }
        });
    }

    function executeBoost(now) {
        gameState.boostedTowers.clear();
        gameState.towers.forEach(tower => {
            gameState.boostedTowers.add(tower.id);
            const towerEl = document.querySelector(`[data-tower-id="${tower.id}"]`);
            if (towerEl) towerEl.classList.add('boosted');
        });
        
        setTimeout(() => {
            gameState.boostedTowers.clear();
            document.querySelectorAll('.tower').forEach(el => el.classList.remove('boosted'));
        }, ABILITIES.boost.duration);
    }

    function executeReinforcement(now) {
        const endPath = gameState.path[gameState.path.length - 1];
        
        for (let i = 0; i < 5; i++) {
            setTimeout(() => {
                const reinforcement = {
                    x: endPath.x * CELL_SIZE + CELL_SIZE / 2,
                    y: endPath.y * CELL_SIZE + CELL_SIZE / 2,
                    pathIndex: gameState.path.length - 1,
                    damage: 30,
                    expiresAt: now + ABILITIES.reinforcement.duration + (i * 500),
                    id: Date.now() + Math.random()
                };
                
                gameState.reinforcements.push(reinforcement);
                
                const reinforcementEl = document.createElement('div');
                reinforcementEl.className = 'enemy-unit absolute text-green-400';
                reinforcementEl.style.left = (reinforcement.x - 15) + 'px';
                reinforcementEl.style.top = (reinforcement.y - 15) + 'px';
                reinforcementEl.style.zIndex = '3';
                reinforcementEl.innerHTML = '<i class="fas fa-helicopter text-2xl"></i>';
                reinforcementEl.dataset.reinforcementId = reinforcement.id;
                
                document.getElementById('game-grid').appendChild(reinforcementEl);
            }, i * 500);
        }
    }

    function updateReinforcements(now) {
        gameState.reinforcements = gameState.reinforcements.filter(reinforcement => {
            if (now > reinforcement.expiresAt) {
                const el = document.querySelector(`[data-reinforcement-id="${reinforcement.id}"]`);
                if (el) el.remove();
                return false;
            }
            
            if (reinforcement.pathIndex > 0) {
                const target = gameState.path[reinforcement.pathIndex - 1];
                const targetX = target.x * CELL_SIZE + CELL_SIZE / 2;
                const targetY = target.y * CELL_SIZE + CELL_SIZE / 2;
                
                const dx = targetX - reinforcement.x;
                const dy = targetY - reinforcement.y;
                const distance = Math.sqrt(dx * dx + dy * dy);
                
                if (distance < 2) {
                    reinforcement.pathIndex--;
                } else {
                    reinforcement.x += (dx / distance) * 2;
                    reinforcement.y += (dy / distance) * 2;
                }
                
                const el = document.querySelector(`[data-reinforcement-id="${reinforcement.id}"]`);
                if (el) {
                    el.style.left = (reinforcement.x - 15) + 'px';
                    el.style.top = (reinforcement.y - 15) + 'px';
                }
                
                gameState.enemies.forEach(enemy => {
                    if (enemy.health <= 0) return;
                    
                    const dx = enemy.x - reinforcement.x;
                    const dy = enemy.y - reinforcement.y;
                    const dist = Math.sqrt(dx * dx + dy * dy);
                    
                    if (dist < 30) {
                        enemy.health -= reinforcement.damage;
                        if (enemy.health <= 0) {
                            killEnemy(enemy.id, true);
                        }
                    }
                });
            }
            
            return true;
        });
    }

    function updateAbilityCooldowns(now) {
        Object.keys(ABILITIES).forEach(abilityType => {
            const btn = document.getElementById(`ability-${abilityType}`);
            if (!btn) return;
            
            const cooldownEnd = gameState.abilityCooldowns[abilityType];
            
            let existingCooldown = btn.querySelector('.ability-cooldown');
            
            if (cooldownEnd && now < cooldownEnd) {
                const remaining = Math.ceil((cooldownEnd - now) / 1000);
                
                if (!existingCooldown) {
                    existingCooldown = document.createElement('div');
                    existingCooldown.className = 'ability-cooldown';
                    btn.appendChild(existingCooldown);
                }
                
                existingCooldown.textContent = remaining + 's';
                btn.disabled = true;
                btn.classList.add('opacity-50');
            } else {
                if (existingCooldown) {
                    existingCooldown.remove();
                }
                btn.disabled = false;
                btn.classList.remove('opacity-50');
            }
        });
    }

    function updateHUD() {
        document.getElementById('wave').textContent = gameState.wave;
        document.getElementById('money').textContent = gameState.money;
        document.getElementById('score').textContent = gameState.score;
        document.getElementById('base-health-text').textContent = Math.max(0, Math.floor(gameState.baseHealth));
        
        const healthPercent = Math.max(0, (gameState.baseHealth / gameState.maxBaseHealth) * 100);
        const healthBar = document.getElementById('base-health');
        healthBar.style.width = healthPercent + '%';
        
        if (healthPercent < 30) {
            healthBar.className = 'health-bar h-full bg-gradient-to-r from-red-500 to-red-400';
        } else if (healthPercent < 60) {
            healthBar.className = 'health-bar h-full bg-gradient-to-r from-yellow-500 to-yellow-400';
        } else {
            healthBar.className = 'health-bar h-full bg-gradient-to-r from-green-500 to-green-400';
        }
    }

    function endGame(victory) {
        clearInterval(gameLoop);
        
        document.getElementById('game-area').classList.add('hidden');
        document.getElementById('game-over-screen').classList.remove('hidden');
        
        if (victory) {
            document.getElementById('game-over-icon').className = 'fas fa-trophy text-yellow-500 text-8xl mb-6';
            document.getElementById('game-over-title').textContent = gameState.difficulty === 'hard' ? 'VICTOIRE LÉGENDAIRE !' : 'Victoire !';
        } else {
            document.getElementById('game-over-icon').className = 'fas fa-skull-crossbones text-red-500 text-8xl mb-6';
            document.getElementById('game-over-title').textContent = 'Mission Échouée';
        }
        
        document.getElementById('final-score').textContent = gameState.score;
        document.getElementById('final-wave').textContent = gameState.wave;
        document.getElementById('total-kills').textContent = gameState.kills;
        document.getElementById('total-towers').textContent = gameState.towers.length;
    }

    function restartGame() {
        const difficulty = gameState.difficulty;
        
        gameState = {
            wave: 1,
            money: 600,
            baseHealth: 100,
            maxBaseHealth: 100,
            score: 0,
            selectedTower: null,
            towers: [],
            enemies: [],
            projectiles: [],
            kills: 0,
            waveActive: false,
            path: [],
            difficulty: difficulty,
            abilityCooldowns: {},
            reinforcements: [],
            boostedTowers: new Set()
        };
        
        lastUpdate = Date.now();
        
        document.getElementById('game-over-screen').classList.add('hidden');
        
        startGame(difficulty);
    }

    function backToStart() {
        gameState = {
            wave: 1,
            money: 600,
            baseHealth: 100,
            maxBaseHealth: 100,
            score: 0,
            selectedTower: null,
            towers: [],
            enemies: [],
            projectiles: [],
            kills: 0,
            waveActive: false,
            path: [],
            difficulty: 'medium',
            abilityCooldowns: {},
            reinforcements: [],
            boostedTowers: new Set()
        };
        
        clearInterval(gameLoop);
        
        document.getElementById('game-over-screen').classList.add('hidden');
        document.getElementById('game-area').classList.add('hidden');
        document.getElementById('start-screen').classList.remove('hidden');
        
        document.getElementById('game-grid').innerHTML = '';
    }
    </script>
</body>
</html>
