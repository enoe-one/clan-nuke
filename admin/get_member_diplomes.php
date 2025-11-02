<?php
require_once '../config.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isAdmin() || !hasAccess('access_edit_members')) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

header('Content-Type: application/json');

$member_id = $_GET['member_id'] ?? 0;

if (!$member_id) {
    echo json_encode(['error' => 'ID membre manquant']);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT 
            md.id as member_diplome_id,
            d.id as diplome_id,
            d.code,
            d.nom,
            d.categorie,
            d.niveau,
            DATE_FORMAT(md.obtained_at, '%d/%m/%Y') as obtained_at
        FROM member_diplomes md
        JOIN diplomes d ON md.diplome_id = d.id
        WHERE md.member_id = ?
        ORDER BY d.categorie, d.niveau, d.nom
    ");
    
    $stmt->execute([$member_id]);
    $diplomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($diplomes);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données']);
}
?>
