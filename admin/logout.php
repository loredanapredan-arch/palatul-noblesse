<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/security.php';

ini_set('session.use_strict_mode',  '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.cookie_httponly',  '1');
ini_set('session.cookie_samesite',  'Strict');

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Distruge sesiunea complet
$_SESSION = [];
if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}
session_destroy();

header('Location: login.php?msg=logout');
exit;
