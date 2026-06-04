<?php
/**
 * The 3-step register flow now handles OTP verification on register.php?step=otp.
 * This file exists only so that older bookmarks / email links continue to work.
 */
require_once __DIR__ . '/includes/auth.php';
header('Location: register.php?step=otp');
exit;
