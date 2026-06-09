<?php
// ── Load homepage settings from DB ───────────────────────────
$_hs = [];
try {
    require_once __DIR__ . '/config/db.php';
    $rows = db()->query("SELECT section, data FROM home_settings")->fetchAll();
    foreach ($rows as $r) {
        $_hs[$r['section']] = json_decode($r['data'], true) ?: [];
    }
} catch (\Throwable $e) { $_hs = []; }

// Load dynamic grids from cms_items
$_porto   = [];
$_svc     = [];
$_saloane = [];
try {
    $stmt = db()->prepare("SELECT slug, title, meta_desc, og_image FROM cms_items WHERE type=? AND active=1 ORDER BY sort_order ASC, id ASC");
    $stmt->execute(['portofoliu']); $_porto   = $stmt->fetchAll();
    $stmt->execute(['servicii']);   $_svc     = $stmt->fetchAll();
    $stmt->execute(['saloane']);    $_saloane = $stmt->fetchAll();
} catch (\Throwable $e) {}

// Helper: get setting value with fallback
function h(array $s, string $k, string $def = ''): string {
    return htmlspecialchars($s[$k] ?? $def, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

$_seo  = $_hs['seo']   ?? [];
$_hero = $_hs['hero']  ?? [];
$_st   = $_hs['story'] ?? [];
$_ph   = $_hs['portfolio_heading'] ?? [];
$_sh   = $_hs['services_heading']  ?? [];
$_slh  = $_hs['saloane_heading']   ?? [];
$_why  = $_hs['why_items']         ?? [];
$_gr   = $_hs['bleed_gradina']     ?? [];
$_bib  = $_hs['biblioteka']        ?? [];
$_tst  = $_hs['testimonials']      ?? [];

$title      = h($_seo, 'title', 'Palatul Noblesse — Centru de evenimente și artă, București');
$meta_desc  = h($_seo, 'meta_desc', 'Palatul Noblesse — monument istoric din 1881, locație premium pentru nunți, botezuri, evenimente corporate, petreceri private și shooting-uri în centrul Bucureștiului.');
$canonical  = '/';
$nav_active = 'home';
$json_ld    = $_seo['json_ld'] ?? '';
require_once 'includes/header.php';

// ── Icon SVG map ──────────────────────────────────────────────
function icon_svg(string $key): string {
    $map = [
        'location' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>',
        'monument' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 7l5 5 5-9 5 9 5-5-2 13H4L2 7z"/></svg>',
        'star'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.9 4.6L18.5 9l-4.6 1.4L12 15l-1.9-4.6L5.5 9l4.6-1.4z"/></svg>',
        'tree'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M10 10v.2A3 3 0 0 1 8.9 16v0H5v0h0a3 3 0 0 1-1-5.8V10a3 3 0 0 1 6 0z"/><path d="M7 16v6"/><path d="M13 19v3"/><path d="M12 19h8.3a1 1 0 0 0 .7-1.7L18 14h.3a1 1 0 0 0 .7-1.7L16 9h.2a1 1 0 0 0 .8-1.7L13 3l-1.4 1.5"/></svg>',
        'building' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 17v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5"/><path d="M2 17h20v3"/><path d="M6 10V7a2 2 0 0 1 2-2h3v5"/></svg>',
        'medal'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="9" r="6"/><polyline points="8.21 13.89 7 22 12 19 17 22 15.79 13.88"/></svg>',
        'users'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'heart'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"/></svg>',
        'camera'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>',
        'music'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/></svg>',
        'ring'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/></svg>',
        'flower'   => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s-8-4.5-8-11.8A8 8 0 0 1 12 2a8 8 0 0 1 8 8.2c0 7.3-8 11.8-8 11.8z"/></svg>',
    ];
    return $map[$key] ?? $map['star'];
}

// Default why items if none in DB
$_why_items = $_why['items'] ?? [
    ['icon'=>'location','h3'=>'Locație ultracentrală',   'text'=>'Centrul istoric al Bucureștiului, la 5 minute de Universitate.'],
    ['icon'=>'monument','h3'=>'Monument istoric 1881',   'text'=>'Bijuterie arhitecturală în stil eclectic, prima de acest fel.'],
    ['icon'=>'star',    'h3'=>'Exclusivitate totală',    'text'=>'Închiriere exclusivă a întregii clădiri și grădinii.'],
    ['icon'=>'tree',    'h3'=>'Grădină boemă 600 mp',    'text'=>'O oază de verdeață pariziană pentru evenimente în aer liber.'],
    ['icon'=>'building','h3'=>'Apartament Royal',        'text'=>'60 mp de lux la etaj pentru pregătirile mirilor sau cazare.'],
    ['icon'=>'medal',   'h3'=>'Experiență din 2014',     'text'=>'Sute de evenimente reușite, echipă profesionistă dedicată.'],
];

$_tst_items = $_tst['items'] ?? [
    ['quote'=>'Locația de vis, profesionalismul echipei dedicate au transformat botezul fiicei noastre într-un basm. Atmosfera magică, grija și atenția pentru detalii — totul a fost impecabil.','author'=>'Mihaela Stanciu'],
    ['quote'=>'Palatul Noblesse a fost spațiul care ne-a susținut nunta de poveste. O simfonie de emoții resimțită de noi și de invitați.','author'=>'Luca Eugen'],
    ['quote'=>'O bijuterie de palat, gazde extraordinare, evenimentul a fost un SUCCES. Toată lumea a spus asta.','author'=>'Dorina Niga'],
];

// Default portfolio cards if none in DB
if (empty($_porto)) {
    $_porto = [
        ['slug'=>'nunta',             'title'=>'Nuntă de vis',         'meta_desc'=>'O nuntă regală într-un cadru unic, cu apartament Royal pentru pregătiri și grădină boemă pentru ceremonie.', 'og_image'=>'/assets/wedding.jpg'],
        ['slug'=>'botez',             'title'=>'Botez de poveste',      'meta_desc'=>'Un botez intim și personalizat în saloanele istorice ale palatului, alături de Biserica Sfinților din sec. XVIII.', 'og_image'=>'/assets/botez.jpg'],
        ['slug'=>'corporate',         'title'=>'Evenimente corporate',  'meta_desc'=>'Lansări de produs, gala dinners, conferințe și networking într-un cadru memorabil.', 'og_image'=>'/assets/corporate.jpg'],
        ['slug'=>'petreceri-private', 'title'=>'Petreceri private',     'meta_desc'=>'Aniversări, dineuri private și momente speciale în exclusivitatea palatului.', 'og_image'=>'/assets/private.jpg'],
        ['slug'=>'petreceri-tineri',  'title'=>'Petreceri tineri',      'meta_desc'=>'Majorate și petreceri de tineret cu o atmosferă vibrantă și sofisticată.', 'og_image'=>'/assets/tineri.jpg'],
        ['slug'=>'shooting',          'title'=>'Sesiuni foto &amp; video', 'meta_desc'=>'Shooting-uri editoriale, de modă și nuntă în peste 30 de interioare unice.', 'og_image'=>'/assets/shooting.jpg'],
    ];
}
if (empty($_svc)) {
    $_svc = [
        ['slug'=>'servicii-culinare',       'title'=>'Servicii culinare',       'meta_desc'=>'Meniuri signature semnate de chef-ul palatului, live cooking și experiențe gastronomice memorabile.', 'og_image'=>'/assets/salon-baroque.jpg'],
        ['slug'=>'dulciuri-si-delicii',     'title'=>'Dulciuri și delicii',     'meta_desc'=>'Candy bar premium, torturi de autor, cofetărie franțuzească și deserturi artizanale.', 'og_image'=>'/assets/botez.jpg'],
        ['slug'=>'decoratiuni-si-tematici', 'title'=>'Decorațiuni și tematici', 'meta_desc'=>'Floral design și concept tematic 360°, de la save the date până la table setting.', 'og_image'=>'/assets/salon-florence.jpg'],
        ['slug'=>'bar-si-mixologie',        'title'=>'Bar și mixologie',        'meta_desc'=>'Cocktailuri signature, mixologi de top, sommelier și cigar lounge în Salonul Regent.', 'og_image'=>'/assets/private.jpg'],
        ['slug'=>'servicii-artistice',      'title'=>'Servicii artistice',      'meta_desc'=>'DJ, cvartete, soliști, performeri și show-uri live pentru o atmosferă inegalabilă.', 'og_image'=>'/assets/salon-luxury.jpg'],
        ['slug'=>'foto-video',              'title'=>'Foto &amp; video',        'meta_desc'=>'Echipe foto-video premium, photo booth modern, drone aerial și same-day edit.', 'og_image'=>'/assets/shooting.jpg'],
        ['slug'=>'experiente-exclusiviste', 'title'=>'Experiențe exclusiviste', 'meta_desc'=>'Închiriere exclusivă, concierge personal, cazare Royal și transferuri cu mașini de epocă.', 'og_image'=>'/assets/wedding.jpg'],
        ['slug'=>'divertisment-copii',      'title'=>'Divertisment pentru copii','meta_desc'=>'Animatori, ateliere creative, face painting și mini-meniuri dedicate celor mici.', 'og_image'=>'/assets/tineri.jpg'],
    ];
}
if (empty($_saloane)) {
    $_saloane = [
        ['slug'=>'salon-baroque',    'title'=>'Salonul Baroque',        'meta_desc'=>'Salonul central cu stucaturi bogate, covor de marmură și pictură impresionantă pe tavan în omagiu muzelor.', 'og_image'=>'/assets/salon-baroque.jpg', 'extra'=>'{"suprafata":"45 m²","capacitate":"40 locuri"}'],
        ['slug'=>'salon-florence',   'title'=>'Salonul Florence',       'meta_desc'=>'O atmosferă rafinată și luminoasă, evocând farmecul inconfundabil al Florenței.', 'og_image'=>'/assets/salon-florence.jpg', 'extra'=>'{"suprafata":"35 m²","capacitate":"36 locuri"}'],
        ['slug'=>'salon-regent',     'title'=>'Salonul Regent',         'meta_desc'=>'Pereți îmbrăcați în boiserie de nuc, covor de marmură în șah și șemineu — un salon franțuzesc regal.', 'og_image'=>'/assets/salon-regent.jpg', 'extra'=>'{"suprafata":"25 m²","capacitate":"24 locuri"}'],
        ['slug'=>'salon-luxury',     'title'=>'Salonul Luxury',         'meta_desc'=>'Detalii aurii cu marmură albă și neagră — un ambient sofisticat pentru evenimente memorabile.', 'og_image'=>'/assets/salon-luxury.jpg', 'extra'=>'{"suprafata":"25 m²","capacitate":"24 locuri"}'],
        ['slug'=>'gradina',          'title'=>'Grădina Palatului',      'meta_desc'=>'600 mp de verdeață în stil parizian, perfectă pentru ceremonii și mese în aer liber.', 'og_image'=>'/assets/gradina.jpg', 'extra'=>'{"suprafata":"600 m²","capacitate":"200 cocktail"}'],
        ['slug'=>'apartament-royal', 'title'=>'Apartamentul Royal',     'meta_desc'=>'60 mp de lux la etaj, refugiu romantic pentru pregătiri de nuntă sau cazare.', 'og_image'=>'/assets/apartment-royal.jpg', 'extra'=>'{"suprafata":"60 m²","capacitate":"Pregătiri / cazare"}'],
    ];
}

$arrow_svg = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>';
?>

<!-- ── Hero ────────────────────────────────────────────────── -->
<section class="hero">
  <img src="<?= h($_hero,'image','/assets/hero-palace.jpg') ?>" alt="Palatul Noblesse">
  <div class="overlay"></div><div class="overlay2"></div>
  <div class="content fade-up">
    <div class="rule-row"><span class="line"></span><span class="eb"><?= h($_hero,'eyebrow','Centru de evenimente și artă · București') ?></span><span class="line"></span></div>
    <h1><?= h($_hero,'h1','Palatul Noblesse') ?></h1>
    <p class="tagline"><?= h($_hero,'tagline','Special moments that live forever') ?></p>
    <p class="sub"><?= h($_hero,'sub','Monument istoric din 1881 din inima Bucureștiului — restaurat cu pasiune pentru a găzdui cele mai elegante povești.') ?></p>
    <div class="ctas">
      <a class="btn-primary" href="<?= h($_hero,'cta1_url','/contact.php') ?>"><?= h($_hero,'cta1_label','Cere ofertă') ?></a>
      <a class="btn-outline"  href="<?= h($_hero,'cta2_url','/saloane.php') ?>"><?= h($_hero,'cta2_label','Descoperă palatul') ?></a>
    </div>
  </div>
</section>

<!-- ── Povestea noastră ─────────────────────────────────────── -->
<section class="py container-x">
  <div class="story-grid">
    <div class="reveal">
      <div class="story-img-wrap">
        <img src="<?= h($_st,'image','/assets/salon-baroque.jpg') ?>" alt="" loading="lazy">
        <div class="story-badge">
          <div class="num"><?= h($_st,'badge_num','140<span>+</span>') ?></div>
          <div class="lbl"><?= h($_st,'badge_lbl','ani de poveste') ?></div>
        </div>
      </div>
    </div>
    <div class="story-content reveal">
      <span class="eyebrow"><?= h($_st,'eyebrow','Povestea noastră') ?></span>
      <h2><?= h($_st,'h2','O bijuterie arhitecturală de peste 140 de ani') ?></h2>
      <div class="body">
        <p><?= h($_st,'text1','Palatul Noblesse este o clădire monument istoric datând din 1881, complet restaurată și redată patrimoniului cultural bucureștean. O oază de liniște aflată în mijlocul unei grădini boeme.') ?></p>
        <p><?= h($_st,'text2','Prima clădire pe stil eclectic din centrul istoric al Bucureștiului, proiectată de arhitectul Alexandru Săvulescu. Astăzi, un centru de evenimente, design și artă unic în Europa.') ?></p>
      </div>
      <a class="story-cta" href="<?= h($_st,'cta_url','/despre.php') ?>"><?= h($_st,'cta_label','Află mai mult') ?> <?= $arrow_svg ?></a>
    </div>
  </div>
</section>

<!-- ── Portofoliu ───────────────────────────────────────────── -->
<section class="py bg-cream">
  <div class="container-x">
    <div class="section-heading reveal">
      <div class="row-eb"><span class="gold-rule"></span><span class="eyebrow"><?= h($_ph,'eyebrow','Portofoliu') ?></span><span class="gold-rule"></span></div>
      <h2><?= h($_ph,'h2','Evenimente desprinse din povești') ?></h2>
      <p><?= h($_ph,'text','Fiecare eveniment la Palatul Noblesse este unic, croit cu grijă pentru a deveni o amintire de neuitat.') ?></p>
    </div>
    <div class="mt-16 grid g-3">
      <?php foreach ($_porto as $card):
        $img   = htmlspecialchars($card['og_image'] ?? '', ENT_QUOTES);
        $title_c = htmlspecialchars($card['title'] ?? '', ENT_QUOTES);
        $desc  = htmlspecialchars($card['meta_desc'] ?? '', ENT_QUOTES);
        $url   = '/portofoliu/' . htmlspecialchars($card['slug'], ENT_QUOTES) . '.php';
      ?>
      <a class="svc-card ratio-45 reveal" href="<?= $url ?>">
        <img src="<?= $img ?>" alt="<?= $title_c ?>" loading="lazy">
        <div class="overlay"></div>
        <div class="content">
          <h3><?= htmlspecialchars($card['title'] ?? '', ENT_QUOTES) ?></h3>
          <p><?= htmlspecialchars($card['meta_desc'] ?? '', ENT_QUOTES) ?></p>
          <span class="discover">Descoperă <?= $arrow_svg ?></span>
        </div>
      </a>
      <?php endforeach ?>
    </div>
  </div>
</section>

<!-- ── Servicii ─────────────────────────────────────────────── -->
<section class="py container-x">
  <div class="section-heading reveal">
    <div class="row-eb"><span class="gold-rule"></span><span class="eyebrow"><?= h($_sh,'eyebrow','Servicii dedicate') ?></span><span class="gold-rule"></span></div>
    <h2><?= h($_sh,'h2','Serviciile noastre') ?></h2>
    <p><?= h($_sh,'text','De la bucătărie signature la decor, mixologie și divertisment — totul gândit pentru un eveniment fără cusur.') ?></p>
  </div>
  <div class="mt-16 grid g-4">
    <?php foreach ($_svc as $card):
      $img   = htmlspecialchars($card['og_image'] ?? '', ENT_QUOTES);
      $title_c = htmlspecialchars($card['title'] ?? '');
      $url   = '/servicii/' . htmlspecialchars($card['slug'], ENT_QUOTES) . '.php';
    ?>
    <a class="svc-card ratio-45 reveal" href="<?= $url ?>">
      <img src="<?= $img ?>" alt="<?= $title_c ?>" loading="lazy">
      <div class="overlay"></div>
      <div class="content">
        <h3><?= $title_c ?></h3>
        <p><?= htmlspecialchars($card['meta_desc'] ?? '') ?></p>
        <span class="discover">Descoperă <?= $arrow_svg ?></span>
      </div>
    </a>
    <?php endforeach ?>
  </div>
  <div class="mt-12 text-center">
    <a class="btn-ghost-dark" href="/servicii.php">Vezi toate serviciile</a>
  </div>
</section>

<!-- ── Saloane ──────────────────────────────────────────────── -->
<section class="py bg-cream">
  <div class="container-x">
    <div class="section-heading reveal">
      <div class="row-eb"><span class="gold-rule"></span><span class="eyebrow"><?= h($_slh,'eyebrow','Spațiile palatului') ?></span><span class="gold-rule"></span></div>
      <h2><?= h($_slh,'h2','Saloanele parterului') ?></h2>
      <p><?= h($_slh,'text','Cinci interioare de excepție, fiecare cu propria personalitate, conectate într-un tur fluid al palatului.') ?></p>
    </div>
    <div class="mt-16 grid g-3">
      <?php foreach ($_saloane as $card):
        $extra = json_decode($card['extra'] ?? '{}', true) ?: [];
        $img   = htmlspecialchars($card['og_image'] ?? '', ENT_QUOTES);
        $name  = htmlspecialchars($card['title'] ?? '');
        $desc  = htmlspecialchars($card['meta_desc'] ?? '');
        $sup   = htmlspecialchars($extra['suprafata'] ?? '');
        $cap   = htmlspecialchars($extra['capacitate'] ?? '');
      ?>
      <a class="salon-card reveal" href="/saloane.php">
        <div class="img-wrap"><img src="<?= $img ?>" alt="<?= $name ?>" loading="lazy"></div>
        <div class="body">
          <h3><?= $name ?></h3>
          <p><?= $desc ?></p>
          <?php if ($sup || $cap): ?>
          <div class="meta">
            <?php if ($sup): ?><span><span class="lbl">Suprafață: </span><?= $sup ?></span><?php endif ?>
            <?php if ($cap): ?><span><span class="lbl">Capacitate: </span><?= $cap ?></span><?php endif ?>
          </div>
          <?php endif ?>
        </div>
      </a>
      <?php endforeach ?>
    </div>
    <div class="mt-12 text-center">
      <a class="btn-ghost-dark" href="/saloane.php">Vezi toate spațiile</a>
    </div>
  </div>
</section>

<!-- ── De ce Noblesse ───────────────────────────────────────── -->
<section class="py container-x">
  <div class="section-heading reveal">
    <div class="row-eb"><span class="gold-rule"></span><span class="eyebrow"><?= h($_why,'eyebrow','De ce Palatul Noblesse') ?></span><span class="gold-rule"></span></div>
    <h2><?= h($_why,'h2','Locul în care eleganța întâlnește istoria') ?></h2>
  </div>
  <div class="mt-16 grid g-3">
    <?php foreach ($_why_items as $wi): ?>
    <div class="why-item reveal">
      <?= icon_svg($wi['icon'] ?? 'star') ?>
      <h3><?= htmlspecialchars($wi['h3'] ?? '') ?></h3>
      <p><?= htmlspecialchars($wi['text'] ?? '') ?></p>
    </div>
    <?php endforeach ?>
  </div>
</section>

<!-- ── Grădina boemă ────────────────────────────────────────── -->
<section class="bleed">
  <img src="<?= h($_gr,'image','/assets/gradina.jpg') ?>" alt="" loading="lazy">
  <div class="overlay"></div>
  <div class="container-x content reveal">
    <div class="eyebrow eyebrow-gold"><?= h($_gr,'eyebrow','600 mp · stil parizian') ?></div>
    <h2><?= h($_gr,'h2','Grădina boemă a palatului') ?></h2>
    <p><?= h($_gr,'text','O oază de verdeață, lumini și liniște ascunsă în mijlocul Bucureștiului — locul ideal pentru ceremonii, cocktail-uri și mese în aer liber.') ?></p>
    <a class="btn-outline" href="<?= h($_gr,'cta_url','/saloane.php') ?>"><?= h($_gr,'cta_label','Vezi toate spațiile') ?></a>
  </div>
</section>

<!-- ── Biblioteka intro ─────────────────────────────────────── -->
<section class="py-sm bg-ivory">
  <div class="container-x text-center reveal" style="max-width:48rem;margin-inline:auto">
    <span class="eyebrow"><?= h($_bib,'intro_eyebrow','Un nou capitol') ?></span>
    <h2 style="margin-top:1rem;font-family:var(--font-display);font-size:clamp(1.85rem,3.2vw,3rem);line-height:1.1"><?= h($_bib,'intro_h2','Spațiile dedicate ideilor și conversațiilor') ?></h2>
    <p style="margin-top:1.25rem;color:var(--muted-foreground)"><?= h($_bib,'intro_text','Dincolo de saloanele festive, palatul găzduiește locuri intime gândite pentru cultură, întâlniri creative și momente de inspirație.') ?></p>
  </div>
</section>
<section class="bleed right">
  <img src="<?= h($_bib,'image','/assets/biblioteka.jpg') ?>" alt="Biblioteka Hub — Palatul Noblesse" loading="lazy">
  <div class="overlay"></div>
  <div class="container-x content reveal">
    <div class="eyebrow eyebrow-gold"><?= h($_bib,'eyebrow','Cultură · Conversație · Comunitate') ?></div>
    <h2><?= h($_bib,'h2','Biblioteka Hub') ?></h2>
    <p><?= h($_bib,'text','Un spațiu intim între rafturi de cărți și fotolii de piele — locul perfect pentru lansări, lecturi, dezbateri și întâlniri rafinate în atmosfera caldă a palatului.') ?></p>
    <a class="btn-outline" href="<?= h($_bib,'cta_url','/saloane.php') ?>"><?= h($_bib,'cta_label','Descoperă spațiul') ?></a>
  </div>
</section>

<!-- ── Testimoniale ─────────────────────────────────────────── -->
<section class="py bg-cream">
  <div class="container-x">
    <div class="section-heading reveal">
      <div class="row-eb"><span class="gold-rule"></span><span class="eyebrow"><?= h($_tst,'eyebrow','Recenzii') ?></span><span class="gold-rule"></span></div>
      <h2><?= h($_tst,'h2','Povești spuse de oaspeții noștri') ?></h2>
    </div>
    <div class="mt-16 grid g-3">
      <?php foreach ($_tst_items as $t): ?>
      <figure class="testimonial reveal">
        <div class="quote-mark">"</div>
        <blockquote><?= htmlspecialchars($t['quote'] ?? '') ?></blockquote>
        <figcaption>— <?= htmlspecialchars($t['author'] ?? '') ?></figcaption>
      </figure>
      <?php endforeach ?>
    </div>
  </div>
</section>

<?php require_once 'includes/footer.php'; ?>
