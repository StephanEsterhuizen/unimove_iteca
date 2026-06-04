<?php
require_once __DIR__ . '/includes/auth.php';

logout_user();

// Restart a fresh session purely to carry the flash message
session_start();
$_SESSION['flash_success'] = 'You have been logged out.';

header('Location: login.php');
exit;
