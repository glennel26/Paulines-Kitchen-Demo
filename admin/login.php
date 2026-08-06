<?php
session_start();
if (isset($_SESSION['admin_id'])) { header('Location: dashboard.php'); exit(); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – Pauline's Kitchen</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="../images/icons/icofont/icofont.min.css">
    <link rel="icon" type="image/x-icon" href="../images/logoo.png">
    <style>
        .input-wrap {
          display: flex;
          align-items: center;
          gap: 10px;
          border: 1.5px solid #c3d898;
          border-radius: 8px;
          padding: 11px 14px;
          background: #f9fdf5;
          transition: border-color 0.2s;
          margin-bottom: 16px;
        }
        .input-wrap:focus-within {
          border-color: #7ea16b;
          background: #fff;
        }
        .input-wrap input {
          border: none;
          outline: none;
          background: transparent;
          font-family: "Raleway", sans-serif;
          font-size: 14px;
          color: #1c3144;
          width: 100%;
        }
        .input-wrap label {
          font-size: 12px;
          font-weight: 700;
          color: #596f62;
          display: block;
          margin-bottom: 6px;
          text-align: left;
        }
        .auth-btn {
          width: 100%;
          padding: 14px;
          background: #7ea16b;
          color: #fff;
          border: none;
          border-radius: 8px;
          font-family: "Raleway", sans-serif;
          font-size: 15px;
          font-weight: 700;
          cursor: pointer;
          transition: background 0.2s;
        }
        .auth-btn:hover {
          background: #1c3144;
        }
        .auth-alert {
          padding: 12px 16px;
          border-radius: 8px;
          font-size: 14px;
          font-weight: 600;
          margin-bottom: 18px;
          background: #f8d7da;
          color: #70161e;
          border: 1px solid #70161e;
          text-align: left;
        }
        .field-wrap {
          text-align: left;
          margin-bottom: 16px;
        }
        .field-wrap label {
          font-size: 13px;
          font-weight: 700;
          color: #1c3144;
          display: block;
          margin-bottom: 7px;
        }

        /* Background food image — overrides admin.css body background */
        body.admin-login-page {
          background-image: url("../images/loginbg.png") !important;
          background-size: cover !important;
          background-position: center !important;
          background-attachment: fixed !important;
          background-color: #1c3144 !important;
          position: relative;
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
        }
        body.admin-login-page::before {
          content: "";
          position: fixed;
          inset: 0;
          background: rgba(28, 49, 68, 0.65);
          backdrop-filter: blur(2px);
          z-index: 0;
        }
        .admin-login-card {
          position: relative;
          z-index: 1;
        }
    </style>
</head>
<body class="admin-login-page">
    <div class="admin-login-card">
        <img src="../images/logoo.png" alt="Paulines Kitchen">
        <h2>Paulines' Kitchen</h2>
        <p>Admin Panel — Staff Only</p>

        <?php if (isset($_GET['error'])): ?>
            <div class="auth-alert">❌ Invalid username or password.</div>
        <?php endif; ?>

        <form action="auth/admin_login_handler.php" method="POST">
            <div class="field-wrap">
                <label>Username</label>
                <div class="input-wrap">
                    <input type="text" name="username" placeholder="admin" required autocomplete="off">
                </div>
            </div>
            <div class="field-wrap">
                <label>Password</label>
                <div class="input-wrap">
                    <input type="password" name="password" placeholder="••••••••" required id="apwd">
                    <button type="button" onclick="togglePwd('apwd',this)" style="background:none;border:none;cursor:pointer;color:#aaa;font-size:15px;">👁</button>
                </div>
            </div>
            <button type="submit" class="auth-btn">Sign In →</button>
        </form>
        <p style="text-align:center;margin-top:14px;font-size:13px;">
            <a href="forgot_password.php" style="color:#7EA16B;text-decoration:none;font-weight:600;">Forgot password?</a>
            <span style="color:#666;margin:0 8px;">|</span>
            <a href="../user/login.php" style="color:#7EA16B;text-decoration:none;font-weight:600;">Customer Login</a>
        </p>
    </div>
    <script>
    function togglePwd(id, btn) {
        const i = document.getElementById(id);
        i.type = i.type === 'password' ? 'text' : 'password';
        btn.textContent = i.type === 'password' ? '👁' : '👁';
    }
    </script>
</body>
</html>
