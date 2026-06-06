<?php
require_once 'includes/config.php';
require_once 'includes/auth.php';

// Logout user
$auth->logout();

// Redirect to homepage
header('Location: ' . SITE_URL);
exit();
?>