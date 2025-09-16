<?php
require_once __DIR__ . '/../classes/User.php';

// Déconnexion
$user = new User();
$user->logout();

// Redirection vers la page de connexion
header('Location: login.php');
exit();
?>
