<?php
// SMTP via PHPMailer + Gmail. Falls back to a log file if SMTP fails.

require_once __DIR__ . '/config.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

$smtp_ready = false;
if (file_exists(__DIR__ . '/smtp-config.php')) {
    require_once __DIR__ . '/smtp-config.php';
    $smtp_ready = defined('SMTP_USERNAME') && defined('SMTP_PASSWORD');
}

function sendOTP(string $to_email, string $otp_code, string $purpose = 'verification'): bool {
    global $smtp_ready;

    $subject = SITE_NAME . ' — Your ' . $purpose . ' code';

    $body_text =
        "Hello,\r\n\r\n" .
        "Your one-time code for " . SITE_NAME . " is:\r\n\r\n" .
        "    " . $otp_code . "\r\n\r\n" .
        "This code expires in " . OTP_EXPIRY_MINUTES . " minutes.\r\n" .
        "If you did not request this code, please ignore this email.\r\n\r\n" .
        "— " . SITE_NAME;

    $body_html =
        '<div style="font-family:Arial,sans-serif;font-size:14px;color:#1f2330;line-height:1.5">' .
            '<h2 style="color:#C2185B;margin-bottom:16px">' . htmlspecialchars(SITE_NAME) . '</h2>' .
            '<p>Hello,</p>' .
            '<p>Your one-time code for <strong>' . htmlspecialchars(SITE_NAME) . '</strong> is:</p>' .
            '<div style="font-size:32px;font-weight:700;letter-spacing:8px;color:#C2185B;' .
                'background:#FCE4EC;padding:14px 20px;border-radius:8px;display:inline-block;margin:8px 0">' .
                htmlspecialchars($otp_code) .
            '</div>' .
            '<p>This code expires in <strong>' . OTP_EXPIRY_MINUTES . ' minutes</strong>.</p>' .
            '<p style="color:#666;font-size:12px">If you did not request this code, please ignore this email.</p>' .
            '<hr style="border:none;border-top:1px solid #eee;margin:20px 0">' .
            '<p style="color:#999;font-size:12px">— ' . htmlspecialchars(SITE_NAME) . '</p>' .
        '</div>';

    $sent = false;

    if ($smtp_ready) {
        try {
            $mail = new PHPMailer(true);

            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = SMTP_SECURE === 'ssl'
                                ? PHPMailer::ENCRYPTION_SMTPS
                                : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            $mail->CharSet    = 'UTF-8';

            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer'       => false,
                    'verify_peer_name'  => false,
                    'allow_self_signed' => true,
                ],
            ];

            $mail->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mail->addAddress($to_email);
            $mail->addReplyTo(SMTP_FROM, SMTP_FROM_NAME);

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $body_html;
            $mail->AltBody = $body_text;

            $sent = $mail->send();
        } catch (Throwable $e) {
            // Log SMTP errors but never show them to the user.
            $log = __DIR__ . '/../uploads/mail.log';
            @file_put_contents($log,
                '[' . date('Y-m-d H:i:s') . "] SMTP ERROR for $to_email: " . $e->getMessage() . "\n",
                FILE_APPEND);
        }
    }

    // Log the OTP locally so it can be retrieved if SMTP fails.
    $log = __DIR__ . '/../uploads/mail.log';
    @file_put_contents($log,
        '[' . date('Y-m-d H:i:s') . '] To: ' . $to_email .
        '  OTP: ' . $otp_code .
        '  Purpose: ' . $purpose .
        '  SMTP: ' . ($sent ? 'sent' : 'fallback-only') . "\n",
        FILE_APPEND);

    return $sent || true;
}
