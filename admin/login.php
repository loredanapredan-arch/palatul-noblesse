<?php
session_start();
if (isset($_SESSION['admin'])) { header('Location: index.php'); exit; }

require_once __DIR__ . '/../config/db.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = trim($_POST['username'] ?? '');
    $pass = $_POST['password'] ?? '';
    $row  = db()->prepare('SELECT id, password_hash FROM admin_users WHERE username = ? LIMIT 1');
    $row->execute([$user]);
    $admin = $row->fetch();
    if ($admin && password_verify($pass, $admin['password_hash'])) {
        $_SESSION['admin'] = $admin['id'];
        header('Location: index.php');
        exit;
    }
    $error = 'Utilizator sau parolă incorectă.';
}
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Admin — Palatul Noblesse</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  body { background: #1f1b18; display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #fff; padding: 2.5rem; max-width: 400px; width: 100%; border-radius: 0; }
  .brand { font-family: Georgia, serif; font-size: 1.5rem; margin-bottom: 1.5rem; color: #1f1b18; }
  .brand small { display: block; font-size: 10px; letter-spacing: .3em; text-transform: uppercase; color: #c9a14a; margin-top: 4px; }
  .btn-primary { background: #bd3033; border-color: #bd3033; }
  .btn-primary:hover { background: #8d2225; border-color: #8d2225; }
</style>
</head>
<body>
<div class="card shadow">
  <div class="brand">Palatul Noblesse <small>Admin Panel</small></div>
  <?php if ($error): ?>
    <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <form method="post">
    <div class="mb-3">
      <label class="form-label small text-uppercase fw-semibold ls-1">Utilizator</label>
      <input name="username" type="text" class="form-control" required autofocus>
    </div>
    <div class="mb-4">
      <label class="form-label small text-uppercase fw-semibold">Parolă</label>
      <input name="password" type="password" class="form-control" required>
    </div>
    <button class="btn btn-primary w-100 text-uppercase fw-semibold ls-1">Autentificare</button>
  </form>
</div>
</body>
</html>
