<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'cfwt_db');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration du site
define('SITE_URL', 'http://localhost');
define('DISCORD_INVITE', 'https://discord.gg/Jt24qeYk');

// Sécurité des sessions
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');

// Démarrer la session
session_start();

// Connexion à la base de données
try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonction pour logger les actions admin
function logAdminAction($pdo, $user_id, $action, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR']]);
}

// Fonction pour vérifier si l'utilisateur est connecté
function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['member_id']);
}

// Fonction pour vérifier si c'est un admin
function isAdmin() {
    return isset($_SESSION['user_id']);
}

// Fonction pour vérifier si c'est un membre
function isMember() {
    return isset($_SESSION['member_id']);
}

// Fonction pour vérifier les permissions admin
function hasAccess($permission) {
    return isset($_SESSION[$permission]) && $_SESSION[$permission] == 1;
}

// Liste des grades disponibles
function getGrades() {
    return [
        'Soldat',
        'Caporal',
        'Sergent',
        'Adjudant',
        'Sous-lieutenant',
        'Lieutenant',
        'Capitaine',
        'Commandant',
        'Lieutenant-colonel',
        'Colonel',
        'Général de brigade',
        'Général de division',
        'Général de corps d\'armée',
        'Général d\'armée'
    ];
}

// Liste des rangs disponibles
function getRangs() {
    return [
        'Recrue',
        '2ème classe',
        '1ère classe',
        '2ème classe (avancé)',
        'Commando',
        'Vétéran'
    ];
}
?>
