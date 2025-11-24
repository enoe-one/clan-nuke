<?php
require_once '../config.php';

// Vérifier que l'utilisateur est connecté et a les droits
if (!isAdmin() || !hasAccess('access_edit_members')) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès refusé']);
    exit;
}

// Vérifier que member_id est fourni
if (!isset($_GET['member_id']) || empty($_GET['member_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID membre manquant']);
    exit;
}

$member_id = intval($_GET['member_id']);

try {
    // Récupérer les diplômes du membre avec les informations complètes
    $stmt = $pdo->prepare("
        SELECT 
            md.id as member_diplome_id,
            d.id as diplome_id,
            d.nom,
            d.code,
            d.niveau,
            d.categorie,
            DATE_FORMAT(md.obtained_at, '%d/%m/%Y') as obtained_at
        FROM member_diplomes md
        JOIN diplomes d ON md.diplome_id = d.id
        WHERE md.member_id = ?
        ORDER BY d.categorie, d.niveau, d.nom
    ");
    
    $stmt->execute([$member_id]);
    $diplomes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Retourner les données en JSON
    header('Content-Type: application/json');
    echo json_encode($diplomes);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur de base de données: ' . $e->getMessage()]);
    error_log("Erreur dans get_member_diplomes.php: " . $e->getMessage());
}
?>
