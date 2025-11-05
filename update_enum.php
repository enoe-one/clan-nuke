<?php
$host = "mysql.railway.internal";
$db   = "railway";
$user = "root";
$pass = "JwaAIaqRIRzIGarebfqimmiKHDfnARiE";

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("
        ALTER TABLE events 
        MODIFY COLUMN type ENUM('raid', 'formation', 'reunion', 'competition', 'entrainement', 'important', 'autre') DEFAULT 'autre';
    ");

    echo "✅ Colonne 'type' modifiée avec succès !";
} catch (PDOException $e) {
    echo "❌ Erreur : " . $e->getMessage();
}
?>
