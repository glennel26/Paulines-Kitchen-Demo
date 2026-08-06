<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Pauline's Kitchen</title>
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
            <span class="nav-brand">Paulines' Kitchen</span>
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
            <h2 class="auth-title">Create an Account</h2>
            <p class="auth-sub">Ready to eat?</p>
            <?php if (isset($_GET['error'])): ?>
                <div class="auth-alert error">❌ <?= htmlspecialchars($_GET['error']) ?></div>
            <?php endif; ?>
            <form action="auth/register_handler.php" method="POST" class="auth-form">
                <div class="auth-row">
                    <div class="auth-field">
                        <label>First Name</label>
                        <div class="input-wrap">
                            <i class="icofont-user"></i>
                            <input type="text" name="first_name" placeholder="Juan" required>
                        </div>
                    </div>
                    <div class="auth-field">
                        <label>Last Name</label>
                        <div class="input-wrap">
                            <i class="icofont-user"></i>
                            <input type="text" name="last_name" placeholder="dela Cruz" required>
                        </div>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Email Address</label>
                    <div class="input-wrap">
                        <i class="icofont-email"></i>
                        <input type="email" name="email" placeholder="you@example.com" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Phone Number</label>
                    <div class="input-wrap">
                        <i class="icofont-phone"></i>
                        <input type="text" name="phone" placeholder="09XXXXXXXXX" maxlength="11" pattern="^09\d{9}$" title="Must be a valid 11-digit mobile number starting with 09" oninput="this.value = this.value.replace(/[^0-9]/g, '')" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Password</label>
                    <div class="input-wrap">
                        <i class="icofont-lock"></i>
                        <input type="password" name="password" placeholder="Create a password" required>
                    </div>
                </div>
                <div class="auth-field">
                    <label>Confirm Password</label>
                    <div class="input-wrap">
                        <i class="icofont-lock"></i>
                        <input type="password" name="confirm_password" placeholder="Repeat your password" required>
                    </div>
                </div>
                <button type="submit" class="auth-btn">Create Account →</button>
            </form>
            <p class="auth-switch">Already have an account? <a href="login.php">Sign In</a></p>
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