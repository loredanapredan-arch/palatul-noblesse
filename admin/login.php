<?php
declare(strict_types=1);

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/security.php';

// ── Security headers ─────────────────────────────────────────
header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
header('Content-Security-Policy: default-src \'self\'; style-src \'self\' \'unsafe-inline\' https://fonts.googleapis.com; font-src https://fonts.gstatic.com; script-src \'none\'');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');

// ── Sesiune ──────────────────────────────────────────────────
ini_set('session.use_strict_mode',   '1');
ini_set('session.use_only_cookies',  '1');
ini_set('session.cookie_httponly',   '1');
ini_set('session.cookie_samesite',   'Strict');
ini_set('session.gc_maxlifetime',    (string) SESSION_LIFETIME);

$is_https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
         || ($_SERVER['SERVER_PORT'] ?? 80) == 443;
ini_set('session.cookie_secure', $is_https ? '1' : '0');

session_name(SESSION_NAME);
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Deja autentificat → redirecționează
if (!empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

// ── Helpers ──────────────────────────────────────────────────
function get_client_ip(): string
{
    // Ia IP-ul real, dar nu fă trust blind pe forwarded headers
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

function is_rate_limited(PDO $pdo, string $ip): bool
{
    $since = date('Y-m-d H:i:s', time() - LOCKOUT_MINUTES * 60);
    $stmt  = $pdo->prepare(
        'SELECT COUNT(*) FROM login_attempts
         WHERE ip_address = ? AND success = 0 AND attempted_at > ?'
    );
    $stmt->execute([$ip, $since]);
    return (int) $stmt->fetchColumn() >= MAX_ATTEMPTS;
}

function log_attempt(PDO $pdo, string $ip, string $username, bool $success): void
{
    $pdo->prepare(
        'INSERT INTO login_attempts (ip_address, username, success) VALUES (?, ?, ?)'
    )->execute([$ip, $username, $success ? 1 : 0]);
}

function csrf_generate(): string
{
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(CSRF_BYTES));
    }
    return $_SESSION['_csrf'];
}

function csrf_check(): bool
{
    $token = trim($_POST['_csrf'] ?? '');
    return !empty($token) && hash_equals($_SESSION['_csrf'] ?? '', $token);
}

// ── Procesare POST ───────────────────────────────────────────
$error   = '';
$success = '';

// Mesaje din redirect
if (isset($_GET['msg']) && $_GET['msg'] === 'logout') {
    $success = 'Ai fost deconectat cu succes.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // 1. CSRF
    if (!csrf_check()) {
        $error = 'Cerere invalidă. Te rugăm să reîncerci.';
    } else {
        $ip       = get_client_ip();
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        // 2. Rate limiting
        if (is_rate_limited(db(), $ip)) {
            $error = sprintf(
                'Prea multe încercări eșuate. Încearcă din nou după %d minute.',
                LOCKOUT_MINUTES
            );
        }
        // 3. Input basic
        elseif ($username === '' || $password === '') {
            $error = 'Completează toate câmpurile.';
        } else {
            // 4. Căutare user — fără a dezvălui dacă username există
            $stmt = db()->prepare(
                'SELECT id, username, password_hash FROM admin_users WHERE username = ? LIMIT 1'
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch() ?: null;

            $hash_to_verify = $user['password_hash'] ?? '$2y$12$invalidhashinvalidhashinvalidhashXXX';

            // 5. Verifică parola (constant-time prin password_verify)
            if ($user && password_verify($password, $hash_to_verify)) {
                // ── LOGIN REUȘIT ──────────────────────────────
                log_attempt(db(), $ip, $username, true);

                // Regenerează sesiunea (anti session fixation)
                session_regenerate_id(true);
                $_SESSION['_csrf'] = ''; // invalidează tokenul vechi

                $_SESSION['admin_id']    = (int) $user['id'];
                $_SESSION['admin_user']  = $user['username'];
                $_SESSION['_ua']         = md5($_SERVER['HTTP_USER_AGENT'] ?? '');
                $_SESSION['_last_active'] = time();
                $_SESSION['_regen_at']   = time();

                // Update last_login
                db()->prepare('UPDATE admin_users SET last_login_at = NOW() WHERE id = ?')
                    ->execute([$user['id']]);

                // Rehash dacă costul a crescut
                if (password_needs_rehash($hash_to_verify, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST])) {
                    $new_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => BCRYPT_COST]);
                    db()->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')
                        ->execute([$new_hash, $user['id']]);
                }

                header('Location: index.php');
                exit;
            } else {
                // ── LOGIN EȘUAT ───────────────────────────────
                log_attempt(db(), $ip, $username, false);

                // Același mesaj indiferent că userul nu există sau parola e greșită
                $error = 'Credențiale incorecte.';

                // Verifică dacă după acest eșec IP-ul e blocat
                if (is_rate_limited(db(), $ip)) {
                    $error = sprintf(
                        'Contul a fost blocat după prea multe eșuări. Încearcă din nou după %d minute.',
                        LOCKOUT_MINUTES
                    );
                }
            }
        }
    }

    // Regenerează CSRF după orice POST (inclusiv eșuat)
    $_SESSION['_csrf'] = bin2hex(random_bytes(CSRF_BYTES));
}

