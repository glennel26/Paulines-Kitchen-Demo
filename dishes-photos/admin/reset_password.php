<?php
session_start();
$conn = new mysqli('localhost', 'root', '', 'paulines_kitchen');
if ($conn->connect_error) die("DB error");

$token = trim($_GET['token'] ?? '');
$valid = false;
$msg = '';
$msgType = '';

if (!$token) {
    header('Location: login.php'); exit();
}

// Validate token
$stmt = $conn->prepare("SELECT * FROM admin_resets WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->bind_param('s', $token);
$stmt->execute();
$reset = $stmt->get_result()->fetch_assoc();
$valid = (bool)$reset;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $valid) {
    $pw  = trim($_POST['password']);
    $pw2 = trim($_POST['confirm_password']);

    if (strlen($pw) < 6) {
        $msg = 'Password must be at least 6 characters.';
        $msgType = 'error';
    } elseif ($pw !== $pw2) {
        $msg = 'Passwords do not match.';
        $msgType = 'error';
    } else {
        $hash = password_hash($pw, PASSWORD_DEFAULT);
        // Update all admins (there's only one)
        $upd = $conn->prepare("UPDATE admins SET password = ?");
        $upd->bind_param('s', $hash);
        $upd->execute();
        // Mark token used
        $conn->prepare("UPDATE admin_resets SET used = 1 WHERE token = ?")->execute() || $conn->query("UPDATE admin_resets SET used=1 WHERE token='" . $conn->real_escape_string($token) . "'");
        $upd2 = $conn->prepare("UPDATE admin_resets SET used = 1 WHERE token = ?");
        $upd2->bind_param('s', $token);
        $upd2->execute();
        $msg = 'Password updated successfully! You can now log in.';
        $msgType = 'success';
        $valid = false; // hide form
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password – Admin | Paulines' Kitchen</title>
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
        .input-wrap button {
          background: none;
          border: none;
          cursor: pointer;
          font-size: 16px;
          color: #aaa;
          padding: 0;
        }
        .input-wrap button:hover {
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
        <p>Set New Admin Password</p>

        <?php if ($msg): ?>
            <div class="auth-alert <?= $msgType ?>">
                <i class="icofont-<?= $msgType === 'success' ? 'check' : 'close' ?>"></i>
                <?= htmlspecialchars($msg) ?>
            </div>
        <?php endif; ?>

        <?php if (!$valid && !$msg): ?>
            <div class="auth-alert error">
                <i class="icofont-close"></i> This reset link is invalid or has expired.
            </div>
            <a href="forgot_password.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;margin-top:8px;">Request a New Link</a>
        <?php endif; ?>

        <?php if ($valid): ?>
        <form method="POST">
            <div class="field-wrap">
                <label>New Password</label>
                <div class="input-wrap">
                    <i class="icofont-lock"></i>
                    <input type="password" name="password" id="pw1" placeholder="At least 6 characters" required>
                    <button type="button" onclick="togglePwd('pw1',this)"><i class="icofont-eye"></i></button>
                </div>
            </div>
            <div class="field-wrap">
                <label>Confirm New Password</label>
                <div class="input-wrap">
                    <i class="icofont-lock"></i>
                    <input type="password" name="confirm_password" id="pw2" placeholder="Repeat password" required>
                    <button type="button" onclick="togglePwd('pw2',this)"><i class="icofont-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="auth-btn">Update Password →</button>
        </form>
        <?php endif; ?>

        <?php if ($msgType === 'success'): ?>
            <a href="login.php" class="auth-btn" style="display:block;text-align:center;text-decoration:none;margin-top:12px;background:#1C3144;">Go to Admin Login →</a>
        <?php endif; ?>

        <a href="login.php" class="back-link">← Back to Admin Login</a>
    </div>

    <script>
    function togglePwd(id, btn) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
        btn.innerHTML = input.type === 'password' ? '<i class="icofont-eye"></i>' : '<i class="icofont-eye-blocked"></i>';
    }
    </script>
</body>
</html>
