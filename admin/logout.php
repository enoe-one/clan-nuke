<?php
require_once '../config.php';

if (isLoggedIn()) {
    logAdminAction($pdo, $_SESSION['user_id'], 'Déconnexion');
}

session_destroy();
header('Location: ../index.php');
exit;