$csrf = csrf_generate();
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Autentificare — Admin Palatul Noblesse</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0 }

:root {
  --burg:    #bd3033;
  --burg-dk: #8d2225;
  --gold:    #c9a14a;
  --dark:    #1f1b18;
  --cream:   #f5f0ea;
  --muted:   #6b5f55;
  --border:  #e4ddd5;
  --radius:  8px;
}

body {
  font-family: 'Inter', sans-serif;
  background: var(--dark);
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 1.5rem;
}

/* ── Card ── */
.card {
  background: #fff;
  border-radius: 14px;
  width: 100%;
  max-width: 420px;
  overflow: hidden;
  box-shadow: 0 24px 64px rgba(0,0,0,.55);
}

.card-header {
  background: var(--dark);
  padding: 2.25rem 2rem 1.75rem;
  text-align: center;
  border-bottom: 1px solid rgba(201,161,74,.25);
}
.brand {
  font-family: 'Cormorant Garamond', Georgia, serif;
  font-size: 1.55rem;
  font-weight: 600;
  color: #fff;
  letter-spacing: .02em;
}
.brand-sub {
  font-size: 9px;
  letter-spacing: .38em;
  text-transform: uppercase;
  color: var(--gold);
  margin-top: 5px;
}
.admin-badge {
  display: inline-block;
  margin-top: 1rem;
  font-size: 10px;
  letter-spacing: .2em;
  text-transform: uppercase;
  color: rgba(255,255,255,.45);
  border: 1px solid rgba(255,255,255,.15);
  padding: 3px 12px;
  border-radius: 20px;
}

.card-body {
  padding: 2rem;
}

/* ── Alert ── */
.alert {
  padding: .75rem 1rem;
  border-radius: var(--radius);
  font-size: .83rem;
  margin-bottom: 1.5rem;
  display: flex;
  align-items: flex-start;
  gap: .55rem;
  line-height: 1.5;
}
.alert-error {
  background: #fef2f2;
  border: 1px solid #fca5a5;
  color: #991b1b;
}
.alert-success {
  background: #f0fdf4;
  border: 1px solid #86efac;
  color: #166534;
}
.alert-icon { font-size: 1rem; flex-shrink: 0; margin-top: 1px }

/* ── Form ── */
.field { margin-bottom: 1.25rem }
.field label {
  display: block;
  font-size: .72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .14em;
  color: var(--muted);
  margin-bottom: .5rem;
}
.field input {
  width: 100%;
  padding: .72rem .9rem;
  border: 1.5px solid var(--border);
  border-radius: var(--radius);
  font-size: .9rem;
  font-family: 'Inter', sans-serif;
  color: var(--dark);
  background: #faf8f5;
  transition: border-color .2s, box-shadow .2s;
  outline: none;
}
.field input:focus {
  border-color: var(--burg);
  box-shadow: 0 0 0 3px rgba(189,48,51,.1);
  background: #fff;
}

