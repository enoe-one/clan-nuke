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
    <title>Centre d'Entraînement - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);
            overflow-x: hidden;
        }
        
        .game-card {
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .game-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
        }
        
        .game-container {
            position: relative;
            overflow: hidden;
        }
        
        /* Animations */
        @keyframes explosion {
            0% { transform: scale(0); opacity: 1; }
            100% { transform: scale(2); opacity: 0; }
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-10px); }
            75% { transform: translateX(10px); }
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        @keyframes slide-in {
            from { transform: translateX(-100%); }
            to { transform: translateX(0); }
        }
        
        .explosion {
            animation: explosion 0.5s ease-out;
        }
        
        .shake {
            animation: shake 0.3s;
        }
        
        .pulse {
            animation: pulse 1s infinite;
        }
        
        .enemy {
            transition: all 0.3s;
        }
        
        .enemy:hover {
            filter: brightness(1.2);
        }
        
        .bullet {
            position: absolute;
            width: 8px;
            height: 8px;
            background: #fbbf24;
            border-radius: 50%;
            box-shadow: 0 0 10px #fbbf24;
        }
        
        .crosshair {
            cursor: crosshair;
        }
        
        .health-bar {
            transition: width 0.3s;
        }
        
        .combo-text {
            animation: slide-in 0.5s;
        }
        
        /* Radar */
        .radar-dot {
            animation: pulse 2s infinite;
        }
        
        /* Particules */
        .particle {
            position: absolute;
            pointer-events: none;
        }
        
        /* Mode nuit */
        .night-mode {
            background: radial-gradient(circle at 50% 50%, #1a1a2e 0%, #0a0a15 100%);
        }
        
        /* Écran de sélection */
        .mode-selector {
            backdrop-filter: blur(10px);
            background: rgba(30, 41, 59, 0.8);
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-7xl mx-auto px-4">
            <!-- En-tête -->
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-white mb-4">
                    <i class="fas fa-crosshairs text-red-500 mr-3"></i>
                    Centre d'Entraînement Militaire
                </h1>
                <p class="text-gray-400 text-xl">Entraînez vos compétences de combat</p>
            </div>

            <!-- Sélection du mode -->
            <div id="mode-selection" class="grid md:grid-cols-3 gap-8 mb-12">
                <!-- Mode 1: Tir sur cibles -->
                <div class="game-card bg-gradient-to-br from-red-900 to-red-700 p-8 rounded-lg text-white text-center" 
                     onclick="startGame('shooting')">
                    <i class="fas fa-bullseye text-6xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Tir de Précision</h3>
                    <p class="text-red-200 mb-4">Visez et éliminez les cibles qui apparaissent</p>
                    <div class="flex justify-center space-x-4 text-sm">
                        <span><i class="fas fa-clock mr-1"></i>60s</span>
                        <span><i class="fas fa-crosshairs mr-1"></i>Précision</span>
                    </div>
                    <button class="mt-6 bg-white text-red-900 px-6 py-3 rounded-lg font-bold hover:bg-red-100 transition">
                        Commencer
                    </button>
                </div>

                <!-- Mode 2: Défense de base -->
                <div class="game-card bg-gradient-to-br from-blue-900 to-blue-700 p-8 rounded-lg text-white text-center" 
                     onclick="startGame('defense')">
                    <i class="fas fa-shield-alt text-6xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Défense de Base</h3>
                    <p class="text-blue-200 mb-4">Protégez votre base contre les ennemis</p>
                    <div class="flex justify-center space-x-4 text-sm">
                        <span><i class="fas fa-heart mr-1"></i>3 vies</span>
                        <span><i class="fas fa-users-slash mr-1"></i>Survie</span>
                    </div>
                    <button class="mt-6 bg-white text-blue-900 px-6 py-3 rounded-lg font-bold hover:bg-blue-100 transition">
                        Commencer
                    </button>
                </div>

                <!-- Mode 3: Mission Commando -->
                <div class="game-card bg-gradient-to-br from-green-900 to-green-700 p-8 rounded-lg text-white text-center" 
                     onclick="startGame('commando')">
                    <i class="fas fa-user-ninja text-6xl mb-4"></i>
                    <h3 class="text-2xl font-bold mb-3">Mission Commando</h3>
                    <p class="text-green-200 mb-4">Éliminez les ennemis sans être détecté</p>
                    <div class="flex justify-center space-x-4 text-sm">
                        <span><i class="fas fa-eye-slash mr-1"></i>Furtif</span>
                        <span><i class="fas fa-star mr-1"></i>Expert</span>
                    </div>
                    <button class="mt-6 bg-white text-green-900 px-6 py-3 rounded-lg font-bold hover:bg-green-100 transition">
                        Commencer
                    </button>
                </div>
            </div>

            <!-- Zone de jeu -->
            <div id="game-area" class="hidden">
                <!-- HUD (Head-Up Display) -->
                <div class="bg-gray-900 bg-opacity-90 p-6 rounded-lg mb-4">
                    <div class="flex justify-between items-center flex-wrap gap-4">
                        <div>
                            <p class="text-gray-400 text-sm">Score</p>
                            <p class="text-white text-3xl font-bold" id="score">0</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Combo</p>
                            <p class="text-yellow-400 text-3xl font-bold" id="combo">x1</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Temps</p>
                            <p class="text-white text-3xl font-bold" id="timer">60</p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Munitions</p>
                            <p class="text-white text-3xl font-bold" id="ammo">∞</p>
                        </div>
                        <div id="health-container">
                            <p class="text-gray-400 text-sm mb-1">Santé</p>
                            <div class="w-48 h-6 bg-gray-700 rounded-full overflow-hidden">
                                <div id="health-bar" class="health-bar h-full bg-gradient-to-r from-green-500 to-green-400" style="width: 100%"></div>
                            </div>
                        </div>
                        <button onclick="pauseGame()" class="bg-yellow-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-yellow-700 transition">
                            <i class="fas fa-pause mr-2"></i>Pause
                        </button>
                    </div>
                    
                    <!-- Barre de progression mission -->
                    <div id="mission-progress" class="mt-4 hidden">
                        <div class="flex justify-between items-center mb-2">
                            <p class="text-gray-400 text-sm">Objectif</p>
                            <p class="text-white text-sm" id="objective-text">0/10 ennemis</p>
                        </div>
                        <div class="w-full h-3 bg-gray-700 rounded-full overflow-hidden">
                            <div id="objective-bar" class="h-full bg-gradient-to-r from-blue-500 to-purple-500 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>
                </div>

                <!-- Terrain de jeu -->
                <div id="game-canvas" class="game-container crosshair bg-gradient-to-b from-gray-800 to-gray-900 rounded-lg relative" 
                     style="height: 600px;">
                    <!-- Contenu généré dynamiquement -->
                </div>

                <!-- Messages -->
                <div id="game-messages" class="fixed top-1/4 left-1/2 transform -translate-x-1/2 text-center pointer-events-none">
                    <div id="combo-message" class="combo-text text-6xl font-bold text-yellow-400 drop-shadow-lg hidden"></div>
                    <div id="hit-message" class="text-4xl font-bold text-red-500 drop-shadow-lg hidden">HEADSHOT!</div>
                </div>
            </div>

            <!-- Écran de fin -->
            <div id="game-over" class="hidden text-center">
                <div class="bg-gray-900 bg-opacity-95 p-12 rounded-lg max-w-2xl mx-auto">
                    <i class="fas fa-trophy text-yellow-500 text-8xl mb-6"></i>
                    <h2 class="text-4xl font-bold text-white mb-4">Mission Terminée !</h2>
                    
                    <div class="grid grid-cols-2 gap-6 mb-8">
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <p class="text-gray-400 mb-2">Score Final</p>
                            <p class="text-yellow-400 text-4xl font-bold" id="final-score">0</p>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <p class="text-gray-400 mb-2">Précision</p>
                            <p class="text-green-400 text-4xl font-bold" id="accuracy">0%</p>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <p class="text-gray-400 mb-2">Meilleur Combo</p>
                            <p class="text-purple-400 text-4xl font-bold" id="best-combo">x1</p>
                        </div>
                        <div class="bg-gray-800 p-6 rounded-lg">
                            <p class="text-gray-400 mb-2">Ennemis Éliminés</p>
                            <p class="text-red-400 text-4xl font-bold" id="kills">0</p>
                        </div>
                    </div>

                    <!-- Médailles -->
                    <div id="medals" class="mb-8 hidden">
                        <p class="text-gray-400 mb-4">Médailles Obtenues</p>
                        <div class="flex justify-center space-x-4" id="medals-list"></div>
                    </div>

                    <!-- Classement -->
                    <div id="rank" class="mb-8">
                        <p class="text-gray-400 mb-2">Grade Obtenu</p>
                        <p class="text-white text-3xl font-bold" id="rank-name">Recrue</p>
                    </div>

                    <div class="flex justify-center space-x-4">
                        <button onclick="restartGame()" 
                                class="bg-gradient-to-r from-green-600 to-green-700 text-white px-8 py-4 rounded-lg font-bold hover:from-green-700 hover:to-green-800 transition">
                            <i class="fas fa-redo mr-2"></i>Rejouer
                        </button>
                        <button onclick="backToMenu()" 
                                class="bg-gradient-to-r from-blue-600 to-blue-700 text-white px-8 py-4 rounded-lg font-bold hover:from-blue-700 hover:to-blue-800 transition">
                            <i class="fas fa-home mr-2"></i>Menu Principal
                        </button>
                    </div>
                </div>
            </div>

            <!-- Meilleurs scores -->
            <div class="mt-12 bg-gray-900 bg-opacity-80 p-8 rounded-lg">
                <h3 class="text-3xl font-bold text-white mb-6 text-center">
                    <i class="fas fa-medal text-yellow-500 mr-2"></i>
                    Hall of Fame
                </h3>
                <div class="grid md:grid-cols-3 gap-6">
                    <div>
                        <p class="text-gray-400 text-center mb-4">Tir de Précision</p>
                        <div class="space-y-2" id="leaderboard-shooting">
                            <div class="bg-gray-800 p-3 rounded flex justify-between items-center">
                                <span class="text-white">🥇 En attente...</span>
                                <span class="text-yellow-400 font-bold">0</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-gray-400 text-center mb-4">Défense de Base</p>
                        <div class="space-y-2" id="leaderboard-defense">
                            <div class="bg-gray-800 p-3 rounded flex justify-between items-center">
                                <span class="text-white">🥇 En attente...</span>
                                <span class="text-yellow-400 font-bold">0</span>
                            </div>
                        </div>
                    </div>
                    <div>
                        <p class="text-gray-400 text-center mb-4">Mission Commando</p>
                        <div class="space-y-2" id="leaderboard-commando">
                            <div class="bg-gray-800 p-3 rounded flex justify-between items-center">
                                <span class="text-white">🥇 En attente...</span>
                                <span class="text-yellow-400 font-bold">0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script>
    // Variables globales
    let gameMode = null;
    let gameActive = false;
    let gamePaused = false;
    let score = 0;
    let combo = 1;
    let maxCombo = 1;
    let health = 100;
    let timer = 60;
    let ammo = Infinity;
    let enemies = [];
    let kills = 0;
    let shots = 0;
    let hits = 0;
    let gameInterval = null;
    let spawnInterval = null;
    let comboTimeout = null;
    
    // Sons (simulation avec messages console pour le moment)
    const sounds = {
        shoot: () => console.log('🔫 PEW!'),
        hit: () => console.log('💥 HIT!'),
        miss: () => console.log('❌ MISS'),
        explosion: () => console.log('💣 BOOM!'),
        combo: () => console.log('🔥 COMBO!')
    };

    function startGame(mode) {
        gameMode = mode;
        document.getElementById('mode-selection').classList.add('hidden');
        document.getElementById('game-area').classList.remove('hidden');
        
        // Réinitialiser les variables
        score = 0;
        combo = 1;
        maxCombo = 1;
        health = 100;
        kills = 0;
        shots = 0;
        hits = 0;
        enemies = [];
        
        // Configuration selon le mode
        if (mode === 'shooting') {
            timer = 60;
            document.getElementById('health-container').classList.add('hidden');
            document.getElementById('mission-progress').classList.add('hidden');
        } else if (mode === 'defense') {
            timer = 999;
            document.getElementById('health-container').classList.remove('hidden');
            document.getElementById('mission-progress').classList.add('hidden');
        } else if (mode === 'commando') {
            timer = 90;
            document.getElementById('health-container').classList.remove('hidden');
            document.getElementById('mission-progress').classList.remove('hidden');
        }
        
        updateHUD();
        gameActive = true;
        gamePaused = false;
        
        // Démarrer le jeu
        gameInterval = setInterval(gameLoop, 1000);
        startSpawning();
        
        // Événements de clic
        const canvas = document.getElementById('game-canvas');
        canvas.addEventListener('click', handleClick);
    }

    function gameLoop() {
        if (gamePaused || !gameActive) return;
        
        if (gameMode === 'shooting' || gameMode === 'commando') {
            timer--;
            if (timer <= 0) {
                endGame();
            }
        }
        
        updateHUD();
        moveEnemies();
    }

    function startSpawning() {
        const spawnRate = gameMode === 'commando' ? 2000 : 1500;
        spawnInterval = setInterval(() => {
            if (!gamePaused && gameActive) {
                spawnEnemy();
            }
        }, spawnRate);
    }

    function spawnEnemy() {
        const canvas = document.getElementById('game-canvas');
        const enemy = document.createElement('div');
        
        const types = [
            { icon: 'fa-user-secret', color: 'text-red-500', points: 10, speed: 2 },
            { icon: 'fa-user-ninja', color: 'text-purple-500', points: 20, speed: 3 },
            { icon: 'fa-user-astronaut', color: 'text-blue-500', points: 15, speed: 2.5 }
        ];
        
        const type = types[Math.floor(Math.random() * types.length)];
        
        enemy.className = `enemy absolute text-5xl ${type.color} cursor-crosshair`;
        enemy.innerHTML = `<i class="fas ${type.icon}"></i>`;
        enemy.style.left = Math.random() * (canvas.offsetWidth - 60) + 'px';
        enemy.style.top = Math.random() * (canvas.offsetHeight - 60) + 'px';
        
        enemy.dataset.points = type.points;
        enemy.dataset.speed = type.speed;
        enemy.dataset.id = Date.now() + Math.random();
        
        canvas.appendChild(enemy);
        enemies.push(enemy);
        
        // Animation d'apparition
        enemy.style.transform = 'scale(0)';
        setTimeout(() => {
            enemy.style.transform = 'scale(1)';
        }, 10);
    }

    function moveEnemies() {
        if (gameMode !== 'defense') return;
        
        enemies.forEach(enemy => {
            const currentTop = parseFloat(enemy.style.top);
            const speed = parseFloat(enemy.dataset.speed);
            const newTop = currentTop + speed;
            
            if (newTop > document.getElementById('game-canvas').offsetHeight) {
                // Ennemi a atteint la base
                takeDamage(10);
                enemy.remove();
                enemies = enemies.filter(e => e !== enemy);
            } else {
                enemy.style.top = newTop + 'px';
            }
        });
    }

    function handleClick(e) {
        if (!gameActive || gamePaused) return;
        
        shots++;
        sounds.shoot();
        
        // Effet de tir
        createBulletTrail(e.clientX, e.clientY);
        
        // Vérifier si on a touché un ennemi
        const clickedEnemy = e.target.closest('.enemy');
        
        if (clickedEnemy) {
            hitEnemy(clickedEnemy);
        } else {
            missShot();
        }
    }

    function hitEnemy(enemy) {
        hits++;
        kills++;
        const points = parseInt(enemy.dataset.points);
        score += points * combo;
        
        // Augmenter le combo
        combo++;
        if (combo > maxCombo) maxCombo = combo;
        
        // Réinitialiser le timeout de combo
        clearTimeout(comboTimeout);
        comboTimeout = setTimeout(() => {
            combo = 1;
        }, 3000);
        
        // Afficher combo
        if (combo >= 5) {
            showComboMessage(combo);
            sounds.combo();
        }
        
        // Animation d'explosion
        createExplosion(enemy);
        sounds.hit();
        
        // Retirer l'ennemi
        enemy.remove();
        enemies = enemies.filter(e => e !== enemy);
        
        updateHUD();
    }

    function missShot() {
        combo = Math.max(1, combo - 1);
        sounds.miss();
        updateHUD();
    }

    function takeDamage(amount) {
        health = Math.max(0, health - amount);
        updateHUD();
        
        // Animation de secousse
        document.getElementById('game-canvas').classList.add('shake');
        setTimeout(() => {
            document.getElementById('game-canvas').classList.remove('shake');
        }, 300);
        
        if (health <= 0) {
            endGame();
        }
    }

    function createExplosion(element) {
        const explosion = document.createElement('div');
        explosion.className = 'explosion absolute text-6xl text-red-500 pointer-events-none';
        explosion.innerHTML = '<i class="fas fa-burst"></i>';
        explosion.style.left = element.style.left;
        explosion.style.top = element.style.top;
        
        document.getElementById('game-canvas').appendChild(explosion);
        setTimeout(() => explosion.remove(), 500);
    }

    function createBulletTrail(x, y) {
        const bullet = document.createElement('div');
        bullet.className = 'bullet';
        bullet.style.left = x + 'px';
        bullet.style.top = y + 'px';
        
        document.body.appendChild(bullet);
        setTimeout(() => bullet.remove(), 100);
    }

    function showComboMessage(comboValue) {
        const message = document.getElementById('combo-message');
        message.textContent = `${comboValue}x COMBO!`;
        message.classList.remove('hidden');
        
        setTimeout(() => {
            message.classList.add('hidden');
        }, 1000);
    }

    function updateHUD() {
        document.getElementById('score').textContent = score;
        document.getElementById('combo').textContent = 'x' + combo;
        document.getElementById('timer').textContent = timer;
        
        if (gameMode !== 'shooting') {
            const healthBar = document.getElementById('health-bar');
            healthBar.style.width = health + '%';
            
            if (health < 30) {
                healthBar.className = 'health-bar h-full bg-gradient-to-r from-red-500 to-red-400';
            } else if (health < 60) {
                healthBar.className = 'health-bar h-full bg-gradient-to-r from-yellow-500 to-yellow-400';
            }
        }
        
        if (gameMode === 'commando') {
            const progress = Math.min(100, (kills / 20) * 100);
            document.getElementById('objective-bar').style.width = progress + '%';
            document.getElementById('objective-text').textContent = `${kills}/20 ennemis`;
        }
    }

    function pauseGame() {
        gamePaused = !gamePaused;
        const btn = event.target.closest('button');
        
        if (gamePaused) {
            btn.innerHTML = '<i class="fas fa-play mr-2"></i>Reprendre';
            btn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
            btn.classList.add('bg-green-600', 'hover:bg-green-700');
        } else {
            btn.innerHTML = '<i class="fas fa-pause mr-2"></i>Pause';
            btn.classList.remove('bg-green-600', 'hover:bg-green-700');
            btn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
        }
    }

    function endGame() {
        gameActive = false;
        clearInterval(gameInterval);
        clearInterval(spawnInterval);
        
        // Calculer les stats
        const accuracy = shots > 0 ? Math.round((hits / shots) * 100) : 0;
        
        // Déterminer le grade
        let rank = 'Recrue';
        if (score > 5000) rank = 'Général';
        else if (score > 3000) rank = 'Colonel';
        else if (score > 2000) rank = 'Commandant';
        else if (score > 1000) rank = 'Capitaine';
        else if (score > 500) rank = 'Sergent';
        
        // Afficher l'écran de fin
        document.getElementById('game-area').classList.add('hidden');
        document.getElementById('game-over').classList.remove('hidden');
        
        document.getElementById('final-score').textContent = score;
        document.getElementById('accuracy').textContent = accuracy + '%';
        document.getElementById('best-combo').textContent = 'x' + maxCombo;
        document.getElementById('kills').textContent = kills;
        document.getElementById('rank-name').textContent = rank;
        
        // Médailles
        const medals = [];
        if (accuracy >= 90) medals.push({ icon: 'fa-crosshairs', name: 'Tireur d\'élite', color: 'text-yellow-400' });
        if (maxCombo >= 10) medals.push({ icon: 'fa-fire', name: 'Combo Master', color: 'text-red-400' });
        if (kills >= 50) medals.push({ icon: 'fa-skull', name: 'Exterminateur', color: 'text-purple-400' });
        if (score >= 3000) medals.push({ icon: 'fa-trophy', name: 'Champion', color: 'text-yellow-400' });
        
        if (medals.length > 0) {
            document.getElementById('medals').classList.remove('hidden');
            const medalsList = document.getElementById('medals-list');
            medalsList.innerHTML = medals.map(m => 
                `<div class="text-center">
                    <i class="fas ${m.icon} ${m.color} text-4xl mb-2"></i>
                    <p class="text-white text-sm">${m.name}</p>
                </div>`
            ).join('');
        }
    }

    function restartGame() {
        document.getElementById('game-over').classList.add('hidden');
        document.getElementById('game-canvas').innerHTML = '';
        startGame(gameMode);
    }

    function backToMenu() {
        document.getElementById('game-over').classList.add('hidden');
        document.getElementById('game-canvas').innerHTML = '';
        document.getElementById('mode-selection').classList.remove('hidden');
        gameMode = null;
    }
    </script>
</body>
</html>
