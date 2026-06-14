<?php
// Edit redirect — actual form lives in create-listing.php.

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}
header('Location: create-listing.php?id=' . $id);
exit;
