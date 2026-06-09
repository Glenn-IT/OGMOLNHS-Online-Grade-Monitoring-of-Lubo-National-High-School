<?php
// config/db.php
// PDO_ATTR_EMULATE_PREPARES = false ensures real prepared statements (prevents SQL injection)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');          // change if you set a MySQL password
define('DB_NAME', 'ogms_lnhs');

define('SMS_API_KEY', '');      // fill in after Semaphore account is created
define('SMS_SENDER',  'LNHS_OGMS');

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
