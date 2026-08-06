<?php
session_start();
require_once '../../user/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../forgot_password.php'); exit();
}
require_once '../../config/db.php';
$email = trim($_POST['email']);
$row = $conn->query("SELECT * FROM admins WHERE email = '" . $conn->real_escape_string($email) . "' LIMIT 1")->fetch_assoc();
if (!$row) {
    header('Location: ../forgot_password.php?error=notfound'); exit();
}
$token = bin2hex(random_bytes(32));
$expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
$conn->query("DELETE FROM admin_resets WHERE 1");
$stmt = $conn->prepare("INSERT INTO admin_resets (token, expires_at) VALUES (?, ?)");
$stmt->bind_param('ss', $token, $expires);
$stmt->execute();
$conn->close();
$protocol  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$resetLink = $protocol . '://' . $_SERVER['HTTP_HOST'] . '/admin/reset_password.php?token=' . $token;
try {
    $mail = new PHPMailer(true);
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = 'glennelcruz26@gmail.com';
    $mail->Password   = 'sarq axdh tcdu seaa';
    $mail->SMTPSecure = 'tls';
    $mail->Port       = 587;
    $mail->CharSet    = 'UTF-8';
    $mail->setFrom('glennelcruz26@gmail.com', "Paulines' Kitchen");
    $mail->addAddress($email, $row['name']);
    $mail->isHTML(true);
    $mail->Subject = 'Admin Password Reset – Paulines\' Kitchen';
    $mail->Body = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F5F0E8;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F0E8;padding:40px 20px;">
    <tr><td align="center">
    <table width="520" cellpadding="0" cellspacing="0" style="max-width:520px;width:100%;">
        <tr><td style="background:#1C3144;border-radius:14px 14px 0 0;padding:28px 36px;text-align:center;">
            <h1 style="margin:0;font-size:22px;font-weight:800;color:#fff;">Paulines\' Kitchen</h1>
            <p style="margin:6px 0 0;color:#C3D898;font-size:13px;">Admin Panel</p>
        </td></tr>
        <tr><td style="height:4px;background:#7EA16B;"></td></tr>
        <tr><td style="background:#fff;padding:32px 36px;">
            <p style="font-size:15px;color:#1C3144;margin:0 0 8px;">Hi <strong>' . htmlspecialchars($row['name']) . '</strong>,</p>
            <p style="font-size:15px;color:#596F62;margin:0 0 24px;line-height:1.6;">We received a request to reset your admin password. Click the button below to set a new password. This link expires in <strong>1 hour</strong>.</p>
            <div style="text-align:center;margin:24px 0;">
                <a href="' . $resetLink . '" style="display:inline-block;background:#7EA16B;color:#fff;text-decoration:none;padding:14px 32px;border-radius:8px;font-weight:700;font-size:15px;">Reset My Password →</a>
            </div>
            <p style="font-size:13px;color:#aaa;margin:20px 0 0;text-align:center;">If you did not request this, please ignore this email. Your password will not change.</p>
        </td></tr>
        <tr><td style="height:4px;background:#70161E;border-radius:0 0 14px 14px;"></td></tr>
        <tr><td style="padding:16px;text-align:center;">
            <p style="font-size:12px;color:#aaa;margin:0;">&copy; 2026 Paulines\' Kitchen &nbsp;&middot;&nbsp; Admin Portal</p>
        </td></tr>
    </table>
    </td></tr>
    </table>
    </body></html>';
    $mail->send();
    header('Location: ../forgot_password.php?sent=1'); exit();
} catch (Exception $e) {
    error_log('Admin reset mail error: ' . $e->getMessage());
    header('Location: ../forgot_password.php?error=mail'); exit();
}
