<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Pauline's Kitchen</title>
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
            <h2 class="auth-title">Forgot Password?</h2>
            <p class="auth-sub">Enter your email and we'll send you a reset link.</p>
            <?php if (isset($_GET['sent'])): ?>
                <div class="auth-alert success">✅ Reset link sent! Check your email.</div>
            <?php endif; ?>
            <?php if (isset($_GET['error'])): ?>
                <?php if ($_GET['error'] === 'mail'): ?>
                    <div class="auth-alert error">❌ Failed to send reset link. Please try again later.</div>
                <?php else: ?>
                    <div class="auth-alert error">❌ Email not found in our records.</div>
                <?php endif; ?>
            <?php endif; ?>
            <form action="auth/forgot_handler.php" method="POST" class="auth-form">
                <div class="auth-field">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i class="icofont-email"></i>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Send Reset Link →</button>
            </form>
            <p class="auth-switch"><a href="login.php">← Back to Login</a></p>
        </div>
    </div>
</body>
</html>