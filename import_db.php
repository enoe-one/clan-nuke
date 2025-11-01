<?php
// ⚠️ SUPPRIMER CE FICHIER APRÈS UTILISATION !
set_time_limit(300);
ob_start(); // Ajouter cette ligne au début

require_once 'config.php';

echo "<pre style='background: #000; color: #0f0; padding: 20px; font-family: monospace;'>";
echo "╔════════════════════════════════════════╗\n";
echo "║   IMPORT DATABASE CFWT                 ║\n";
echo "╚════════════════════════════════════════╝\n\n";

$sql_file = 'database_complete.sql';

if (!file_exists($sql_file)) {
    die("❌ ERREUR : Fichier $sql_file introuvable !\n");
}

echo "📄 Lecture de $sql_file...\n";
$sql = file_get_contents($sql_file);
echo "✅ " . strlen($sql) . " caractères lus\n\n";

echo "🔄 Import en cours...\n";
echo "─────────────────────────────────────────\n";

try {
    // Supprimer commentaires
    $sql = preg_replace('/^--.*$/m', '', $sql);
    $sql = preg_replace('/^\s*$/m', '', $sql);
    
    // Séparer les requêtes
    $queries = array_filter(
        explode(';', $sql),
        function($q) { 
            $q = trim($q);
            return !empty($q) && 
                   stripos($q, 'USE ') !== 0 && 
                   stripos($q, 'CREATE DATABASE') === false;
        }
    );
    
    $total = count($queries);
    $success = 0;
    
    foreach ($queries as $i => $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
            $success++;
            
            if ($success % 5 == 0 || $success == $total) {
                $pct = round(($success / $total) * 100);
                echo sprintf("  ⏳ %3d%% [%d/%d]\n", $pct, $success, $total);
                
                // Remplacer les lignes problématiques par ceci :
                if (ob_get_level() > 0) {
                    ob_flush();
                }
                flush();
            }
        } catch(PDOException $e) {
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠️  Requête #$i : " . substr($e->getMessage(), 0, 100) . "\n";
            }
        }
    }
    
    echo "\n╔════════════════════════════════════════╗\n";
    echo "║   ✅ IMPORT TERMINÉ                    ║\n";
    echo "╚════════════════════════════════════════╝\n\n";
    
    // Vérifier les tables
    echo "📁 Tables créées :\n";
    echo "─────────────────────────────────────────\n";
    
    $expected = [
        'users' => 0,
        'legions' => 2,
        'diplomes' => 35,
        'members' => 0,
        'member_diplomes' => 0,
        'member_applications' => 0,
        'faction_applications' => 0,
        'faction_members' => 0,
        'admin_logs' => 0,
        'reports' => 0
    ];
    
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($expected as $table => $expectedCount) {
        if (in_array($table, $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            $status = ($count >= $expectedCount) ? '✅' : '⚠️';
            echo sprintf("  %s %-25s %5d lignes\n", $status, $table, $count);
        } else {
            echo "  ❌ $table (MANQUANTE)\n";
        }
    }
    
    echo "\n🎉 BASE DE DONNÉES PRÊTE !\n\n";
    echo "📋 Prochaines étapes :\n";
    echo "─────────────────────────────────────────\n";
    echo "1. ✅ Base de données importée\n";
    echo "2. 👉 Allez sur : create_users.php\n";
    echo "3. 👉 Créez les 6 comptes admin\n";
    echo "4. ⚠️  SUPPRIMEZ import_db.php\n";
    echo "5. ⚠️  SUPPRIMEZ create_users.php\n";
    echo "6. ⚠️  SUPPRIMEZ database_complete.sql\n";
    
} catch(PDOException $e) {
    echo "\n❌ ERREUR FATALE :\n";
    echo $e->getMessage() . "\n";
}

echo "\n</pre>";
?>
