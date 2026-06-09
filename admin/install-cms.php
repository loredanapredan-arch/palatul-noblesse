<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

$pdo = db();
$msgs = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    try {
        // ── cms_items ────────────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS cms_items (
            id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            type         ENUM('portofoliu','servicii','saloane') NOT NULL,
            slug         VARCHAR(120) NOT NULL,
            title        VARCHAR(255) NOT NULL DEFAULT '',
            meta_desc    TEXT NOT NULL DEFAULT '',
            og_image     VARCHAR(500) DEFAULT NULL,
            json_ld      MEDIUMTEXT DEFAULT NULL,
            blocks       JSON DEFAULT NULL,
            extra        JSON DEFAULT NULL,
            sort_order   SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            active       TINYINT(1) NOT NULL DEFAULT 1,
            created_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY uq_type_slug (type, slug)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $msgs[] = ['ok', 'Tabelul cms_items creat.'];

        // ── media_uploads ────────────────────────────────────────
        $pdo->exec("CREATE TABLE IF NOT EXISTS media_uploads (
            id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            filename    VARCHAR(255) NOT NULL,
            filepath    VARCHAR(500) NOT NULL,
            filesize    INT UNSIGNED NOT NULL DEFAULT 0,
            mime_type   VARCHAR(100) NOT NULL DEFAULT '',
            uploaded_by INT UNSIGNED NOT NULL,
            uploaded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $msgs[] = ['ok', 'Tabelul media_uploads creat.'];

        // ── uploads directory ────────────────────────────────────
        $uploads_dir = __DIR__ . '/../assets/uploads';
        if (!is_dir($uploads_dir)) {
            mkdir($uploads_dir, 0755, true);
            $msgs[] = ['ok', 'Director assets/uploads/ creat.'];
        } else {
            $msgs[] = ['info', 'Director assets/uploads/ există deja.'];
        }

        // ── Seed data ────────────────────────────────────────────
        if (isset($_POST['seed'])) {
            $insert = $pdo->prepare("INSERT IGNORE INTO cms_items
                (type, slug, title, meta_desc, og_image, blocks, extra, sort_order)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");

            $seed_items = [
                // ── Portofoliu ───────────────────────────────────
                ['portofoliu', 'nunta', 'Nuntă de vis',
                    'Nuntă de vis la Palatul Noblesse — locație premium în centrul Bucureștiului, monument istoric din 1881.',
                    '/assets/wedding.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/wedding.jpg','eyebrow'=>'Portofoliu','h1'=>'Nuntă de vis','subtitle'=>'O nuntă regală într-un cadru unic, cu apartament Royal pentru pregătiri și grădină boemă pentru ceremonie.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Servicii incluse','heading'=>'Tot ce aveți nevoie pentru un eveniment de excepție','features'=>['Event manager dedicat','Experiențe culinare din bucătăria palatului','Apartament Royal pentru pregătiri','Grădina boemă pentru ceremonie','Aranjamente florale și decoruri','Photo corner și DJ/band','Parcare privată','Acces la Biserica Sfinților vis-a-vis'],'image'=>'/assets/wedding.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'nunta']), 1
                ],
                ['portofoliu', 'botez', 'Botez special',
                    'Botez la Palatul Noblesse — momente magice într-un cadru elegant, București.',
                    '/assets/botez.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/botez.jpg','eyebrow'=>'Portofoliu','h1'=>'Botez special','subtitle'=>'Cele mai frumoase amintiri ale primilor pași în viață, într-un cadru de basm.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Servicii incluse','heading'=>'Un botez de neuitat','features'=>['Event manager dedicat','Decoruri tematice personalizate','Meniu special pentru copii și adulți','Animatori și activități creative','Photo corner și pachet foto-video','Tort și masă de dulciuri'],'image'=>'/assets/botez.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'botez']), 2
                ],
                ['portofoliu', 'corporate', 'Evenimente corporate',
                    'Evenimente corporate la Palatul Noblesse — spații elegante pentru conferințe, team building și gale.',
                    '/assets/salon-baroque.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/salon-baroque.jpg','eyebrow'=>'Portofoliu','h1'=>'Evenimente corporate','subtitle'=>'Conferințe, lansări de produse, gale și team building într-un cadru care impresionează.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Servicii incluse','heading'=>'Soluții complete pentru business','features'=>['Sistem audio-video profesional','Conexiune internet dedicată','Coffee break și catering personalizat','Coordonator de eveniment dedicat','Parcare privată','Posibilitate brandare spații'],'image'=>'/assets/salon-baroque.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'corporate']), 3
                ],
                ['portofoliu', 'petreceri-private', 'Petreceri private',
                    'Petreceri private la Palatul Noblesse — aniversări, reuniuni și celebrări de neuitat.',
                    '/assets/private.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/private.jpg','eyebrow'=>'Portofoliu','h1'=>'Petreceri private','subtitle'=>'Aniversări, reuniuni și celebrări private într-un spațiu exclusivist.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Servicii incluse','heading'=>'O petrecere pe gustul tău','features'=>['Decoruri personalizate','Meniu à la carte sau bufet','Bar cu mixologi profesioniști','DJ și lighting profesional','Photo corner tematic','Invitații și welcome gifts'],'image'=>'/assets/private.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'petreceri-private']), 4
                ],
                ['portofoliu', 'petreceri-tineri', 'Petreceri pentru tineri',
                    'Petreceri pentru tineri la Palatul Noblesse — energie, stil și amintiri de neuitat.',
                    '/assets/private.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/private.jpg','eyebrow'=>'Portofoliu','h1'=>'Petreceri pentru tineri','subtitle'=>'Energie, muzică și un cadru spectaculos pentru cele mai tari petreceri.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Servicii incluse','heading'=>'Petrecerea perfectă','features'=>['DJ profesionist','Sistem lighting și efecte speciale','Catering modern','Bar cu cocktailuri signature','Photo booth','Securitate și coordonare'],'image'=>'/assets/private.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'petreceri-tineri']), 5
                ],
                ['portofoliu', 'shooting', 'Ședință foto & video',
                    'Ședințe foto și video la Palatul Noblesse — cadre unice într-un monument istoric.',
                    '/assets/apartment-royal.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/apartment-royal.jpg','eyebrow'=>'Portofoliu','h1'=>'Ședință foto & video','subtitle'=>'Decoruri unice, lumini naturale și atmosferă de epocă pentru proiecte creative.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce oferim','heading'=>'Spațiu premium pentru creație','features'=>['Acces la toate saloanele','Lumini naturale și artificiale','Mobilier de epocă și props','Garderobă și make-up room','Asistență logistică','Parcare privată'],'image'=>'/assets/apartment-royal.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['event_category'=>'shooting']), 6
                ],
                // ── Servicii ─────────────────────────────────────
                ['servicii', 'servicii-culinare', 'Servicii culinare',
                    'Servicii culinare la Palatul Noblesse — meniuri signature, live cooking și experiențe gastronomice.',
                    '/assets/salon-baroque.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/salon-baroque.jpg','eyebrow'=>'Servicii','h1'=>'Servicii culinare','subtitle'=>'Meniuri signature semnate de chef-ul palatului, live cooking și experiențe gastronomice memorabile.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Detalii și opțiuni','features'=>['Meniuri signature semnate de chef-ul palatului','Bucătărie internațională, fusion și românească rafinată','Live cooking stations și show culinary','Meniuri vegane, vegetariene și fără gluten','Servire à la carte, bufet sau family style'],'image'=>'/assets/salon-baroque.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'culinar']), 1
                ],
                ['servicii', 'dulciuri-si-delicii', 'Dulciuri și delicii',
                    'Dulciuri și delicii la Palatul Noblesse — torturi artistice, candy bar și desert tables.',
                    '/assets/botez.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/botez.jpg','eyebrow'=>'Servicii','h1'=>'Dulciuri și delicii','subtitle'=>'Torturi artistice, candy bar și mese de desert care transformă orice eveniment.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Dulciuri pentru toate gusturile','features'=>['Torturi personalizate multi-etaj','Candy bar tematic','Macarons, cupcakes și praline artizanale','Desert table decorat','Chocolatier live','Opțiuni fără zahăr și gluten'],'image'=>'/assets/botez.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'dulciuri']), 2
                ],
                ['servicii', 'decoratiuni-si-tematici', 'Decorațiuni și tematici',
                    'Decorațiuni și tematici la Palatul Noblesse — aranjamente florale și decoruri personalizate.',
                    '/assets/salon-florence.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/salon-florence.jpg','eyebrow'=>'Servicii','h1'=>'Decorațiuni și tematici','subtitle'=>'Aranjamente florale, decoruri tematice și styling complet pentru evenimentul tău.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Design și decoruri premium','features'=>['Aranjamente florale personalizate','Draping și iluminat ambiental','Decoruri tematice la alegere','Centrepiece-uri și table settings','Welcome boards și semnalistică','Aranjare și dezasamblare incluse'],'image'=>'/assets/salon-florence.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'decoratiuni']), 3
                ],
                ['servicii', 'bar-si-mixologie', 'Bar și mixologie',
                    'Bar și mixologie la Palatul Noblesse — cocktailuri signature și experiențe premium.',
                    '/assets/private.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/private.jpg','eyebrow'=>'Servicii','h1'=>'Bar și mixologie','subtitle'=>'Cocktailuri signature, vinuri selecte și experiențe premium pentru evenimentul tău.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Experiența completă de bar','features'=>['Mixologi profesioniști cu experiență','Cocktailuri signature și clasice','Vinuri selecte și șampanii premium','Bar tematic personalizat','Mocktailuri și opțiuni non-alcoolice','Open bar sau consumație la bucată'],'image'=>'/assets/private.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'bar']), 4
                ],
                ['servicii', 'servicii-artistice', 'Servicii artistice',
                    'Servicii artistice la Palatul Noblesse — muzică live, entertainment și show-uri memorabile.',
                    '/assets/salon-luxury.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/salon-luxury.jpg','eyebrow'=>'Servicii','h1'=>'Servicii artistice','subtitle'=>'Muzică live, trupe, artiști și show-uri care transformă evenimentul într-o experiență unică.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Entertainment de excepție','features'=>['Formații și soliști live','DJ profesioniști cu echipament premium','Dansatori și show-uri speciale','Foc de artificii și pirotehnie','Artiști de stradă și acrobați','Karaoke și photo booth animat'],'image'=>'/assets/salon-luxury.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'artistic']), 5
                ],
                ['servicii', 'foto-video', 'Foto & video',
                    'Servicii foto și video la Palatul Noblesse — fotografi și videografi profesioniști.',
                    '/assets/apartment-royal.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/apartment-royal.jpg','eyebrow'=>'Servicii','h1'=>'Foto & video','subtitle'=>'Fotografi și videografi profesioniști care surprind fiecare moment prețios.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Amintiri pentru totdeauna','features'=>['Fotograf eveniment profesionist','Videograf cu echipament 4K','Film cinematic de highlight','Drone footage (exterior)','Album foto premium','Livrare digitală HD în 30 zile'],'image'=>'/assets/apartment-royal.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'foto-video']), 6
                ],
                ['servicii', 'experiente-exclusiviste', 'Experiențe exclusiviste',
                    'Experiențe exclusiviste la Palatul Noblesse — servicii premium și surprize pentru oaspeți.',
                    '/assets/salon-baroque.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/salon-baroque.jpg','eyebrow'=>'Servicii','h1'=>'Experiențe exclusiviste','subtitle'=>'Pachete premium și surprize personalizate pentru oaspeții tăi speciali.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Rafinament la cel mai înalt nivel','features'=>['Concierge dedicat pentru VIP','Welcome gifts personalizate','Servicii spa și wellness','Acces la Apartamentul Royal','Transport cu vehicule de lux','Sommelier și degustare vinuri'],'image'=>'/assets/salon-baroque.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'exclusivist']), 7
                ],
                ['servicii', 'divertisment-copii', 'Divertisment pentru copii',
                    'Divertisment pentru copii la Palatul Noblesse — animatori, activități și distracție garantată.',
                    '/assets/botez.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'hero','data'=>['image'=>'/assets/botez.jpg','eyebrow'=>'Servicii','h1'=>'Divertisment pentru copii','subtitle'=>'Animatori profesioniști, activități creative și o lume de poveste pentru cei mici.']],
                        ['id'=>'b002','type'=>'svc-detail','data'=>['eyebrow'=>'Ce includem','heading'=>'Distracție garantată','features'=>['Animatori și clowni profesioniști','Ateliere creative și pictură','Tobogane și castel gonflabil','Personaje de poveste','Meniu special pentru copii','Colț de joacă amenajat'],'image'=>'/assets/botez.jpg','reverse'=>false,'cta_label'=>'Cere ofertă','cta_url'=>'/contact.php']],
                    ]),
                    json_encode(['service_category'=>'copii']), 8
                ],
                // ── Saloane ──────────────────────────────────────
                ['saloane', 'salon-baroque', 'Salonul Baroque',
                    'Salonul Baroque la Palatul Noblesse — stucaturi bogate, covor de marmură și pictură pe tavan.',
                    '/assets/salon-baroque.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'01','name'=>'Salonul Baroque','description'=>'Salonul central cu stucaturi bogate, covor de marmură și pictură impresionantă pe tavan în omagiu muzelor.','image'=>'/assets/salon-baroque.jpg','suprafata'=>'45 m²','capacitate'=>'40 locuri','reverse'=>false]],
                    ]),
                    json_encode(['index_number'=>'01','suprafata'=>'45 m²','capacitate'=>'40 locuri','style'=>'Baroque']), 1
                ],
                ['saloane', 'salon-florence', 'Salonul Florence',
                    'Salonul Florence la Palatul Noblesse — atmosferă rafinată și luminoasă în stil florentin.',
                    '/assets/salon-florence.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'02','name'=>'Salonul Florence','description'=>'O atmosferă rafinată și luminoasă, evocând farmecul inconfundabil al Florenței.','image'=>'/assets/salon-florence.jpg','suprafata'=>'35 m²','capacitate'=>'36 locuri','reverse'=>true]],
                    ]),
                    json_encode(['index_number'=>'02','suprafata'=>'35 m²','capacitate'=>'36 locuri','style'=>'Florentin']), 2
                ],
                ['saloane', 'salon-regent', 'Salonul Regent',
                    'Salonul Regent — boiserie de nuc, covor în șah și șemineu, un salon franțuzesc regal.',
                    '/assets/salon-regent.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'03','name'=>'Salonul Regent','description'=>'Pereți îmbrăcați în boiserie de nuc, covor de marmură în șah și șemineu — un salon franțuzesc regal.','image'=>'/assets/salon-regent.jpg','suprafata'=>'25 m²','capacitate'=>'24 locuri','reverse'=>false]],
                    ]),
                    json_encode(['index_number'=>'03','suprafata'=>'25 m²','capacitate'=>'24 locuri','style'=>'Franțuzesc']), 3
                ],
                ['saloane', 'salon-luxury', 'Salonul Luxury',
                    'Salonul Luxury la Palatul Noblesse — detalii aurii cu marmură albă și neagră.',
                    '/assets/salon-luxury.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'04','name'=>'Salonul Luxury','description'=>'Detalii aurii cu marmură albă și neagră — un ambient sofisticat pentru evenimente memorabile.','image'=>'/assets/salon-luxury.jpg','suprafata'=>'25 m²','capacitate'=>'24 locuri','reverse'=>true]],
                    ]),
                    json_encode(['index_number'=>'04','suprafata'=>'25 m²','capacitate'=>'24 locuri','style'=>'Luxury']), 4
                ],
                ['saloane', 'gradina', 'Grădina Palatului',
                    'Grădina Palatului Noblesse — 600 mp în stil parizian, perfectă pentru ceremonii în aer liber.',
                    '/assets/gradina.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'05','name'=>'Grădina Palatului','description'=>'600 mp de verdeață în stil parizian, perfectă pentru ceremonii și mese în aer liber.','image'=>'/assets/gradina.jpg','suprafata'=>'600 m²','capacitate'=>'200 cocktail','reverse'=>false]],
                    ]),
                    json_encode(['index_number'=>'05','suprafata'=>'600 m²','capacitate'=>'200 cocktail','style'=>'Grădină']), 5
                ],
                ['saloane', 'apartament-royal', 'Apartamentul Royal',
                    'Apartamentul Royal — 60 mp de lux la etaj, refugiu romantic pentru pregătiri de nuntă.',
                    '/assets/apartment-royal.jpg',
                    json_encode([
                        ['id'=>'b001','type'=>'salon-row','data'=>['index'=>'06','name'=>'Apartamentul Royal','description'=>'60 mp de lux la etaj, refugiu romantic pentru pregătiri de nuntă sau cazare.','image'=>'/assets/apartment-royal.jpg','suprafata'=>'60 m²','capacitate'=>'Pregătiri / cazare','reverse'=>true]],
                    ]),
                    json_encode(['index_number'=>'06','suprafata'=>'60 m²','capacitate'=>'Pregătiri / cazare','style'=>'Royal']), 6
                ],
            ];

            $seeded = 0;
            foreach ($seed_items as $item) {
                $insert->execute($item);
                if ($insert->rowCount() > 0) $seeded++;
            }
            $msgs[] = ['ok', "Date inițiale adăugate: {$seeded} înregistrări noi."];
        }

        $msgs[] = ['ok', 'Instalare completă.'];

    } catch (\PDOException $e) {
        $msgs[] = ['err', 'Eroare DB: ' . htmlspecialchars($e->getMessage())];
    }
}

