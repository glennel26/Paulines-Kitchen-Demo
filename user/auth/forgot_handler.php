<?php
require '../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../../config/db.php';
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../forgot_password.php");
    exit();
}
$email = strtolower(trim($_POST['email']));
$check = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = ?");
$check->bind_param("s", $email);
$check->execute();
$check->store_result();
if ($check->num_rows === 0) {
    header("Location: ../forgot_password.php?error=1");
    exit();
}
$check->close();
$token   = bin2hex(random_bytes(32));
$expires = gmdate('Y-m-d H:i:s', time() + 3600);
$del = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
$del->bind_param("s", $email);
$del->execute();
$del->close();
$stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $email, $token, $expires);
$stmt->execute();
$stmt->close();
$conn->close();
$protocol   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$reset_link = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/user/reset_password.php?token=' . $token;
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'glennelcruz26@gmail.com';
    $mail->Password   = 'sarq axdh tcdu seaa';
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom('glennelcruz26@gmail.com', "Pauline's Kitchen");
    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = "Reset your Pauline's Kitchen password";
    $mail->Body    = "
        <div style='font-family: Raleway, sans-serif; max-width: 500px; margin: 0 auto;'>
            <h2 style='color: #1C3144;'>Password Reset</h2>
            <p>We received a request to reset your password. Click the button below to proceed:</p>
            <a href='{$reset_link}' style='display:inline-block; background:#7EA16B; color:#fff; padding:14px 28px; border-radius:8px; text-decoration:none; font-weight:700; margin: 16px 0;'>Reset Password</a>
            <p style='color:#999; font-size:13px;'>This link will expire in 1 hour. If you didn't request this, just ignore this email.</p>
        </div>
    ";
    $mail->send();
    header("Location: ../forgot_password.php?sent=1");
    exit();
} catch (Exception $e) {
    error_log('PHPMailer error in forgot_password: ' . $mail->ErrorInfo);
    header("Location: ../forgot_password.php?error=mail");
    exit();
}
?>