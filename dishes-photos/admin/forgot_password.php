<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password – Admin | Paulines' Kitchen</title>
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
        .input-wrap i {
          font-size: 18px;
          color: #7ea16b;
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
          text-align: left;
        }
        .auth-alert.error {
          background: #f8d7da;
          color: #70161e;
          border: 1px solid #70161e;
        }
        .auth-alert.success {
          background: #d4edda;
          color: #1c3144;
          border: 1px solid #7ea16b;
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
        .back-link {
          display: block;
          text-align: center;
          margin-top: 16px;
          color: #7ea16b;
          text-decoration: none;
          font-size: 13px;
          font-weight: 600;
        }
        .back-link:hover {
          color: #1c3144;
        }
    </style>
</head>
<body class="admin-login-page">
    <div class="admin-login-card">
        <img src="../images/logoo.png" alt="Paulines Kitchen">
        <h2>Paulines' Kitchen</h2>
        <p>Reset Admin Password</p>

        <?php if (isset($_GET['sent'])): ?>
            <div class="auth-alert success"><i class="icofont-check"></i> Reset link sent! Check your email inbox.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php if ($_GET['error'] === 'notfound'): ?>
                <div class="auth-alert error"><i class="icofont-close"></i> That email is not registered as admin.</div>
            <?php else: ?>
                <div class="auth-alert error"><i class="icofont-close"></i> Failed to send email. Please try again.</div>
            <?php endif; ?>
        <?php endif; ?>

        <form action="auth/admin_forgot_handler.php" method="POST">
            <div class="field-wrap">
                <label>Admin Email Address</label>
                <div class="input-wrap">
                    <i class="icofont-email"></i>
                    <input type="email" name="email" placeholder="Admin's Email" required>
                </div>
            </div>
            <button type="submit" class="auth-btn">Send Reset Link →</button>
        </form>

        <a href="login.php" class="back-link">← Back to Admin Login</a>
    </div>
</body>
</html>
