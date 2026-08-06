<?php
session_start();
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: ../login.php'); exit(); }
$username = trim($_POST['username']);
$password = trim($_POST['password']);
$stmt = $conn->prepare("SELECT * FROM admins WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();
if (!$row || !password_verify($password, $row['password'])) {
    header('Location: ../login.php?error=1');
    exit();
}
$_SESSION['admin_id']   = $row['id'];
$_SESSION['admin_name'] = $row['name'];
header('Location: ../dashboard.php');
exit();
