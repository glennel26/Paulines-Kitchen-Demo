<?php
session_start();
require_once '../../config/db.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../register.php");
    exit();
}
$first_name       = trim($_POST['first_name']);
$last_name        = trim($_POST['last_name']);
$email            = trim($_POST['email']);
$phone            = trim($_POST['phone']);
$password         = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
if ($password !== $confirm_password) {
    header("Location: ../register.php?error=Passwords+do+not+match");
    exit();
}
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    header("Location: ../register.php?error=Email+already+registered");
    exit();
}
$check->close();
$hashed = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (first_name, last_name, email, phone, password, role, created_at) VALUES (?, ?, ?, ?, ?, 'customer', NOW())");
$stmt->bind_param("sssss", $first_name, $last_name, $email, $phone, $hashed);
if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    header("Location: ../login.php?registered=1");
    exit();
} else {
    $stmt->close();
    $conn->close();
    header("Location: ../register.php?error=Something+went+wrong");
    exit();
}
?>
