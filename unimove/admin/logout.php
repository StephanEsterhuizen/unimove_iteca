<?php
require_once __DIR__ . '/../includes/auth.php';

logout_user();
session_start();
$_SESSION['flash_success'] = 'You have been logged out of the admin panel.';
header('Location: index.php');
exit;
