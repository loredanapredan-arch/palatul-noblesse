<?php
require_once __DIR__ . '/config/db.php';
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false]);
    exit;
}

$name    = trim(strip_tags($_POST['name']    ?? ''));
$email   = trim(strip_tags($_POST['email']   ?? ''));
$phone   = trim(strip_tags($_POST['phone']   ?? ''));
$type    = trim(strip_tags($_POST['type']    ?? ''));
$date    = trim(strip_tags($_POST['date']    ?? ''));
$guests  = (int)($_POST['guests'] ?? 0);
$message = trim(strip_tags($_POST['message'] ?? ''));

if (!$name || !$email || !$phone) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Completați câmpurile obligatorii.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['ok' => false, 'error' => 'Email invalid.']);
    exit;
}

try {
    $stmt = db()->prepare('
        INSERT INTO contact_requests (name, email, phone, event_type, event_date, guests, message)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ');
    $stmt->execute([
        $name, $email, $phone, $type,
        $date ?: null,
        $guests ?: null,
        $message
    ]);
    echo json_encode(['ok' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Eroare server.']);
}
