php
 ⚠️ SUPPRIMER CE FICHIER APRÈS UTILISATION !

require_once 'config.php';

echo pre style='background #1a1a1a; color #00ff00; padding 20px;';
echo ╔════════════════════════════════════════╗n;
echo ║   IMPORT DATABASE_COMPLETE.SQL         ║n;
echo ╚════════════════════════════════════════╝nn;

 Lire le fichier SQL
$sql_file = 'database_complete.sql';

if (!file_exists($sql_file)) {
    die(❌ Erreur  Le fichier $sql_file n'existe pas !n);
}

echo 📄 Lecture de $sql_file...n;
$sql = file_get_contents($sql_file);

if (!$sql) {
    die(❌ Erreur  Impossible de lire le fichier !n);
}

echo ✅ Fichier lu ( . strlen($sql) .  caractères)nn;

echo 🔄 Import en cours...n;
echo ─────────────────────────────────────────n;

try {
     Séparer les requêtes
    $queries = array_filter(
        array_map('trim', explode(';', $sql)),
        function($query) {
            return !empty($query) && 
                   strpos($query, '--') !== 0 && 
                   strpos($query, '') !== 0;
        }
    );
    
    $total = count($queries);
    $success = 0;
    $errors = 0;
    
    foreach ($queries as $i = $query) {
        try {
             Ignorer les commandes USE database
            if (stripos($query, 'USE ') === 0) {
                continue;
            }
            
             Ignorer CREATE DATABASE
            if (stripos($query, 'CREATE DATABASE') !== false) {
                continue;
            }
            
            $pdo-exec($query);
            $success++;
            
             Afficher le progrès
            if ($success % 10 == 0  $success == $total) {
                $percent = round(($success  $total)  100);
                echo   ⏳ Progression  $success$total requêtes ($percent%)n;
            }
            
        } catch(PDOException $e) {
            $errors++;
             Afficher seulement les erreurs importantes
            if (strpos($e-getMessage(), 'already exists') === false) {
                echo   ⚠️  Erreur requête # . ($i + 1) .    . $e-getMessage() . n;
            }
        }
    }
    
    echo n╔════════════════════════════════════════╗n;
    echo ║   IMPORT TERMINÉ                       ║n;
    echo ╚════════════════════════════════════════╝nn;
    
    echo ✅ Succès  $success requêtesn;
    if ($errors  0) {
        echo ⚠️  Erreurs  $errors (probablement normales)n;
    }
    
     Vérifier les tables créées
    echo n📊 Tables créées n;
    echo ─────────────────────────────────────────n;
    $tables = $pdo-query(SHOW TABLES)-fetchAll(PDOFETCH_COLUMN);
    foreach ($tables as $table) {
        $count = $pdo-query(SELECT COUNT() FROM `$table`)-fetchColumn();
        echo   📁 $table ($count lignes)n;
    }
    
    echo n🎉 BASE DE DONNÉES PRÊTE !nn;
    echo 📌 Prochaines étapes n;
    echo 1. Allez sur  create_users.phpn;
    echo 2. Créez les comptes adminn;
    echo 3. SUPPRIMEZ import_db.php ET create_users.phpn;
    
} catch(PDOException $e) {
    echo ❌ ERREUR FATALE n;
    echo $e-getMessage() . n;
}

echo n⚠️  N'OUBLIEZ PAS DE SUPPRIMER CE FICHIER !n;
echo pre;
