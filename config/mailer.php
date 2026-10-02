<?php
// config/mailer.php — Centralized PHPMailer / Gmail SMTP Dispatcher
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}
require_once __DIR__ . '/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$GLOBALS['last_mailer_error'] = '';

/**
 * Returns the most recent error message from sendMail().
 */
function getMailerLastError(): string {
    return $GLOBALS['last_mailer_error'] ?? '';
}

/**
 * Sends an email via Gmail SMTP.
 * Returns true on success, false on failure (never throws).
 */
function sendMail(string $toEmail, string $toName, string $subject, string $bodyHtml): bool {
    $GLOBALS['last_mailer_error'] = '';

    // Check 1: Composer dependencies installed
    if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        $GLOBALS['last_mailer_error'] = "PHPMailer is missing. Run 'composer install' or copy the 'vendor' folder to this device.";
        error_log('Mailer Error: ' . $GLOBALS['last_mailer_error']);
        return false;
    }

    // Check 2: Environment credentials configured
    if (!defined('SMTP_USER') || empty(SMTP_USER) || !defined('SMTP_PASS') || empty(SMTP_PASS)) {
        $GLOBALS['last_mailer_error'] = "Gmail SMTP credentials not configured in .env (SMTP_USER or SMTP_PASS is empty). Please create a .env file with your credentials.";
        error_log('Mailer Error: ' . $GLOBALS['last_mailer_error']);
        return false;
    }

    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = trim(SMTP_USER);
        $mail->Password   = preg_replace('/\s+/', '', (string)SMTP_PASS);
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

        // Windows XAMPP SSL fix: allow self-signed / bypass missing local CA bundle
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ],
        ];

        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($toEmail, $toName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $bodyHtml;
        $mail->AltBody  = strip_tags($bodyHtml);

        $mail->send();
        return true;
    } catch (Exception $e) {
        $GLOBALS['last_mailer_error'] = $mail->ErrorInfo ?: $e->getMessage();
        error_log('Mailer Error: ' . $GLOBALS['last_mailer_error']);
        return false;
    }
}