/* ── Password toggle ── */
.pw-wrap { position: relative }
.pw-wrap input { padding-right: 2.75rem }
.pw-toggle {
  position: absolute;
  right: .75rem;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--muted);
  padding: .2rem;
  line-height: 1;
  font-size: 1rem;
}
.pw-toggle:hover { color: var(--dark) }

/* ── Submit ── */
.btn-login {
  width: 100%;
  padding: .8rem;
  background: var(--burg);
  color: #fff;
  border: none;
  border-radius: var(--radius);
  font-family: 'Inter', sans-serif;
  font-size: .82rem;
  font-weight: 600;
  letter-spacing: .18em;
  text-transform: uppercase;
  cursor: pointer;
  transition: background .2s, transform .1s;
  margin-top: .25rem;
}
.btn-login:hover  { background: var(--burg-dk) }
.btn-login:active { transform: scale(.99) }

/* ── Footer card ── */
.card-footer {
  padding: 1rem 2rem;
  background: var(--cream);
  text-align: center;
  font-size: .75rem;
  color: var(--muted);
  border-top: 1px solid var(--border);
}
.card-footer a { color: var(--burg); text-decoration: none }
.card-footer a:hover { text-decoration: underline }

/* ── Back link ── */
.back-link {
  display: block;
  text-align: center;
  margin-top: 1.25rem;
  font-size: .78rem;
  color: rgba(255,255,255,.4);
  text-decoration: none;
  letter-spacing: .05em;
  transition: color .2s;
}
.back-link:hover { color: rgba(255,255,255,.75) }
</style>
</head>
<body>

<div>
  <div class="card">
    <div class="card-header">
      <div class="brand">Palatul Noblesse</div>
      <div class="brand-sub">Lifestyle Palace · Est. 1881</div>
      <span class="admin-badge">Panou de administrare</span>
    </div>

    <div class="card-body">

      <?php if ($error !== ''): ?>
      <div class="alert alert-error">
        <span class="alert-icon">⚠</span>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
      <?php endif ?>

      <?php if ($success !== ''): ?>
      <div class="alert alert-success">
        <span class="alert-icon">✓</span>
        <span><?= htmlspecialchars($success) ?></span>
      </div>
      <?php endif ?>

      <form method="post" autocomplete="off" novalidate>
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

        <div class="field">
          <label for="username">Utilizator</label>
          <input
            type="text"
            id="username"
            name="username"
            autocomplete="username"
            autofocus
            required
            maxlength="80"
            value="<?= htmlspecialchars(trim($_POST['username'] ?? '')) ?>"
          >
        </div>

        <div class="field">
          <label for="password">Parolă</label>
          <div class="pw-wrap">
            <input
              type="password"
              id="password"
              name="password"
              autocomplete="current-password"
              required
              maxlength="200"
            >
            <button type="button" class="pw-toggle" aria-label="Arată/ascunde parola"
                    onclick="togglePw()">👁</button>
          </div>
        </div>

        <button type="submit" class="btn-login">Intră în cont</button>
      </form>

    </div>

    <div class="card-footer">
      Probleme de acces? Contactează <a href="mailto:it@upeventi.com">it@upeventi.com</a>
    </div>
  </div>

  <a href="/" class="back-link">← Înapoi la site</a>
</div>

<script>
function togglePw() {
  const inp = document.getElementById('password');
  const btn = document.querySelector('.pw-toggle');
  if (inp.type === 'password') {
    inp.type = 'text';
    btn.textContent = '🙈';
  } else {
    inp.type = 'password';
    btn.textContent = '👁';
  }
}
</script>
</body>
</html>
