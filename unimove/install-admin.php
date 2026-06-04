<?php
/**
 * UniMove Res Essentials — One-shot admin installer
 *
 * Run this ONCE after importing schema.sql to create the first admin
 * account, then DELETE this file from the server.
 *
 * If an admin already exists, the script refuses to run.
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';

// Refuse if any admin already exists
$stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'");
$existing_admins = (int)$stmt->fetchColumn();

$done = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $existing_admins === 0) {
    $name     = trim($_POST['full_name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if ($name === '')                                  $errors[] = 'Name is required.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))    $errors[] = 'Valid email required.';
    if (strlen($password) < 8)                         $errors[] = 'Password must be at least 8 characters.';

    if (empty($errors)) {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (full_name, email, password_hash, role, is_verified, university)
             VALUES (?, ?, ?, "admin", 1, "UniMove HQ")'
        );
        $stmt->execute([$name, $email, $hash]);
        $done = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Install Admin — UniMove</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width: 540px;">

    <h1 class="h3 fw-bold mb-3">UniMove — First-time admin setup</h1>

    <?php if ($existing_admins > 0): ?>
        <div class="alert alert-warning">
            <strong>An admin already exists.</strong> For security, please delete
            <code>install-admin.php</code> from your server now.
        </div>
        <a href="login.php" class="btn um-btn-primary">Go to login</a>

    <?php elseif ($done): ?>
        <div class="alert alert-success">
            <strong>Admin account created.</strong> You can now log in.
            <hr>
            For security, <strong>delete this file (<code>install-admin.php</code>) from the server immediately.</strong>
        </div>
        <a href="login.php" class="btn um-btn-primary">Go to login</a>

    <?php else: ?>
        <p class="text-muted">Create the first admin account. This page will refuse to run once an admin exists.</p>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger"><ul class="mb-0 ps-3">
                <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>

        <form method="POST" class="card um-card">
            <div class="card-body p-4">
                <div class="mb-3">
                    <label class="form-label">Full name</label>
                    <input type="text" name="full_name" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Admin email</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password (min. 8 chars)</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <button type="submit" class="btn um-btn-primary w-100">Create admin</button>
            </div>
        </form>
    <?php endif; ?>

</div>
</body>
</html>
