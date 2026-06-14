<?php
// Admin login — refuses any non-admin / non-moderator account.

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/db.php';


if (is_logged_in() && in_array(current_role(), ['admin', 'moderator'], true)) {
    header('Location: dashboard.php');
    exit;
}

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if ($pw === '')                                  $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT user_id, full_name, email, password_hash, role, is_verified, is_suspended
               FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if (!$u || !password_verify($pw, $u['password_hash'])) {
            $errors[] = 'Incorrect email or password.';
        } elseif (!in_array($u['role'], ['admin', 'moderator'], true)) {
            $errors[] = 'This account does not have admin access.';
        } elseif ((int)$u['is_suspended'] === 1) {
            $errors[] = 'This account is suspended.';
        } else {
            login_user($u);
            $_SESSION['flash_success'] = 'Welcome, ' . $u['full_name'] . '.';
            header('Location: dashboard.php');
            exit;
        }
    }
}

$page_title  = 'Admin Login';
$base_path   = '../';
$hide_chrome = true;
include __DIR__ . '/../includes/header.php';
?>

<div class="min-h-screen flex items-center justify-center bg-gray-100 px-4">
    <div class="max-w-md w-full">

        <a href="../index.php" class="flex items-center gap-2 justify-center mb-6">
            <i data-lucide="shopping-bag" class="icon-xl text-pink-600"></i>
            <span class="font-bold text-xl">UniMove Res Essentials</span>
        </a>

        <div class="bg-white rounded-xl shadow-lg p-8">
            <div class="flex items-center gap-2 mb-6">
                <i data-lucide="shield-check" class="icon-lg text-pink-600"></i>
                <h1 class="text-2xl font-bold">Admin Panel</h1>
            </div>
            <p class="text-sm text-gray-600 mb-6">
                Restricted area. Only authorised UniMove administrators and moderators may enter.
            </p>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php" class="space-y-6">
                <?= csrf_field() ?>
                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="email" name="email" required autofocus value="<?= e($email) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="password" required
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>
                <button type="submit" class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                    Sign in to admin
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-500">
                <a href="../index.php" class="hover:text-pink-600">← Back to UniMove</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
