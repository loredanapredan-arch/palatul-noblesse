<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$admin = current_admin();

// Check if cms_items table exists
$cms_ready = false;
$counts = ['portofoliu' => 0, 'servicii' => 0, 'saloane' => 0];
try {
    foreach (array_keys($counts) as $type) {
        $stmt = db()->prepare("SELECT COUNT(*) FROM cms_items WHERE type = ?");
        $stmt->execute([$type]);
        $counts[$type] = (int) $stmt->fetchColumn();
    }
    $cms_ready = true;
} catch (\PDOException $e) {
    // Table doesn't exist yet
}

$total = array_sum($counts);
?>
<!doctype html>
<html lang="ro">
<head><?php admin_head('Dashboard') ?></head>
<body>
<?php admin_nav('index') ?>
<?php admin_css() ?>
<style>
.stat-card { background:#fff; border-radius:12px; border:1px solid var(--border); padding:1.75rem; transition:box-shadow .2s; text-decoration:none; color:inherit; display:block }
.stat-card:hover { box-shadow:0 8px 32px rgba(0,0,0,.1); color:inherit }
.stat-num { font-size:2.5rem; font-weight:600; font-family:'Cormorant Garamond',serif; color:var(--dark); line-height:1 }
.stat-label { font-size:.75rem; letter-spacing:.2em; text-transform:uppercase; color:var(--muted); margin-top:.4rem }
.stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; margin-bottom:1rem }
.welcome { background:var(--dark); color:#fff; border-radius:16px; padding:2rem 2.5rem; margin-bottom:2rem }
.welcome h1 { font-family:'Cormorant Garamond',serif; font-size:1.8rem; font-weight:600 }
.quick-link { display:flex; align-items:center; gap:.75rem; padding:1rem; background:#fff; border-radius:10px; border:1px solid var(--border); text-decoration:none; color:var(--dark); font-size:.85rem; font-weight:500; transition:all .2s }
.quick-link:hover { border-color:var(--gold); color:var(--dark); box-shadow:0 4px 12px rgba(0,0,0,.08) }
.quick-link svg { flex-shrink:0; opacity:.6 }
</style>

<div class="container-xl py-4 px-4">

  <?php if (!$cms_ready): ?>
  <div class="alert alert-warning d-flex align-items-center gap-2" role="alert">
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
    <span>Tabelele CMS nu există încă. <a href="install-cms.php" class="alert-link">Rulează instalarea</a> pentru a continua.</span>
  </div>
  <?php endif ?>

  <!-- Welcome -->
  <div class="welcome mb-4">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
      <div>
        <p class="eyebrow" style="color:rgba(201,161,74,.8)">Panou de control</p>
        <h1 class="mt-1">Bună ziua, <?= htmlspecialchars($admin['username']) ?>!</h1>
        <p style="color:rgba(255,255,255,.55);font-size:.88rem;margin-top:.5rem">
          Gestionezi conținutul site-ului Palatul Noblesse.
        </p>
      </div>
      <div class="d-flex gap-2 flex-wrap">
        <a href="pages-list.php?type=portofoliu&action=new" class="btn-gold btn">+ Portofoliu</a>
        <a href="pages-list.php?type=servicii&action=new" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:6px;padding:.5rem 1.25rem;font-size:.82rem;font-weight:600">+ Serviciu</a>
        <a href="pages-list.php?type=saloane&action=new" class="btn" style="background:rgba(255,255,255,.1);color:#fff;border:1px solid rgba(255,255,255,.2);border-radius:6px;padding:.5rem 1.25rem;font-size:.82rem;font-weight:600">+ Salon</a>
      </div>
    </div>
  </div>

  <!-- Stats -->
  <div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
      <a href="#" class="stat-card">
        <div class="stat-icon" style="background:#fef3c7">🏆</div>
        <div class="stat-num"><?= $total ?></div>
        <div class="stat-label">Total pagini</div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="pages-list.php?type=portofoliu" class="stat-card">
        <div class="stat-icon" style="background:#ede9fe">📸</div>
        <div class="stat-num"><?= $counts['portofoliu'] ?></div>
        <div class="stat-label">Portofoliu</div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="pages-list.php?type=servicii" class="stat-card">
        <div class="stat-icon" style="background:#dcfce7">⭐</div>
        <div class="stat-num"><?= $counts['servicii'] ?></div>
        <div class="stat-label">Servicii</div>
      </a>
    </div>
    <div class="col-6 col-md-3">
      <a href="pages-list.php?type=saloane" class="stat-card">
        <div class="stat-icon" style="background:#fce7f3">🏛️</div>
        <div class="stat-num"><?= $counts['saloane'] ?></div>
        <div class="stat-label">Saloane</div>
      </a>
    </div>
  </div>

  <!-- Sections -->
  <div class="row g-4">
    <div class="col-md-4">
      <div class="card-admin h-100">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="eyebrow">Portofoliu</div>
          <a href="pages-list.php?type=portofoliu" class="text-decoration-none" style="font-size:.78rem;color:var(--burg)">Vezi tot →</a>
        </div>
        <p class="text-muted small mb-3">Gestionează evenimentele din portofoliu: nunți, botezuri, corporate și altele.</p>
        <div class="d-flex gap-2">
          <a href="pages-list.php?type=portofoliu" class="btn btn-sm btn-outline-secondary">Listare</a>
          <a href="page-edit.php?type=portofoliu" class="btn btn-sm btn-burg">+ Adaugă</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-admin h-100">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="eyebrow">Servicii</div>
          <a href="pages-list.php?type=servicii" class="text-decoration-none" style="font-size:.78rem;color:var(--burg)">Vezi tot →</a>
        </div>
        <p class="text-muted small mb-3">Editează paginile de servicii: culinar, bar, decorațiuni, artistic și altele.</p>
        <div class="d-flex gap-2">
          <a href="pages-list.php?type=servicii" class="btn btn-sm btn-outline-secondary">Listare</a>
          <a href="page-edit.php?type=servicii" class="btn btn-sm btn-burg">+ Adaugă</a>
        </div>
      </div>
    </div>
    <div class="col-md-4">
      <div class="card-admin h-100">
        <div class="d-flex align-items-center justify-content-between mb-3">
          <div class="eyebrow">Saloane</div>
          <a href="pages-list.php?type=saloane" class="text-decoration-none" style="font-size:.78rem;color:var(--burg)">Vezi tot →</a>
        </div>
        <p class="text-muted small mb-3">Gestionează saloanele: Baroque, Florence, Regent, Luxury, Grădina, Royal.</p>
        <div class="d-flex gap-2">
          <a href="pages-list.php?type=saloane" class="btn btn-sm btn-outline-secondary">Listare</a>
          <a href="page-edit.php?type=saloane" class="btn btn-sm btn-burg">+ Adaugă</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Quick links -->
  <div class="mt-4">
    <div class="eyebrow mb-3">Acțiuni rapide</div>
    <div class="row g-2">
      <div class="col-sm-6 col-lg-3">
        <a href="install-cms.php" class="quick-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg>
          Instalare / Actualizare DB
        </a>
      </div>
      <div class="col-sm-6 col-lg-3">
        <a href="/" target="_blank" class="quick-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Vizualizează site-ul
        </a>
      </div>
      <div class="col-sm-6 col-lg-3">
        <a href="logout.php" class="quick-link">
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
          Deconectare
        </a>
      </div>
    </div>
  </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
