<?php
require_once '../../includes/config.php';
$db = new Database();

$data = json_decode(file_get_contents('php://input'), true);

if(isset($data['id']) && isset($data['status'])) {
   $id = (int)$data['id'];
   $status = (int)$data['status'];
   
   $db->query("UPDATE products SET status = ? WHERE id = ?", [$status, $id]);
   echo json_encode(['success' => true]);
}
?>