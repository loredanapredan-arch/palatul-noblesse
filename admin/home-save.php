<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

function json_err(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['ok' => false, 'error' => $msg]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_err('Method not allowed', 405);
if (!csrf_verify()) json_err('Token CSRF invalid', 403);

$raw = trim($_POST['data'] ?? '');
if ($raw === '') json_err('Date lipsă.');

$data = json_decode($raw, true);
if (!is_array($data)) json_err('JSON invalid: ' . json_last_error_msg());

$allowed_sections = [
    'seo', 'hero', 'story',
    'portfolio_heading', 'services_heading', 'saloane_heading',
    'why_items', 'bleed_gradina', 'biblioteka', 'testimonials',
];

try {
    $pdo = db();

    // Auto-create table if needed
    $pdo->exec("CREATE TABLE IF NOT EXISTS home_settings (
        id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        section    VARCHAR(60) NOT NULL UNIQUE,
        data       JSON NOT NULL,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $stmt = $pdo->prepare("INSERT INTO home_settings (section, data)
        VALUES (?, ?)
        ON DUPLICATE KEY UPDATE data = VALUES(data), updated_at = NOW()");

    foreach ($allowed_sections as $section) {
        if (!isset($data[$section])) continue;
        $section_data = $data[$section];
        if (!is_array($section_data)) continue;

        // Basic sanitization: remove keys with suspicious values
        array_walk_recursive($section_data, function (&$val) {
            if (is_string($val)) $val = mb_substr(strip_tags($val, '<br><b><i><em><strong>'), 0, 5000);
        });

        $stmt->execute([$section, json_encode($section_data, JSON_UNESCAPED_UNICODE)]);
    }

    echo json_encode(['ok' => true, 'msg' => 'Homepage salvat cu succes.']);

} catch (\PDOException $e) {
    json_err('Eroare bază de date: ' . $e->getMessage(), 500);
}
