<?php
session_start();

if (empty($_SESSION['cart'])) {
    header('Location: menu.php');
    exit();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: user/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  try {

    require_once __DIR__ . '/config/db.php';

    if (!defined('PAYMONGO_SECRET_KEY')) define('PAYMONGO_SECRET_KEY', 'SECRET_KEY');
    $_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    if (!defined('SITE_URL')) define('SITE_URL', $_protocol . '://' . $_SERVER['HTTP_HOST']);

    $user_id        = $_SESSION['user_id'];
    $fulfillment    = $_POST['fulfillment']    ?? 'pickup';
    $fullname       = trim($_POST['fullname']  ?? '');
    $phone          = trim($_POST['phone']     ?? '');
    $email          = trim($_POST['email']     ?? '');
    $datetime       = trim($_POST['datetime']  ?? '');
    $notes          = trim($_POST['notes']     ?? '');
    $address        = trim($_POST['address']   ?? '');
    $landmark       = trim($_POST['landmark']  ?? '');
    $payment_method = $_POST['payment_method'] ?? 'paymongo';

    if (!$fullname || !$phone || !$email || !$datetime) {
        header('Location: checkout.php?error=missing_fields');
        exit();
    }

    $cart_items = $_SESSION['cart'];
    $total_amt  = 0;
    $line_items = [];

    foreach ($cart_items as $item) {
        $unit_price  = ($item['price'] + ($item['rice_addon'] ?? 0));
        $sub         = $unit_price * $item['quantity'];
        $total_amt  += $sub;

        $line_items[] = [
            'currency'    => 'PHP',
            'amount'      => (int) round($unit_price * 100),
            'name'        => $item['name'] . (!empty($item['pax']) ? ' (' . $item['pax'] . ' pax)' : ''),
            'quantity'    => (int) $item['quantity'],
            'description' => ($item['rice_addon'] ?? 0) > 0 ? 'Includes rice add-on' : '',
        ];
    }

    $init_status = ($payment_method === 'cod') ? 'Pending' : 'Pending Payment';

    $stmt = $conn->prepare("
        INSERT INTO orders
        (reference, user_id, fullname, phone, email, fulfillment, target_datetime, notes, address, landmark, total, status, created_at)
        VALUES ('TEMP', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    if (!$stmt) {
        error_log('Order prepare failed: ' . $conn->error);
        header('Location: checkout.php?error=db_error');
        exit();
    }
    $stmt->bind_param('issssssssds',
        $user_id, $fullname, $phone, $email,
        $fulfillment, $datetime, $notes, $address, $landmark,
        $total_amt, $init_status
    );
    if (!$stmt->execute()) {
        error_log('Order INSERT failed: ' . $stmt->error);
        header('Location: checkout.php?error=db_error');
        exit();
    }
    $order_id  = $conn->insert_id;
    $reference = 'PK-' . str_pad($order_id, 5, '0', STR_PAD_LEFT);
    $conn->query("UPDATE orders SET reference = '$reference' WHERE id = $order_id");

    $istmt = $conn->prepare("INSERT INTO order_items (order_id, item_name, pax, quantity, unit_price, rice_addon) VALUES (?, ?, ?, ?, ?, ?)");
    if ($istmt) {
        $name = $pax = $qty = $price = $rice = null;
        $istmt->bind_param('issidd', $order_id, $name, $pax, $qty, $price, $rice);
        foreach ($cart_items as $item) {
            $name  = $item['name'];
            $pax   = $item['pax'];
            $qty   = $item['quantity'];
            $price = $item['price'];
            $rice  = $item['rice_addon'] ?? 0;
            $istmt->execute();
        }
    }

    $_SESSION['last_order_ref'] = $reference;

    if ($payment_method === 'cod') {
        unset($_SESSION['cart']);
        header('Location: order-success.php?ref=' . $reference);
        exit();
    }

    $payload = json_encode([
        'data' => [
            'attributes' => [
                'billing'              => ['name' => $fullname, 'phone' => $phone, 'email' => $email],
                'line_items'           => $line_items,
                'payment_method_types' => ['gcash', 'paymaya', 'card'],
                'success_url'          => SITE_URL . '/order-success.php?ref=' . $reference,
                'cancel_url'           => SITE_URL . '/checkout.php?cancelled=1',
                'description'          => 'Paulines Kitchen Order ' . $reference,
                'reference_number'     => $reference,
                'metadata'             => ['order_id' => $order_id, 'reference' => $reference],
            ]
        ]
    ]);

    $ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Content-Type: application/json',
            'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
        ],
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response  = curl_exec($ch);
    $curl_err  = curl_error($ch);
    curl_close($ch);

    if ($curl_err || !$response) {
        error_log('PayMongo cURL error: ' . $curl_err);
        header('Location: checkout.php?error=payment_init_failed');
        exit();
    }

    $result = json_decode($response, true);
    if (!isset($result['data']['attributes']['checkout_url'])) {
        error_log('PayMongo response error: ' . $response);
        header('Location: checkout.php?error=payment_init_failed');
        exit();
    }

    $session_id = $result['data']['id'];
    $sstmt = $conn->prepare("UPDATE orders SET paymongo_session_id = ? WHERE id = ?");
    if ($sstmt) {
        $sstmt->bind_param('si', $session_id, $order_id);
        $sstmt->execute();
    }

    unset($_SESSION['cart']);
    header('Location: ' . $result['data']['attributes']['checkout_url']);
    exit();

  } catch (Throwable $e) {
    die('<pre style="background:#fee;padding:20px;font-size:14px;"><strong>Checkout Error:</strong> '
        . htmlspecialchars($e->getMessage()) . "\n"
        . 'File: ' . htmlspecialchars($e->getFile()) . ' line ' . $e->getLine()
        . '</pre>');
  }
}
$cart  = $_SESSION['cart'];
$total = 0;
foreach ($cart as $item) {
    $total += ($item['price'] + ($item['rice_addon'] ?? 0)) * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout – Pauline's Kitchen</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="images/icons/icofont/icofont.min.css">
    <link rel="icon" type="image/x-icon" href="images/logoo.png">
    <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        }

        body {
          font-family: "Raleway", sans-serif;
          background: #f5f0e8;
          color: #1c3144;
        }

        .navbar {
          width: 100%;
          background: #7ea16b;
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 14px 120px;
          position: fixed;
          top: 0;
          z-index: 999;
          border-bottom: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo-img {
          height: 70px;
          width: 70px;
        }

        .nav-links {
          list-style: none;
          display: flex;
          gap: 36px;
        }

        .nav-links a {
          text-decoration: none;
          color: #fff;
          font-size: 16px;
          font-weight: 500;
        }

        .nav-links a:hover {
          color: #1c3144;
        }

        .nav-right {
          display: flex;
          align-items: center;
          gap: 22px;
        }

        .cart-icon {
          color: #fff;
          text-decoration: none;
          position: relative;
          display: flex;
          align-items: center;
          font-size: 42px;
        }

        .badge {
          position: absolute;
          top: -7px;
          right: -10px;
          background: #70161e;
          color: #fff;
          font-size: 10px;
          width: 17px;
          height: 17px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
        }

        .login-btn {
          text-decoration: none;
          background: #fff;
          color: #7ea16b;
          padding: 9px 24px;
          border-radius: 4px;
          font-size: 15px;
          font-weight: 700;
        }

        .login-btn:hover {
          background: #1c3144;
          color: #fff;
        }

        .page-header {
          background: #1c3144;
          padding: 140px 80px 50px;
        }

        .page-header h1 {
          font-size: 52px;
          font-weight: 800;
          color: #fff;
        }

        .page-header h1 span {
          color: #c3d898;
        }

        .header-divider {
          width: 70px;
          height: 4px;
          background: #c3d898;
          margin-top: 14px;
          border-radius: 2px;
        }

        .checkout-wrap {
          max-width: 1200px;
          margin: 50px auto 80px;
          padding: 0 40px;
          display: grid;
          grid-template-columns: 1fr 420px;
          gap: 40px;
          align-items: start;
        }

        .card {
          background: #fff;
          border-radius: 14px;
          padding: 32px;
          margin-bottom: 24px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
          border: 1px solid rgba(126, 161, 107, 0.15);
        }

        .card:last-child {
          margin-bottom: 0;
        }

        .card-title {
          font-size: 18px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 24px;
          padding-bottom: 14px;
          border-bottom: 2px solid #f5f0e8;
        }

        .fulfillment-options {
          display: flex;
          gap: 14px;
          margin-bottom: 4px;
        }

        .fulfillment-opt {
          flex: 1;
          border: 2px solid #c3d898;
          border-radius: 10px;
          padding: 16px;
          cursor: pointer;
          transition: all 0.2s;
          display: flex;
          align-items: flex-start;
          gap: 12px;
        }

        .fulfillment-opt:has(input:checked) {
          border-color: #7ea16b;
          background: #f4faf0;
        }

        .fulfillment-opt input[type="radio"] {
          margin-top: 3px;
          accent-color: #7ea16b;
          flex-shrink: 0;
        }

        .fulfillment-opt strong {
          display: block;
          font-size: 15px;
          color: #1c3144;
          margin-bottom: 4px;
        }

        .fulfillment-opt span {
          font-size: 12px;
          color: #888;
        }

        .pickup-info {
          background: #f4faf0;
          border: 1px solid #c3d898;
          border-radius: 10px;
          padding: 16px 20px;
          font-size: 14px;
          color: #2e4a2e;
          font-weight: 600;
          margin-top: 16px;
          display: none;
        }

        .pickup-info.show {
          display: block;
        }

        .courier-disclaimer {
          background: #fff8e6;
          border: 1px solid #e6c96e;
          border-radius: 10px;
          padding: 14px 18px;
          font-size: 13px;
          color: #7a5c00;
          margin-top: 16px;
          display: none;
          line-height: 1.6;
        }

        .courier-disclaimer.show {
          display: block;
        }

        .form-group {
          margin-bottom: 18px;
        }

        .form-group label {
          display: block;
          font-size: 13px;
          font-weight: 700;
          color: #1c3144;
          margin-bottom: 7px;
        }

        .form-group label span {
          color: #70161e;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
          width: 100%;
          padding: 12px 16px;
          border: 1.5px solid #c3d898;
          border-radius: 8px;
          font-family: "Raleway", sans-serif;
          font-size: 14px;
          color: #1c3144;
          background: #f9fdf5;
          outline: none;
          transition: border-color 0.2s;
        }

        .form-group input:focus,
        .form-group textarea:focus {
          border-color: #7ea16b;
          background: #fff;
        }

        .form-group textarea {
          resize: vertical;
          min-height: 90px;
        }

        .form-group input.error {
          border-color: #70161e;
        }

        .error-msg {
          font-size: 12px;
          color: #70161e;
          margin-top: 5px;
          display: none;
        }

        .error-msg.show {
          display: block;
        }

        #delivery-fields {
          display: none;
        }

        #delivery-fields.show {
          display: block;
        }

        .summary-item {
          display: flex;
          justify-content: space-between;
          align-items: flex-start;
          padding: 12px 0;
          border-bottom: 1px solid #f0ebe0;
          gap: 10px;
        }

        .summary-item:last-child {
          border-bottom: none;
        }

        .summary-item-name {
          font-size: 14px;
          font-weight: 600;
          color: #1c3144;
        }

        .summary-item-meta {
          font-size: 12px;
          color: #aaa;
          margin-top: 2px;
        }

        .summary-item-price {
          font-size: 14px;
          font-weight: 700;
          color: #70161e;
          white-space: nowrap;
        }

        .summary-divider {
          border: none;
          border-top: 2px solid #f5f0e8;
          margin: 16px 0;
        }

        .summary-row {
          display: flex;
          justify-content: space-between;
          font-size: 15px;
          font-weight: 600;
          margin-bottom: 10px;
          color: #596f62;
        }

        .summary-row.total {
          font-size: 20px;
          font-weight: 800;
          color: #1c3144;
          margin-top: 14px;
          padding-top: 14px;
          border-top: 2px solid #f5f0e8;
        }

        .summary-row.total span {
          color: #70161e;
        }

        #delivery-fee-row {
          display: none;
        }

        #delivery-fee-row.show {
          display: flex;
        }

        .delivery-fee-tag {
          font-size: 12px;
          font-weight: 700;
          color: #7a5c00;
          background: #fff8e6;
          padding: 3px 8px;
          border-radius: 20px;
          border: 1px solid #e6c96e;
        }

        #courier-total-note {
          display: none;
          margin-top: 14px;
          background: #fff8e6;
          border: 1px solid #e6c96e;
          border-radius: 8px;
          padding: 12px 16px;
          font-size: 12px;
          color: #7a5c00;
          line-height: 1.6;
        }

        #courier-total-note.show {
          display: block;
        }

        .btn-place-order {
          width: 100%;
          padding: 16px;
          background: #7ea16b;
          color: #fff;
          border: none;
          border-radius: 10px;
          font-family: "Raleway", sans-serif;
          font-size: 16px;
          font-weight: 800;
          cursor: pointer;
          transition: background 0.2s;
          margin-top: 20px;
          letter-spacing: 0.5px;
        }

        .btn-place-order:hover {
          background: #1c3144;
        }

        .btn-back-cart {
          display: block;
          text-align: center;
          margin-top: 14px;
          font-size: 13px;
          color: #aaa;
          text-decoration: none;
          font-weight: 600;
        }

        .btn-back-cart:hover {
          color: #70161e;
        }

        .checkout-error {
          background: #fff0f0;
          border: 1.5px solid #70161e;
          border-radius: 10px;
          padding: 14px 20px;
          font-size: 14px;
          color: #70161e;
          font-weight: 600;
          margin: 20px 40px 0;
          display: none;
        }

        .checkout-error.show {
          display: block;
        }

        @media (max-width: 900px) {
          .checkout-wrap {
            grid-template-columns: 1fr;
            padding: 0 16px;
          }

          .navbar {
            padding: 14px 24px;
          }

          .page-header {
            padding: 100px 24px 36px;
          }
        }

        @media (max-width: 768px) {
          .page-header {
            padding: 20px 20px 30px;
          }

          .page-header h1 {
            font-size: 36px;
          }

          .checkout-wrap {
            margin: 20px auto 50px;
            padding: 0 14px;
          }

          .card {
            padding: 20px 16px;
          }

          .fulfillment-options {
            flex-direction: column;
          }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div class="nav-logo">
        <img src="images/logoo.png" alt="Paulines Kitchen" class="logo-img">
        <span class="nav-brand">Paulines' Kitchen</span>
    </div>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="cart.php" class="nav-active">Order</a></li>
        <li><a href="index.php#faqs">FAQs</a></li>
    </ul>
    <div class="nav-right">
        <a href="cart.php" class="cart-icon">
            <i class="icofont-food-basket"></i>
            <span class="badge"><?= count($cart) ?></span>
        </a>
        <?php if (isset($_SESSION['user_name'])): ?>
            <span style="color:#fff; font-weight:600;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="user/auth/logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="user/login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>
<?php $active_page='cart'; include 'includes/mobile_nav.php'; ?>

<div class="page-header">
    <h1>Check<span>out</span></h1>
    <div class="header-divider"></div>
</div>

<div class="checkout-wrap">

<?php
$checkout_err = $_GET['error'] ?? '';
$err_msgs = [
    'missing_fields'       => '⚠️ Some required fields were missing. Please fill in all fields and try again.',
    'db_error'             => '⚠️ A database error occurred while saving your order. Please try again or contact support.',
    'payment_init_failed'  => '⚠️ Could not connect to the payment gateway. Please try again in a moment.',
];
$err_text = $err_msgs[$checkout_err] ?? '';
?>
<?php if ($err_text): ?>
<div class="checkout-error show" style="margin: 20px 40px 0;"><?= htmlspecialchars($err_text) ?></div>
<?php endif; ?>

    <div>

        <div class="card">
            <div class="card-title">How do you want to receive your order?</div>

            <div class="fulfillment-options">
                <label class="fulfillment-opt">
                    <input type="radio" name="fulfillment" value="pickup" onchange="setFulfillment('pickup')" checked>
                    <div>
                        <strong>Self Pick-up</strong>
                        <span>Pick up hours: May depend on the order's scheduled time</span>
                    </div>
                </label>
                <label class="fulfillment-opt">
                    <input type="radio" name="fulfillment" value="courier" onchange="setFulfillment('courier')">
                    <div>
                        <strong>Third-Party Courier</strong>
                        <span>We book the rider for you.</span>
                    </div>
                </label>
            </div>

            <div class="pickup-info show" id="pickup-info">
                [Pick-up] <strong>Pick-up Location:</strong> Pauline's Kitchen, San Miguel, Bulacan.<br>
                We will notify you when your order is ready for pick-up.
            </div>

            <div class="courier-disclaimer" id="courier-disclaimer">
                [Delivery] <strong>Note:</strong> We will book the rider for you! The exact delivery fee will be based on the courier's rate and must be paid <strong>directly to the rider upon arrival</strong>.
            </div>
        </div>

        <div class="card">
            <div class="card-title">Payment Method</div>
            <div class="fulfillment-options">
                <label class="fulfillment-opt">
                    <input type="radio" name="payment_method" value="paymongo" checked>
                    <div>
                        <strong>Online Payment</strong>
                        <span>Pay via GCash, Maya, or Card</span>
                    </div>
                </label>
                <label class="fulfillment-opt">
                    <input type="radio" name="payment_method" value="cod">
                    <div>
                        <strong>Cash on Delivery / Pick-up</strong>
                        <span>Pay with cash when you receive your order.</span>
                    </div>
                </label>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Your Information</div>

            <div class="form-group">
                <label>Full Name <span>*</span></label>
                <input type="text" id="fullname" placeholder="Juan dela Cruz" oninput="clearErr('fullname')">
                <div class="error-msg" id="fullname-err">Please enter your full name.</div>
            </div>

            <div class="form-group">
                <label>Philippine Mobile Number <span>*</span></label>
                <input type="text" id="phone" placeholder="09XX XXX XXXX" maxlength="11" oninput="clearErr('phone')">
                <div class="error-msg" id="phone-err">Please enter a valid PH mobile number (09XXXXXXXXX).</div>
            </div>

            <div class="form-group">
                <label>Email Address <span>*</span></label>
                <input type="email" id="email" placeholder="you@example.com" oninput="clearErr('email')">
                <div class="error-msg" id="email-err">Please enter a valid email address.</div>
            </div>

            <div class="form-group">
                <label>Target Date & Time <span>*</span></label>
                <input type="datetime-local" id="datetime" oninput="clearErr('datetime')">
                <div class="error-msg" id="datetime-err">Please select your target date and time.</div>
            </div>

            <div class="form-group">
                <label>Order Notes / Special Requests</label>
                <textarea id="notes" placeholder="Any special instructions, allergies, or requests..."></textarea>
            </div>
        </div>

        <div class="card" id="delivery-fields">
            <div class="card-title">Delivery Details</div>

            <div class="form-group">
                <label>Delivery Address <span>*</span></label>
                <textarea id="address" placeholder="Street, Barangay, City / Municipality" oninput="clearErr('address')"></textarea>
                <div class="error-msg" id="address-err">Please enter your delivery address.</div>
            </div>

            <div class="form-group">
                <label>Nearest Landmark <span style="color:#aaa; font-weight:400;">(Optional)</span></label>
                <input type="text" id="landmark" placeholder="e.g. Near SM Bulacan, beside 7-Eleven">
            </div>
        </div>

    </div>

    <div>
        <div class="card" style="position: sticky; top: 120px;">
            <div class="card-title">Order Summary</div>

            <?php foreach ($cart as $item):
                $unit     = $item['price'] + $item['rice_addon'];
                $subtotal = $unit * $item['quantity'];
            ?>
            <div class="summary-item">
                <div>
                    <div class="summary-item-name">
                        <?= htmlspecialchars($item['name']) ?>
                        <?php if ($item['quantity'] > 1): ?>
                            <span style="color:#7EA16B;"> ×<?= $item['quantity'] ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="summary-item-meta">
                        <?php if ($item['pax']): ?>PAX: <?= $item['pax'] ?><?php endif; ?>
                        <?php if ($item['rice_addon'] > 0): ?> · Rice +₱<?= number_format($item['rice_addon'], 2) ?><?php endif; ?>
                    </div>
                </div>
                <div class="summary-item-price">PHP <?= number_format($subtotal, 2) ?></div>
            </div>
            <?php endforeach; ?>

            <hr class="summary-divider">

            <div class="summary-row">
                <span>Subtotal</span>
                <span>PHP <?= number_format($total, 2) ?></span>
            </div>

            <div class="summary-row" id="delivery-fee-row">
                <span>Delivery Fee</span>
                <span class="delivery-fee-tag">To be paid directly to rider</span>
            </div>

            <div class="summary-row total">
                <span>Total</span>
                <span>PHP <?= number_format($total, 2) ?></span>
            </div>

            <div id="courier-total-note">
                Note: We will book the rider for you! The exact delivery fee will be based on the courier's rate and must be paid directly to the rider upon arrival.
            </div>

            <form action="checkout.php" method="POST" id="checkout-form">
                
                <input type="hidden" name="email" id="h-email">
                <input type="hidden" name="fulfillment" id="h-fulfillment" value="pickup">
                <input type="hidden" name="payment_method" id="h-payment_method" value="paymongo">
                <input type="hidden" name="fullname"    id="h-fullname">
                <input type="hidden" name="phone"       id="h-phone">
                <input type="hidden" name="datetime"    id="h-datetime">
                <input type="hidden" name="notes"       id="h-notes">
                <input type="hidden" name="address"     id="h-address">
                <input type="hidden" name="landmark"    id="h-landmark">

                <button type="button" class="btn-place-order" onclick="submitOrder()">
                    Place Order →
                </button>
            </form>

            <a href="cart.php" class="btn-back-cart">← Back to Cart</a>
        </div>
    </div>

</div>

<script>
function setFulfillment(type) {
    const deliveryFields   = document.getElementById('delivery-fields');
    const pickupInfo       = document.getElementById('pickup-info');
    const courierNote      = document.getElementById('courier-disclaimer');
    const deliveryFeeRow   = document.getElementById('delivery-fee-row');
    const courierTotalNote = document.getElementById('courier-total-note');

    if (type === 'pickup') {
        deliveryFields.classList.remove('show');
        pickupInfo.classList.add('show');
        courierNote.classList.remove('show');
        deliveryFeeRow.classList.remove('show');
        courierTotalNote.classList.remove('show');
    } else {
        deliveryFields.classList.add('show');
        pickupInfo.classList.remove('show');
        courierNote.classList.add('show');
        deliveryFeeRow.classList.add('show');
        courierTotalNote.classList.add('show');
    }
}

function clearErr(id) {
    document.getElementById(id).classList.remove('error');
    document.getElementById(id + '-err').classList.remove('show');
}

function showErr(id, msg) {
    const input = document.getElementById(id);
    const err   = document.getElementById(id + '-err');
    input.classList.add('error');
    if (msg) err.textContent = msg;
    err.classList.add('show');
}

function submitOrder() {
    let valid = true;
    const fulfillment = document.querySelector('input[name="fulfillment"]:checked').value;
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

    const fullname = document.getElementById('fullname').value.trim();
    if (!fullname) { showErr('fullname'); valid = false; }

    const phone = document.getElementById('phone').value.trim();
    if (!/^09\d{9}$/.test(phone)) { showErr('phone'); valid = false; }

    const email = document.getElementById('email').value.trim();
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) { showErr('email', 'Please enter a valid email address.'); valid = false; }

    const datetime = document.getElementById('datetime').value;
    if (!datetime) { showErr('datetime'); valid = false; }

    if (fulfillment === 'courier') {
        const address = document.getElementById('address').value.trim();
        if (!address) { showErr('address'); valid = false; }
    }

    if (!valid) return;

    const btn = document.querySelector('.btn-place-order');
    btn.disabled    = true;
    btn.textContent = '⏳ Securing your tray...';
    btn.style.opacity = '0.7';

    document.getElementById('h-fulfillment').value = fulfillment;
    document.getElementById('h-payment_method').value = paymentMethod;
    document.getElementById('h-fullname').value    = fullname;
    document.getElementById('h-phone').value       = phone;
    document.getElementById('h-email').value       = email;
    document.getElementById('h-datetime').value    = datetime;
    document.getElementById('h-notes').value       = document.getElementById('notes').value.trim();
    document.getElementById('h-address').value     = document.getElementById('address')?.value.trim() || '';
    document.getElementById('h-landmark').value    = document.getElementById('landmark')?.value.trim() || '';

    document.getElementById('checkout-form').submit();
}

const dtInput   = document.getElementById('datetime');
const LEAD_HRS  = 2;   
const OPEN_HR   = 8;   
const CLOSE_HR  = 22;  

function pad(n) { return String(n).padStart(2, '0'); }

function toLocalISO(date) {
    return date.getFullYear()         + '-' +
           pad(date.getMonth() + 1)   + '-' +
           pad(date.getDate())         + 'T' +
           pad(date.getHours())        + ':' +
           pad(date.getMinutes());
}

function setDatetimeConstraints() {
    const maxTime = new Date();
    maxTime.setFullYear(maxTime.getFullYear() + 1);
    dtInput.max = toLocalISO(maxTime);
}

setDatetimeConstraints();

dtInput.addEventListener('change', function () {
    clearErr('datetime');
});

</script>

</body>
</html>