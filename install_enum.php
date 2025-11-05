<?php
/**
 * Script simple pour modifier la colonne "type" de la table "events"
 * Remplis les valeurs ci-dessous avec celles de ta base MySQL
 */

$host = 'mysql.railway.internal'; // <-- ton host
$port = '3306';                             // <-- ton port
$db   = 'railway';                          // <-- nom de ta base
$user = 'root';                             // <-- utilisateur
$pass = 'JwaAIaqRIRzIGarebfqimmiKHDfnARiE';                       // <-- mot de passe

try {
    // Connexion PDO
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
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
