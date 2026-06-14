<?php
// 3-step registration: email -> OTP -> details. DB write happens only at step 3.

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/mailer.php';

if (is_logged_in()) { header('Location: dashboard.php'); exit; }

$valid_steps = ['email', 'otp', 'details'];
$step        = $_GET['step'] ?? 'email';
if (!in_array($step, $valid_steps, true)) $step = 'email';

if (!isset($_SESSION['register'])) $_SESSION['register'] = [];
$reg     = &$_SESSION['register'];
$errors  = [];

// Step 1: email
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'email') {
    require_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } else {
        $domain   = strtolower(substr(strrchr($email, '@') ?: '', 1));
        $accepted = false;
        foreach (STUDENT_EMAIL_SUFFIXES as $suffix) {
            if ($domain === $suffix || str_ends_with($domain, '.' . $suffix)) {
                $accepted = true;
                break;
            }
        }
        if (!$accepted) {
            $errors[] = 'You must use a recognised university email (e.g. .ac.za, .edu, @vossie.net).';
        }
    }
    if (empty($errors)) {
        // Check uniqueness against verified accounts only
        $stmt = $pdo->prepare('SELECT user_id, is_verified FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $existing = $stmt->fetch();
        if ($existing && (int)$existing['is_verified'] === 1) {
            $errors[] = 'An account with this email already exists. Please <a href="login.php" class="underline">log in</a>.';
        } else {
            $otp = generate_otp();
            $reg = [
                'email'      => $email,
                'otp'        => $otp,
                'expires_at' => time() + OTP_EXPIRY_MINUTES * 60,
            ];
            sendOTP($email, $otp, 'account verification');
            header('Location: register.php?step=otp');
            exit;
        }
    }
    $step = 'email';
}

// Resend OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'resend') {
    require_csrf();
    if (!empty($reg['email'])) {
        $reg['otp']        = generate_otp();
        $reg['expires_at'] = time() + OTP_EXPIRY_MINUTES * 60;
        sendOTP($reg['email'], $reg['otp'], 'account verification');
        $_SESSION['flash_success'] = 'A new code has been sent to ' . $reg['email'] . '.';
    }
    header('Location: register.php?step=otp');
    exit;
}

// Step 2: OTP
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'otp') {
    require_csrf();
    $otp_input = preg_replace('/\D/', '', $_POST['otp'] ?? '');

    if (empty($reg['email']) || empty($reg['otp'])) {
        $errors[] = 'Your session has expired. Please start again.';
        $step = 'email';
    } elseif (strlen($otp_input) !== 6) {
        $errors[] = 'Please enter the 6-digit code.';
        $step = 'otp';
    } elseif (time() > ($reg['expires_at'] ?? 0)) {
        $errors[] = 'That code has expired. Please request a new one.';
        $step = 'otp';
    } elseif (!hash_equals((string)$reg['otp'], $otp_input)) {
        $errors[] = 'Incorrect code. Please check your email and try again.';
        $step = 'otp';
    } else {
        $reg['email_verified'] = true;
        header('Location: register.php?step=details');
        exit;
    }
}

// Step 3: details
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'details') {
    require_csrf();
    $name       = trim($_POST['full_name'] ?? '');
    $university = trim($_POST['university'] ?? '');
    $pw         = $_POST['password'] ?? '';
    $confirm    = $_POST['confirm_password'] ?? '';

    if (empty($reg['email_verified'])) {
        $errors[] = 'Please verify your email first.';
        $step = 'email';
    } else {
        if (mb_strlen($name) < 2)            $errors[] = 'Please enter your full name.';
        if (strlen($pw) < 8)                 $errors[] = 'Password must be at least 8 characters.';
        if ($pw !== $confirm)                $errors[] = 'Passwords do not match.';
        if ($university === '')              $errors[] = 'Please enter your university name.';

        if (empty($errors)) {
            // Insert user (or update unverified row) and log in
            $hash  = password_hash($pw, PASSWORD_DEFAULT);
            $email = $reg['email'];

            $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $existing = $stmt->fetch();

            if ($existing) {
                $stmt = $pdo->prepare(
                    'UPDATE users
                        SET full_name = ?, password_hash = ?, university = ?,
                            is_verified = 1, otp_code = NULL, otp_expires_at = NULL
                      WHERE user_id = ?'
                );
                $stmt->execute([$name, $hash, $university, $existing['user_id']]);
                $user_id = $existing['user_id'];
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO users (full_name, email, password_hash, role, is_verified, university)
                     VALUES (?, ?, ?, "student", 1, ?)'
                );
                $stmt->execute([$name, $email, $hash, $university]);
                $user_id = (int)$pdo->lastInsertId();
            }

            login_user([
                'user_id'   => $user_id,
                'full_name' => $name,
                'email'     => $email,
                'role'      => 'student',
            ]);

            unset($_SESSION['register']);
            $_SESSION['flash_success'] = 'Welcome to UniMove, ' . $name . '!';
            header('Location: dashboard.php');
            exit;
        }
        $step = 'details';
    }
}

$page_title = 'Create account';
include __DIR__ . '/includes/header.php';
?>