$csrf = csrf_token();
$admin = current_admin();
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Instalare CMS — Admin Palatul Noblesse</title>
<meta name="robots" content="noindex,nofollow">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
body { background: #f5f0ea; font-family: 'Inter', sans-serif; }
.card { border: none; border-radius: 12px; box-shadow: 0 4px 24px rgba(0,0,0,.08); }
</style>
</head>
<body class="py-5">
<div class="container" style="max-width:600px">
  <div class="mb-4 d-flex align-items-center gap-3">
    <a href="index.php" class="text-decoration-none text-secondary">← Dashboard</a>
    <h1 class="h4 mb-0">Instalare CMS</h1>
  </div>

  <?php foreach ($msgs as [$type, $text]): ?>
  <div class="alert alert-<?= $type === 'ok' ? 'success' : ($type === 'info' ? 'info' : 'danger') ?> py-2">
    <?= htmlspecialchars($text) ?>
  </div>
  <?php endforeach ?>

  <div class="card p-4">
    <p class="text-muted mb-4">Creează tabelele necesare CMS-ului și opțional adaugă date inițiale din paginile existente.</p>
    <form method="post">
      <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">
      <div class="form-check mb-3">
        <input class="form-check-input" type="checkbox" name="seed" id="seed" checked>
        <label class="form-check-label" for="seed">
          Adaugă date inițiale (portofoliu, servicii, saloane din site-ul existent)
        </label>
      </div>
      <button type="submit" class="btn btn-danger">Rulează instalarea</button>
    </form>
  </div>

  <div class="alert alert-warning mt-3 small">
    ⚠ Rulează o singură dată. Dacă rulezi din nou, datele existente nu se suprascriu (INSERT IGNORE).
  </div>
</div>
</body>
</html>
