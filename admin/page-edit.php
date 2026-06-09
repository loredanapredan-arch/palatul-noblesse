<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

$allowed_types = ['portofoliu', 'servicii', 'saloane'];
$type = trim($_GET['type'] ?? 'portofoliu');
if (!in_array($type, $allowed_types, true)) {
    header('Location: index.php');
    exit;
}

$type_labels = ['portofoliu' => 'Portofoliu', 'servicii' => 'Servicii', 'saloane' => 'Saloane'];
$type_label  = $type_labels[$type];

// Load item
$item = null;
$id   = (int) ($_GET['id'] ?? 0);
if ($id > 0) {
    $stmt = db()->prepare("SELECT * FROM cms_items WHERE id = ? AND type = ? LIMIT 1");
    $stmt->execute([$id, $type]);
    $item = $stmt->fetch() ?: null;
    if (!$item) {
        header("Location: pages-list.php?type={$type}");
        exit;
    }
}

$is_new      = $item === null;
$blocks_json = $item ? ($item['blocks'] ?? '[]') : '[]';
$extra_json  = $item ? ($item['extra']  ?? '{}') : '{}';
$extra       = json_decode($extra_json, true) ?: [];

$csrf = csrf_token();
?>
<!doctype html>
<html lang="ro">
<head>
<?php admin_head(($is_new ? 'Adaugă' : 'Editează') . " — {$type_label}") ?>
<?php admin_css() ?>
<style>
html, body { height: 100%; overflow: hidden }
body { display: flex; flex-direction: column }
.editor-shell { flex: 1; display: flex; flex-direction: column; overflow: hidden }
/* ── Top bar ── */
.editor-topbar {
  background: #fff;
  border-bottom: 1px solid var(--border);
  padding: .65rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
  flex-shrink: 0;
  z-index: 10;
}
.topbar-breadcrumb { display: flex; align-items: center; gap: .4rem; font-size: .82rem; color: var(--muted); flex: 1; min-width: 0 }
.topbar-breadcrumb a { color: var(--muted); text-decoration: none; white-space: nowrap }
.topbar-breadcrumb a:hover { color: var(--dark) }
.topbar-breadcrumb .sep { color: var(--border) }
.topbar-breadcrumb .current { color: var(--dark); font-weight: 500; white-space: nowrap; overflow: hidden; text-overflow: ellipsis }
.topbar-actions { display: flex; align-items: center; gap: .5rem; flex-shrink: 0 }
/* ── Editor body ── */
.editor-body { flex: 1; display: flex; overflow: hidden }
/* ── Sidebar ── */
.editor-sidebar {
  width: 320px;
  flex-shrink: 0;
  background: #fff;
  border-right: 1px solid var(--border);
  overflow-y: auto;
  display: flex;
  flex-direction: column;
}
.sidebar-section { border-bottom: 1px solid var(--border); padding: 1.1rem 1.25rem }
.sidebar-section:last-child { border-bottom: none; flex: 1 }
.sidebar-section-title {
  font-size: 10px;
  letter-spacing: .22em;
  text-transform: uppercase;
  color: var(--muted);
  font-weight: 600;
  margin-bottom: .9rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
  cursor: pointer;
  user-select: none;
}
.sidebar-section-title .chevron { transition: transform .2s; font-size: .7rem }
.sidebar-section-title.collapsed .chevron { transform: rotate(-90deg) }
.sidebar-section-body { }
.sidebar-section-body.d-none + .sidebar-section-title { /* nothing */ }
/* ── Form controls ── */
.f-group { margin-bottom: .9rem }
.f-group:last-child { margin-bottom: 0 }
.f-label {
  display: block;
  font-size: .72rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: .12em;
  color: var(--muted);
  margin-bottom: .35rem;
}
.f-label .hint { font-weight: 400; text-transform: none; letter-spacing: 0; color: #aaa; margin-left: .3rem }
.f-input {
  width: 100%;
  padding: .55rem .75rem;
  border: 1.5px solid var(--border);
  border-radius: 6px;
  font-size: .83rem;
  font-family: 'Inter', sans-serif;
  color: var(--dark);
  background: #faf8f5;
  outline: none;
  transition: border-color .2s;
  resize: vertical;
}
.f-input:focus { border-color: var(--burg); background: #fff }
.f-input.mono { font-family: monospace; font-size: .75rem }
.char-count { font-size: .68rem; color: var(--muted); text-align: right; margin-top: .2rem }
.char-count.warn { color: #ef4444 }
/* ── Image picker ── */
.img-picker-preview {
  width: 100%;
  height: 100px;
  border-radius: 8px;
  background: #f0ece6;
  overflow: hidden;
  margin-bottom: .5rem;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1.5px dashed var(--border);
  position: relative;
}
.img-picker-preview img { width: 100%; height: 100%; object-fit: cover }
.img-picker-preview .placeholder { color: var(--muted); font-size: .8rem; text-align: center; padding: .5rem }
.img-picker-btns { display: flex; gap: .4rem; align-items: center }
.img-picker-url { flex: 1; padding: .45rem .65rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .75rem; background: #faf8f5; outline: none; color: var(--dark) }
.img-picker-url:focus { border-color: var(--burg) }
.upload-btn { background: none; border: 1.5px solid var(--border); border-radius: 6px; padding: .45rem .7rem; font-size: .75rem; cursor: pointer; color: var(--muted); transition: all .15s; white-space: nowrap }
.upload-btn:hover { border-color: var(--dark); color: var(--dark) }
.upload-input { display: none }
/* ── Content area ── */
.editor-content {
  flex: 1;
  overflow-y: auto;
  padding: 1.5rem;
  background: #f5f0ea;
  min-width: 0;
}
/* ── Block card ── */
.block-card {
  background: #fff;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  margin-bottom: .75rem;
  transition: border-color .2s, box-shadow .2s;
}
.block-card:hover { border-color: #c9b9a8 }
.block-card.is-open { border-color: var(--burg); box-shadow: 0 4px 20px rgba(189,48,51,.08) }
.block-header {
  display: flex;
  align-items: center;
  gap: .75rem;
  padding: .9rem 1rem;
  cursor: pointer;
  user-select: none;
}
.block-handle { cursor: grab; color: #c9b9a8; font-size: 1rem; flex-shrink: 0 }
.block-handle:active { cursor: grabbing }
.block-badge {
  font-size: 9px;
  letter-spacing: .2em;
  text-transform: uppercase;
  font-weight: 700;
  padding: 3px 10px;
  border-radius: 20px;
  flex-shrink: 0;
}
.badge-hero      { background: #ede9fe; color: #5b21b6 }
.badge-svc-detail { background: #dcfce7; color: #15803d }
.badge-text      { background: #fef3c7; color: #92400e }
.badge-stats     { background: #dbeafe; color: #1e40af }
.badge-salon-row { background: #fce7f3; color: #9d174d }
.badge-related   { background: #f3f4f6; color: #374151 }
.block-summary { flex: 1; font-size: .83rem; color: var(--muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; min-width: 0 }
.block-actions { display: flex; gap: .25rem; flex-shrink: 0; margin-left: auto }
.block-btn {
  width: 28px;
  height: 28px;
  display: flex;
  align-items: center;
  justify-content: center;
  border: 1px solid var(--border);
  border-radius: 6px;
  background: none;
  cursor: pointer;
  font-size: .8rem;
  color: var(--muted);
  transition: all .15s;
  padding: 0;
}
.block-btn:hover { border-color: var(--dark); color: var(--dark) }
.block-btn.del { border-color: #fca5a5; color: #ef4444 }
.block-btn.del:hover { background: #fef2f2 }
/* ── Block form ── */
.block-form {
  border-top: 1px solid var(--border);
  padding: 1.1rem;
  display: none;
}
.block-card.is-open .block-form { display: block }
.bf-label { font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); margin-bottom: .35rem; display: block }
.bf-input { width: 100%; padding: .5rem .7rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .83rem; font-family: 'Inter', sans-serif; color: var(--dark); background: #faf8f5; outline: none; transition: border-color .2s; resize: vertical }
.bf-input:focus { border-color: var(--burg); background: #fff }
.bf-row { margin-bottom: .85rem }
.bf-row:last-child { margin-bottom: 0 }
/* ── Features list ── */
.feature-item { display: flex; gap: .4rem; align-items: center; margin-bottom: .4rem }
.feature-item input { flex: 1; padding: .45rem .65rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .82rem; background: #faf8f5; outline: none; color: var(--dark) }
.feature-item input:focus { border-color: var(--burg); background: #fff }
.feature-item .rem-btn { width: 26px; height: 26px; border: none; background: none; color: #ccc; cursor: pointer; font-size: 1rem; flex-shrink: 0; display: flex; align-items: center; justify-content: center; border-radius: 4px }
.feature-item .rem-btn:hover { background: #fef2f2; color: #ef4444 }
.add-feature-btn { background: none; border: 1.5px dashed var(--border); border-radius: 6px; padding: .4rem .8rem; font-size: .78rem; cursor: pointer; color: var(--muted); transition: all .15s; width: 100%; margin-top: .2rem }
.add-feature-btn:hover { border-color: var(--dark); color: var(--dark) }
/* ── Stats ── */
.stat-item { display: grid; grid-template-columns: 1fr 1fr auto; gap: .4rem; align-items: center; margin-bottom: .4rem }
.stat-item input { padding: .45rem .65rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .82rem; background: #faf8f5; outline: none; color: var(--dark) }
.stat-item input:focus { border-color: var(--burg); background: #fff }
/* ── Add block area ── */
.add-block-area { margin-top: 1rem }
.block-type-picker {
  background: #fff;
  border: 1.5px solid var(--border);
  border-radius: 10px;
  padding: 1rem;
  display: none;
  margin-top: .75rem;
}
.block-type-picker.is-open { display: block }
.block-type-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(140px, 1fr)); gap: .5rem }
.block-type-btn {
  padding: .65rem .75rem;
  border: 1.5px solid var(--border);
  border-radius: 8px;
  background: #faf8f5;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  font-size: .78rem;
  font-weight: 500;
  color: var(--dark);
  text-align: left;
  transition: all .15s;
}
.block-type-btn:hover { border-color: var(--burg); background: #fff }
.block-type-btn .bicon { font-size: 1.2rem; display: block; margin-bottom: .3rem }
/* ── Toast ── */
.toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999 }
.toast-msg {
  background: var(--dark);
  color: #fff;
  padding: .65rem 1.25rem;
  border-radius: 8px;
  font-size: .83rem;
  margin-top: .4rem;
  opacity: 0;
  transform: translateY(8px);
  transition: all .25s;
  max-width: 320px;
  box-shadow: 0 8px 24px rgba(0,0,0,.25);
}
.toast-msg.show { opacity: 1; transform: translateY(0) }
.toast-msg.err { background: var(--burg) }
/* ── Save btn states ── */
#btn-save { min-width: 100px }
#btn-save.saving { opacity: .7; cursor: wait }
/* ── Delete confirm ── */
.del-overlay { position: fixed; inset: 0; background: rgba(0,0,0,.5); z-index: 1000; display: none; align-items: center; justify-content: center }
.del-overlay.show { display: flex }
.del-box { background: #fff; border-radius: 14px; padding: 2rem; max-width: 380px; width: 90%; text-align: center }
/* ── Checkbox toggle ── */
.toggle-wrap { display: flex; align-items: center; gap: .65rem }
.toggle-wrap input[type=checkbox] { width: 18px; height: 18px; cursor: pointer; accent-color: var(--burg) }
.toggle-wrap label { font-size: .83rem; color: var(--dark); cursor: pointer; margin: 0 }
</style>
</head>
<body>
<?php admin_nav($type) ?>

<div class="editor-shell">

  <!-- Top bar -->
  <div class="editor-topbar">
    <div class="topbar-breadcrumb">
      <a href="pages-list.php?type=<?= $type ?>">← <?= $type_label ?></a>
      <span class="sep">/</span>
      <span class="current" id="topbar-title">
        <?= $is_new ? "Adaugă " . strtolower($type_label) : htmlspecialchars($item['title'] ?: '(fără titlu)') ?>
      </span>
    </div>
    <div class="topbar-actions">
      <?php if (!$is_new): ?>
      <a href="/<?= $type ?>/<?= htmlspecialchars($item['slug']) ?>.php" target="_blank"
         class="btn btn-sm btn-outline-secondary" style="font-size:.78rem">
        ↗ Vizualizează
      </a>
      <button type="button" id="btn-delete" class="btn btn-sm"
              style="border:1px solid #fca5a5;color:#ef4444;font-size:.78rem;background:none">
        Șterge
      </button>
      <?php endif ?>
      <button type="button" id="btn-save" class="btn-burg btn btn-sm">Salvează</button>
    </div>
  </div>

  <!-- Editor body -->
  <div class="editor-body">

    <!-- ── SIDEBAR ── -->
    <aside class="editor-sidebar">

      <!-- SEO & Meta -->
      <div class="sidebar-section">
        <div class="sidebar-section-title" onclick="toggleSection(this)">
          SEO &amp; Meta <span class="chevron">▾</span>
        </div>
        <div class="sidebar-section-body">
          <div class="f-group">
            <label class="f-label">Titlu pagină <span class="hint">max 70 car.</span></label>
            <input id="meta-title" class="f-input" type="text" maxlength="100"
              value="<?= htmlspecialchars($item['title'] ?? '') ?>"
              oninput="document.getElementById('topbar-title').textContent = this.value || '(fără titlu)';updateCharCount('meta-title','cc-title',70)">
            <div class="char-count" id="cc-title"></div>
          </div>
          <div class="f-group">
            <label class="f-label">Meta descriere <span class="hint">max 160 car.</span></label>
            <textarea id="meta-desc" class="f-input" rows="3" maxlength="320"
              oninput="updateCharCount('meta-desc','cc-desc',160)"><?= htmlspecialchars($item['meta_desc'] ?? '') ?></textarea>
            <div class="char-count" id="cc-desc"></div>
          </div>
          <div class="f-group">
            <label class="f-label">Slug URL</label>
            <input id="meta-slug" class="f-input" type="text"
              value="<?= htmlspecialchars($item['slug'] ?? '') ?>"
              placeholder="ex: nunta-de-vis"
              oninput="this.value = this.value.toLowerCase().replace(/[^a-z0-9\-]/g,'')">
            <div style="font-size:.68rem;color:var(--muted);margin-top:.2rem">
              /<?= $type ?>/<span id="slug-preview"><?= htmlspecialchars($item['slug'] ?? '') ?></span>.php
            </div>
          </div>
          <div class="f-group">
            <label class="f-label">JSON-LD <span class="hint">structurat</span></label>
            <textarea id="meta-jsonld" class="f-input mono" rows="4"
              placeholder='{"@context":"https://schema.org", ...}'><?= htmlspecialchars($item['json_ld'] ?? '') ?></textarea>
          </div>
        </div>
      </div>

      <!-- Imagine principală -->
      <div class="sidebar-section">
        <div class="sidebar-section-title" onclick="toggleSection(this)">
          Imagine principală <span class="chevron">▾</span>
        </div>
        <div class="sidebar-section-body">
          <div class="f-group">
            <label class="f-label">OG Image / Hero</label>
            <?php $og = $item['og_image'] ?? '' ?>
            <div class="img-picker-preview" id="og-img-preview">
              <?php if ($og): ?>
              <img src="<?= htmlspecialchars($og) ?>" alt="" onerror="this.parentNode.innerHTML='<div class=placeholder>Imagine negăsită</div>'">
              <?php else: ?>
              <div class="placeholder">🖼️<br>Nicio imagine</div>
              <?php endif ?>
            </div>
            <div class="img-picker-btns">
              <input id="og-img-url" class="img-picker-url" type="text"
                value="<?= htmlspecialchars($og) ?>"
                placeholder="/assets/..."
                oninput="updateImgPreview('og-img-url','og-img-preview')">
              <button type="button" class="upload-btn" onclick="triggerUpload('og-file-input','og-img-url','og-img-preview')">
                ↑ Upload
              </button>
            </div>
            <input type="file" id="og-file-input" class="upload-input" accept="image/*">
          </div>
        </div>
      </div>

      <!-- Type-specific fields -->
      <div class="sidebar-section">
        <div class="sidebar-section-title" onclick="toggleSection(this)">
          <?php
          if ($type === 'portofoliu') echo 'Detalii eveniment';
          elseif ($type === 'servicii') echo 'Detalii serviciu';
          else echo 'Detalii salon';
          ?>
          <span class="chevron">▾</span>
        </div>
        <div class="sidebar-section-body">

          <?php if ($type === 'portofoliu'): ?>
          <div class="f-group">
            <label class="f-label">Categorie eveniment</label>
            <select id="extra-event_category" class="f-input">
              <?php foreach (['nunta'=>'Nuntă','botez'=>'Botez','corporate'=>'Corporate','petreceri-private'=>'Petrecere privată','petreceri-tineri'=>'Petrecere tineri','shooting'=>'Ședință foto'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= ($extra['event_category'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <?php elseif ($type === 'servicii'): ?>
          <div class="f-group">
            <label class="f-label">Categorie serviciu</label>
            <select id="extra-service_category" class="f-input">
              <?php foreach (['culinar'=>'Culinar','dulciuri'=>'Dulciuri','decoratiuni'=>'Decorațiuni','bar'=>'Bar','artistic'=>'Artistic','foto-video'=>'Foto & video','exclusivist'=>'Exclusivist','copii'=>'Copii'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= ($extra['service_category'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
              <?php endforeach ?>
            </select>
          </div>

          <?php else: // saloane ?>
          <div class="f-group">
            <label class="f-label">Nr. index</label>
            <input id="extra-index_number" class="f-input" type="text"
              value="<?= htmlspecialchars($extra['index_number'] ?? '') ?>" placeholder="01">
          </div>
          <div class="f-group">
            <label class="f-label">Suprafață</label>
            <input id="extra-suprafata" class="f-input" type="text"
              value="<?= htmlspecialchars($extra['suprafata'] ?? '') ?>" placeholder="45 m²">
          </div>
          <div class="f-group">
            <label class="f-label">Capacitate</label>
            <input id="extra-capacitate" class="f-input" type="text"
              value="<?= htmlspecialchars($extra['capacitate'] ?? '') ?>" placeholder="40 locuri">
          </div>
          <div class="f-group">
            <label class="f-label">Stil</label>
            <input id="extra-style" class="f-input" type="text"
              value="<?= htmlspecialchars($extra['style'] ?? '') ?>" placeholder="Baroque">
          </div>
          <?php endif ?>

        </div>
      </div>

    </aside>

    <!-- ── CONTENT EDITOR ── -->
    <div class="editor-content">

      <div id="blocks-container">
        <!-- Blocks rendered by JS -->
      </div>

      <!-- Add block -->
      <div class="add-block-area">
        <button type="button" id="btn-add-block" onclick="toggleBlockPicker()"
          style="width:100%;padding:.75rem;border:1.5px dashed var(--border);border-radius:10px;background:rgba(255,255,255,.7);cursor:pointer;font-size:.83rem;color:var(--muted);font-family:'Inter',sans-serif;transition:all .2s"
          onmouseover="this.style.borderColor='var(--burg)';this.style.color='var(--burg)'"
          onmouseout="this.style.borderColor='var(--border)';this.style.color='var(--muted)'">
          + Adaugă bloc
        </button>

        <div class="block-type-picker" id="block-type-picker">
          <div style="font-size:.78rem;color:var(--muted);margin-bottom:.65rem;font-weight:500">Alege tipul blocului:</div>
          <div class="block-type-grid">
            <button type="button" class="block-type-btn" onclick="addBlock('hero')">
              <span class="bicon">🦸</span>Hero
            </button>
            <button type="button" class="block-type-btn" onclick="addBlock('svc-detail')">
              <span class="bicon">📋</span>Detalii serviciu
            </button>
            <button type="button" class="block-type-btn" onclick="addBlock('text')">
              <span class="bicon">📝</span>Text
            </button>
            <button type="button" class="block-type-btn" onclick="addBlock('stats')">
              <span class="bicon">📊</span>Statistici
            </button>
            <?php if ($type === 'saloane'): ?>
            <button type="button" class="block-type-btn" onclick="addBlock('salon-row')">
              <span class="bicon">🏛️</span>Rând salon
            </button>
            <?php endif ?>
          </div>
        </div>
      </div>

    </div><!-- /editor-content -->
  </div><!-- /editor-body -->
</div><!-- /editor-shell -->

<!-- Delete confirm overlay -->
<div class="del-overlay" id="del-overlay">
  <div class="del-box">
    <div style="font-size:2rem;margin-bottom:.75rem">⚠️</div>
    <h3 style="font-size:1.1rem;margin-bottom:.5rem">Ștergi această pagină?</h3>
    <p style="font-size:.85rem;color:var(--muted);margin-bottom:1.5rem">
      Această acțiune este ireversibilă. Conținutul va fi șters definitiv.
    </p>
    <div class="d-flex gap-2 justify-content-center">
      <button onclick="document.getElementById('del-overlay').classList.remove('show')"
        class="btn btn-outline-secondary btn-sm">Anulează</button>
      <button id="btn-delete-confirm" class="btn btn-danger btn-sm">Da, șterge</button>
    </div>
  </div>
</div>

<!-- Toast container -->
<div class="toast-container" id="toast-container"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// ── Config ───────────────────────────────────────────────────
const ITEM_ID   = <?= $id ?>;
const ITEM_TYPE = <?= json_encode($type) ?>;
const CSRF      = <?= json_encode($csrf) ?>;

// ── Initial blocks ───────────────────────────────────────────
let blocks = <?= $blocks_json ?> || [];

// ── Bootstrap ────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  renderAllBlocks();
  initCharCounts();
  initSlugSync();
  initOgUpload();
  initDelete();
  document.getElementById('btn-save').addEventListener('click', savePage);
});

// ── Char counters ─────────────────────────────────────────────
function initCharCounts() {
  updateCharCount('meta-title','cc-title',70);
  updateCharCount('meta-desc','cc-desc',160);
}
function updateCharCount(inputId, counterId, limit) {
  const inp = document.getElementById(inputId);
  const el  = document.getElementById(counterId);
  if (!inp || !el) return;
  const len = inp.value.length;
  el.textContent = len + ' / ' + limit;
  el.className = 'char-count' + (len > limit ? ' warn' : '');
}

// ── Slug sync ────────────────────────────────────────────────
function initSlugSync() {
  const slugInput = document.getElementById('meta-slug');
  slugInput.addEventListener('input', () => {
    document.getElementById('slug-preview').textContent = slugInput.value;
  });
}

// ── Section toggle ───────────────────────────────────────────
function toggleSection(titleEl) {
  const body = titleEl.nextElementSibling;
  const collapsed = body.classList.toggle('d-none');
  titleEl.classList.toggle('collapsed', collapsed);
}

// ── Image preview ────────────────────────────────────────────
function updateImgPreview(urlInputId, previewId) {
  const url     = document.getElementById(urlInputId).value.trim();
  const preview = document.getElementById(previewId);
  if (!url) {
    preview.innerHTML = '<div class="placeholder">🖼️<br>Nicio imagine</div>';
    return;
  }
  preview.innerHTML = `<img src="${escAttr(url)}" alt="" onerror="this.parentNode.innerHTML='<div class=placeholder>Negăsit</div>'">`;
}

// ── OG Image upload ───────────────────────────────────────────
function initOgUpload() {
  const fileInput = document.getElementById('og-file-input');
  fileInput.addEventListener('change', () => {
    if (!fileInput.files[0]) return;
    uploadFile(fileInput.files[0], path => {
      document.getElementById('og-img-url').value = path;
      updateImgPreview('og-img-url', 'og-img-preview');
    });
  });
}

function triggerUpload(fileInputId, urlInputId, previewId) {
  const fi = document.getElementById(fileInputId);
  fi.onchange = () => {
    if (!fi.files[0]) return;
    uploadFile(fi.files[0], path => {
      if (urlInputId) {
        const urlEl = document.getElementById(urlInputId);
        if (urlEl) { urlEl.value = path; urlEl.dispatchEvent(new Event('input')); }
      }
      if (previewId) updateImgPreview(urlInputId, previewId);
    });
  };
  fi.click();
}

function uploadFile(file, callback) {
  const fd = new FormData();
  fd.append('_csrf', CSRF);
  fd.append('file', file);
  showToast('Se încarcă imaginea...', false, 60000);
  fetch('media-upload.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.ok) { callback(data.path); showToast('Imagine încărcată!') }
      else showToast(data.error || 'Eroare upload', true);
    })
    .catch(() => showToast('Eroare de rețea', true));
}

// ── Block type labels & badge classes ────────────────────────
const BLOCK_META = {
  'hero':       { label: 'Hero',              badge: 'badge-hero',       icon: '🦸' },
  'svc-detail': { label: 'Detalii serviciu',  badge: 'badge-svc-detail', icon: '📋' },
  'text':       { label: 'Text',              badge: 'badge-text',       icon: '📝' },
  'stats':      { label: 'Statistici',        badge: 'badge-stats',      icon: '📊' },
  'salon-row':  { label: 'Rând salon',        badge: 'badge-salon-row',  icon: '🏛️' },
};

function getBlockSummary(block) {
  const d = block.data || {};
  switch (block.type) {
    case 'hero':       return d.h1 || d.subtitle || '(gol)';
    case 'svc-detail': return d.heading || d.eyebrow || '(gol)';
    case 'text':       return d.heading || (d.content || '').substr(0,60) || '(gol)';
    case 'stats':      return (d.stats || []).map(s => s.label).join(', ') || '(gol)';
    case 'salon-row':  return d.name || '(gol)';
    default:           return '(gol)';
  }
}

// ── Block forms ──────────────────────────────────────────────
function buildBlockForm(block) {
  const d = block.data || {};
  const id = block.id;
  switch (block.type) {
    case 'hero': return `
      <div class="bf-row">
        <label class="bf-label">Imagine Hero</label>
        ${imgPickerHtml(`img_${id}`, d.image||'')}
      </div>
      <div class="bf-row">
        <label class="bf-label">Eyebrow text</label>
        <input class="bf-input" data-field="eyebrow" value="${escAttr(d.eyebrow||'')}" placeholder="ex: Portofoliu">
      </div>
      <div class="bf-row">
        <label class="bf-label">Titlu H1</label>
        <input class="bf-input" data-field="h1" value="${escAttr(d.h1||'')}">
      </div>
      <div class="bf-row">
        <label class="bf-label">Subtitlu</label>
        <textarea class="bf-input" data-field="subtitle" rows="2">${escHtml(d.subtitle||'')}</textarea>
      </div>`;

    case 'svc-detail': return `
      <div class="row g-2 bf-row">
        <div class="col-8">
          <label class="bf-label">Eyebrow</label>
          <input class="bf-input" data-field="eyebrow" value="${escAttr(d.eyebrow||'')}">
        </div>
        <div class="col-4 d-flex align-items-end pb-1">
          <div class="toggle-wrap">
            <input type="checkbox" id="rev_${id}" data-field="reverse" ${d.reverse?'checked':''}>
            <label for="rev_${id}" style="font-size:.72rem">Img stânga</label>
          </div>
        </div>
      </div>
      <div class="bf-row">
        <label class="bf-label">Titlu secțiune</label>
        <input class="bf-input" data-field="heading" value="${escAttr(d.heading||'')}">
      </div>
      <div class="bf-row">
        <label class="bf-label">Listă caracteristici</label>
        <div class="features-container" data-field="features">
          ${(d.features||[]).map(f => featureItemHtml(f)).join('')}
        </div>
        <button type="button" class="add-feature-btn" onclick="addFeature(this)">+ Adaugă caracteristică</button>
      </div>
      <div class="bf-row">
        <label class="bf-label">Imagine</label>
        ${imgPickerHtml(`img2_${id}`, d.image||'')}
      </div>
      <div class="row g-2 bf-row">
        <div class="col-6">
          <label class="bf-label">Text buton CTA</label>
          <input class="bf-input" data-field="cta_label" value="${escAttr(d.cta_label||'Cere ofertă')}">
        </div>
        <div class="col-6">
          <label class="bf-label">URL CTA</label>
          <input class="bf-input" data-field="cta_url" value="${escAttr(d.cta_url||'/contact.php')}">
        </div>
      </div>`;

    case 'text': return `
      <div class="bf-row">
        <label class="bf-label">Titlu (opțional)</label>
        <input class="bf-input" data-field="heading" value="${escAttr(d.heading||'')}">
      </div>
      <div class="bf-row">
        <label class="bf-label">Conținut</label>
        <textarea class="bf-input" data-field="content" rows="5">${escHtml(d.content||'')}</textarea>
      </div>`;

    case 'stats': return `
      <div class="bf-row">
        <label class="bf-label">Statistici</label>
        <div class="stats-container" data-field="stats">
          ${(d.stats||[]).map(s => statItemHtml(s.label||'', s.value||'')).join('')}
        </div>
        <button type="button" class="add-feature-btn" onclick="addStat(this)">+ Adaugă statistică</button>
      </div>`;

    case 'salon-row': return `
      <div class="row g-2 bf-row">
        <div class="col-3">
          <label class="bf-label">Index</label>
          <input class="bf-input" data-field="index" value="${escAttr(d.index||'')}" placeholder="01">
        </div>
        <div class="col-9">
          <label class="bf-label">Nume salon</label>
          <input class="bf-input" data-field="name" value="${escAttr(d.name||'')}">
        </div>
      </div>
      <div class="bf-row">
        <label class="bf-label">Descriere</label>
        <textarea class="bf-input" data-field="description" rows="3">${escHtml(d.description||'')}</textarea>
      </div>
      <div class="bf-row">
        <label class="bf-label">Imagine</label>
        ${imgPickerHtml(`img_s_${id}`, d.image||'')}
      </div>
      <div class="row g-2 bf-row">
        <div class="col-4">
          <label class="bf-label">Suprafață</label>
          <input class="bf-input" data-field="suprafata" value="${escAttr(d.suprafata||'')}" placeholder="45 m²">
        </div>
        <div class="col-4">
          <label class="bf-label">Capacitate</label>
          <input class="bf-input" data-field="capacitate" value="${escAttr(d.capacitate||'')}" placeholder="40 locuri">
        </div>
        <div class="col-4 d-flex align-items-end pb-1">
          <div class="toggle-wrap">
            <input type="checkbox" id="srev_${id}" data-field="reverse" ${d.reverse?'checked':''}>
            <label for="srev_${id}" style="font-size:.72rem">Img stânga</label>
          </div>
        </div>
      </div>`;

    default: return `<p class="text-muted small">Tip necunoscut: ${escHtml(block.type)}</p>`;
  }
}

function imgPickerHtml(uid, value) {
  const hasImg = value && value.trim();
  return `
    <div class="img-picker-preview" id="prev_${uid}">
      ${hasImg ? `<img src="${escAttr(value)}" alt="" onerror="this.parentNode.innerHTML='<div class=placeholder>Negăsit</div>'">` : '<div class="placeholder">🖼️<br>Nicio imagine</div>'}
    </div>
    <div class="img-picker-btns">
      <input id="url_${uid}" class="img-picker-url" type="text" value="${escAttr(value)}" placeholder="/assets/..."
        oninput="updateImgPreview('url_${uid}','prev_${uid}')">
      <button type="button" class="upload-btn" onclick="triggerUploadInline('${uid}')">↑ Upload</button>
    </div>
    <input type="file" id="file_${uid}" class="upload-input" accept="image/*">`;
}

function featureItemHtml(val) {
  return `<div class="feature-item">
    <input type="text" value="${escAttr(val)}" placeholder="Caracteristică...">
    <button type="button" class="rem-btn" onclick="this.closest('.feature-item').remove()">×</button>
  </div>`;
}

function statItemHtml(label, value) {
  return `<div class="stat-item">
    <input type="text" placeholder="Label" value="${escAttr(label)}">
    <input type="text" placeholder="Valoare" value="${escAttr(value)}">
    <button type="button" class="rem-btn" onclick="this.closest('.stat-item').remove()">×</button>
  </div>`;
}

function triggerUploadInline(uid) {
  const fi = document.getElementById('file_' + uid);
  fi.onchange = () => {
    if (!fi.files[0]) return;
    uploadFile(fi.files[0], path => {
      const urlEl = document.getElementById('url_' + uid);
      if (urlEl) { urlEl.value = path; updateImgPreview('url_' + uid, 'prev_' + uid); }
    });
  };
  fi.click();
}

// ── Render all blocks ─────────────────────────────────────────
function renderAllBlocks() {
  const container = document.getElementById('blocks-container');
  container.innerHTML = '';
  blocks.forEach(b => container.appendChild(createBlockEl(b)));
}

function createBlockEl(block) {
  const meta = BLOCK_META[block.type] || { label: block.type, badge: '', icon: '□' };
  const div = document.createElement('div');
  div.className = 'block-card';
  div.dataset.id = block.id;
  div.dataset.type = block.type;
  div.innerHTML = `
    <div class="block-header" onclick="toggleBlock(this.closest('.block-card'))">
      <span class="block-handle" title="Drag to reorder" onclick="event.stopPropagation()">⠿</span>
      <span class="block-badge ${meta.badge}">${meta.icon} ${meta.label}</span>
      <span class="block-summary">${escHtml(getBlockSummary(block))}</span>
      <div class="block-actions" onclick="event.stopPropagation()">
        <button type="button" class="block-btn" title="Mută sus" onclick="moveBlock('${block.id}', -1)">↑</button>
        <button type="button" class="block-btn" title="Mută jos" onclick="moveBlock('${block.id}', 1)">↓</button>
        <button type="button" class="block-btn del" title="Șterge bloc" onclick="removeBlock('${block.id}')">×</button>
      </div>
    </div>
    <div class="block-form">${buildBlockForm(block)}</div>`;
  return div;
}

// ── Block operations ──────────────────────────────────────────
function toggleBlock(card) {
  card.classList.toggle('is-open');
}

function addBlock(type) {
  const block = { id: 'b_' + Math.random().toString(36).substr(2, 8), type, data: {} };
  blocks.push(block);
  const container = document.getElementById('blocks-container');
  const el = createBlockEl(block);
  el.classList.add('is-open');
  container.appendChild(el);
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  document.getElementById('block-type-picker').classList.remove('is-open');
  document.getElementById('btn-add-block').style.borderColor = 'var(--border)';
  document.getElementById('btn-add-block').style.color = 'var(--muted)';
}

function removeBlock(id) {
  if (!confirm('Ștergi acest bloc?')) return;
  blocks = blocks.filter(b => b.id !== id);
  const el = document.querySelector(`.block-card[data-id="${id}"]`);
  if (el) el.remove();
}

function moveBlock(id, dir) {
  const idx = blocks.findIndex(b => b.id === id);
  if (idx < 0) return;
  const newIdx = idx + dir;
  if (newIdx < 0 || newIdx >= blocks.length) return;
  [blocks[idx], blocks[newIdx]] = [blocks[newIdx], blocks[idx]];
  renderAllBlocks();
}

function toggleBlockPicker() {
  document.getElementById('block-type-picker').classList.toggle('is-open');
}

function addFeature(btn) {
  btn.previousElementSibling.insertAdjacentHTML('beforeend', featureItemHtml(''));
}

function addStat(btn) {
  btn.previousElementSibling.insertAdjacentHTML('beforeend', statItemHtml('',''));
}

// ── Collect block data from DOM ───────────────────────────────
function collectBlocks() {
  const result = [];
  document.querySelectorAll('.block-card').forEach(card => {
    const id   = card.dataset.id;
    const type = card.dataset.type;
    const form = card.querySelector('.block-form');
    const data = {};

    // Text fields
    form.querySelectorAll('[data-field]').forEach(el => {
      const field = el.dataset.field;
      if (el.type === 'checkbox') {
        data[field] = el.checked;
      } else if (el.tagName === 'TEXTAREA' || el.tagName === 'INPUT') {
        data[field] = el.value;
      }
    });

    // Image URL from img picker
    form.querySelectorAll('.img-picker-url').forEach(el => {
      // determine field name from context
      const previewId = el.nextElementSibling ? null : null;
      // Just collect the value; match to "image" field
      if (!data.image) data.image = el.value;
    });

    // Features
    const featContainer = form.querySelector('.features-container');
    if (featContainer) {
      data.features = Array.from(featContainer.querySelectorAll('input[type=text]'))
        .map(i => i.value.trim()).filter(Boolean);
    }

    // Stats
    const statsContainer = form.querySelector('.stats-container');
    if (statsContainer) {
      data.stats = Array.from(statsContainer.querySelectorAll('.stat-item')).map(row => {
        const inputs = row.querySelectorAll('input');
        return { label: inputs[0]?.value || '', value: inputs[1]?.value || '' };
      });
    }

    result.push({ id, type, data });
  });
  return result;
}

// ── Collect extra (type-specific sidebar fields) ──────────────
function collectExtra() {
  const extra = {};
  document.querySelectorAll('[id^="extra-"]').forEach(el => {
    const key = el.id.replace('extra-', '');
    extra[key] = el.value;
  });
  return extra;
}

// ── Save ─────────────────────────────────────────────────────
function savePage() {
  const btn = document.getElementById('btn-save');
  btn.classList.add('saving');
  btn.textContent = 'Se salvează...';

  const collected = collectBlocks();
  const extra     = collectExtra();

  const fd = new FormData();
  fd.append('_csrf',     CSRF);
  fd.append('id',        ITEM_ID);
  fd.append('type',      ITEM_TYPE);
  fd.append('slug',      document.getElementById('meta-slug').value);
  fd.append('title',     document.getElementById('meta-title').value);
  fd.append('meta_desc', document.getElementById('meta-desc').value);
  fd.append('og_image',  document.getElementById('og-img-url').value);
  fd.append('json_ld',   document.getElementById('meta-jsonld').value);
  fd.append('blocks',    JSON.stringify(collected));
  fd.append('extra',     JSON.stringify(extra));

  fetch('page-save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      btn.classList.remove('saving');
      btn.textContent = 'Salvează';
      if (data.ok) {
        showToast('✓ ' + (data.msg || 'Salvat!'));
        // If new item, redirect to edit URL
        if (ITEM_ID === 0 && data.id) {
          setTimeout(() => {
            window.location.href = `page-edit.php?type=${ITEM_TYPE}&id=${data.id}`;
          }, 800);
        }
      } else {
        showToast('⚠ ' + (data.error || 'Eroare'), true);
      }
    })
    .catch(() => {
      btn.classList.remove('saving');
      btn.textContent = 'Salvează';
      showToast('Eroare de rețea', true);
    });
}

// ── Delete ───────────────────────────────────────────────────
function initDelete() {
  const btnDel = document.getElementById('btn-delete');
  if (!btnDel) return;
  btnDel.addEventListener('click', () => {
    document.getElementById('del-overlay').classList.add('show');
  });
  document.getElementById('btn-delete-confirm').addEventListener('click', () => {
    const fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', 'delete');
    fd.append('id', ITEM_ID);
    fetch(`pages-list.php?type=${ITEM_TYPE}`, { method: 'POST', body: fd })
      .then(() => { window.location.href = `pages-list.php?type=${ITEM_TYPE}`; })
      .catch(() => showToast('Eroare la ștergere', true));
  });
}

// ── Toast ─────────────────────────────────────────────────────
let toastTimer = null;
function showToast(msg, isErr = false, duration = 3000) {
  const container = document.getElementById('toast-container');
  container.innerHTML = '';
  const el = document.createElement('div');
  el.className = 'toast-msg' + (isErr ? ' err' : '');
  el.textContent = msg;
  container.appendChild(el);
  requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
  if (toastTimer) clearTimeout(toastTimer);
  toastTimer = setTimeout(() => { el.classList.remove('show'); setTimeout(() => container.innerHTML = '', 300); }, duration);
}

// ── Escape helpers ────────────────────────────────────────────
function escHtml(s) {
  return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function escAttr(s) { return escHtml(s); }

// ── Keyboard save ─────────────────────────────────────────────
document.addEventListener('keydown', e => {
  if ((e.ctrlKey || e.metaKey) && e.key === 's') {
    e.preventDefault();
    savePage();
  }
});

// ── Slug auto-generate from title ────────────────────────────
document.getElementById('meta-title').addEventListener('blur', function() {
  const slugInput = document.getElementById('meta-slug');
  if (slugInput.value === '' && this.value) {
    const slug = this.value
      .toLowerCase()
      .normalize('NFD').replace(/[̀-ͯ]/g, '')
      .replace(/ș/g,'s').replace(/ț/g,'t').replace(/ă/g,'a').replace(/â/g,'a').replace(/î/g,'i')
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-|-$/g, '');
    slugInput.value = slug;
    document.getElementById('slug-preview').textContent = slug;
  }
});
</script>
</body>
</html>
