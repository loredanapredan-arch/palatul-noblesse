<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_err(string $msg, int $code = 400): never
{
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_err('Method not allowed', 405);
}

if (!csrf_verify()) {
    json_err('Token CSRF invalid', 403);
}

// ── Input validation ─────────────────────────────────────────
$id    = (int) ($_POST['id'] ?? 0);
$type  = trim($_POST['type'] ?? '');
$slug  = trim($_POST['slug'] ?? '');
$title = trim($_POST['title'] ?? '');

$allowed_types = ['portofoliu', 'servicii', 'saloane'];
if (!in_array($type, $allowed_types, true)) {
    json_err('Tip invalid.');
}

if ($slug === '') {
    json_err('Slug-ul este obligatoriu.');
}
// Sanitize slug: only lowercase letters, digits, hyphens
$slug = preg_replace('/[^a-z0-9\-]/', '', mb_strtolower($slug));
$slug = preg_replace('/-{2,}/', '-', trim($slug, '-'));
if ($slug === '') {
    json_err('Slug invalid după sanitizare.');
}

if (mb_strlen($title) > 255) {
    json_err('Titlul este prea lung (max 255 caractere).');
}

$meta_desc = trim($_POST['meta_desc'] ?? '');
if (mb_strlen($meta_desc) > 320) {
    $meta_desc = mb_substr($meta_desc, 0, 320);
}

$og_image = trim($_POST['og_image'] ?? '') ?: null;
if ($og_image !== null && mb_strlen($og_image) > 500) {
    json_err('Calea imaginii este prea lungă.');
}

// Validate og_image path (must start with / or be empty)
if ($og_image !== null && !preg_match('#^/#', $og_image)) {
    $og_image = null;
}

$json_ld_raw = trim($_POST['json_ld'] ?? '');
$json_ld = null;
if ($json_ld_raw !== '') {
    // Validate JSON
    json_decode($json_ld_raw);
    if (json_last_error() !== JSON_ERROR_NONE) {
        json_err('JSON-LD invalid: ' . json_last_error_msg());
    }
    $json_ld = $json_ld_raw;
}

// blocks — must be valid JSON array
$blocks_raw = trim($_POST['blocks'] ?? '[]');
$blocks_decoded = json_decode($blocks_raw, true);
if (!is_array($blocks_decoded)) {
    json_err('Blocurile trebuie să fie un array JSON valid.');
}
// Sanitize: remove any keys that could cause injection
$blocks_clean = [];
foreach ($blocks_decoded as $block) {
    if (!is_array($block) || !isset($block['type'])) continue;
    $blocks_clean[] = $block;
}
$blocks = json_encode($blocks_clean, JSON_UNESCAPED_UNICODE);

// extra — type-specific JSON
$extra_raw = trim($_POST['extra'] ?? '{}');
$extra_decoded = json_decode($extra_raw, true);
if (!is_array($extra_decoded)) {
    $extra_decoded = [];
}
$extra = json_encode($extra_decoded, JSON_UNESCAPED_UNICODE);

// ── DB upsert ────────────────────────────────────────────────
try {
    $pdo = db();

    if ($id > 0) {
        // Update existing
        $stmt = $pdo->prepare("SELECT id FROM cms_items WHERE id = ? AND type = ? LIMIT 1");
        $stmt->execute([$id, $type]);
        if (!$stmt->fetch()) {
            json_err('Înregistrarea nu există sau tipul nu corespunde.', 404);
        }

        // Check slug uniqueness (excluding current)
        $dup = $pdo->prepare("SELECT id FROM cms_items WHERE type = ? AND slug = ? AND id != ? LIMIT 1");
        $dup->execute([$type, $slug, $id]);
        if ($dup->fetch()) {
            json_err("Slug-ul „{$slug}" este deja folosit pentru alt element de tip {$type}.");
        }

        $pdo->prepare("UPDATE cms_items SET
            slug      = ?,
            title     = ?,
            meta_desc = ?,
            og_image  = ?,
            json_ld   = ?,
            blocks    = ?,
            extra     = ?,
            updated_at = NOW()
            WHERE id = ?")
            ->execute([$slug, $title, $meta_desc, $og_image, $json_ld, $blocks, $extra, $id]);

        echo json_encode(['ok' => true, 'id' => $id, 'msg' => 'Salvat cu succes.']);
    } else {
        // Insert new
        $dup = $pdo->prepare("SELECT id FROM cms_items WHERE type = ? AND slug = ? LIMIT 1");
        $dup->execute([$type, $slug]);
        if ($dup->fetch()) {
            json_err("Slug-ul „{$slug}" este deja folosit pentru un element de tip {$type}.");
        }

        $max_order = $pdo->prepare("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM cms_items WHERE type = ?");
        $max_order->execute([$type]);
        $sort_order = (int) $max_order->fetchColumn();

        $pdo->prepare("INSERT INTO cms_items
            (type, slug, title, meta_desc, og_image, json_ld, blocks, extra, sort_order)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)")
            ->execute([$type, $slug, $title, $meta_desc, $og_image, $json_ld, $blocks, $extra, $sort_order]);

        $new_id = (int) $pdo->lastInsertId();
        echo json_encode(['ok' => true, 'id' => $new_id, 'msg' => 'Creat cu succes.']);
    }
} catch (\PDOException $e) {
    json_err('Eroare bază de date: ' . $e->getMessage(), 500);
}
