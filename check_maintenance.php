<?php
// check_maintenance.php
header('Content-Type: application/json');
require_once 'config.php';

// Vérifier si une maintenance est active
$active = $pdo->query("SELECT COUNT(*) FROM maintenance_settings WHERE is_active = 1")->fetchColumn();

echo json_encode([
    'maintenance_active' => ($active > 0),
    'timestamp' => time()
]);
