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

// CSRF check (passed as POST field or header)
if (!csrf_verify()) {
    json_err('Token CSRF invalid', 403);
}

if (empty($_FILES['file'])) {
    json_err('Niciun fișier primit.');
}

$file = $_FILES['file'];

if ($file['error'] !== UPLOAD_ERR_OK) {
    $err_map = [
        UPLOAD_ERR_INI_SIZE   => 'Fișierul depășește upload_max_filesize.',
        UPLOAD_ERR_FORM_SIZE  => 'Fișierul depășește MAX_FILE_SIZE.',
        UPLOAD_ERR_PARTIAL    => 'Fișierul a fost încărcat parțial.',
        UPLOAD_ERR_NO_FILE    => 'Niciun fișier încărcat.',
        UPLOAD_ERR_NO_TMP_DIR => 'Lipsește directorul temporar.',
        UPLOAD_ERR_CANT_WRITE => 'Nu se poate scrie pe disc.',
        UPLOAD_ERR_EXTENSION  => 'Încărcarea blocată de extensie PHP.',
    ];
    json_err($err_map[$file['error']] ?? 'Eroare upload necunoscută.');
}

// Max size: 8 MB
$max_bytes = 8 * 1024 * 1024;
if ($file['size'] > $max_bytes) {
    json_err('Fișierul depășește limita de 8 MB.');
}

// Allowed MIME types (verified via finfo, not Content-Type header)
$allowed_mimes = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/gif'  => 'gif',
    'image/webp' => 'webp',
    'image/svg+xml' => 'svg',
];

$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($file['tmp_name']);

if (!array_key_exists($mime, $allowed_mimes)) {
    json_err("Tip de fișier nepermis ({$mime}). Sunt acceptate: JPEG, PNG, GIF, WebP, SVG.");
}

// SVG extra check: must not contain script tags
if ($mime === 'image/svg+xml') {
    $svg_content = file_get_contents($file['tmp_name']);
    if (preg_match('/<script/i', $svg_content)) {
        json_err('SVG-ul conține scripturi și nu poate fi încărcat din motive de securitate.');
    }
}

// Build upload path
$upload_base = __DIR__ . '/../assets/uploads';
$sub_dir     = date('Y-m');
$upload_dir  = $upload_base . '/' . $sub_dir;

if (!is_dir($upload_dir)) {
    if (!mkdir($upload_dir, 0755, true)) {
        json_err('Nu se poate crea directorul de upload.', 500);
    }
}

// Generate unique filename
$ext        = $allowed_mimes[$mime];
$original   = pathinfo($file['name'], PATHINFO_FILENAME);
$safe_name  = preg_replace('/[^a-z0-9\-_]/', '', mb_strtolower(str_replace(' ', '-', $original)));
$safe_name  = $safe_name ?: 'image';
$filename   = $safe_name . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$dest_path  = $upload_dir . '/' . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest_path)) {
    json_err('Nu se poate muta fișierul la destinație.', 500);
}

// Log to media_uploads
try {
    db()->prepare("INSERT INTO media_uploads (filename, filepath, filesize, mime_type, uploaded_by)
        VALUES (?, ?, ?, ?, ?)")
        ->execute([
            $filename,
            "/assets/uploads/{$sub_dir}/{$filename}",
            $file['size'],
            $mime,
            current_admin()['id'],
        ]);
} catch (\PDOException $e) {
    // Non-fatal: file is saved, just DB logging failed
}

$public_path = "/assets/uploads/{$sub_dir}/{$filename}";
echo json_encode([
    'ok'   => true,
    'path' => $public_path,
    'name' => $filename,
    'size' => $file['size'],
]);
