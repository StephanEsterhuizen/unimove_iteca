<?php
// Global config.

// Database
define('DB_HOST',    getenv('DB_HOST')    ?: 'sql109.infinityfree.com');
define('DB_NAME',    getenv('DB_NAME')    ?: 'if0_42090921_unimove_db');
define('DB_USER',    getenv('DB_USER')    ?: 'if0_42090921');
define('DB_PASS',    getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'StephanUNIMOVE2');
define('DB_CHARSET', getenv('DB_CHARSET') ?: 'utf8mb4');

// Site
define('SITE_NAME', 'UniMove Res Essentials');
define('SITE_URL', 'https://unimove-res-essentials.infinityfreeapp.com');
define('UPLOAD_DIR', __DIR__ . '/../uploads/listings/');
define('UPLOAD_URL', 'uploads/listings/');

// Auth
define('OTP_EXPIRY_MINUTES', 15);
define('SESSION_NAME', 'unimove_sess');

// Allowed university email suffixes
define('STUDENT_EMAIL_SUFFIXES', [
    'ac.za',
    'edu',
    'vossie.net',
    'mygsm.school',
    'iielearn.ac.za',
]);

// Mail
define('MAIL_FROM', 'no-reply@unimove.local');
define('MAIL_FROM_NAME', 'UniMove Res Essentials');

// Errors
error_reporting(E_ALL);
ini_set('log_errors', '1');
ini_set('display_errors',         '1');
ini_set('display_startup_errors', '1');

date_default_timezone_set('Africa/Johannesburg');
