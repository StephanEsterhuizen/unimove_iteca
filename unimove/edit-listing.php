<?php
/**
 * Edit a listing — actual handling lives in create-listing.php
 * (the same form is used for both create and edit).
 */
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: dashboard.php');
    exit;
}
header('Location: create-listing.php?id=' . $id);
exit;
