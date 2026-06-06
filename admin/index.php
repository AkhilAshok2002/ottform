<?php
require_once '../includes/config.php';
require_once '../includes/auth.php';

// Redirect to admin login
header('Location: ' . SITE_URL . '/admin/login.php');
exit();
?>