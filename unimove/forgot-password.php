<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$errors  = [];
$email   = '';
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }

    if (empty($errors)) {
        $stmt = $pdo->prepare('SELECT user_id, full_name, is_suspended FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && (int)$user['is_suspended'] === 0) {
            $otp     = generate_otp();
            $expires = otp_expiry_sql();
            $stmt = $pdo->prepare('UPDATE users SET otp_code = ?, otp_expires_at = ? WHERE user_id = ?');
            $stmt->execute([$otp, $expires, $user['user_id']]);
            sendOTP($email, $otp, 'password reset');
        }

        // Always show success — never reveal whether an email is in the DB.
        $success = true;
    }
}

$page_title = 'Forgot password';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-2">Forgot your password?</h2>
            <p class="text-gray-600">Enter your email and we'll send you a code to reset it.</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">

            <?php if ($success): ?>
                <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded-lg text-sm text-green-800">
                    <p class="font-semibold mb-1">Check your inbox.</p>
                    <p>If an account exists for <strong><?= e($email) ?></strong>, we've sent a 6-digit reset code that expires in <?= OTP_EXPIRY_MINUTES ?> minutes.</p>
                </div>
                <a href="reset-password.php?email=<?= urlencode($email) ?>"
                   class="block w-full text-center bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                    I have my code — reset password
                </a>
                <p class="text-center text-sm text-gray-500 mt-4">
                    <a href="login.php" class="hover:text-pink-600">← Back to login</a>
                </p>

            <?php else: ?>

                <?php if (!empty($errors)): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="forgot-password.php" class="space-y-6">
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

                    <button type="submit" class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors">
                        Send reset code
                    </button>
                </form>

                <div class="mt-6 text-center text-sm text-gray-600">
                    Remembered it? <a href="login.php" class="text-pink-600 hover:text-pink-700 font-medium">Log in</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
