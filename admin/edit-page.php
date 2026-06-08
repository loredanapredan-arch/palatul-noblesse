<?php
require_once 'auth.php';
require_once __DIR__ . '/../config/db.php';

$id   = (int)($_GET['id'] ?? 0);
$page = db()->prepare('SELECT * FROM pages WHERE id = ?');
$page->execute([$id]);
$page = $page->fetch();

if (!$page) { header('Location: pages.php'); exit; }

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title    = trim(strip_tags($_POST['title'] ?? ''));
    $meta     = trim(strip_tags($_POST['meta_description'] ?? ''));
    $canonical = trim(strip_tags($_POST['canonical'] ?? ''));
    if ($title) {
        db()->prepare('UPDATE pages SET title=?, meta_description=?, updated_at=NOW() WHERE id=?')
            ->execute([$title, $meta, $id]);
        // Actualizez și canonical în content_blocks dacă există
        $page['title']            = $title;
        $page['meta_description'] = $meta;
        $saved = true;
    }
}
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Editează pagină — Admin</title>
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
  label{font-size:.75rem;text-transform:uppercase;letter-spacing:.2em;color:#6b5f55;font-weight:500}
  .btn-save{background:var(--burgundy);color:#fff;border:none;padding:.75rem 2rem;text-transform:uppercase;letter-spacing:.2em;font-size:.8rem}
  .btn-save:hover{background:#8d2225;color:#fff}
  .char-count{font-size:.75rem;color:#6b5f55;text-align:right;margin-top:.25rem}
  .char-count.warn{color:#bd3033}
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
  <div class="d-flex align-items-center gap-3 mb-4">
    <a href="pages.php" class="text-muted text-decoration-none">← Pagini</a>
    <h1 class="mb-0"><?= htmlspecialchars($page['title']) ?></h1>
  </div>

  <?php if ($saved): ?>
    <div class="alert alert-success py-2">✓ Salvat cu succes.</div>
  <?php endif; ?>

  <form method="post" class="bg-white p-4">
    <div class="mb-4">
      <label class="form-label">Titlu pagină (tag &lt;title&gt;)</label>
      <input name="title" type="text" class="form-control"
             value="<?= htmlspecialchars($page['title']) ?>" required maxlength="70" id="inp-title">
      <div class="char-count" id="cnt-title">0 / 70</div>
    </div>

    <div class="mb-4">
      <label class="form-label">Meta description <span class="fw-normal text-muted">(recomandat 120–160 caractere)</span></label>
      <textarea name="meta_description" class="form-control" rows="3" maxlength="200" id="inp-meta"><?= htmlspecialchars($page['meta_description'] ?? '') ?></textarea>
      <div class="char-count" id="cnt-meta">0 / 160</div>
    </div>

    <div class="mb-4">
      <label class="form-label">URL (slug)</label>
      <input type="text" class="form-control" value="/<?= htmlspecialchars($page['slug']) ?>" disabled
             style="background:#f5f0ea;color:#6b5f55">
      <small class="text-muted">URL-ul nu se modifică (protejat SEO)</small>
    </div>

    <button type="submit" class="btn btn-save">Salvează modificările</button>
  </form>
</div>

<script>
function countChars(inp, cnt, max) {
  var el = document.getElementById(inp), counter = document.getElementById(cnt);
  function update() {
    var n = el.value.length;
    counter.textContent = n + ' / ' + max;
    counter.classList.toggle('warn', n > max);
  }
  el.addEventListener('input', update);
  update();
}
countChars('inp-title', 'cnt-title', 70);
countChars('inp-meta',  'cnt-meta',  160);
</script>
</body>
</html>
