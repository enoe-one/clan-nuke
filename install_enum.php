<?php
/**
 * Script simple pour modifier la colonne "type" de la table "events"
 * sur Railway. Il doit être lancé depuis un service PHP ayant la variable
 * d'environnement DATABASE_URL définie.
 */

try {
    // Récupère la variable DATABASE_URL
    $databaseUrl = getenv('DATABASE_URL');
    if (!$databaseUrl) {
        die("❌ La variable DATABASE_URL n'est pas définie.\n");
    }

    // Exemple DATABASE_URL: mysql://user:pass@host:port/dbname
    $databaseUrl = str_replace('mysql://', '', $databaseUrl);
    list($auth, $hostInfo) = explode('@', $databaseUrl);
    list($user, $pass) = explode(':', $auth);
    list($hostPort, $dbName) = explode('/', $hostInfo);
    list($host, $port) = explode(':', $hostPort);

    // Connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbName;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✅ Connecté à la base MySQL.\n";

    // Commande SQL à exécuter
    $sql = "
        ALTER TABLE events 
        MODIFY COLUMN type ENUM(
            'raid', 'formation', 'reunion', 'competition', 'entrainement', 'important', 'autre'
        ) DEFAULT 'autre';
    ";

    $pdo->exec($sql);

    echo "✅ Colonne 'type' modifiée avec succès !\n";

} catch (PDOException $e) {
    die("❌ Erreur PDO : " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("❌ Erreur : " . $e->getMessage() . "\n");
}
