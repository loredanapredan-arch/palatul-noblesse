<?php
$title     = $title     ?? 'Palatul Noblesse — Centru de evenimente și artă, București';
$meta_desc = $meta_desc ?? 'Palatul Noblesse — monument istoric din 1881 din inima Bucureștiului — restaurat cu pasiune pentru a găzdui cele mai elegante povești.';
$canonical = $canonical ?? '/';
$nav_active = $nav_active ?? '';

function nav_class(string $key, string $active): string {
    return $key === $active ? ' class="active"' : '';
}
?>
<!doctype html>
<html lang="ro">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= htmlspecialchars($title) ?></title>
<meta name="description" content="<?= htmlspecialchars($meta_desc) ?>">
<link rel="canonical" href="<?= htmlspecialchars($canonical) ?>">
<meta property="og:title" content="<?= htmlspecialchars($title) ?>">
<meta property="og:description" content="<?= htmlspecialchars($meta_desc) ?>">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,500;0,600;1,400;1,500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="/css/styles.css">
</head>
<body>

<header class="site-header">
  <div class="container-x inner">
    <a class="brand" href="/">
      <span class="brand-name">Palatul Noblesse</span>
      <span class="brand-sub">Lifestyle Palace · 1881</span>
    </a>
    <nav class="nav-main">
      <a href="/"<?= nav_class('home', $nav_active) ?>>Acasă</a>
      <a href="/despre.php"<?= nav_class('despre', $nav_active) ?>>Despre</a>
      <a href="/portofoliu.php"<?= nav_class('portofoliu', $nav_active) ?>>Portofoliu</a>
      <div class="dropdown">
        <a href="/servicii.php"<?= nav_class('servicii', $nav_active) ?>>Servicii <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chev"><polyline points="6 9 12 15 18 9"/></svg></a>
        <div class="dropdown-menu-custom"><div>
          <a href="/servicii/servicii-culinare.php">Servicii culinare</a>
          <a href="/servicii/dulciuri-si-delicii.php">Dulciuri și delicii</a>
          <a href="/servicii/decoratiuni-si-tematici.php">Decorațiuni și tematici</a>
          <a href="/servicii/bar-si-mixologie.php">Bar și mixologie</a>
          <a href="/servicii/servicii-artistice.php">Servicii artistice</a>
          <a href="/servicii/foto-video.php">Foto &amp; video</a>
          <a href="/servicii/experiente-exclusiviste.php">Experiențe exclusiviste</a>
          <a href="/servicii/divertisment-copii.php">Divertisment pentru copii</a>
        </div></div>
      </div>
      <a href="/saloane.php"<?= nav_class('saloane', $nav_active) ?>>Saloane</a>
      <a href="/galerie.php"<?= nav_class('galerie', $nav_active) ?>>Galerie</a>
      <a href="/contact.php"<?= nav_class('contact', $nav_active) ?>>Contact</a>
    </nav>
    <div class="nav-actions">
      <div class="lang-toggle">
        <button data-lang="ro" class="active">RO</button><span class="sep">/</span>
        <button data-lang="en">EN</button>
      </div>
      <a class="btn-primary btn-quote" href="/contact.php">Cere ofertă</a>
      <button class="menu-toggle" aria-label="Menu">
        <svg class="ic-menu" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
        <svg class="ic-close" style="display:none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="22" height="22"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
      </button>
    </div>
  </div>
  <div class="mobile-menu">
    <div class="inner">
      <a href="/">Acasă</a>
      <a href="/despre.php">Despre</a>
      <a href="/portofoliu.php">Portofoliu</a>
      <div>
        <button class="mobile-svc-toggle" style="background:none;border:0;width:100%;display:flex;justify-content:space-between;align-items:center;padding:0;font:inherit;color:inherit;text-transform:uppercase;letter-spacing:.2em;font-size:13px;">Servicii <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="chev"><polyline points="6 9 12 15 18 9"/></svg></button>
        <div class="mobile-svc">
          <a href="/servicii.php">Toate serviciile</a>
          <a href="/servicii/servicii-culinare.php">Servicii culinare</a>
          <a href="/servicii/dulciuri-si-delicii.php">Dulciuri și delicii</a>
          <a href="/servicii/decoratiuni-si-tematici.php">Decorațiuni și tematici</a>
          <a href="/servicii/bar-si-mixologie.php">Bar și mixologie</a>
          <a href="/servicii/servicii-artistice.php">Servicii artistice</a>
          <a href="/servicii/foto-video.php">Foto &amp; video</a>
          <a href="/servicii/experiente-exclusiviste.php">Experiențe exclusiviste</a>
          <a href="/servicii/divertisment-copii.php">Divertisment pentru copii</a>
        </div>
      </div>
      <a href="/saloane.php">Saloane</a>
      <a href="/galerie.php">Galerie</a>
      <a href="/contact.php">Contact</a>
      <a class="btn-primary" href="/contact.php" style="align-self:flex-start;margin-top:.5rem">Cere ofertă</a>
    </div>
  </div>
</header>
