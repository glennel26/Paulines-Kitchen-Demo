<?php
require_once '../../config/db.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../forgot_password.php");
    exit();
}
$token            = trim($_POST['token']);
$password         = trim($_POST['password']);
$confirm_password = trim($_POST['confirm_password']);
if ($password !== $confirm_password) {
    header("Location: ../reset_password.php?token=$token&error=1");
    exit();
}
$stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > UTC_TIMESTAMP()");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: ../forgot_password.php?error=1");
    exit();
}
$row   = $result->fetch_assoc();
$email = $row['email'];
$stmt->close();
$hashed = password_hash($password, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$update->bind_param("ss", $hashed, $email);
$update->execute();
$update->close();
$del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
$del->bind_param("s", $token);
$del->execute();
$del->close();
$conn->close();
header("Location: ../reset_password.php?success=1");
exit();
?>