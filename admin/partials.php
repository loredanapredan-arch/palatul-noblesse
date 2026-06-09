<?php
declare(strict_types=1);

function admin_head(string $page_title = ''): void
{
    $title = $page_title ? "{$page_title} — Admin Palatul Noblesse" : 'Admin Palatul Noblesse';
    echo <<<HTML
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{$title}</title>
<meta name="robots" content="noindex,nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,400;0,600;1,400&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
HTML;
}

function admin_nav(string $active = ''): void
{
    $admin = current_admin();
    $username = htmlspecialchars($admin['username']);
    $nav_items = [
        'index'      => ['Dashboard',   'index.php'],
        'portofoliu' => ['Portofoliu',  'pages-list.php?type=portofoliu'],
        'servicii'   => ['Servicii',    'pages-list.php?type=servicii'],
        'saloane'    => ['Saloane',     'pages-list.php?type=saloane'],
    ];
    echo '<nav class="admin-nav navbar navbar-expand-md" style="background:#1f1b18;border-bottom:1px solid rgba(201,161,74,.2)">';
    echo '<div class="container-fluid px-4">';
    echo '<a class="navbar-brand d-flex align-items-center gap-2 text-decoration-none" href="index.php">';
    echo '<span style="font-family:\'Cormorant Garamond\',serif;font-size:1.15rem;color:#fff;font-weight:600">Palatul Noblesse</span>';
    echo '<span style="font-size:9px;letter-spacing:.3em;text-transform:uppercase;color:#c9a14a;margin-top:2px">Admin</span>';
    echo '</a>';
    echo '<button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav" style="color:#fff">';
    echo '<span class="navbar-toggler-icon" style="filter:invert(1)"></span></button>';
    echo '<div class="collapse navbar-collapse" id="adminNav">';
    echo '<ul class="navbar-nav me-auto gap-1">';
    foreach ($nav_items as $key => [$label, $href]) {
        $is_active = $active === $key;
        $cls = $is_active ? 'nav-link px-3 py-2 rounded-2 active' : 'nav-link px-3 py-2 rounded-2';
        $style = $is_active ? 'background:rgba(201,161,74,.15);color:#c9a14a' : 'color:rgba(255,255,255,.7)';
        echo "<li class=\"nav-item\"><a class=\"{$cls}\" href=\"{$href}\" style=\"{$style};font-size:.82rem;font-weight:500;letter-spacing:.04em\">{$label}</a></li>";
    }
    echo '</ul>';
    echo '<div class="d-flex align-items-center gap-3">';
    echo "<span style=\"color:rgba(255,255,255,.45);font-size:.78rem\">{$username}</span>";
    echo '<a href="logout.php" style="color:rgba(255,255,255,.45);font-size:.78rem;text-decoration:none" onmouseover="this.style.color=\'#fff\'" onmouseout="this.style.color=\'rgba(255,255,255,.45)\'">Deconectare</a>';
    echo '</div>';
    echo '</div></div></nav>';
}

function admin_css(): void
{
    echo <<<CSS
<style>
*, *::before, *::after { box-sizing: border-box }
:root {
  --burg:    #bd3033;
  --burg-dk: #8d2225;
  --gold:    #c9a14a;
  --dark:    #1f1b18;
  --cream:   #f5f0ea;
  --muted:   #6b5f55;
  --border:  #e4ddd5;
}
body { font-family: 'Inter', sans-serif; background: var(--cream); color: var(--dark); min-height: 100vh }
.eyebrow { font-size: 10px; letter-spacing: .25em; text-transform: uppercase; color: var(--muted); font-weight: 600 }
.btn-gold { background: var(--gold); color: #fff; border: none; border-radius: 6px; padding: .5rem 1.25rem; font-size: .82rem; font-weight: 600; letter-spacing: .08em; cursor: pointer; transition: opacity .2s }
.btn-gold:hover { opacity: .85; color: #fff }
.btn-burg { background: var(--burg); color: #fff; border: none; border-radius: 6px; padding: .5rem 1.25rem; font-size: .82rem; font-weight: 600; letter-spacing: .08em; cursor: pointer; transition: background .2s }
.btn-burg:hover { background: var(--burg-dk); color: #fff }
.card-admin { background: #fff; border-radius: 12px; border: 1px solid var(--border); padding: 1.5rem }
.status-dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block }
.status-dot.active { background: #16a34a }
.status-dot.inactive { background: #9ca3af }
</style>
CSS;
}
