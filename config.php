<?php
// Configuration de la base de données pour Railway
// Railway fournit les variables d'environnement automatiquement
$db_host = getenv('MYSQLHOST') ?: 'localhost';
$db_name = getenv('MYSQLDATABASE') ?: 'railway';
$db_user = getenv('MYSQLUSER') ?: 'root';
$db_pass = getenv('MYSQLPASSWORD') ?: '';
$db_port = getenv('MYSQLPORT') ?: '3306';

define('DB_HOST', $db_host);
define('DB_NAME', $db_name);
define('DB_USER', $db_user);
define('DB_PASS', $db_pass);
define('DB_PORT', $db_port);

// Configuration du site
define('SITE_URL', getenv('RAILWAY_PUBLIC_DOMAIN') ? 'https://' . getenv('RAILWAY_PUBLIC_DOMAIN') : 'http://localhost');
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
        "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4",
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