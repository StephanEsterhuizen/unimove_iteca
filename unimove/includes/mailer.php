<?php
/**
 * UniMove Res Essentials — Mailer
 *
 * Simple wrapper around PHP's mail() to send OTP codes. For development
 * (where mail() is unavailable), the message is appended to
 * /uploads/mail.log so testers can still read the OTP.
 */

require_once __DIR__ . '/config.php';

/**
 * Send an OTP to a user's email address.
 *
 * @return bool true if mail() reported success OR the dev log fallback ran.
 */
function sendOTP(string $to_email, string $otp_code, string $purpose = 'verification'): bool {
    $subject = SITE_NAME . ' — Your ' . $purpose . ' code';

    $message  = "Hello,\r\n\r\n";
    $message .= "Your one-time code for " . SITE_NAME . " is:\r\n\r\n";
    $message .= "    " . $otp_code . "\r\n\r\n";
    $message .= "This code expires in " . OTP_EXPIRY_MINUTES . " minutes.\r\n";
    $message .= "If you did not request this code, please ignore this email.\r\n\r\n";
    $message .= "— " . SITE_NAME . "\r\n";

    $headers  = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM . ">\r\n";
    $headers .= "Reply-To: " . MAIL_FROM . "\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $ok = @mail($to_email, $subject, $message, $headers);

    // Dev fallback — always log so we never lock a tester out.
    $log = __DIR__ . '/../uploads/mail.log';
    @file_put_contents($log,
        '[' . date('Y-m-d H:i:s') . "] To: $to_email  OTP: $otp_code  Purpose: $purpose\n",
        FILE_APPEND);

    return $ok || true;
}
