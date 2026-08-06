<?php
session_start();
require_once '../../config/db.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../login.php");
    exit();
}
$email    = trim($_POST['email']);
$password = trim($_POST['password']);
$stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    header("Location: ../login.php?error=1");
    exit();
}
$user_row = $result->fetch_assoc();
if (!password_verify($password, $user_row['password'])) {
    header("Location: ../login.php?error=1");
    exit();
}
$_SESSION['user_id']    = $user_row['id'];
$_SESSION['user_name']  = $user_row['first_name'];
$_SESSION['user_email'] = $user_row['email'];
$_SESSION['role']       = $user_row['role'];
if ($user_row['role'] === 'admin') {
    $_SESSION['admin_id']   = $user_row['id'];
    $_SESSION['admin_name'] = $user_row['first_name'] . ' ' . $user_row['last_name'];
}
$stmt->close();
$conn->close();
if ($user_row['role'] === 'admin') {
    header("Location: ../../admin/dashboard.php");
} else {
    header("Location: ../../index.php");
}
exit();
?>