<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$allowed_types = ['portofoliu', 'servicii', 'saloane'];
$type = $_GET['type'] ?? 'portofoliu';
if (!in_array($type, $allowed_types, true)) {
    $type = 'portofoliu';
}

$type_labels = [
    'portofoliu' => 'Portofoliu',
    'servicii'   => 'Servicii',
    'saloane'    => 'Saloane',
];
$type_label = $type_labels[$type];

// ── Handle actions ───────────────────────────────────────────
$msg = '';
$msg_type = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    if ($action === 'delete' && $id > 0) {
        $stmt = db()->prepare("DELETE FROM cms_items WHERE id = ? AND type = ?");
        $stmt->execute([$id, $type]);
        $msg = 'Înregistrarea a fost ștearsă.';
        $msg_type = 'success';
    }

    if ($action === 'toggle' && $id > 0) {
        $stmt = db()->prepare("UPDATE cms_items SET active = 1 - active WHERE id = ? AND type = ?");
        $stmt->execute([$id, $type]);
        $msg = 'Stare actualizată.';
    }

    if ($action === 'reorder' && isset($_POST['order'])) {
        $order = array_map('intval', explode(',', $_POST['order']));
        foreach ($order as $sort => $item_id) {
            db()->prepare("UPDATE cms_items SET sort_order = ? WHERE id = ? AND type = ?")
                ->execute([$sort + 1, $item_id, $type]);
        }
        header('Content-Type: application/json');
        echo json_encode(['ok' => true]);
        exit;
    }
}

