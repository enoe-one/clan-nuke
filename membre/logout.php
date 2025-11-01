<?php
require_once '../config.php';

// Supprimer uniquement les variables de session membre
// mais garder la session active pour l'admin si nécessaire
unset($_SESSION['member_id']);
unset($_SESSION['member_discord']);
unset($_SESSION['member_roblox']);
unset($_SESSION['member_must_change_password']);

// Rediriger vers la page d'accueil
header('Location: ../index.php');
exit;
?>