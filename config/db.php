<?php
declare(strict_types=1);

$_host = $_SERVER['HTTP_HOST'] ?? '';
$_is_local = php_sapi_name() === 'cli'
    || in_array($_host, ['localhost', '127.0.0.1', 'localhost:8000', 'localhost:8080'], true);

define('DB_HOST', 'localhost');
define('DB_NAME', $_is_local ? 'palatul_noblesse'    : 'palatulnoblesse_dev');
define('DB_USER', $_is_local ? 'noblesse'            : 'palatulnoblesse_dev');
define('DB_PASS', $_is_local ? 'Noblesse2026!'       : 'Marco008!');
define('DB_CHARSET', 'utf8mb4');

unset($_host, $_is_local);

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }
    return $pdo;
}
