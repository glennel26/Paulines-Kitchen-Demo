<?php
session_start();
require_once '../config/db.php';
$token   = trim($_GET['token'] ?? '');
$invalid = false;
if (!isset($_GET['success'])) {
    if (empty($token)) {
        header("Location: forgot_password.php");
        exit();
    }
    $stmt = $conn->prepare("SELECT email FROM password_resets WHERE token = ? AND expires_at > UTC_TIMESTAMP()");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $invalid = true;
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Pauline's Kitchen</title>
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="css/auth.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../images/icons/icofont/icofont.min.css">
    <link rel="icon" type="image/x-icon" href="../images/logoo.png">
</head>
<body class="auth-page">
    <nav class="navbar">
        <div class="nav-logo">
            <img src="../images/logoo.png" alt="Paulines Kitchen" class="logo-img">
        </div>
        <ul class="nav-links">
            <li><a href="../index.php">Home</a></li>
            <li><a href="../menu.php">Menu</a></li>
            <li><a href="../about.php">About</a></li>
            <li><a href="../cart.php">Order</a></li>
            <li><a href="../index.php#faqs">FAQs</a></li>
        </ul>
        <div class="nav-right">
            <a href="../cart.php" class="cart-icon">
                <i class="icofont-food-basket"></i>
                <span class="badge"><?= count($_SESSION['cart'] ?? []) ?></span>
            </a>
            <?php if (isset($_SESSION['user_name'])): ?>
                <span style="color:#fff; font-weight:600;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
                <a href="auth/logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.php" class="login-btn">Login</a>
            <?php endif; ?>
        </div>
    </nav>
    <div class="auth-wrapper">
        <div class="auth-card">
            <div class="auth-logo">
                <img src="../images/logoo.png" alt="Paulines Kitchen">
            </div>
            <h2 class="auth-title">Reset Password</h2>
            <p class="auth-sub">Enter your new password below.</p>
            <?php if ($invalid): ?>
                <div class="auth-alert error">❌ This reset link is invalid or has expired.</div>
                <p class="auth-switch"><a href="forgot_password.php">Request a new link</a></p>
            <?php elseif (isset($_GET['success'])): ?>
                <div class="auth-alert success">✅ Password updated! You can now log in.</div>
                <p class="auth-switch"><a href="login.php">Go to Login</a></p>
            <?php elseif (isset($_GET['error'])): ?>
                <div class="auth-alert error">❌ Passwords do not match. Try again.</div>
                <form action="auth/reset_handler.php" method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="auth-field">
                        <label>New Password</label>
                        <div class="input-wrap">
                            <i class="icofont-lock"></i>
                            <input type="password" name="password" placeholder="Enter new password" required>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label>Confirm New Password</label>
                        <div class="input-wrap">
                            <i class="icofont-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn">Update Password →</button>
                </form>
            <?php else: ?>
                <form action="auth/reset_handler.php" method="POST" class="auth-form">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
                    <div class="auth-field">
                        <label>New Password</label>
                        <div class="input-wrap">
                            <i class="icofont-lock"></i>
                            <input type="password" name="password" placeholder="Enter new password" required>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label>Confirm New Password</label>
                        <div class="input-wrap">
                            <i class="icofont-lock"></i>
                            <input type="password" name="confirm_password" placeholder="Repeat new password" required>
                        </div>
                    </div>
                    <button type="submit" class="auth-btn">Update Password →</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
<script>
function togglePwd(id, btn) {
    const input = document.getElementById(id);
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}
</script>
</body>
</html>