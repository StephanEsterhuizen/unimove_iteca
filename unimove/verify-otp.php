<?php
// Legacy redirect — OTP verification now lives at register.php?step=otp.

require_once __DIR__ . '/includes/auth.php';
header('Location: register.php?step=otp');
exit;
