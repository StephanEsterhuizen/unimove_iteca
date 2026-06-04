<?php
/**
 * UniMove Res Essentials — Global Config
 *
 * Edit DB_* constants before deployment to InfinityFree.
 * SITE_URL should be the public root URL of the deployed site (no trailing slash).
 */

// ---- Database ----
// Reads env vars first (Docker uses these via docker-compose.yml),
// falls back to the static values below for shared hosting like InfinityFree.
define('DB_HOST',    getenv('DB_HOST')    ?: 'localhost');
define('DB_NAME',    getenv('DB_NAME')    ?: 'unimove');
define('DB_USER',    getenv('DB_USER')    ?: 'root');
define('DB_PASS',    getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// ---- Site ----
define('SITE_NAME', 'UniMove Res Essentials');
define('SITE_URL', '');           // e.g. 'https://unimove.infinityfreeapp.com' — leave blank for relative
define('UPLOAD_DIR', __DIR__ . '/../uploads/listings/');
define('UPLOAD_URL', 'uploads/listings/');

// ---- Auth ----
define('OTP_EXPIRY_MINUTES', 15);
define('SESSION_NAME', 'unimove_sess');

// Allowed student-email domain suffixes (used in registration validation).
// Add more here if your campus uses a non-standard domain.
define('STUDENT_EMAIL_SUFFIXES', [
    'ac.za',       // all SA universities (UCT, Wits, UP, Sun, etc.)
    'edu',         // US/international universities
    'vossie.net',  // Eduvos student domain
    'mygsm.school',// Belgium Campus / Boston College sometimes
    'iielearn.ac.za',
]);

// ---- Mail (OTP) ----
define('MAIL_FROM', 'no-reply@unimove.local');
define('MAIL_FROM_NAME', 'UniMove Res Essentials');

// ---- Error handling ----
// Errors are always logged; they're only displayed to the browser when the
// APP_DEBUG env var is set to "1". In Docker + production, leave it unset.
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors',         getenv('APP_DEBUG') === '1' ? '1' : '0');
ini_set('display_startup_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');

// Default timezone (South Africa)
date_default_timezone_set('Africa/Johannesburg');
