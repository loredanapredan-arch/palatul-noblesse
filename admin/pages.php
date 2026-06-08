<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$pages = db()->query('SELECT * FROM pages ORDER BY sort_order, id')->fetchAll();
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Pagini — Admin Palatul Noblesse</title>
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
  h1{font-family:Georgia,serif;font-size:1.75rem;color:var(--charcoal)}
  .table th{font-size:.75rem;text-transform:uppercase;letter-spacing:.15em;color:#6b5f55;font-weight:500}
  .btn-edit{background:var(--burgundy);color:#fff;border:none;font-size:.75rem;padding:.3rem .75rem;text-transform:uppercase;letter-spacing:.1em}
  .btn-edit:hover{background:#8d2225;color:#fff}
</style>
</head>
<body>
<div class="sidebar">
  <div class="brand">Palatul Noblesse <small>Admin Panel</small></div>
  <a href="index.php">Dashboard</a>
  <a href="pages.php" class="active">Pagini</a>
  <a href="contacts.php">Cereri de ofertă</a>
  <a href="change-password.php">Schimbă parola</a>
  <a href="../index.php" target="_blank">Vezi site-ul →</a>
  <a href="logout.php" style="position:absolute;bottom:1rem;width:100%">Deconectare</a>
</div>

<div class="main">
  <h1 class="mb-4">Pagini site</h1>

  <div class="bg-white">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr><th>#</th><th>Titlu</th><th>URL</th><th>Meta description</th><th></th></tr>
      </thead>
      <tbody>
      <?php foreach ($pages as $p): ?>
        <tr>
          <td class="text-muted"><?= $p['id'] ?></td>
          <td><?= htmlspecialchars($p['title']) ?></td>
          <td><code>/<?= htmlspecialchars($p['slug']) ?></code></td>
          <td class="text-muted" style="font-size:.8rem;max-width:300px">
            <?= $p['meta_description'] ? mb_substr(htmlspecialchars($p['meta_description']), 0, 80) . '…' : '<em>—</em>' ?>
          </td>
          <td>
            <a href="edit-page.php?id=<?= $p['id'] ?>" class="btn btn-edit">Editează</a>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
</body>
</html>
