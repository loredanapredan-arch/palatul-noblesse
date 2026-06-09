<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/partials.php';

// ── Auto-create home_settings table if needed ─────────────────
try {
    db()->exec("CREATE TABLE IF NOT EXISTS home_settings (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        section    VARCHAR(60) NOT NULL UNIQUE,
        data       JSON NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
} catch (\PDOException $e) { /* ignore */ }

// ── Load all sections ─────────────────────────────────────────
$raw = [];
try {
    $rows = db()->query("SELECT section, data FROM home_settings")->fetchAll();
    foreach ($rows as $row) {
        $raw[$row['section']] = json_decode($row['data'], true) ?: [];
    }
} catch (\PDOException $e) { $raw = []; }

// Helper: get value from section array, html-escaped
function hs(array $s, string $key, string $def = ''): string {
    return htmlspecialchars($s[$key] ?? $def, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$seo       = $raw['seo']       ?? [];
$hero      = $raw['hero']      ?? [];
$story     = $raw['story']     ?? [];
$ph        = $raw['portfolio_heading'] ?? [];
$sh        = $raw['services_heading']  ?? [];
$slh       = $raw['saloane_heading']   ?? [];
$why       = $raw['why_items'] ?? [];
$gradina   = $raw['bleed_gradina']     ?? [];
$bib       = $raw['biblioteka']        ?? [];
$test      = $raw['testimonials']      ?? [];

$why_items = $why['items'] ?? [
    ['icon'=>'location','h3'=>'Locație ultracentrală',   'text'=>'Centrul istoric al Bucureștiului, la 5 minute de Universitate.'],
    ['icon'=>'monument','h3'=>'Monument istoric 1881',   'text'=>'Bijuterie arhitecturală în stil eclectic, prima de acest fel.'],
    ['icon'=>'star',    'h3'=>'Exclusivitate totală',    'text'=>'Închiriere exclusivă a întregii clădiri și grădinii.'],
    ['icon'=>'tree',    'h3'=>'Grădină boemă 600 mp',    'text'=>'O oază de verdeață pariziană pentru evenimente în aer liber.'],
    ['icon'=>'building','h3'=>'Apartament Royal',        'text'=>'60 mp de lux la etaj pentru pregătirile mirilor sau cazare.'],
    ['icon'=>'medal',   'h3'=>'Experiență din 2014',     'text'=>'Sute de evenimente reușite, echipă profesionistă dedicată.'],
];

$test_items = $test['items'] ?? [
    ['quote'=>'Locația de vis, profesionalismul echipei dedicate au transformat botezul fiicei noastre într-un basm.','author'=>'Mihaela Stanciu'],
    ['quote'=>'Palatul Noblesse a fost spațiul care ne-a susținut nunta de poveste. O simfonie de emoții resimțită de noi și de invitați.','author'=>'Luca Eugen'],
    ['quote'=>'O bijuterie de palat, gazde extraordinare, evenimentul a fost un SUCCES. Toată lumea a spus asta.','author'=>'Dorina Niga'],
];

$csrf = csrf_token();

$ICONS = [
    'location' => 'Pin locație',   'monument' => 'Monument',
    'star'     => 'Stea',          'tree'     => 'Copac/Grădină',
    'building' => 'Clădire',       'medal'    => 'Medalie/Award',
    'users'    => 'Persoane',      'heart'    => 'Inimă',
    'camera'   => 'Cameră foto',   'music'    => 'Muzică',
    'ring'     => 'Inel',          'flower'   => 'Floare',
];
?>
<!doctype html>
<html lang="ro">
<head>
<?php admin_head('Editare Homepage') ?>
<?php admin_css() ?>
<style>
body { overflow-y: auto }
.page-header { padding: 1.25rem 0 1rem; border-bottom: 1px solid var(--border); margin-bottom: 1.5rem }
/* Accordion */
.acc-card { background: #fff; border: 1.5px solid var(--border); border-radius: 12px; margin-bottom: .75rem; overflow: hidden }
.acc-header {
  display: flex; align-items: center; gap: .75rem;
  padding: 1rem 1.25rem; cursor: pointer; user-select: none;
  transition: background .15s;
}
.acc-header:hover { background: #faf8f5 }
.acc-icon { width: 34px; height: 34px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 1rem; flex-shrink: 0 }
.acc-title { flex: 1; font-weight: 600; font-size: .88rem }
.acc-sub { font-size: .75rem; color: var(--muted); margin-top: 1px }
.acc-chev { color: var(--muted); transition: transform .2s; font-size: .75rem }
.acc-card.open .acc-chev { transform: rotate(180deg) }
.acc-body { display: none; padding: 1.25rem; border-top: 1px solid var(--border); background: #fdfcfa }
.acc-card.open .acc-body { display: block }
/* Form */
.fg { margin-bottom: 1rem }
.fg:last-child { margin-bottom: 0 }
.fl { display: block; font-size: .72rem; font-weight: 600; text-transform: uppercase; letter-spacing: .12em; color: var(--muted); margin-bottom: .35rem }
.fl .hint { font-weight: 400; text-transform: none; letter-spacing: 0; color: #bbb; margin-left: .3rem; font-size: .68rem }
.fi { width: 100%; padding: .55rem .75rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .84rem; font-family: 'Inter', sans-serif; color: var(--dark); background: #faf8f5; outline: none; resize: vertical; transition: border-color .2s }
.fi:focus { border-color: var(--burg); background: #fff }
.fi.mono { font-family: monospace; font-size: .75rem }
.cc { font-size: .68rem; color: var(--muted); text-align: right; margin-top: .2rem }
.cc.warn { color: #ef4444 }
/* Image picker */
.ip-wrap { display: flex; gap: .5rem; align-items: center; margin-top: .4rem }
.ip-preview { width: 64px; height: 48px; border-radius: 6px; object-fit: cover; background: #e9e4dd; border: 1px solid var(--border); flex-shrink: 0 }
.ip-preview-placeholder { width: 64px; height: 48px; border-radius: 6px; background: #e9e4dd; border: 1px solid var(--border); flex-shrink: 0; display: flex; align-items: center; justify-content: center; font-size: 1.2rem }
.ip-input { flex: 1; padding: .45rem .65rem; border: 1.5px solid var(--border); border-radius: 6px; font-size: .78rem; background: #faf8f5; outline: none; color: var(--dark) }
.ip-input:focus { border-color: var(--burg) }
.ip-btn { background: none; border: 1.5px solid var(--border); border-radius: 6px; padding: .45rem .65rem; font-size: .75rem; cursor: pointer; color: var(--muted); white-space: nowrap; transition: all .15s }
.ip-btn:hover { border-color: var(--dark); color: var(--dark) }
/* Items (why/testimonials) */
.item-card { background: #fff; border: 1px solid var(--border); border-radius: 8px; padding: .85rem; margin-bottom: .6rem }
.item-card:last-child { margin-bottom: 0 }
.item-header { display: flex; align-items: center; justify-content: space-between; margin-bottom: .75rem }
.item-num { font-size: 10px; letter-spacing: .2em; text-transform: uppercase; color: var(--muted); font-weight: 600 }
.rem-item-btn { background: none; border: none; color: #ccc; cursor: pointer; font-size: 1rem; padding: 2px 6px; border-radius: 4px }
.rem-item-btn:hover { background: #fef2f2; color: #ef4444 }
.add-item-btn { width: 100%; padding: .55rem; background: none; border: 1.5px dashed var(--border); border-radius: 8px; font-size: .8rem; color: var(--muted); cursor: pointer; font-family: 'Inter', sans-serif; transition: all .15s; margin-top: .5rem }
.add-item-btn:hover { border-color: var(--dark); color: var(--dark) }
/* Toast */
.toast-container { position: fixed; bottom: 1.5rem; right: 1.5rem; z-index: 9999 }
.toast-msg { background: var(--dark); color: #fff; padding: .65rem 1.25rem; border-radius: 8px; font-size: .83rem; margin-top: .4rem; opacity: 0; transform: translateY(8px); transition: all .25s; box-shadow: 0 8px 24px rgba(0,0,0,.25); min-width: 200px }
.toast-msg.show { opacity: 1; transform: translateY(0) }
.toast-msg.err { background: var(--burg) }
.note-box { background: #fef3c7; border: 1px solid #fde68a; border-radius: 8px; padding: .75rem 1rem; font-size: .8rem; color: #78350f; margin-bottom: 1rem }
</style>
</head>
<body>
<?php admin_nav('home') ?>

<div class="container-xl py-4 px-4">

  <div class="page-header d-flex align-items-center justify-content-between flex-wrap gap-3">
    <div>
      <div class="eyebrow mb-1">Conținut</div>
      <h1 class="h3 mb-0">Editare Homepage</h1>
    </div>
    <div class="d-flex gap-2 align-items-center">
      <a href="/" target="_blank" class="btn btn-sm btn-outline-secondary">↗ Vizualizează</a>
      <button type="button" id="btn-save" class="btn-burg btn">Salvează tot</button>
    </div>
  </div>

  <!-- ── SEO & Meta ──────────────────────────────────────── -->
  <div class="acc-card open">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#f3f4f6">🔍</div>
      <div><div class="acc-title">SEO &amp; Meta</div><div class="acc-sub">Titlu, meta descriere, JSON-LD</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Titlu pagină <span class="hint">max 70 caractere</span></label>
          <input class="fi" id="seo_title" maxlength="100"
            value="<?= hs($seo,'title','Palatul Noblesse — Centru de evenimente și artă, București') ?>"
            oninput="cc(this,'seo_title_cc',70)">
          <div class="cc" id="seo_title_cc"></div>
        </div>
        <div class="col-12">
          <label class="fl">Meta descriere <span class="hint">max 160 caractere</span></label>
          <textarea class="fi" id="seo_meta_desc" rows="3" maxlength="320"
            oninput="cc(this,'seo_meta_desc_cc',160)"><?= hs($seo,'meta_desc','Palatul Noblesse — monument istoric din 1881, locație premium pentru nunți, botezuri, evenimente corporate, petreceri private și shooting-uri în centrul Bucureștiului.') ?></textarea>
          <div class="cc" id="seo_meta_desc_cc"></div>
        </div>
        <div class="col-12">
          <label class="fl">JSON-LD <span class="hint">opțional</span></label>
          <textarea class="fi mono" id="seo_json_ld" rows="5" placeholder='{"@context":"https://schema.org",...}'><?= hs($seo,'json_ld','') ?></textarea>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Hero ────────────────────────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#ede9fe">🦸</div>
      <div><div class="acc-title">Hero</div><div class="acc-sub">Imagine principală, titlu, subtitlu, CTA</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Imagine hero</label>
          <?php imgPicker('hero_image', $hero['image'] ?? '/assets/hero-palace.jpg') ?>
        </div>
        <div class="col-12">
          <label class="fl">Eyebrow text</label>
          <input class="fi" id="hero_eyebrow" value="<?= hs($hero,'eyebrow','Centru de evenimente și artă · București') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Titlu H1</label>
          <input class="fi" id="hero_h1" value="<?= hs($hero,'h1','Palatul Noblesse') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Tagline</label>
          <input class="fi" id="hero_tagline" value="<?= hs($hero,'tagline','Special moments that live forever') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Subtitlu</label>
          <textarea class="fi" id="hero_sub" rows="2"><?= hs($hero,'sub','Monument istoric din 1881 din inima Bucureștiului — restaurat cu pasiune pentru a găzdui cele mai elegante povești.') ?></textarea>
        </div>
        <div class="col-6">
          <label class="fl">Buton 1 — text</label>
          <input class="fi" id="hero_cta1_label" value="<?= hs($hero,'cta1_label','Cere ofertă') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Buton 1 — URL</label>
          <input class="fi" id="hero_cta1_url" value="<?= hs($hero,'cta1_url','/contact.php') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Buton 2 — text</label>
          <input class="fi" id="hero_cta2_label" value="<?= hs($hero,'cta2_label','Descoperă palatul') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Buton 2 — URL</label>
          <input class="fi" id="hero_cta2_url" value="<?= hs($hero,'cta2_url','/saloane.php') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Povestea noastră ─────────────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#dcfce7">📖</div>
      <div><div class="acc-title">Povestea noastră</div><div class="acc-sub">Secțiunea cu imaginea și textul despre palat</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Imagine</label>
          <?php imgPicker('story_image', $story['image'] ?? '/assets/salon-baroque.jpg') ?>
        </div>
        <div class="col-4">
          <label class="fl">Număr badge <span class="hint">ex: 140+</span></label>
          <input class="fi" id="story_badge_num" value="<?= hs($story,'badge_num','140+') ?>">
        </div>
        <div class="col-8">
          <label class="fl">Text badge</label>
          <input class="fi" id="story_badge_lbl" value="<?= hs($story,'badge_lbl','ani de poveste') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Eyebrow</label>
          <input class="fi" id="story_eyebrow" value="<?= hs($story,'eyebrow','Povestea noastră') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Titlu H2</label>
          <input class="fi" id="story_h2" value="<?= hs($story,'h2','O bijuterie arhitecturală de peste 140 de ani') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Paragraf 1</label>
          <textarea class="fi" id="story_text1" rows="3"><?= hs($story,'text1','Palatul Noblesse este o clădire monument istoric datând din 1881, complet restaurată și redată patrimoniului cultural bucureștean. O oază de liniște aflată în mijlocul unei grădini boeme.') ?></textarea>
        </div>
        <div class="col-12">
          <label class="fl">Paragraf 2</label>
          <textarea class="fi" id="story_text2" rows="3"><?= hs($story,'text2','Prima clădire pe stil eclectic din centrul istoric al Bucureștiului, proiectată de arhitectul Alexandru Săvulescu. Astăzi, un centru de evenimente, design și artă unic în Europa.') ?></textarea>
        </div>
        <div class="col-6">
          <label class="fl">Text link CTA</label>
          <input class="fi" id="story_cta_label" value="<?= hs($story,'cta_label','Află mai mult') ?>">
        </div>
        <div class="col-6">
          <label class="fl">URL CTA</label>
          <input class="fi" id="story_cta_url" value="<?= hs($story,'cta_url','/despre.php') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Headings pentru grilele dinamice ────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#fef3c7">📋</div>
      <div><div class="acc-title">Titluri secțiuni (Portofoliu / Servicii / Saloane)</div><div class="acc-sub">Cardurile se încarcă automat din baza de date</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="note-box">💡 Cardurile din aceste secțiuni se generează automat din elementele active din <a href="pages-list.php?type=portofoliu">Portofoliu</a>, <a href="pages-list.php?type=servicii">Servicii</a> și <a href="pages-list.php?type=saloane">Saloane</a>. Aici editezi doar titlurile secțiunilor.</div>
      <div class="row g-4">
        <div class="col-md-4">
          <div class="eyebrow mb-2">Portofoliu</div>
          <div class="fg"><label class="fl">Eyebrow</label><input class="fi" id="ph_eyebrow" value="<?= hs($ph,'eyebrow','Portofoliu') ?>"></div>
          <div class="fg"><label class="fl">Titlu H2</label><input class="fi" id="ph_h2" value="<?= hs($ph,'h2','Evenimente desprinse din povești') ?>"></div>
          <div class="fg"><label class="fl">Descriere</label><textarea class="fi" id="ph_text" rows="2"><?= hs($ph,'text','Fiecare eveniment la Palatul Noblesse este unic, croit cu grijă pentru a deveni o amintire de neuitat.') ?></textarea></div>
        </div>
        <div class="col-md-4">
          <div class="eyebrow mb-2">Servicii</div>
          <div class="fg"><label class="fl">Eyebrow</label><input class="fi" id="sh_eyebrow" value="<?= hs($sh,'eyebrow','Servicii dedicate') ?>"></div>
          <div class="fg"><label class="fl">Titlu H2</label><input class="fi" id="sh_h2" value="<?= hs($sh,'h2','Serviciile noastre') ?>"></div>
          <div class="fg"><label class="fl">Descriere</label><textarea class="fi" id="sh_text" rows="2"><?= hs($sh,'text','De la bucătărie signature la decor, mixologie și divertisment — totul gândit pentru un eveniment fără cusur.') ?></textarea></div>
        </div>
        <div class="col-md-4">
          <div class="eyebrow mb-2">Saloane</div>
          <div class="fg"><label class="fl">Eyebrow</label><input class="fi" id="slh_eyebrow" value="<?= hs($slh,'eyebrow','Spațiile palatului') ?>"></div>
          <div class="fg"><label class="fl">Titlu H2</label><input class="fi" id="slh_h2" value="<?= hs($slh,'h2','Saloanele parterului') ?>"></div>
          <div class="fg"><label class="fl">Descriere</label><textarea class="fi" id="slh_text" rows="2"><?= hs($slh,'text','Cinci interioare de excepție, fiecare cu propria personalitate, conectate într-un tur fluid al palatului.') ?></textarea></div>
        </div>
      </div>
    </div>
  </div>

  <!-- ── De ce Noblesse ─────────────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#dbeafe">⭐</div>
      <div><div class="acc-title">De ce Noblesse</div><div class="acc-sub">6 motive cu icoane</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="fl">Eyebrow</label>
          <input class="fi" id="why_eyebrow" value="<?= hs($why,'eyebrow','De ce Palatul Noblesse') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Titlu H2</label>
          <input class="fi" id="why_h2" value="<?= hs($why,'h2','Locul în care eleganța întâlnește istoria') ?>">
        </div>
      </div>
      <div id="why-items-container">
        <?php foreach ($why_items as $i => $wi): ?>
        <div class="item-card why-item-card">
          <div class="item-header">
            <span class="item-num">Motiv <?= $i+1 ?></span>
            <button type="button" class="rem-item-btn" onclick="this.closest('.why-item-card').remove()">✕</button>
          </div>
          <div class="row g-2">
            <div class="col-md-3">
              <label class="fl">Iconiță</label>
              <select class="fi why-icon">
                <?php foreach ($ICONS as $key => $label): ?>
                <option value="<?= $key ?>" <?= ($wi['icon']??'') === $key ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach ?>
              </select>
            </div>
            <div class="col-md-4">
              <label class="fl">Titlu</label>
              <input class="fi why-h3" type="text" value="<?= htmlspecialchars($wi['h3']??'', ENT_QUOTES) ?>">
            </div>
            <div class="col-md-5">
              <label class="fl">Text</label>
              <input class="fi why-text" type="text" value="<?= htmlspecialchars($wi['text']??'', ENT_QUOTES) ?>">
            </div>
          </div>
        </div>
        <?php endforeach ?>
      </div>
      <button type="button" class="add-item-btn" onclick="addWhyItem()">+ Adaugă motiv</button>
    </div>
  </div>

  <!-- ── Grădina boemă (bleed) ─────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#dcfce7">🌿</div>
      <div><div class="acc-title">Grădina boemă</div><div class="acc-sub">Secțiunea fullbleed cu grădina</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3">
        <div class="col-12">
          <label class="fl">Imagine fundal</label>
          <?php imgPicker('gradina_image', $gradina['image'] ?? '/assets/gradina.jpg') ?>
        </div>
        <div class="col-6">
          <label class="fl">Eyebrow</label>
          <input class="fi" id="gradina_eyebrow" value="<?= hs($gradina,'eyebrow','600 mp · stil parizian') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Titlu H2</label>
          <input class="fi" id="gradina_h2" value="<?= hs($gradina,'h2','Grădina boemă a palatului') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Text</label>
          <textarea class="fi" id="gradina_text" rows="2"><?= hs($gradina,'text','O oază de verdeață, lumini și liniște ascunsă în mijlocul Bucureștiului — locul ideal pentru ceremonii, cocktail-uri și mese în aer liber.') ?></textarea>
        </div>
        <div class="col-6">
          <label class="fl">Text CTA</label>
          <input class="fi" id="gradina_cta_label" value="<?= hs($gradina,'cta_label','Vezi toate spațiile') ?>">
        </div>
        <div class="col-6">
          <label class="fl">URL CTA</label>
          <input class="fi" id="gradina_cta_url" value="<?= hs($gradina,'cta_url','/saloane.php') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Biblioteka Hub ─────────────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#fce7f3">📚</div>
      <div><div class="acc-title">Biblioteka Hub</div><div class="acc-sub">Intro text + secțiunea fullbleed</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="note-box mb-3">Secțiunea intro (fundal crem) + secțiunea fullbleed cu imaginea.</div>
      <div class="row g-3">
        <div class="col-12"><div class="eyebrow mb-2">Intro (text centrat)</div></div>
        <div class="col-4">
          <label class="fl">Eyebrow intro</label>
          <input class="fi" id="bib_intro_eyebrow" value="<?= hs($bib,'intro_eyebrow','Un nou capitol') ?>">
        </div>
        <div class="col-8">
          <label class="fl">Titlu intro H2</label>
          <input class="fi" id="bib_intro_h2" value="<?= hs($bib,'intro_h2','Spațiile dedicate ideilor și conversațiilor') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Text intro</label>
          <textarea class="fi" id="bib_intro_text" rows="2"><?= hs($bib,'intro_text','Dincolo de saloanele festive, palatul găzduiește locuri intime gândite pentru cultură, întâlniri creative și momente de inspirație.') ?></textarea>
        </div>

        <div class="col-12 mt-2"><div class="eyebrow mb-2">Fullbleed (cu imagine)</div></div>
        <div class="col-12">
          <label class="fl">Imagine fundal</label>
          <?php imgPicker('bib_image', $bib['image'] ?? '/assets/biblioteka.jpg') ?>
        </div>
        <div class="col-6">
          <label class="fl">Eyebrow</label>
          <input class="fi" id="bib_eyebrow" value="<?= hs($bib,'eyebrow','Cultură · Conversație · Comunitate') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Titlu H2</label>
          <input class="fi" id="bib_h2" value="<?= hs($bib,'h2','Biblioteka Hub') ?>">
        </div>
        <div class="col-12">
          <label class="fl">Text</label>
          <textarea class="fi" id="bib_text" rows="3"><?= hs($bib,'text','Un spațiu intim între rafturi de cărți și fotolii de piele — locul perfect pentru lansări, lecturi, dezbateri și întâlniri rafinate în atmosfera caldă a palatului.') ?></textarea>
        </div>
        <div class="col-6">
          <label class="fl">Text CTA</label>
          <input class="fi" id="bib_cta_label" value="<?= hs($bib,'cta_label','Descoperă spațiul') ?>">
        </div>
        <div class="col-6">
          <label class="fl">URL CTA</label>
          <input class="fi" id="bib_cta_url" value="<?= hs($bib,'cta_url','/saloane.php') ?>">
        </div>
      </div>
    </div>
  </div>

  <!-- ── Testimoniale ─────────────────────────────────────── -->
  <div class="acc-card">
    <div class="acc-header" onclick="this.closest('.acc-card').classList.toggle('open')">
      <div class="acc-icon" style="background:#fef3c7">💬</div>
      <div><div class="acc-title">Testimoniale</div><div class="acc-sub">Recenzii și citate de la clienți</div></div>
      <span class="acc-chev">▾</span>
    </div>
    <div class="acc-body">
      <div class="row g-3 mb-3">
        <div class="col-6">
          <label class="fl">Eyebrow</label>
          <input class="fi" id="test_eyebrow" value="<?= hs($test,'eyebrow','Recenzii') ?>">
        </div>
        <div class="col-6">
          <label class="fl">Titlu H2</label>
          <input class="fi" id="test_h2" value="<?= hs($test,'h2','Povești spuse de oaspeții noștri') ?>">
        </div>
      </div>
      <div id="test-items-container">
        <?php foreach ($test_items as $i => $ti): ?>
        <div class="item-card test-item-card">
          <div class="item-header">
            <span class="item-num">Testimonial <?= $i+1 ?></span>
            <button type="button" class="rem-item-btn" onclick="this.closest('.test-item-card').remove()">✕</button>
          </div>
          <div class="row g-2">
            <div class="col-md-9">
              <label class="fl">Citat</label>
              <textarea class="fi test-quote" rows="2"><?= htmlspecialchars($ti['quote']??'', ENT_QUOTES) ?></textarea>
            </div>
            <div class="col-md-3">
              <label class="fl">Autor</label>
              <input class="fi test-author" type="text" value="<?= htmlspecialchars($ti['author']??'', ENT_QUOTES) ?>">
            </div>
          </div>
        </div>
        <?php endforeach ?>
      </div>
      <button type="button" class="add-item-btn" onclick="addTestItem()">+ Adaugă testimonial</button>
    </div>
  </div>

  <div style="height:2rem"></div>
</div>

<!-- Toast -->
<div class="toast-container" id="toast-container"></div>

<!-- Hidden file inputs for image uploads -->
<input type="file" id="__file_upload" class="d-none" accept="image/*">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const CSRF = <?= json_encode($csrf) ?>;

// ── Char counter ──────────────────────────────────────────────
function cc(el, cid, max) {
  const n = el.value.length;
  const cel = document.getElementById(cid);
  if (cel) { cel.textContent = n + ' / ' + max; cel.className = 'cc' + (n > max ? ' warn' : ''); }
}
document.addEventListener('DOMContentLoaded', () => {
  cc(document.getElementById('seo_title'), 'seo_title_cc', 70);
  cc(document.getElementById('seo_meta_desc'), 'seo_meta_desc_cc', 160);
});

// ── Image picker inline ───────────────────────────────────────
function updateIpPreview(uid) {
  const url = document.getElementById('ipurl_' + uid).value.trim();
  const prev = document.getElementById('ipprev_' + uid);
  if (!prev) return;
  if (url) { prev.innerHTML = `<img src="${url}" class="ip-preview" onerror="this.outerHTML='<div class=ip-preview-placeholder>🖼</div>'">`; }
  else      { prev.innerHTML = '<div class="ip-preview-placeholder">🖼</div>'; }
}
function triggerIpUpload(uid) {
  const fi = document.getElementById('__file_upload');
  fi.onchange = () => {
    if (!fi.files[0]) return;
    const fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('file', fi.files[0]);
    showToast('Se încarcă...', false, 60000);
    fetch('media-upload.php', { method: 'POST', body: fd })
      .then(r => r.json())
      .then(d => {
        if (d.ok) {
          document.getElementById('ipurl_' + uid).value = d.path;
          updateIpPreview(uid);
          showToast('✓ Imagine încărcată!');
        } else showToast(d.error || 'Eroare', true);
      }).catch(() => showToast('Eroare rețea', true));
  };
  fi.value = '';
  fi.click();
}

// ── Add why item ──────────────────────────────────────────────
const ICON_OPTIONS = <?= json_encode(array_keys($ICONS)) ?>;
const ICON_LABELS  = <?= json_encode($ICONS) ?>;
let whyCount = <?= count($why_items) ?>;
function addWhyItem() {
  whyCount++;
  const options = ICON_OPTIONS.map(k => `<option value="${k}">${ICON_LABELS[k]}</option>`).join('');
  const html = `<div class="item-card why-item-card">
    <div class="item-header">
      <span class="item-num">Motiv ${whyCount}</span>
      <button type="button" class="rem-item-btn" onclick="this.closest('.why-item-card').remove()">✕</button>
    </div>
    <div class="row g-2">
      <div class="col-md-3"><label class="fl">Iconiță</label><select class="fi why-icon">${options}</select></div>
      <div class="col-md-4"><label class="fl">Titlu</label><input class="fi why-h3" type="text"></div>
      <div class="col-md-5"><label class="fl">Text</label><input class="fi why-text" type="text"></div>
    </div>
  </div>`;
  document.getElementById('why-items-container').insertAdjacentHTML('beforeend', html);
}

// ── Add testimonial ───────────────────────────────────────────
let testCount = <?= count($test_items) ?>;
function addTestItem() {
  testCount++;
  const html = `<div class="item-card test-item-card">
    <div class="item-header">
      <span class="item-num">Testimonial ${testCount}</span>
      <button type="button" class="rem-item-btn" onclick="this.closest('.test-item-card').remove()">✕</button>
    </div>
    <div class="row g-2">
      <div class="col-md-9"><label class="fl">Citat</label><textarea class="fi test-quote" rows="2"></textarea></div>
      <div class="col-md-3"><label class="fl">Autor</label><input class="fi test-author" type="text"></div>
    </div>
  </div>`;
  document.getElementById('test-items-container').insertAdjacentHTML('beforeend', html);
}

// ── Collect & save ────────────────────────────────────────────
function v(id, def='') { const el=document.getElementById(id); return el ? el.value : def; }

function collectData() {
  // Why items
  const whyItems = [];
  document.querySelectorAll('.why-item-card').forEach(card => {
    whyItems.push({
      icon: card.querySelector('.why-icon')?.value || '',
      h3:   card.querySelector('.why-h3')?.value   || '',
      text: card.querySelector('.why-text')?.value  || '',
    });
  });

  // Testimonials
  const testItems = [];
  document.querySelectorAll('.test-item-card').forEach(card => {
    testItems.push({
      quote:  card.querySelector('.test-quote')?.value  || '',
      author: card.querySelector('.test-author')?.value || '',
    });
  });

  return {
    seo: {
      title:     v('seo_title'),
      meta_desc: v('seo_meta_desc'),
      json_ld:   v('seo_json_ld'),
    },
    hero: {
      image:      v('ipurl_hero_image'),
      eyebrow:    v('hero_eyebrow'),
      h1:         v('hero_h1'),
      tagline:    v('hero_tagline'),
      sub:        v('hero_sub'),
      cta1_label: v('hero_cta1_label'),
      cta1_url:   v('hero_cta1_url'),
      cta2_label: v('hero_cta2_label'),
      cta2_url:   v('hero_cta2_url'),
    },
    story: {
      image:     v('ipurl_story_image'),
      badge_num: v('story_badge_num'),
      badge_lbl: v('story_badge_lbl'),
      eyebrow:   v('story_eyebrow'),
      h2:        v('story_h2'),
      text1:     v('story_text1'),
      text2:     v('story_text2'),
      cta_label: v('story_cta_label'),
      cta_url:   v('story_cta_url'),
    },
    portfolio_heading: { eyebrow: v('ph_eyebrow'), h2: v('ph_h2'), text: v('ph_text') },
    services_heading:  { eyebrow: v('sh_eyebrow'), h2: v('sh_h2'), text: v('sh_text') },
    saloane_heading:   { eyebrow: v('slh_eyebrow'), h2: v('slh_h2'), text: v('slh_text') },
    why_items: {
      eyebrow: v('why_eyebrow'),
      h2:      v('why_h2'),
      items:   whyItems,
    },
    bleed_gradina: {
      image:     v('ipurl_gradina_image'),
      eyebrow:   v('gradina_eyebrow'),
      h2:        v('gradina_h2'),
      text:      v('gradina_text'),
      cta_label: v('gradina_cta_label'),
      cta_url:   v('gradina_cta_url'),
    },
    biblioteka: {
      intro_eyebrow: v('bib_intro_eyebrow'),
      intro_h2:      v('bib_intro_h2'),
      intro_text:    v('bib_intro_text'),
      image:         v('ipurl_bib_image'),
      eyebrow:       v('bib_eyebrow'),
      h2:            v('bib_h2'),
      text:          v('bib_text'),
      cta_label:     v('bib_cta_label'),
      cta_url:       v('bib_cta_url'),
    },
    testimonials: {
      eyebrow: v('test_eyebrow'),
      h2:      v('test_h2'),
      items:   testItems,
    },
  };
}

document.getElementById('btn-save').addEventListener('click', () => {
  const btn = document.getElementById('btn-save');
  btn.textContent = 'Se salvează...';
  btn.disabled = true;

  const fd = new FormData();
  fd.append('_csrf', CSRF);
  fd.append('data', JSON.stringify(collectData()));

  fetch('home-save.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(d => {
      btn.textContent = 'Salvează tot';
      btn.disabled = false;
      if (d.ok) showToast('✓ Homepage salvat cu succes!');
      else showToast('⚠ ' + (d.error || 'Eroare'), true);
    })
    .catch(() => { btn.textContent = 'Salvează tot'; btn.disabled = false; showToast('Eroare de rețea', true); });
});

// Ctrl+S
document.addEventListener('keydown', e => { if ((e.ctrlKey||e.metaKey) && e.key==='s') { e.preventDefault(); document.getElementById('btn-save').click(); } });

// Toast
let _tt = null;
function showToast(msg, isErr=false, dur=3000) {
  const c = document.getElementById('toast-container');
  c.innerHTML = '';
  const el = document.createElement('div');
  el.className = 'toast-msg' + (isErr?' err':'');
  el.textContent = msg;
  c.appendChild(el);
  requestAnimationFrame(() => requestAnimationFrame(() => el.classList.add('show')));
  if (_tt) clearTimeout(_tt);
  _tt = setTimeout(() => { el.classList.remove('show'); setTimeout(()=>c.innerHTML='',300); }, dur);
}
</script>
</body>
</html>
<?php
function imgPicker(string $uid, string $value): void {
    $esc = htmlspecialchars($value, ENT_QUOTES);
    $hasImg = trim($value) !== '';
    echo '<div class="ip-wrap">';
    echo '<div id="ipprev_' . $uid . '">';
    if ($hasImg) echo '<img src="' . $esc . '" class="ip-preview" onerror="this.outerHTML=\'<div class=ip-preview-placeholder>🖼</div>\'">';
    else         echo '<div class="ip-preview-placeholder">🖼</div>';
    echo '</div>';
    echo '<input id="ipurl_' . $uid . '" class="ip-input" type="text" value="' . $esc . '" placeholder="/assets/..." oninput="updateIpPreview(\'' . $uid . '\')">';
    echo '<button type="button" class="ip-btn" onclick="triggerIpUpload(\'' . $uid . '\')">↑ Upload</button>';
    echo '</div>';
}
