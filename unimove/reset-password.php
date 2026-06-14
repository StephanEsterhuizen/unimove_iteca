<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$email   = strtolower(trim($_GET['email'] ?? $_POST['email'] ?? ''));
$errors  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $otp_input = preg_replace('/\D/', '', $_POST['otp'] ?? '');
    $pw        = $_POST['password'] ?? '';
    $confirm   = $_POST['confirm_password'] ?? '';

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Invalid email address.';
    if (strlen($otp_input) !== 6)                   $errors[] = 'Please enter the 6-digit code.';
    if (strlen($pw) < 8)                            $errors[] = 'Password must be at least 8 characters.';
    if ($pw !== $confirm)                           $errors[] = 'Passwords do not match.';

    if (empty($errors)) {
        $stmt = $pdo->prepare(
            'SELECT user_id, otp_code, otp_expires_at, is_suspended
               FROM users WHERE email = ?'
        );
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            $errors[] = 'No account found for that email.';
        } elseif ((int)$user['is_suspended'] === 1) {
            $errors[] = 'This account has been suspended.';
        } elseif (empty($user['otp_code']) || empty($user['otp_expires_at'])) {
            $errors[] = 'No active code on file. Please request a new one.';
        } elseif (strtotime($user['otp_expires_at']) < time()) {
            $errors[] = 'That code has expired. Please request a new one.';
        } elseif (!hash_equals((string)$user['otp_code'], $otp_input)) {
            $errors[] = 'Incorrect code.';
        } else {
            $hash = password_hash($pw, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'UPDATE users SET password_hash = ?, otp_code = NULL, otp_expires_at = NULL,
                                  is_verified = 1
                   WHERE user_id = ?'
            );
            $stmt->execute([$hash, $user['user_id']]);

            $_SESSION['flash_success'] = 'Password reset! You can now log in.';
            header('Location: login.php');
            exit;
        }
    }
}

$page_title = 'Reset password';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-2">Reset your password</h2>
            <p class="text-gray-600">Enter the 6-digit code we sent to your email and a new password.</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">

            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="reset-password.php" class="space-y-5">
                <?= csrf_field() ?>
                <input type="hidden" name="otp" id="otpHidden">

                <div>
                    <label class="block text-sm font-medium mb-2">Email</label>
                    <div class="relative">
                        <i data-lucide="mail" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="email" name="email" required readonly
                               value="<?= e($email) ?>"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg bg-gray-50 focus:outline-none">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">6-digit code</label>
                    <div class="um-otp-boxes flex gap-2 justify-center" data-otp-target="#otpHidden">
                        <?php for ($i = 0; $i < 6; $i++): ?>
                            <input type="text" inputmode="numeric" maxlength="1" <?= $i === 0 ? 'autofocus' : '' ?>
                                   class="um-otp-cell w-12 h-14 border-2 border-gray-300 rounded-lg text-center text-2xl font-semibold focus:outline-none focus:border-pink-500 transition-colors">
                        <?php endfor; ?>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">New password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="password" required minlength="8"
                               placeholder="At least 8 characters"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium mb-2">Confirm new password</label>
                    <div class="relative">
                        <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                        <input type="password" name="confirm_password" required minlength="8"
                               class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                    </div>
                </div>

                <button type="submit" class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                    Reset password
                </button>
            </form>

            <div class="mt-6 text-center text-sm text-gray-600">
                Didn't get a code? <a href="forgot-password.php" class="text-pink-600 hover:text-pink-700 font-medium">Request a new one</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
