<?php
declare(strict_types=1);

// ── Session ──────────────────────────────────────────────────
define('SESSION_NAME',     'nls_admin');
define('SESSION_LIFETIME', 7200);          // 2 ore inactivitate

// ── Brute-force protection ───────────────────────────────────
define('MAX_ATTEMPTS',     5);             // incercari inainte de lockout
define('LOCKOUT_MINUTES',  15);            // durata lockout

// ── Bcrypt cost ──────────────────────────────────────────────
define('BCRYPT_COST',      12);

// ── CSRF token length (bytes) ────────────────────────────────
define('CSRF_BYTES',       32);
