<?php
require_once '../../includes/config.php';
require_once '../../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

// Ürün aktif/pasif durumu -> products.edit. Önceden hiç auth yoktu:
// herhangi bir istemci JSON gövdesiyle ürünü satıştan çıkarabiliyordu.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isLoggedIn() || !hasPermission('products.edit')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Yetkiniz yok.']);
    exit;
}

$data = json_decode((string)file_get_contents('php://input'), true);

if (!is_array($data) || !isset($data['id']) || !isset($data['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Eksik veri']);
    exit;
}

$db = new Database();

$id     = (int)$data['id'];
$status = (int)$data['status'];

// products.status tinyint(1): yalnız 0/1
if ($id <= 0 || !in_array($status, [0, 1], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Geçersiz veri']);
    exit;
}

$prod = $db->query("SELECT id FROM products WHERE id = ? LIMIT 1", [$id])->fetch();
if (!$prod) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Ürün bulunamadı.']);
    exit;
}

$db->query("UPDATE products SET status = ? WHERE id = ?", [$status, $id]);

echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);