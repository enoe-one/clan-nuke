<?php
// Configuration Railway - Gestion automatique MySQL
$database_url = getenv('DATABASE_URL');

if ($database_url) {
    // Railway fournit DATABASE_URL (format: mysql://user:pass@host:port/dbname)
    $url_parts = parse_url($database_url);
    
    define('DB_HOST', $url_parts['host']);
    define('DB_NAME', ltrim($url_parts['path'], '/'));
    define('DB_USER', $url_parts['user']);
    define('DB_PASS', $url_parts['pass']);
    define('DB_PORT', $url_parts['port'] ?? '3306');
} else {
    // Fallback variables individuelles
    define('DB_HOST', getenv('MYSQLHOST') ?: 'localhost');
    define('DB_NAME', getenv('MYSQLDATABASE') ?: 'railway');
    define('DB_USER', getenv('MYSQLUSER') ?: 'root');
    define('DB_PASS', getenv('MYSQLPASSWORD') ?: '');
    define('DB_PORT', getenv('MYSQLPORT') ?: '3306');
}

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
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $pdo = new PDO(
        $dsn,
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch(PDOException $e) {
    die('Erreur de connexion à la base de données. Veuillez recharger la page et attendre. 
    Si au bout de 2 minutes le site n\'est pas lancé, veuillez contacter l\'administrateur 
    en cliquant <a href="https://discord.gg/KhSBWp8X" style="color:#fff;background-color:#5865F2;padding:6px 12px;border-radius:6px;text-decoration:none;">ici</a>.');
}

// Fonctions utilitaires...
function logAdminAction($pdo, $user_id, $action, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) || isset($_SESSION['member_id']);
}

function isAdmin() {
    return isset($_SESSION['user_id']);
}

function isMember() {
    return isset($_SESSION['member_id']);
}

function hasAccess($permission) {
    return isset($_SESSION[$permission]) && $_SESSION[$permission] == 1;
}

function getGrades() {
    return [
        'Soldat', 'Caporal', 'Sergent', 'Adjudant', 'Sous-lieutenant',
        'Lieutenant', 'Capitaine', 'Commandant', 'Lieutenant-colonel',
        'Colonel', 'Général de brigade', 'Général de division',
        'Général de corps d\'armée', 'Général d\'armée'
    ];
}

function getRangs() {
    return [
        'Recrue', '2ème classe', '1ère classe', '2ème classe (avancé)',
        'Commando', 'Vétéran'
    ];
}

?>
