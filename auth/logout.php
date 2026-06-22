<?php
require_once __DIR__ . '/../includes/helpers.php';
initSecureSession();
session_destroy();
header('Location: /auth/login.php');
exit;
?>
