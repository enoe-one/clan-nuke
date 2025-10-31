<?php require_once 'config.php'; ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mini-Jeu - CFWT</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-900">
    <?php include 'includes/header.php'; ?>
    
    <div class="min-h-screen py-12">
        <div class="max-w-5xl mx-auto px-4">
            <h1 class="text-4xl font-bold text-white mb-8 text-center">Entraînement de Tir</h1>
            
            <div class="bg-gray-800 p-8 rounded-lg">
                <div class="flex justify-between items-center mb-6">
                    <div class="text-white">
                        <p class="text-2xl font-bold">Score: <span id="score">0</span></p>
                    </div>
                    <div class="text-white">
                        <p class="text-2xl font-bold">Temps: <span id="time">30</span>s</p>
                    </div>
                </div>

                <div id="start-screen" class="text-center py-20">
                    <i class="fas fa-gamepad text-blue-500 text-8xl mb-6"></i>
                    <h2 class="text-2xl text-white mb-4">Prêt pour l'entraînement ?</h2>
                    <p class="text-gray-400 mb-6">Cliquez sur les cibles avant la fin du temps !</p>
                    <button id="start-btn"
                            class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-blue-900 transition">
                        Commencer
                    </button>
                </div>

                <div id="game-screen" style="display:none; height: 400px;" class="relative bg-gradient-to-b from-sky-600 to-green-700 rounded-lg cursor-crosshair">
                </div>

                <div id="end-screen" style="display:none;" class="text-center py-20">
                    <i class="fas fa-trophy text-yellow-500 text-8xl mb-6"></i>
                    <h2 class="text-3xl text-white mb-4">Partie terminée !</h2>
                    <p class="text-2xl text-yellow-400 mb-6">Score final: <span id="final-score">0</span></p>
                    <button id="restart-btn"
                            class="bg-gradient-to-r from-blue-600 to-blue-800 text-white px-8 py-4 rounded-lg font-bold text-lg hover:from-blue-700 hover:to-blue-900 transition">
                        Rejouer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    
    <script>
    let score = 0;
    let timeLeft = 30;
    let gameActive = false;
    let timer;
    
    function startGame() {
        score = 0;
        timeLeft = 30;
        gameActive = true;
        document.getElementById('score').textContent = score;
        document.getElementById('time').textContent = timeLeft;
        document.getElementById('start-screen').style.display = 'none';
        document.getElementById('end-screen').style.display = 'none';
        document.getElementById('game-screen').style.display = 'block';
        
        generateTargets();
        
        timer = setInterval(() => {
            timeLeft--;
            document.getElementById('time').textContent = timeLeft;
            if (timeLeft <= 0) {
                endGame();
            }
        }, 1000);
    }
    
    function generateTargets() {
        const gameScreen = document.getElementById('game-screen');
        gameScreen.innerHTML = '';
        for (let i = 0; i < 5; i++) {
            createTarget();
        }
    }
    
    function createTarget() {
        const gameScreen = document.getElementById('game-screen');
        const target = document.createElement('div');
        target.className = 'absolute w-12 h-12 rounded-full bg-red-600 hover:bg-red-700 cursor-crosshair transition-all';
        target.style.left = (Math.random() * 85) + '%';
        target.style.top = (Math.random() * 75) + '%';
        target.style.border = '3px solid white';
        target.style.boxShadow = '0 0 10px rgba(0,0,0,0.5)';
        
        const center = document.createElement('div');
        center.className = 'absolute inset-0 flex items-center justify-center';
        center.innerHTML = '<div class="w-4 h-4 bg-white rounded-full"></div>';
        target.appendChild(center);
        
        target.addEventListener('click', function() {
            if (gameActive) {
                score += 10;
                document.getElementById('score').textContent = score;
                target.classList.add('scale-150', 'opacity-0', 'bg-red-500');
                setTimeout(() => {
                    target.remove();
                    createTarget();
                }, 300);
            }
        });
        
        gameScreen.appendChild(target);
    }
    
    function endGame() {
        gameActive = false;
        clearInterval(timer);
        document.getElementById('game-screen').style.display = 'none';
        document.getElementById('end-screen').style.display = 'block';
        document.getElementById('final-score').textContent = score;
    }
    
    document.getElementById('start-btn').addEventListener('click', startGame);
    document.getElementById('restart-btn').addEventListener('click', startGame);
    </script>
</body>
</html>
