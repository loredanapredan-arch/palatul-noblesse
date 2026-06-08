<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$msg = '';
$err = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current = $_POST['current'] ?? '';
    $new1    = $_POST['new1']    ?? '';
    $new2    = $_POST['new2']    ?? '';

    $row = db()->prepare('SELECT password_hash FROM admin_users WHERE id = ?');
    $row->execute([$_SESSION['admin']]);
    $admin = $row->fetch();

    if (!password_verify($current, $admin['password_hash'])) {
        $err = 'Parola curentă este incorectă.';
    } elseif (strlen($new1) < 8) {
        $err = 'Parola nouă trebuie să aibă minim 8 caractere.';
    } elseif ($new1 !== $new2) {
        $err = 'Parolele noi nu coincid.';
    } else {
        $hash = password_hash($new1, PASSWORD_BCRYPT);
        db()->prepare('UPDATE admin_users SET password_hash=? WHERE id=?')
            ->execute([$hash, $_SESSION['admin']]);
        $msg = 'Parola a fost schimbată cu succes.';
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Schimbă parola — Admin</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  :root{--burgundy:#bd3033;--gold:#c9a14a;--charcoal:#1f1b18}
  body{background:#f5f0ea}
  .sidebar{background:var(--charcoal);min-height:100vh;width:240px;position:fixed;top:0;left:0;padding:1.5rem 0}
  .sidebar .brand{color:#fff;font-family:Georgia,serif;font-size:1.2rem;padding:.5rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1)}
  .sidebar .brand small{display:block;font-size:9px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-top:3px}
  .sidebar a{display:block;padding:.65rem 1.5rem;color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;text-transform:uppercase;letter-spacing:.15em}
  .sidebar a:hover,.sidebar a.active{color:#fff;background:rgba(255,255,255,.08)}
  .sidebar a.active{border-left:3px solid var(--burgundy)}
  .main{margin-left:240px;padding:2rem}
  h1{font-family:Georgia,serif;font-size:1.75rem}
  label{font-size:.75rem;text-transform:uppercase;letter-spacing:.2em;color:#6b5f55;font-weight:500}
  .btn-save{background:var(--burgundy);color:#fff;border:none;padding:.75rem 2rem;text-transform:uppercase;letter-spacing:.2em;font-size:.8rem}
  .btn-save:hover{background:#8d2225;color:#fff}
</style>
</head>
<body>
<div class="sidebar">
  <div class="brand">Palatul Noblesse <small>Admin Panel</small></div>
  <a href="index.php">Dashboard</a>
  <a href="pages.php">Pagini</a>
  <a href="contacts.php">Cereri de ofertă</a>
  <a href="change-password.php" class="active">Schimbă parola</a>
  <a href="../index.php" target="_blank">Vezi site-ul →</a>
  <a href="logout.php" style="position:absolute;bottom:1rem;width:100%">Deconectare</a>
</div>
<div class="main">
  <h1 class="mb-4">Schimbă parola</h1>
  <?php if ($msg): ?><div class="alert alert-success"><?= htmlspecialchars($msg) ?></div><?php endif; ?>
  <?php if ($err): ?><div class="alert alert-danger"><?= htmlspecialchars($err) ?></div><?php endif; ?>
  <form method="post" class="bg-white p-4" style="max-width:420px">
    <div class="mb-3"><label>Parola curentă</label><input name="current" type="password" class="form-control" required></div>
    <div class="mb-3"><label>Parola nouă</label><input name="new1" type="password" class="form-control" required minlength="8"></div>
    <div class="mb-4"><label>Confirmă parola nouă</label><input name="new2" type="password" class="form-control" required minlength="8"></div>
    <button class="btn btn-save">Schimbă parola</button>
  </form>
</div>
</body>
</html>