<div class="min-h-[calc(100vh-4rem)] flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full">

        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold mb-2">Create your account</h2>
            <p class="text-gray-600">Join the campus marketplace community</p>
        </div>

        <div class="bg-white rounded-xl shadow-lg p-8">

            <!-- Progress Steps -->
            <div class="flex items-center justify-between mb-8">
                <?php
                $step_index = ['email' => 1, 'otp' => 2, 'details' => 3][$step];
                $circle = function (int $n, string $label) use ($step_index) {
                    $state = $n === $step_index ? 'current' : ($n < $step_index ? 'done' : 'pending');
                    $bg = $state === 'current' ? 'bg-pink-600 text-white'
                        : ($state === 'done'    ? 'bg-green-500 text-white'
                                                : 'bg-gray-200 text-gray-600');
                    echo '<div class="flex items-center gap-2"><div class="w-8 h-8 rounded-full flex items-center justify-center ' . $bg . '">' . $n . '</div><span class="text-sm font-medium">' . htmlspecialchars($label) . '</span></div>';
                };
                $bar = function (bool $filled) {
                    echo '<div class="flex-1 h-1 bg-gray-200 mx-2"><div class="h-full bg-pink-600 transition-all ' . ($filled ? 'w-full' : 'w-0') . '"></div></div>';
                };
                ?>
                <?php $circle(1, 'Email');   $bar($step_index > 1); ?>
                <?php $circle(2, 'Verify');  $bar($step_index > 2); ?>
                <?php $circle(3, 'Details'); ?>
            </div>

            <?php if (!empty($errors)): ?>
                <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-lg text-sm text-red-800">
                    <?php foreach ($errors as $err): ?>
                        <div><?= $err /* may contain inline link */ ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <?php if ($step === 'email'): ?>
                <!-- ============== Step 1: Email ============== -->
                <form method="POST" action="register.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="email">

                    <div>
                        <label class="block text-sm font-medium mb-2">Student Email</label>
                        <div class="relative">
                            <i data-lucide="mail" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="email" name="email" required autofocus
                                   value="<?= e($reg['email'] ?? '') ?>"
                                   placeholder="yourname@vossie.net or yourname@university.ac.za"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Accepted: <?= e(implode(', ', array_map(fn($s) => '.'.$s, STUDENT_EMAIL_SUFFIXES))) ?>
                        </p>
                    </div>
                    <button type="submit"
                            class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors flex items-center justify-center gap-2">
                        Send Verification Code
                        <i data-lucide="arrow-right" class="icon-sm"></i>
                    </button>
                </form>

            <?php elseif ($step === 'otp'): ?>
                <!-- ============== Step 2: OTP ============== -->
                <form method="POST" action="register.php" class="space-y-6">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="otp">
                    <input type="hidden" name="otp" id="otpHidden">

                    <div class="text-center">
                        <p class="text-sm text-gray-600 mb-4">
                            We sent a 6-digit code to <strong><?= e($reg['email'] ?? '') ?></strong>
                        </p>
                        <div class="um-otp-boxes flex gap-2 justify-center mb-4" data-otp-target="#otpHidden">
                            <?php for ($i = 0; $i < 6; $i++): ?>
                                <input type="text" inputmode="numeric" maxlength="1" autofocus="<?= $i === 0 ? 'true' : '' ?>"
                                       class="um-otp-cell w-12 h-14 border-2 border-gray-300 rounded-lg text-center text-2xl font-semibold focus:outline-none focus:border-pink-500 transition-colors">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors flex items-center justify-center gap-2">
                        Verify Code
                        <i data-lucide="arrow-right" class="icon-sm"></i>
                    </button>
                </form>

                <form method="POST" action="register.php" class="text-center mt-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="resend">
                    <button type="submit" class="text-sm text-pink-600 hover:text-pink-700">
                        Didn't receive? Resend code
                    </button>
                </form>

            <?php else: /* details */ ?>
                <!-- ============== Step 3: Details ============== -->
                <form method="POST" action="register.php" class="space-y-4">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="details">

                    <div>
                        <label class="block text-sm font-medium mb-2">Full Name</label>
                        <div class="relative">
                            <i data-lucide="user" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="full_name" required autofocus
                                   placeholder="Alex Student"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">University</label>
                        <div class="relative">
                            <i data-lucide="graduation-cap" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="text" name="university" required
                                   placeholder="e.g. Eduvos"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="password" required minlength="8"
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium mb-2">Confirm Password</label>
                        <div class="relative">
                            <i data-lucide="lock" class="icon-md text-gray-400 absolute left-3 top-1/2 -translate-y-1/2"></i>
                            <input type="password" name="confirm_password" required minlength="8"
                                   placeholder="••••••••"
                                   class="w-full pl-10 pr-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-pink-500">
                        </div>
                    </div>

                    <button type="submit"
                            class="w-full bg-pink-600 text-white py-3 rounded-lg hover:bg-pink-700 transition-colors flex items-center justify-center gap-2">
                        Create Account
                        <i data-lucide="arrow-right" class="icon-sm"></i>
                    </button>
                </form>
            <?php endif; ?>

            <div class="mt-6 text-center text-sm text-gray-600">
                Already have an account?
                <a href="login.php" class="text-pink-600 hover:text-pink-700 font-medium">Log in</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
