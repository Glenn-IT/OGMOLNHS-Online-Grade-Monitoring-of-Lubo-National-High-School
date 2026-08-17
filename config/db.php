<?php
// config/db.php
// Loads environment credentials from .env (which is ignored by Git)

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($name, $value) = explode('=', $line, 2);
            $name  = trim($name);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            if (!defined($name)) {
                define($name, $value);
            }
        }
    }
}

// Fallback constants if not defined in .env
if (!defined('DB_HOST'))           define('DB_HOST', 'localhost');
if (!defined('DB_USER'))           define('DB_USER', 'root');
if (!defined('DB_PASS'))           define('DB_PASS', '');
if (!defined('DB_NAME'))           define('DB_NAME', 'ogms_lnhs');

if (!defined('PHILSMS_API_TOKEN')) define('PHILSMS_API_TOKEN', '');
if (!defined('PHILSMS_SENDER_ID'))  define('PHILSMS_SENDER_ID', 'PhilSMS');

if (!defined('SMTP_HOST'))         define('SMTP_HOST', 'smtp.gmail.com');
if (!defined('SMTP_PORT'))         define('SMTP_PORT', 587);
if (!defined('SMTP_USER'))         define('SMTP_USER', '');
if (!defined('SMTP_PASS'))         define('SMTP_PASS', '');
if (!defined('SMTP_FROM_NAME'))   define('SMTP_FROM_NAME', 'OGMS - Lubo National High School');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
