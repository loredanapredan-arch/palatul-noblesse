<?php
declare(strict_types=1);
/**
 * Middleware de autentificare — include la începutul oricărei pagini admin.
 * Verifică sesiunea, integritatea ei și refresh-ul periodic al token-ului.
 */

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

// ── Configurare sesiune securizată ───────────────────────────
ini_set('session.use_strict_mode',   '1');
ini_set('session.use_only_cookies',  '1');
ini_set('session.cookie_httponly',   '1');
ini_set('session.cookie_samesite',   'Strict');
ini_set('session.gc_maxlifetime',    (string) SESSION_LIFETIME);

// Secure cookie doar pe HTTPS
$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
ini_set('session.cookie_secure', $is_https ? '1' : '0');

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Verifică autentificare ───────────────────────────────────
if (empty($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// ── Verifică integritate sesiune (User-Agent binding) ────────
$ua_hash = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
if (($_SESSION['_ua'] ?? '') !== $ua_hash) {
    session_unset();
    session_destroy();
    header('Location: login.php?err=session');
    exit;
}

// ── Timeout inactivitate ─────────────────────────────────────
if (isset($_SESSION['_last_active'])
    && (time() - $_SESSION['_last_active']) > SESSION_LIFETIME) {
    session_unset();
    session_destroy();
    header('Location: login.php?err=timeout');
    exit;
}
$_SESSION['_last_active'] = time();

// ── Regenerare periodică ID sesiune (anti-fixation) ──────────
if (!isset($_SESSION['_regen_at'])) {
    $_SESSION['_regen_at'] = time();
} elseif ((time() - $_SESSION['_regen_at']) > 300) { // la fiecare 5 min
    session_regenerate_id(true);
    $_SESSION['_regen_at'] = time();
}

// ── Helper: current admin ────────────────────────────────────
function current_admin(): array
{
    return [
        'id'       => (int) ($_SESSION['admin_id']   ?? 0),
        'username' => (string) ($_SESSION['admin_user'] ?? ''),
    ];
}

// ── Helper: CSRF token ───────────────────────────────────────
function csrf_token(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(CSRF_BYTES));
    }
    return $_SESSION['_csrf'];
}

function csrf_verify(): bool
{
    $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    return hash_equals($_SESSION['_csrf'] ?? '', $token);
}
