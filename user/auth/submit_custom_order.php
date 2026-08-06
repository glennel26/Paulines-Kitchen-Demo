<?php
session_start();
header('Content-Type: application/json');
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
$name    = trim($_POST['name'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$dish    = trim($_POST['dish'] ?? '');
$pax     = intval($_POST['pax'] ?? 0);
$qty     = intval($_POST['qty'] ?? 0);
if (empty($name) || empty($contact) || empty($dish) || $pax <= 0 || $qty <= 0) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit();
}
$user_id = $_SESSION['user_id'] ?? 0;
$email = '';
if ($user_id > 0) {
    $u_stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
    $u_stmt->bind_param('i', $user_id);
    $u_stmt->execute();
    $u_res = $u_stmt->get_result()->fetch_assoc();
    if ($u_res) {
        $email = $u_res['email'];
    }
}
$stmt = $conn->prepare("
    INSERT INTO custom_requests (full_name, email, contact, dish_name, pax, quantity, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error.']);
    exit();
}
$stmt->bind_param('ssssii', $name, $email, $contact, $dish, $pax, $qty);
if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to save request.']);
}
$stmt->close();
$conn->close();
?>