// ── Load items ───────────────────────────────────────────────
$items = db()->prepare("SELECT id, slug, title, og_image, active, sort_order, updated_at
    FROM cms_items WHERE type = ? ORDER BY sort_order ASC, id ASC");
$items->execute([$type]);
$items = $items->fetchAll();

$csrf = csrf_token();
?>
<!doctype html>
<html lang="ro">
<head>
<?php admin_head($type_label) ?>
<?php admin_css() ?>
<style>
.page-header { padding: 1.5rem 0 1rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem }
.items-table { width:100%; border-collapse:collapse; background:#fff; border-radius:12px; overflow:hidden; border:1px solid var(--border) }
.items-table th { font-size:10px; letter-spacing:.2em; text-transform:uppercase; color:var(--muted); padding:.9rem 1.25rem; background:#faf8f5; border-bottom:1px solid var(--border); font-weight:600 }
.items-table td { padding:.9rem 1.25rem; border-bottom:1px solid #f0ece6; vertical-align:middle; font-size:.85rem }
.items-table tr:last-child td { border-bottom:none }
.items-table tr:hover td { background:#faf8f5 }
.item-thumb { width:52px; height:40px; object-fit:cover; border-radius:6px; background:#e9e4dd }
.item-thumb-placeholder { width:52px; height:40px; border-radius:6px; background:#e9e4dd; display:flex; align-items:center; justify-content:center; font-size:1.2rem }
.badge-active { background:#dcfce7; color:#15803d; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600 }
.badge-inactive { background:#f3f4f6; color:#6b7280; padding:2px 10px; border-radius:20px; font-size:.72rem; font-weight:600 }
.action-btn { background:none; border:1px solid var(--border); border-radius:6px; padding:4px 12px; font-size:.78rem; cursor:pointer; color:var(--muted); transition:all .15s; text-decoration:none; display:inline-block }
.action-btn:hover { border-color:var(--dark); color:var(--dark) }
.action-btn.edit:hover { border-color:var(--burg); color:var(--burg) }
.action-btn.del { border-color:#fca5a5; color:#ef4444 }
.action-btn.del:hover { background:#fef2f2; border-color:#ef4444 }
.type-tabs a { padding:.5rem 1.25rem; border-radius:20px; text-decoration:none; font-size:.82rem; font-weight:500; color:var(--muted); transition:all .2s }
.type-tabs a.active { background:var(--dark); color:#fff }
.type-tabs a:not(.active):hover { background:var(--border); color:var(--dark) }
.drag-handle { cursor:grab; color:var(--border); font-size:1.1rem; padding-right:.5rem }
.drag-handle:hover { color:var(--muted) }
.empty-state { text-align:center; padding:4rem 2rem; color:var(--muted) }
</style>
</head>
<body>
<?php admin_nav($type) ?>

<div class="container-xl py-4 px-4">

  <!-- Page header -->
  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <div class="eyebrow mb-1">Conținut</div>
      <h1 class="h3 mb-0"><?= $type_label ?></h1>
    </div>
    <a href="page-edit.php?type=<?= $type ?>" class="btn-burg btn">+ Adaugă <?= strtolower($type_label) ?></a>
  </div>

  <!-- Type tabs -->
  <div class="type-tabs d-flex gap-1 mb-4">
    <a href="?type=portofoliu" class="<?= $type === 'portofoliu' ? 'active' : '' ?>">Portofoliu</a>
    <a href="?type=servicii"   class="<?= $type === 'servicii'   ? 'active' : '' ?>">Servicii</a>
    <a href="?type=saloane"    class="<?= $type === 'saloane'    ? 'active' : '' ?>">Saloane</a>
  </div>

  <?php if ($msg !== ''): ?>
  <div class="alert alert-<?= $msg_type ?> py-2 mb-3"><?= htmlspecialchars($msg) ?></div>
  <?php endif ?>

  <!-- Table -->
  <?php if (empty($items)): ?>
  <div class="empty-state">
    <div style="font-size:3rem;margin-bottom:1rem">📄</div>
    <p>Nicio înregistrare găsită.</p>
    <a href="page-edit.php?type=<?= $type ?>" class="btn-burg btn mt-2">Creează prima</a>
  </div>
  <?php else: ?>
  <table class="items-table" id="items-table">
    <thead>
      <tr>
        <th style="width:30px"></th>
        <th style="width:60px">Img</th>
        <th>Titlu</th>
        <th style="width:120px">Slug</th>
        <th style="width:90px">Stare</th>
        <th style="width:130px">Actualizat</th>
        <th style="width:160px">Acțiuni</th>
      </tr>
    </thead>
    <tbody id="sortable-body">
    <?php foreach ($items as $item): ?>
    <tr data-id="<?= $item['id'] ?>">
      <td><span class="drag-handle" title="Trage pentru reordonare">⠿</span></td>
      <td>
        <?php if ($item['og_image']): ?>
          <img src="<?= htmlspecialchars($item['og_image']) ?>" alt="" class="item-thumb" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
          <div class="item-thumb-placeholder" style="display:none">🖼</div>
        <?php else: ?>
          <div class="item-thumb-placeholder">🖼</div>
        <?php endif ?>
      </td>
      <td>
        <div style="font-weight:500"><?= htmlspecialchars($item['title'] ?: '(fără titlu)') ?></div>
      </td>
      <td>
        <code style="font-size:.78rem;color:var(--muted)">/<?= $type ?>/<?= htmlspecialchars($item['slug']) ?></code>
      </td>
      <td>
        <span class="<?= $item['active'] ? 'badge-active' : 'badge-inactive' ?>">
          <?= $item['active'] ? 'Activ' : 'Inactiv' ?>
        </span>
      </td>
      <td style="color:var(--muted);font-size:.78rem">
        <?= date('d.m.Y H:i', strtotime($item['updated_at'])) ?>
      </td>
      <td>
        <div class="d-flex gap-1 align-items-center">
          <a href="page-edit.php?type=<?= $type ?>&id=<?= $item['id'] ?>" class="action-btn edit">Editează</a>
          <form method="post" style="display:inline" onsubmit="return confirm('Sigur vrei să schimbi starea?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <button type="submit" class="action-btn" title="<?= $item['active'] ? 'Dezactivează' : 'Activează' ?>">
              <?= $item['active'] ? '⏸' : '▶' ?>
            </button>
          </form>
          <form method="post" style="display:inline" onsubmit="return confirm('Ștergi definitiv această înregistrare?')">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" value="<?= $item['id'] ?>">
            <button type="submit" class="action-btn del" title="Șterge">✕</button>
          </form>
        </div>
      </td>
    </tr>
    <?php endforeach ?>
    </tbody>
  </table>
  <?php endif ?>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Simple drag-to-reorder
(function() {
  const tbody = document.getElementById('sortable-body');
  if (!tbody) return;
  let dragged = null;

  tbody.querySelectorAll('tr').forEach(row => {
    row.draggable = true;
    row.addEventListener('dragstart', () => { dragged = row; row.style.opacity = '.4'; });
    row.addEventListener('dragend',   () => { row.style.opacity = ''; dragged = null; saveOrder(); });
    row.addEventListener('dragover',  e => { e.preventDefault(); const after = getDragAfterEl(e.clientY); after ? tbody.insertBefore(dragged, after) : tbody.appendChild(dragged); });
  });

  function getDragAfterEl(y) {
    return [...tbody.querySelectorAll('tr:not(.dragging)')].reduce((closest, el) => {
      const box = el.getBoundingClientRect();
      const offset = y - box.top - box.height / 2;
      return (offset < 0 && offset > closest.offset) ? { offset, el } : closest;
    }, { offset: -Infinity }).el;
  }

  function saveOrder() {
    const ids = [...tbody.querySelectorAll('tr')].map(r => r.dataset.id).join(',');
    const csrf = document.querySelector('input[name=_csrf]').value;
    fetch('', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: `_csrf=${encodeURIComponent(csrf)}&action=reorder&order=${ids}`
    });
  }
})();
</script>
</body>
</html>
