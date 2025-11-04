<?php
// Fonction pour récupérer les paramètres d'apparence
function getAppearanceSettings($pdo) {
    static $settings = null;
    
    if ($settings !== null) {
        return $settings;
    }
    
    $settings = [];
    
    try {
        $stmt = $pdo->query("SELECT section, content FROM site_content WHERE page = 'appearance'");
        while ($row = $stmt->fetch()) {
            $settings[$row['section']] = $row['content'];
        }
    } catch (PDOException $e) {
        // Si la table n'existe pas encore, utiliser les valeurs par défaut
    }
    
    // Valeurs par défaut
    $defaults = [
        'site_title' => 'CFWT - Coalition Française de Wars Tycoon',
        'site_description' => 'Rejoignez la plus grande coalition francophone de Wars Tycoon',
        'primary_color' => '#dc2626',
        'secondary_color' => '#2563eb',
        'accent_color' => '#7c3aed',
        'background_style' => 'gradient',
        'show_stats_home' => '1',
        'show_latest_members' => '1',
        'maintenance_mode' => '0',
        'logo_path' => ''
    ];
    
    foreach ($defaults as $key => $value) {
        if (!isset($settings[$key])) {
            $settings[$key] = $value;
        }
    }
    
    return $settings;
}

// Fonction pour vérifier le mode maintenance
function isMaintenanceMode($pdo) {
    // Les admins peuvent toujours accéder au site
    if (isAdmin()) {
        return false;
    }
    
    $settings = getAppearanceSettings($pdo);
    return $settings['maintenance_mode'] == '1';
}
?>
