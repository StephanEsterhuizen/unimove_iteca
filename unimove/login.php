<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $pw    = $_POST['password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
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
        } elseif ((int)$u['is_suspended'] === 1) {
            $errors[] = 'This account has been suspended. Please contact support.';
        } elseif ((int)$u['is_verified'] === 0) {
            $_SESSION['flash_error'] = 'Please verify your email before logging in.';
            $_SESSION['register']    = ['email' => $email];
            header('Location: register.php?step=otp');
            exit;
        } else {
            login_user($u);
            $_SESSION['flash_success'] = 'Welcome back, ' . $u['full_name'] . '!';
            $dest = in_array($u['role'], ['admin', 'moderator'], true) ? 'admin/dashboard.php' : 'dashboard.php';
            header('Location: ' . $dest);
            exit;
        }
    }
}

$page_title = 'Log in';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-2">Welcome back</h2>
            <p class="text-gray-600">Log in to your UniMove account</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">

            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" class="space-y-6">
                <?= csrf_field() ?>

                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="email" name="email" required autofocus
                               value="<?= e($email) ?>"
                               placeholder="yourname@university.ac.za"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="password" required
                               placeholder="••••••••"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" class="w-4 h-4 text-pink-600 rounded">
                        <span class="text-sm text-gray-600">Remember me</span>
                    </label>
                    <a href="#" class="text-sm text-pink-600 hover:text-pink-700">Forgot password?</a>
                </div>

                <button type="submit"
                        class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                    Log In
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                Don't have an account?
                <a href="register.php" class="text-pink-600 hover:text-pink-700 font-medium">Sign up</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
