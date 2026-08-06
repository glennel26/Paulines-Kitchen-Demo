<?php
error_reporting(0);
header('Content-Type: application/json');
require_once 'config/db.php';

$ref = trim($_GET['ref'] ?? '');
if (!$ref) { echo json_encode(['status' => 'not_found']); exit; }

$stmt = $conn->prepare("SELECT status FROM orders WHERE reference = ? LIMIT 1");
$stmt->bind_param('s', $ref);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

$raw = strtolower(trim($row['status'] ?? ''));

$map = [
    'pending payment'  => 'pending',
    'paid & preparing' => 'preparing',
    'ready'            => 'ready',
    'completed'        => 'completed',
    'cancelled'        => 'cancelled',
];

echo json_encode(['status' => $map[$raw] ?? 'pending']);
exit;
?>