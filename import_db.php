<?php
// ⚠️ SUPPRIMER CE FICHIER APRÈS UTILISATION !
set_time_limit(300); // 5 minutes max

require_once 'config.php';

echo "<pre style='background: #000; color: #0f0; padding: 20px; font-family: monospace;'>";
echo "╔════════════════════════════════════════╗\n";
echo "║   IMPORT DATABASE CFWT                 ║\n";
echo "╚════════════════════════════════════════╝\n\n";

$sql_file = 'database_complete.sql';

if (!file_exists($sql_file)) {
    die("❌ ERREUR : Fichier $sql_file introuvable !\nAssurez-vous qu'il est uploadé à la racine.\n");
}

echo "📄 Lecture de $sql_file...\n";
$sql = file_get_contents($sql_file);
echo "✅ " . strlen($sql) . " caractères lus\n\n";

echo "🔄 Import en cours (peut prendre 1-2 minutes)...\n";
echo "─────────────────────────────────────────\n";

try {
    // Supprimer les commentaires et lignes vides
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
                flush();
                ob_flush();
            }
        } catch(PDOException $e) {
            // Ignorer les erreurs "already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "  ⚠️  Requête #$i : " . substr($e->getMessage(), 0, 100) . "\n";
            }
        }
    }
    
    echo "\n╔════════════════════════════════════════╗\n";
    echo "║   ✅ IMPORT TERMINÉ                    ║\n";
    echo "╚════════════════════════════════════════╝\n\n";
    
    echo "📊 Résumé :\n";
    echo "  • $success requêtes exécutées\n";
    echo "  • " . ($total - $success) . " erreurs (normales)\n\n";
    
    // Lister les tables créées
    echo "📁 Tables créées :\n";
    echo "─────────────────────────────────────────\n";
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    $expected_tables = [
        'users', 'legions', 'diplomes', 'members', 'member_diplomes',
        'member_applications', 'faction_applications', 'faction_members',
        'admin_logs', 'reports'
    ];
    
    foreach ($expected_tables as $table) {
        if (in_array($table, $tables)) {
            $count = $pdo->query("SELECT COUNT(*) FROM `$table`")->fetchColumn();
            echo sprintf("  ✅ %-25s %5d lignes\n", $table, $count);
        } else {
            echo "  ❌ $table (MANQUANTE)\n";
        }
    }
    
    echo "\n🎉 BASE DE DONNÉES PRÊTE !\n\n";
    echo "📋 Prochaines étapes :\n";
    echo "1. Allez sur : create_users.php\n";
    echo "2. Créez les comptes admin\n";
    echo "3. ⚠️  SUPPRIMEZ import_db.php ET create_users.php\n";
    
} catch(PDOException $e) {
    echo "\n❌ ERREUR FATALE :\n";
    echo $e->getMessage() . "\n\n";
    echo "MySQL est peut-être en veille.\n";
    echo "Rafraîchissez cette page dans 2 minutes.\n";
}

echo "\n</pre>";
?>
```

**Instructions :**

1. **Uploadez** `import_db.php` et `database_complete.sql` sur Railway
2. **Allez immédiatement** sur `https://votre-app.up.railway.app/import_db.php`
3. **Attendez** 1-2 minutes (ne fermez pas la page)
4. Si erreur "MySQL server has gone away", **rafraîchissez** la page (MySQL se réveillera)

---

## 🎯 Après l'import réussi

Une fois que vous voyez :
```
✅ users                     0 lignes
✅ legions                   2 lignes
✅ diplomes                 35 lignes
...
