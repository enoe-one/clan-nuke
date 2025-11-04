<?php
// Configuration Railway - Version simplifiée avec DATABASE_URL uniquement
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
    // Fallback variables individuelles pour local
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
session_start();

// Connexion à la base de données
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false
    ]);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données.");
}

// Inclure les fonctions d'apparence
require_once __DIR__ . '/includes/get_appearance.php';

// Vérifier le mode maintenance (sauf pour les pages d'admin et de connexion)
$current_page = basename($_SERVER['PHP_SELF']);
$excluded_pages = ['login.php', 'member_login.php', 'maintenance.php'];

if (!in_array($current_page, $excluded_pages) && 
    strpos($_SERVER['REQUEST_URI'], '/admin/') === false &&
    isMaintenanceMode($pdo)) {
    header('Location: maintenance.php');
    exit;
}

// Fonction pour logger les actions admin
function logAdminAction($pdo, $user_id, $action, $details = null) {
    try {
        $stmt = $pdo->prepare("INSERT INTO admin_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $_SERVER['REMOTE_ADDR'] ?? 'unknown']);
    } catch (PDOException $e) {
        // Erreur silencieuse
    }
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
        'Soldat', 'Caporal', 'Sergent', 'Adjudant', 'Sous-lieutenant',
        'Lieutenant', 'Capitaine', 'Commandant', 'Lieutenant-colonel',
        'Colonel', 'Général de brigade', 'Général de division',
        'Général de corps d\'armée', 'Général d\'armée'
    ];
}

// Liste des rangs disponibles
function getRangs() {
    return [
        'Recrue', '2ème classe', '1ère classe', '2ème classe (avancé)',
        'Commando', 'Vétéran'
    ];
}
?>
