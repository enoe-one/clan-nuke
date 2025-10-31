<?php
echo "<pre>";
echo "=== TEST CONNEXION RAILWAY MySQL ===\n\n";

echo "Variables d'environnement disponibles :\n";
echo "---------------------------------------\n";
echo "MYSQLHOST     : " . (getenv('MYSQLHOST') ?: '❌ NON DÉFINI') . "\n";
echo "MYSQLPORT     : " . (getenv('MYSQLPORT') ?: '❌ NON DÉFINI') . "\n";
echo "MYSQLDATABASE : " . (getenv('MYSQLDATABASE') ?: '❌ NON DÉFINI') . "\n";
echo "MYSQLUSER     : " . (getenv('MYSQLUSER') ?: '❌ NON DÉFINI') . "\n";
echo "MYSQLPASSWORD : " . (getenv('MYSQLPASSWORD') ? '✓ Défini' : '❌ NON DÉFINI') . "\n";
echo "DATABASE_URL  : " . (getenv('DATABASE_URL') ? '✓ Défini' : '❌ NON DÉFINI') . "\n\n";

// Si DATABASE_URL existe, l'utiliser
$database_url = getenv('DATABASE_URL');
if ($database_url) {
    echo "Utilisation de DATABASE_URL\n";
    $url_parts = parse_url($database_url);
    
    $host = $url_parts['host'];
    $port = $url_parts['port'] ?? 3306;
    $dbname = ltrim($url_parts['path'], '/');
    $user = $url_parts['user'];
    $pass = $url_parts['pass'];
} else {
    echo "Utilisation des variables individuelles\n";
    $host = getenv('MYSQLHOST');
    $port = getenv('MYSQLPORT') ?: 3306;
    $dbname = getenv('MYSQLDATABASE');
    $user = getenv('MYSQLUSER');
    $pass = getenv('MYSQLPASSWORD');
}

echo "\nParamètres de connexion :\n";
echo "-------------------------\n";
echo "Host     : $host\n";
echo "Port     : $port\n";
echo "Database : $dbname\n";
echo "User     : $user\n";
echo "Password : " . ($pass ? str_repeat('*', strlen($pass)) : 'vide') . "\n\n";

if (!$host || !$dbname || !$user) {
    echo "❌ ERREUR : Variables MySQL manquantes !\n";
    echo "\nVeuillez :\n";
    echo "1. Ajouter une base MySQL dans Railway\n";
    echo "2. Référencer les variables MySQL dans votre service PHP\n";
    exit;
}

echo "Test de connexion...\n";
echo "--------------------\n";

try {
    $dsn = "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4";
    
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ CONNEXION RÉUSSIE !\n\n";
    
    // Tester une requête
    $stmt = $pdo->query("SELECT VERSION() as version, DATABASE() as db");
    $result = $stmt->fetch();
    
    echo "MySQL Version : " . $result['version'] . "\n";
    echo "Base active   : " . $result['db'] . "\n\n";
    
    // Vérifier les tables
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "Tables présentes (" . count($tables) . ") :\n";
    if (count($tables) > 0) {
        foreach ($tables as $table) {
            echo "  - $table\n";
        }
    } else {
        echo "  ⚠️  Aucune table (base vide)\n";
        echo "  → Importez database_complete.sql\n";
    }
    
} catch(PDOException $e) {
    echo "❌ ERREUR DE CONNEXION :\n";
    echo $e->getMessage() . "\n\n";
    
    echo "Causes possibles :\n";
    echo "- MySQL n'est pas encore démarré (attendez 2 min)\n";
    echo "- Variables mal configurées\n";
    echo "- Problème de réseau Railway\n";
}

echo "</pre>";
?>