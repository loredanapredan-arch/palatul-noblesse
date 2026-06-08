<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$contacts_count = db()->query('SELECT COUNT(*) FROM contact_requests')->fetchColumn();
$pages_count    = db()->query('SELECT COUNT(*) FROM pages')->fetchColumn();
$recent = db()->query('SELECT name, email, event_type, created_at FROM contact_requests ORDER BY created_at DESC LIMIT 5')->fetchAll();
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Dashboard — Admin Palatul Noblesse</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  :root { --burgundy: #bd3033; --gold: #c9a14a; --charcoal: #1f1b18; }
  body { background: #f5f0ea; }
  .sidebar { background: var(--charcoal); min-height: 100vh; width: 240px; position: fixed; top:0; left:0; padding: 1.5rem 0; }
  .sidebar .brand { color: #fff; font-family: Georgia,serif; font-size: 1.2rem; padding: .5rem 1.5rem 1.5rem; border-bottom: 1px solid rgba(255,255,255,.1); }
  .sidebar .brand small { display:block; font-size:9px; letter-spacing:.3em; text-transform:uppercase; color: var(--gold); margin-top:3px; }
  .sidebar a { display: block; padding: .65rem 1.5rem; color: rgba(255,255,255,.7); text-decoration: none; font-size: .85rem; text-transform: uppercase; letter-spacing: .15em; }
  .sidebar a:hover, .sidebar a.active { color: #fff; background: rgba(255,255,255,.08); }
  .sidebar a.active { border-left: 3px solid var(--burgundy); }
  .main { margin-left: 240px; padding: 2rem; }
  .stat-card { background: #fff; padding: 1.5rem; border-top: 3px solid var(--burgundy); }
  .stat-card .num { font-family: Georgia,serif; font-size: 2.5rem; color: var(--burgundy); }
  .stat-card .lbl { font-size: .75rem; text-transform: uppercase; letter-spacing: .2em; color: #6b5f55; margin-top: .25rem; }
  h1 { font-family: Georgia,serif; font-size: 1.75rem; color: var(--charcoal); }
  .table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .15em; color: #6b5f55; font-weight: 500; }
</style>
</head>
<body>

<div class="sidebar">
  <div class="brand">Palatul Noblesse <small>Admin Panel</small></div>
  <a href="index.php" class="active">Dashboard</a>
  <a href="pages.php">Pagini</a>
  <a href="contacts.php">Cereri de ofertă</a>
  <a href="change-password.php">Schimbă parola</a>
  <a href="../index.php" target="_blank">Vezi site-ul →</a>
  <a href="logout.php" style="margin-top:auto;position:absolute;bottom:1rem;width:100%">Deconectare</a>
</div>

<div class="main">
  <h1 class="mb-4">Dashboard</h1>

  <div class="row g-3 mb-4">
    <div class="col-md-3">
      <div class="stat-card">
        <div class="num"><?= $contacts_count ?></div>
        <div class="lbl">Cereri de ofertă</div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div class="num"><?= $pages_count ?></div>
        <div class="lbl">Pagini</div>
      </div>
    </div>
  </div>

  <div class="bg-white p-4">
    <h5 class="mb-3" style="font-family:Georgia,serif">Ultimele cereri primite</h5>
    <?php if ($recent): ?>
    <table class="table table-hover mb-0">
      <thead><tr><th>Nume</th><th>Email</th><th>Tip eveniment</th><th>Data cererii</th></tr></thead>
      <tbody>
      <?php foreach ($recent as $r): ?>
        <tr>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><?= htmlspecialchars($r['email']) ?></td>
          <td><?= htmlspecialchars($r['event_type'] ?? '—') ?></td>
          <td><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <div class="mt-3"><a href="contacts.php" class="btn btn-sm btn-outline-secondary">Vezi toate →</a></div>
    <?php else: ?>
      <p class="text-muted">Nu există cereri încă.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
