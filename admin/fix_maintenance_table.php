<?php
// admin/fix_maintenance_table.php
// Script à exécuter UNE SEULE FOIS pour corriger la table maintenance_settings

require_once '../config.php';

// Vérifier que c'est bien Enoe
if (!isAdmin()) {
    die('Accès refusé');
}

$stmt = $pdo->prepare("SELECT username FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user || strtolower($user['username']) !== 'enoe') {
    die('Accès refusé - Réservé à Enoe');
}

try {
    // Supprimer l'ancienne table si elle existe
    $pdo->exec("DROP TABLE IF EXISTS maintenance_settings");
    
    // Recréer la table avec la bonne structure
    $pdo->exec("CREATE TABLE maintenance_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        is_active BOOLEAN DEFAULT FALSE,
        maintenance_type VARCHAR(50) NOT NULL DEFAULT 'scheduled',
        title VARCHAR(255) NOT NULL,
        message TEXT,
        estimated_duration VARCHAR(100),
        end_time DATETIME,
        show_countdown BOOLEAN DEFAULT TRUE,
        show_discord_link BOOLEAN DEFAULT TRUE,
        created_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        INDEX idx_active (is_active),
        INDEX idx_type (maintenance_type)
    )");
    
    echo "<h1>✅ Table maintenance_settings corrigée avec succès !</h1>";
    echo "<p>Vous pouvez maintenant créer des maintenances.</p>";
    echo "<p><a href='maintenance_manager.php'>Retour à la gestion de maintenance</a></p>";
    
    logAdminAction($pdo, $_SESSION['user_id'], 'Fix table maintenance_settings', 'Table recréée');
    
} catch (PDOException $e) {
    echo "<h1>❌ Erreur</h1>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
