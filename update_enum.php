<?php
try {
    // 1️⃣ Récupère la variable d'environnement DATABASE_URL
    $url = getenv('DATABASE_URL');

    if (!$url) {
        throw new Exception("❌ La variable DATABASE_URL n'est pas définie sur Railway !");
    }

    // 2️⃣ Analyse l'URL de connexion MySQL
    // Exemple : mysql://user:pass@host:port/database
    $url = str_replace('mysql://', '', $url);
    list($auth, $hostinfo) = explode('@', $url);
    list($user, $pass) = explode(':', $auth);
    list($hostport, $db) = explode('/', $hostinfo);
    list($host, $port) = explode(':', $hostport);

    // 3️⃣ Connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user,
        $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    echo "✅ Connecté à la base MySQL Railway !<br>";

    // 4️⃣ Exécute la commande SQL
    $sql = "
        ALTER TABLE events 
        MODIFY COLUMN type ENUM('raid', 'formation', 'reunion', 'competition', 'entrainement', 'important', 'autre') DEFAULT 'autre';
    ";

    $pdo->exec($sql);

    echo "✅ Colonne 'type' modifiée avec succès !";

} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage();
} catch (PDOException $e) {
    echo "❌ Erreur PDO : " . $e->getMessage();
}
?>
