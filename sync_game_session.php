<?php
require_once 'config.php';

header('Content-Type: application/json');

// Créer la table si elle n'existe pas
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS game_sessions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            session_id VARCHAR(255) UNIQUE NOT NULL,
            player_name VARCHAR(100),
            difficulty VARCHAR(20) NOT NULL,
            wave INT DEFAULT 1,
            money INT DEFAULT 600,
            base_health INT DEFAULT 100,
            max_base_health INT DEFAULT 100,
            score INT DEFAULT 0,
            kills INT DEFAULT 0,
            towers_count INT DEFAULT 0,
            enemies_alive INT DEFAULT 0,
            game_state LONGTEXT,
            started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_update TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_active (is_active),
            INDEX idx_last_update (last_update),
            INDEX idx_session (session_id)
        )
    ");
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    exit;
}

// Action: Synchroniser l'état du jeu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_session') {
    $sessionId = $_POST['session_id'] ?? '';
    $playerName = $_POST['player_name'] ?? 'Invité';
    $difficulty = $_POST['difficulty'] ?? 'medium';
    $wave = intval($_POST['wave'] ?? 1);
    $money = intval($_POST['money'] ?? 600);
    $baseHealth = intval($_POST['base_health'] ?? 100);
    $maxBaseHealth = intval($_POST['max_base_health'] ?? 100);
    $score = intval($_POST['score'] ?? 0);
    $kills = intval($_POST['kills'] ?? 0);
    $towersCount = intval($_POST['towers_count'] ?? 0);
    $enemiesAlive = intval($_POST['enemies_alive'] ?? 0);
    $gameState = $_POST['game_state'] ?? '{}';
    $isActive = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
    
    if (empty($sessionId)) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        exit;
    }
    
    try {
        // Vérifier si la session existe
        $stmt = $pdo->prepare("SELECT id FROM game_sessions WHERE session_id = ?");
        $stmt->execute([$sessionId]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            // Mettre à jour la session existante
            $stmt = $pdo->prepare("
                UPDATE game_sessions 
                SET player_name = ?, 
                    difficulty = ?, 
                    wave = ?, 
                    money = ?, 
                    base_health = ?, 
                    max_base_health = ?,
                    score = ?, 
                    kills = ?, 
                    towers_count = ?, 
                    enemies_alive = ?, 
                    game_state = ?, 
                    is_active = ?,
                    last_update = NOW()
                WHERE session_id = ?
            ");
            $stmt->execute([
                $playerName, $difficulty, $wave, $money, $baseHealth, $maxBaseHealth,
                $score, $kills, $towersCount, $enemiesAlive, $gameState, $isActive, $sessionId
            ]);
        } else {
            // Créer une nouvelle session
            $stmt = $pdo->prepare("
                INSERT INTO game_sessions 
                (session_id, player_name, difficulty, wave, money, base_health, max_base_health, 
                 score, kills, towers_count, enemies_alive, game_state, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $sessionId, $playerName, $difficulty, $wave, $money, $baseHealth, $maxBaseHealth,
                $score, $kills, $towersCount, $enemiesAlive, $gameState, $isActive
            ]);
        }
        
        echo json_encode(['success' => true, 'message' => 'Session synchronized']);
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

// Action: Récupérer les commandes admin
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_commands') {
    $sessionId = $_GET['session_id'] ?? '';
    
    if (empty($sessionId)) {
        echo json_encode(['success' => false, 'message' => 'Session ID required']);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("SELECT game_state FROM game_sessions WHERE session_id = ? AND is_active = TRUE");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch();
        
        if ($session) {
            $gameState = json_decode($session['game_state'], true);
            echo json_encode(['success' => true, 'gameState' => $gameState]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Session not found']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);
?>
