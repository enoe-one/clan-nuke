<?php
echo "<pre style='background: #1a1a1a; color: #00ff00; padding: 20px;'>";
echo "╔════════════════════════════════════════╗\n";
echo "║   VÉRIFICATION FICHIERS CFWT           ║\n";
echo "╚════════════════════════════════════════╝\n\n";

$files_to_check = [
    // Pages principales
    'index.php',
    'config.php',
    'grades.php',
    'diplomes.php',
    'members.php',
    'legions.php',
    'recruitment.php',
    'recruitment_member.php',
    'recruitment_faction.php',
    'report.php',
    'login.php',
    'member_login.php',
    'game.php',
    
    // Dossiers
    'includes/header.php',
    'includes/footer.php',
    'admin/',
    'member/',
];

echo "📁 Fichiers présents :\n";
echo "─────────────────────────────────────────\n";

foreach ($files_to_check as $file) {
    $exists = file_exists($file);
    $readable = is_readable($file);
    
    if ($exists && $readable) {
        $size = filesize($file);
        echo sprintf("✅ %-30s %8d bytes\n", $file, $size);
    } elseif ($exists) {
        echo "⚠️  $file (existe mais non lisible)\n";
    } else {
        echo "❌ $file (MANQUANT)\n";
    }
}

echo "\n📂 Structure des dossiers :\n";
echo "─────────────────────────────────────────\n";
echo "Racine : " . __DIR__ . "\n";

$dirs = ['admin', 'member', 'includes'];
foreach ($dirs as $dir) {
    if (is_dir($dir)) {
        echo "✅ /$dir/\n";
        $files = scandir($dir);
        foreach ($files as $f) {
            if ($f != '.' && $f != '..') {
                echo "   ├─ $f\n";
            }
        }
    } else {
        echo "❌ /$dir/ (MANQUANT)\n";
    }
}

echo "\n🔧 Configuration PHP :\n";
echo "─────────────────────────────────────────\n";
echo "PHP Version : " . phpversion() . "\n";
echo "Document Root : " . $_SERVER['DOCUMENT_ROOT'] . "\n";
echo "Script Filename : " . $_SERVER['SCRIPT_FILENAME'] . "\n";

echo "\n🌐 Test des includes :\n";
echo "─────────────────────────────────────────\n";

// Test config.php
if (file_exists('config.php')) {
    try {
        require_once 'config.php';
        echo "✅ config.php chargé\n";
        echo "   DB_HOST: " . DB_HOST . "\n";
        echo "   DB_NAME: " . DB_NAME . "\n";
    } catch (Exception $e) {
        echo "❌ config.php erreur : " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ config.php MANQUANT\n";
}

echo "\n</pre>";
?>