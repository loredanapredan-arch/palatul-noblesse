<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$rows = db()->query('SELECT * FROM contact_requests ORDER BY created_at DESC')->fetchAll();
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Cereri ofertă — Admin Palatul Noblesse</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<style>
  :root { --burgundy:#bd3033; --gold:#c9a14a; --charcoal:#1f1b18; }
  body { background:#f5f0ea; }
  .sidebar{background:var(--charcoal);min-height:100vh;width:240px;position:fixed;top:0;left:0;padding:1.5rem 0}
  .sidebar .brand{color:#fff;font-family:Georgia,serif;font-size:1.2rem;padding:.5rem 1.5rem 1.5rem;border-bottom:1px solid rgba(255,255,255,.1)}
  .sidebar .brand small{display:block;font-size:9px;letter-spacing:.3em;text-transform:uppercase;color:var(--gold);margin-top:3px}
  .sidebar a{display:block;padding:.65rem 1.5rem;color:rgba(255,255,255,.7);text-decoration:none;font-size:.85rem;text-transform:uppercase;letter-spacing:.15em}
  .sidebar a:hover,.sidebar a.active{color:#fff;background:rgba(255,255,255,.08)}
  .sidebar a.active{border-left:3px solid var(--burgundy)}
  .main{margin-left:240px;padding:2rem}
  h1{font-family:Georgia,serif;font-size:1.75rem;color:var(--charcoal)}
  .table th{font-size:.75rem;text-transform:uppercase;letter-spacing:.15em;color:#6b5f55;font-weight:500}
  .badge-new{background:var(--burgundy);color:#fff;font-size:.7rem;padding:.2rem .5rem}
</style>
</head>
<body>
<div class="sidebar">
  <div class="brand">Palatul Noblesse <small>Admin Panel</small></div>
  <a href="index.php">Dashboard</a>
  <a href="pages.php">Pagini</a>
  <a href="contacts.php" class="active">Cereri de ofertă</a>
  <a href="change-password.php">Schimbă parola</a>
  <a href="../index.php" target="_blank">Vezi site-ul →</a>
  <a href="logout.php" style="position:absolute;bottom:1rem;width:100%">Deconectare</a>
</div>

<div class="main">
  <h1 class="mb-4">Cereri de ofertă <span class="fs-5 text-muted">(<?= count($rows) ?>)</span></h1>

  <?php if ($rows): ?>
  <div class="bg-white">
    <table class="table table-hover mb-0">
      <thead class="table-light">
        <tr>
          <th>#</th><th>Nume</th><th>Email</th><th>Telefon</th>
          <th>Tip eveniment</th><th>Data dorită</th><th>Invitați</th><th>Primit</th><th></th>
        </tr>
      </thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td class="text-muted"><?= $r['id'] ?></td>
          <td><?= htmlspecialchars($r['name']) ?></td>
          <td><a href="mailto:<?= htmlspecialchars($r['email']) ?>"><?= htmlspecialchars($r['email']) ?></a></td>
          <td><a href="tel:<?= htmlspecialchars($r['phone']) ?>"><?= htmlspecialchars($r['phone']) ?></a></td>
          <td><?= htmlspecialchars($r['event_type'] ?? '—') ?></td>
          <td><?= $r['event_date'] ? date('d.m.Y', strtotime($r['event_date'])) : '—' ?></td>
          <td><?= $r['guests'] ?: '—' ?></td>
          <td><?= date('d.m.Y H:i', strtotime($r['created_at'])) ?></td>
          <td>
            <?php if ($r['message']): ?>
            <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#msg<?= $r['id'] ?>">Mesaj</button>
            <div class="modal fade" id="msg<?= $r['id'] ?>">
              <div class="modal-dialog"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title"><?= htmlspecialchars($r['name']) ?></h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <div class="modal-body"><p style="white-space:pre-wrap"><?= htmlspecialchars($r['message']) ?></p></div>
              </div></div>
            </div>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php else: ?>
    <div class="bg-white p-4 text-muted">Nu există cereri de ofertă încă.</div>
  <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
