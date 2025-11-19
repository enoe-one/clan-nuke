<?php 
require_once 'config.php';

// Récupérer les paramètres d'apparence pour le style
$appearance = getAppearanceSettings($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Base Defense - CFWT</title>
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
        }
        
        .tower:hover {
            transform: scale(1.1);
            filter: brightness(1.3);
        }
        
        .enemy-unit {
            transition: all 0.1s linear;
        }
        
        .projectile {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #fbbf24;
            border-radius: 50%;
            box-shadow: 0 0 15px #fbbf24;
            transition: all 0.1s linear;
        }
        
        @keyframes explosion {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
        
        .explosion {
            animation: explosion 0.5s ease-out;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .tower-range {
            border: 2px dashed rgba(59, 130, 246, 0.4);
            border-radius: 50%;
            pointer-events: none;
            animation: pulse 2s infinite;
        }
        
        .game-cell {
            border: 1px solid rgba(255, 255, 255, 0.1);
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
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
        
        .tower-menu {
            backdrop-filter: blur(10px);
            background: rgba(17, 24, 39, 0.95);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- En-tête -->
            <div class="text-center mb-8">
                <h1 class="text-5xl font-bold text-white mb-4">
                    <i class="fas fa-fort-awesome text-green-500 mr-3"></i>
                    Base Defense
                </h1>
                <p class="text-gray-400 text-xl">Protégez votre base contre les vagues d'ennemis !</p>
            </div>

            <!-- Écran d'accueil -->
            <div id="start-screen" class="text-center">
                <div class="bg-gray-900 bg-opacity-90 p-12 rounded-lg max-w-3xl mx-auto">
                    <i class="fas fa-shield-alt text-green-500 text-8xl mb-6"></i>
                    <h2 class="text-4xl font-bold text-white mb-6">Comment Jouer ?</h2>
                    
                    <div class="grid md:grid-cols-3 gap-6 mb-8 text-left">
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <i class="fas fa-tower-broadcast text-blue-400 text-3xl mb-3"></i>
                            <h3 class="text-white font-bold mb-2">1. Construisez des Tours</h3>
                            <p class="text-gray-400 text-sm">Placez des tours stratégiquement pour défendre votre base</p>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <i class="fas fa-users-slash text-red-400 text-3xl mb-3"></i>
                            <h3 class="text-white font-bold mb-2">2. Éliminez les Ennemis</h3>
                            <p class="text-gray-400 text-sm">Empêchez les ennemis d'atteindre votre base</p>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <i class="fas fa-level-up-alt text-yellow-400 text-3xl mb-3"></i>
                            <h3 class="text-white font-bold mb-2">3. Améliorez</h3>
                            <p class="text-gray-400 text-sm">Utilisez vos ressources pour améliorer vos tours</p>
                        </div>
                    </div>

                    <div class="bg-gray-800 p-6 rounded-lg mb-8">
                        <h3 class="text-white font-bold mb-4">Types de Tours</h3>
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <div>
                                <i class="fas fa-gun text-gray-400 text-2xl mb-2"></i>
                                <p class="text-white font-bold">Basique</p>
                                <p class="text-green-400">50 💰</p>
                                <p class="text-gray-400">Attaque rapide</p>
                            </div>
                            <div>
                                <i class="fas fa-crosshairs text-red-400 text-2xl mb-2"></i>
                                <p class="text-white font-bold">Sniper</p>
                                <p class="text-green-400">100 💰</p>
                                <p class="text-gray-400">Longue portée</p>
                            </div>
                            <div>
                                <i class="fas fa-bomb text-orange-400 text-2xl mb-2"></i>
                                <p class="text-white font-bold">Canon</p>
                                <p class="text-green-400">150 💰</p>
                                <p class="text-gray-400">Dégâts de zone</p>
                            </div>
                            <div>
                                <i class="fas fa-rocket text-purple-400 text-2xl mb-2"></i>
                                <p class="text-white font-bold">Missile</p>
                                <p class="text-green-400">200 💰</p>
                                <p class="text-gray-400">Très puissant</p>
                            </div>
                        </div>
                    </div>

                    <button onclick="startGame()" 
                            class="bg-gradient-to-r from-green-600 to-green-700 text-white px-12 py-4 rounded-lg font-bold text-xl hover:from-green-700 hover:to-green-800 transition">
                        <i class="fas fa-play mr-2"></i>Commencer la Partie
                    </button>
                </div>
            </div>

            <!-- Zone de jeu -->
            <div id="game-area" class="hidden">
                <!-- HUD -->
                <div class="bg-gray-900 bg-opacity-90 p-6 rounded-lg mb-4">
                    <div class="flex justify-between items-center flex-wrap gap-4">
                        <div>
                            <p class="text-gray-400 text-sm">Vague</p>
                            <p class="text-white text-3xl font-bold"><span id="wave">1</span>/20</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Argent</p>
                            <p class="text-yellow-400 text-3xl font-bold">💰 <span id="money">500</span></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Vie de la Base</p>
                            <div class="w-48 h-6 bg-gray-700 rounded-full overflow-hidden mt-2">
                                <div id="base-health" class="health-bar h-full bg-gradient-to-r from-green-500 to-green-400" style="width: 100%"></div>
                            </div>
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

                <!-- Alerte de vague -->
                <div id="wave-alert" class="hidden fixed top-1/4 left-1/2 transform -translate-x-1/2 z-50">
                    <div class="wave-alert bg-red-600 text-white px-12 py-6 rounded-lg text-center shadow-2xl">
                        <p class="text-5xl font-bold mb-2">VAGUE <span id="wave-number">1</span></p>
                        <p class="text-xl"><span id="enemy-count">10</span> ennemis en approche !</p>
                    </div>
                </div>

                <!-- Menu des tours -->
                <div class="bg-gray-900 bg-opacity-90 p-4 rounded-lg mb-4">
                    <p class="text-gray-400 text-sm mb-3">Sélectionnez une tour à construire :</p>
                    <div class="flex flex-wrap gap-3">
                        <button onclick="selectTower('basic')" 
                                class="tower-menu flex-1 min-w-[150px] bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-white transition border-2 border-transparent"
                                id="tower-basic">
                            <i class="fas fa-gun text-gray-400 text-3xl mb-2"></i>
                            <p class="font-bold">Basique</p>
                            <p class="text-yellow-400 text-sm">💰 50</p>
                            <p class="text-gray-400 text-xs mt-1">Dégâts: 10 | Portée: 100</p>
                        </button>
                        <button onclick="selectTower('sniper')" 
                                class="tower-menu flex-1 min-w-[150px] bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-white transition border-2 border-transparent"
                                id="tower-sniper">
                            <i class="fas fa-crosshairs text-red-400 text-3xl mb-2"></i>
                            <p class="font-bold">Sniper</p>
                            <p class="text-yellow-400 text-sm">💰 100</p>
                            <p class="text-gray-400 text-xs mt-1">Dégâts: 30 | Portée: 200</p>
                        </button>
                        <button onclick="selectTower('cannon')" 
                                class="tower-menu flex-1 min-w-[150px] bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-white transition border-2 border-transparent"
                                id="tower-cannon">
                            <i class="fas fa-bomb text-orange-400 text-3xl mb-2"></i>
                            <p class="font-bold">Canon</p>
                            <p class="text-yellow-400 text-sm">💰 150</p>
                            <p class="text-gray-400 text-xs mt-1">Dégâts: 50 (Zone) | Portée: 120</p>
                        </button>
                        <button onclick="selectTower('missile')" 
                                class="tower-menu flex-1 min-w-[150px] bg-gray-800 hover:bg-gray-700 p-4 rounded-lg text-white transition border-2 border-transparent"
                                id="tower-missile">
                            <i class="fas fa-rocket text-purple-400 text-3xl mb-2"></i>
                            <p class="font-bold">Missile</p>
                            <p class="text-yellow-400 text-sm">💰 200</p>
                            <p class="text-gray-400 text-xs mt-1">Dégâts: 100 | Portée: 150</p>
                        </button>
                    </div>
                </div>

                <!-- Terrain de jeu -->
                <div id="game-grid" class="bg-gray-800 rounded-lg p-2 relative" style="width: 100%; height: 600px;">
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
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Configuration du jeu
    const GRID_SIZE = 15;
    const CELL_SIZE = 40;
    const TOWER_TYPES = {
        basic: { cost: 50, damage: 10, range: 100, fireRate: 1000, icon: 'fa-gun', color: 'text-gray-400' },
        sniper: { cost: 100, damage: 30, range: 200, fireRate: 2000, icon: 'fa-crosshairs', color: 'text-red-400' },
        cannon: { cost: 150, damage: 50, range: 120, fireRate: 1500, icon: 'fa-bomb', color: 'text-orange-400', splash: 50 },
        missile: { cost: 200, damage: 100, range: 150, fireRate: 2500, icon: 'fa-rocket', color: 'text-purple-400' }
    };

    // Variables du jeu
    let gameState = {
        wave: 1,
        money: 500,
        baseHealth: 100,
        score: 0,
        selectedTower: null,
        towers: [],
        enemies: [],
        projectiles: [],
        kills: 0,
        waveActive: false,
        path: []
    };

    let gameLoop = null;

    function startGame() {
        document.getElementById('start-screen').classList.add('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        
        // Initialiser le jeu
        initializeGrid();
        generatePath();
        
        // Démarrer la boucle du jeu
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
        // Créer un chemin simple de gauche à droite avec quelques virages
        gameState.path = [];
        let x = 0, y = Math.floor(GRID_SIZE / 2);
        
        while (x < GRID_SIZE) {
            gameState.path.push({ x, y });
            
            const cell = document.querySelector(`[data-x="${x}"][data-y="${y}"]`);
            if (cell) cell.classList.add('path');
            
            // Ajouter quelques virages aléatoires
            if (Math.random() < 0.2 && x < GRID_SIZE - 3) {
                const direction = Math.random() < 0.5 ? -1 : 1;
                if (y + direction >= 0 && y + direction < GRID_SIZE) {
                    y += direction;
                }
            }
            x++;
        }
        
        // Marquer la base
        const baseCell = document.querySelector(`[data-x="${GRID_SIZE-1}"][data-y="${y}"]`);
        if (baseCell) {
            baseCell.classList.add('base');
            baseCell.innerHTML = '<i class="fas fa-flag text-green-500 text-2xl"></i>';
        }
    }

    function selectTower(type) {
        // Désélectionner l'ancienne tour
        document.querySelectorAll('.tower-menu').forEach(btn => {
            btn.classList.remove('border-blue-500');
        });
        
        // Sélectionner la nouvelle
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
        
        // Vérifier l'argent
        if (gameState.money < towerType.cost) {
            alert('Pas assez d\'argent !');
            return;
        }
        
        // Vérifier si c'est le chemin ou une tour existante
        const cell = document.querySelector(`[data-x="${x}"][data-y="${y}"]`);
        if (cell.classList.contains('path') || cell.querySelector('.tower')) {
            alert('Impossible de placer une tour ici !');
            return;
        }
        
        // Placer la tour
        gameState.money -= towerType.cost;
        
        const tower = {
            x, y,
            type: gameState.selectedTower,
            ...towerType,
            lastFire: 0
        };
        
        gameState.towers.push(tower);
        
        // Afficher la tour
        const towerEl = document.createElement('div');
        towerEl.className = `tower absolute ${towerType.color}`;
        towerEl.style.left = x * CELL_SIZE + 'px';
        towerEl.style.top = y * CELL_SIZE + 'px';
        towerEl.style.width = CELL_SIZE + 'px';
        towerEl.style.height = CELL_SIZE + 'px';
        towerEl.style.display = 'flex';
        towerEl.style.alignItems = 'center';
        towerEl.style.justifyContent = 'center';
        towerEl.innerHTML = `<i class="fas ${towerType.icon} text-2xl"></i>`;
        towerEl.dataset.towerId = gameState.towers.length - 1;
        
        document.getElementById('game-grid').appendChild(towerEl);
        
        updateHUD();
    }

    function startWave() {
        if (gameState.waveActive) return;
        
        gameState.waveActive = true;
        document.getElementById('start-wave-btn').disabled = true;
        document.getElementById('start-wave-btn').classList.add('opacity-50');
        
        // Afficher l'alerte
        const enemyCount = 10 + (gameState.wave * 2);
        document.getElementById('wave-number').textContent = gameState.wave;
        document.getElementById('enemy-count').textContent = enemyCount;
        document.getElementById('wave-alert').classList.remove('hidden');
        
        setTimeout(() => {
            document.getElementById('wave-alert').classList.add('hidden');
        }, 2000);
        
        // Spawner les ennemis
        for (let i = 0; i < enemyCount; i++) {
            setTimeout(() => spawnEnemy(), i * 1000);
        }
    }

    function spawnEnemy() {
        const startPos = gameState.path[0];
        const enemy = {
            x: startPos.x * CELL_SIZE + CELL_SIZE / 2,
            y: startPos.y * CELL_SIZE + CELL_SIZE / 2,
            pathIndex: 0,
            health: 50 + (gameState.wave * 10),
            maxHealth: 50 + (gameState.wave * 10),
            speed: 1 + (gameState.wave * 0.1),
            reward: 10 + gameState.wave
        };
        
        gameState.enemies.push(enemy);
        
        // Créer l'élément visuel
        const enemyEl = document.createElement('div');
        enemyEl.className = 'enemy-unit absolute text-red-500';
        enemyEl.style.left = enemy.x + 'px';
        enemyEl.style.top = enemy.y + 'px';
        enemyEl.innerHTML = '<i class="fas fa-user-secret text-2xl"></i>';
        enemyEl.dataset.enemyId = gameState.enemies.length - 1;
        
        document.getElementById('game-grid').appendChild(enemyEl);
    }

    function update() {
        updateEnemies();
        updateTowers();
        updateProjectiles();
    }

    function updateEnemies() {
        gameState.enemies.forEach((enemy, index) => {
            if (enemy.health <= 0) return;
            
            // Déplacement le long du chemin
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
                
                // Mettre à jour la position visuelle
                const enemyEl = document.querySelector(`[data-enemy-id="${index}"]`);
                if (enemyEl) {
                    enemyEl.style.left = enemy.x + 'px';
                    enemyEl.style.top = enemy.y + 'px';
                }
            } else {
                // Ennemi a atteint la base
                gameState.baseHealth -= 10;
                killEnemy(index);
                
                if (gameState.baseHealth <= 0) {
                    endGame(false);
                }
            }
        });
        
        // Vérifier si la vague est terminée
        if (gameState.waveActive && gameState.enemies.every(e => e.health <= 0)) {
            gameState.waveActive = false;
            gameState.wave++;
            gameState.money += 100;
            
            document.getElementById('start-wave-btn').disabled = false;
            document.getElementById('start-wave-btn').classList.remove('opacity-50');
            
            if (gameState.wave > 20) {
                endGame(true);
            }
        }
        
        updateHUD();
    }

    function updateTowers() {
        const now = Date.now();
        
        gameState.towers.forEach(tower => {
            if (now - tower.lastFire < tower.fireRate) return;
            
            // Trouver l'ennemi le plus proche dans la portée
            let target = null;
            let minDist = Infinity;
            
            gameState.enemies.forEach((enemy, index) => {
                if (enemy.health <= 0) return;
                
                const dx = enemy.x - (tower.x * CELL_SIZE + CELL_SIZE / 2);
                const dy = enemy.y - (tower.y * CELL_SIZE + CELL_SIZE / 2);
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < tower.range && dist < minDist) {
                    minDist = dist;
                    target = { enemy, index };
                }
            });
            
            if (target) {
                tower.lastFire = now;
                fireProjectile(tower, target.enemy, target.index);
            }
        });
    }

    function fireProjectile(tower, enemy, enemyIndex) {
        const projectile = {
            x: tower.x * CELL_SIZE + CELL_SIZE / 2,
            y: tower.y * CELL_SIZE + CELL_SIZE / 2,
            targetX: enemy.x,
            targetY: enemy.y,
            damage: tower.damage,
            speed: 5,
            enemyIndex,
            splash: tower.splash || 0
        };
        
        gameState.projectiles.push(projectile);
        
        // Créer l'élément visuel
        const projEl = document.createElement('div');
        projEl.className = 'projectile';
        projEl.style.left = projectile.x + 'px';
        projEl.style.top = projectile.y + 'px';
        projEl.dataset.projId = gameState.projectiles.length - 1;
        
        document.getElementById('game-grid').appendChild(projEl);
    }

    function updateProjectiles() {
        gameState.projectiles.forEach((proj, index) => {
            const dx = proj.targetX - proj.x;
            const dy = proj.targetY - proj.y;
            const distance = Math.sqrt(dx * dx + dy * dy);
            
            if (distance < proj.speed) {
                // Impact
                hitEnemy(proj.enemyIndex, proj.damage, proj.splash, proj.targetX, proj.targetY);
                
                // Retirer le projectile
                const projEl = document.querySelector(`[data-proj-id="${index}"]`);
                if (projEl) projEl.remove();
                proj.x = -1000; // Marquer comme supprimé
            } else {
                // Déplacer le projectile
                proj.x += (dx / distance) * proj.speed;
                proj.y += (dy / distance) * proj.speed;
                
                const projEl = document.querySelector(`[data-proj-id="${index}"]`);
                if (projEl) {
                    projEl.style.left = proj.x + 'px';
                    projEl.style.top = proj.y + 'px';
                }
            }
        });
        
        // Nettoyer les projectiles supprimés
        gameState.projectiles = gameState.projectiles.filter(p => p.x > -100);
    }

    function hitEnemy(enemyIndex, damage, splash, x, y) {
        if (splash > 0) {
            // Dégâts de zone
            gameState.enemies.forEach((enemy, index) => {
                if (enemy.health <= 0) return;
                
                const dx = enemy.x - x;
                const dy = enemy.y - y;
                const dist = Math.sqrt(dx * dx + dy * dy);
                
                if (dist < splash) {
                    enemy.health -= damage;
                    if (enemy.health <= 0) {
                        killEnemy(index);
                    }
                }
            });
            
            // Animation d'explosion
            createExplosion(x, y);
        } else {
            // Dégâts simples
            const enemy = gameState.enemies[enemyIndex];
            if (enemy && enemy.health > 0) {
                enemy.health -= damage;
                if (enemy.health <= 0) {
                    killEnemy(enemyIndex);
                }
            }
        }
    }

    function killEnemy(index) {
        const enemy = gameState.enemies[index];
        if (!enemy || enemy.health <= 0) {
            const enemyEl = document.querySelector(`[data-enemy-id="${index}"]`);
            if (enemyEl && enemyEl.parentElement) {
                createExplosion(enemy.x, enemy.y);
                enemyEl.remove();
            }
            return;
        }
        
        enemy.health = 0;
        gameState.money += enemy.reward;
        gameState.score += enemy.reward * 10;
        gameState.kills++;
        
        // Retirer l'élément visuel
        const enemyEl = document.querySelector(`[data-enemy-id="${index}"]`);
        if (enemyEl) {
            createExplosion(enemy.x, enemy.y);
            enemyEl.remove();
        }
    }

    function createExplosion(x, y) {
        const explosion = document.createElement('div');
        explosion.className = 'explosion absolute text-4xl text-orange-500 pointer-events-none';
        explosion.innerHTML = '<i class="fas fa-burst"></i>';
        explosion.style.left = x + 'px';
        explosion.style.top = y + 'px';
        explosion.style.transform = 'translate(-50%, -50%)';
        
        document.getElementById('game-grid').appendChild(explosion);
        setTimeout(() => explosion.remove(), 500);
    }

    function updateHUD() {
        document.getElementById('wave').textContent = gameState.wave;
        document.getElementById('money').textContent = gameState.money;
        document.getElementById('score').textContent = gameState.score;
        
        const healthPercent = Math.max(0, (gameState.baseHealth / 100) * 100);
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
            document.getElementById('game-over-title').textContent = 'Victoire !';
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
        // Réinitialiser l'état
        gameState = {
            wave: 1,
            money: 500,
            baseHealth: 100,
            score: 0,
            selectedTower: null,
            towers: [],
            enemies: [],
            projectiles: [],
            kills: 0,
            waveActive: false,
            path: []
        };
        
        document.getElementById('game-over-screen').classList.add('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        
        // Nettoyer la grille
        document.getElementById('game-grid').innerHTML = '';
        
        // Réinitialiser
        initializeGrid();
        generatePath();
        updateHUD();
        
        document.getElementById('start-wave-btn').disabled = false;
        document.getElementById('start-wave-btn').classList.remove('opacity-50');
        
        // Redémarrer la boucle
        gameLoop = setInterval(update, 50);
    }

    function backToStart() {
        // Réinitialiser l'état
        gameState = {
            wave: 1,
            money: 500,
            baseHealth: 100,
            score: 0,
            selectedTower: null,
            towers: [],
            enemies: [],
            projectiles: [],
            kills: 0,
            waveActive: false,
            path: []
        };
        
        clearInterval(gameLoop);
        
        document.getElementById('game-over-screen').classList.add('hidden');
        document.getElementById('start-screen').classList.remove('hidden');
        
        // Nettoyer la grille
        document.getElementById('game-grid').innerHTML = '';
    }
    </script>
</body>
</html>
