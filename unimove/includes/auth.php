<?php
// Session, RBAC, CSRF, and small helpers.

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_name(SESSION_NAME);
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => isset($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// Session helpers

function is_logged_in(): bool {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'user_id'   => $_SESSION['user_id'],
        'full_name' => $_SESSION['full_name'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'role'      => $_SESSION['role'] ?? 'student',
    ];
}

function current_user_id(): ?int {
    return is_logged_in() ? (int)$_SESSION['user_id'] : null;
}

function current_role(): string {
    return $_SESSION['role'] ?? 'guest';
}

function login_user(array $user): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = (int)$user['user_id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['email']     = $user['email'];
    $_SESSION['role']      = $user['role'];
}

function logout_user(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

// Guards

function require_login(string $redirect = 'login.php'): void {
    if (!is_logged_in()) {
        $_SESSION['flash_error'] = 'Please log in to continue.';
        header('Location: ' . $redirect);
        exit;
    }
}

function require_role(array $allowed_roles, string $redirect = 'login.php'): void {
    require_login($redirect);
    if (!in_array(current_role(), $allowed_roles, true)) {
        http_response_code(403);
        $_SESSION['flash_error'] = 'You do not have permission to view that page.';
        header('Location: ' . $redirect);
        exit;
    }
}

function require_admin(): void {
    require_role(['admin', 'moderator'], 'index.php');
}

// CSRF

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function verify_csrf(): bool {
    $sent = $_POST['csrf_token'] ?? '';
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $sent);
}

function require_csrf(): void {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !verify_csrf()) {
        http_response_code(419);
        die('Invalid CSRF token. Please refresh the page and try again.');
    }
}

// Output

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function flash_pop(string $key): ?string {
    if (!isset($_SESSION[$key])) return null;
    $msg = $_SESSION[$key];
    unset($_SESSION[$key]);
    return $msg;
}

// OTP + images

function generate_otp(): string {
    return str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
}

function listing_image_url(?string $path, string $base = ''): string {
    if (empty($path)) {
        return 'https://placehold.co/400x300?text=No+image';
    }
    if (preg_match('#^https?://#i', $path)) {
        return $path;
    }
    return $base . UPLOAD_URL . $path;
}

function otp_expiry_sql(): string {
    return (new DateTime('+' . OTP_EXPIRY_MINUTES . ' minutes'))->format('Y-m-d H:i:s');
}
