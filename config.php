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
    die('Erreur de connexion à la base de données. Veuillez recharger la page et attendre. 
    Si au bout de 2 minutes le site n\'est pas lancé, veuillez contacter l\'administrateur 
    en cliquant <a href="https://discord.gg/KhSBWp8X" style="color:#fff;background-color:#5865F2;padding:6px 12px;border-radius:6px;text-decoration:none;">ici</a>.');
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

// ===== FONCTIONS DE BASE =====

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

// ===== PERMISSIONS ÉVÉNEMENTS =====

/**
 * Vérifie si l'utilisateur peut créer des événements importants
 * Réservé aux chefs (role = 'chef') et au super admin "Enoe"
 * 
 * @param PDO $pdo Instance PDO de la base de données
 * @return bool True si l'utilisateur peut créer des événements importants
 */
function canCreateImportantEvents($pdo) {
    if (!isset($_SESSION['user_id'])) return false;
    
    try {
        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) return false;
        
        // Chefs, super admins, ou utilisateur "Enoe" spécifiquement
        return $user['role'] === 'chef' 
            || $user['role'] === 'super_admin' 
            || strtolower($user['username']) === 'enoe';
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Vérifie si l'utilisateur peut créer des événements (au moins état-major)
 * 
 * @return bool True si l'utilisateur peut créer des événements normaux
 */
function canCreateEvents() {
    return isAdmin(); // Tous les admins peuvent créer des événements normaux
}

/**
 * Vérifie si l'utilisateur peut modifier/supprimer un événement spécifique
 * - Le créateur de l'événement
 * - Les chefs et super admins
 * - Enoe
 * 
 * @param PDO $pdo Instance PDO de la base de données
 * @param int $event_id ID de l'événement
 * @return bool True si l'utilisateur peut gérer l'événement
 */
function canManageEvent($pdo, $event_id) {
    if (!isset($_SESSION['user_id'])) return false;
    
    try {
        $stmt = $pdo->prepare("
            SELECT e.created_by, u.role, u.username 
            FROM events e
            LEFT JOIN users u ON u.id = ?
            WHERE e.id = ?
        ");
        $stmt->execute([$_SESSION['user_id'], $event_id]);
        $result = $stmt->fetch();
        
        if (!$result) return false;
        
        // Le créateur de l'événement peut le modifier
        if ($result['created_by'] == $_SESSION['user_id']) return true;
        
        // Les chefs et super admins peuvent tout modifier
        if ($result['role'] === 'chef' || $result['role'] === 'super_admin') return true;
        
        // Enoe peut tout modifier
        if (strtolower($result['username']) === 'enoe') return true;
        
        return false;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Récupère le niveau de permission de l'utilisateur pour les événements
 * 
 * @param PDO $pdo Instance PDO de la base de données
 * @return string 'none', 'basic', 'important', 'full'
 */
function getEventPermissionLevel($pdo) {
    if (!isset($_SESSION['user_id'])) return 'none';
    
    try {
        $stmt = $pdo->prepare("SELECT role, username FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user) return 'none';
        
        // Enoe et super admins ont accès complet
        if ($user['role'] === 'super_admin' || strtolower($user['username']) === 'enoe') {
            return 'full';
        }
        
        // Les chefs peuvent créer des événements importants
        if ($user['role'] === 'chef') {
            return 'important';
        }
        
        // État-major et recruteurs peuvent créer des événements normaux
        if (in_array($user['role'], ['etat_major', 'recruteur', 'moderateur'])) {
            return 'basic';
        }
        
        return 'none';
    } catch (PDOException $e) {
        return 'none';
    }
}

/**
 * Retourne un badge HTML indiquant les permissions de l'utilisateur pour les événements
 * 
 * @param PDO $pdo Instance PDO de la base de données
 * @return string Badge HTML formaté
 */
function getEventPermissionBadge($pdo) {
    $level = getEventPermissionLevel($pdo);
    
    $badges = [
        'full' => '<span class="bg-red-600 text-white px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-crown mr-1"></i>Accès Complet</span>',
        'important' => '<span class="bg-orange-600 text-white px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-star mr-1"></i>Événements Importants</span>',
        'basic' => '<span class="bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-bold"><i class="fas fa-check mr-1"></i>Événements Standard</span>',
        'none' => '<span class="bg-gray-600 text-white px-3 py-1 rounded-full text-xs"><i class="fas fa-ban mr-1"></i>Aucun accès</span>'
    ];
    
    return $badges[$level] ?? $badges['none'];
}

/**
 * Génère un token CSRF pour sécuriser les formulaires
 * 
 * @return string Token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valide un token CSRF
 * 
 * @param string $token Token à valider
 * @return bool True si le token est valide
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}
?>
