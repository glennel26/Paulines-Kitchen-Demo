<?php
session_start();

if (isset($_GET['remove'])) {
    $i = intval($_GET['remove']);
    if (isset($_SESSION['cart'][$i])) {
        array_splice($_SESSION['cart'], $i, 1);
    }
    header('Location: cart.php');
    exit();
}

if (isset($_GET['clear'])) {
    $_SESSION['cart'] = [];
    header('Location: cart.php');
    exit();
}

$cart  = $_SESSION['cart'] ?? [];
$total = 0;
foreach ($cart as $item) {
    $total += ($item['price'] + $item['rice_addon']) * $item['quantity'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Cart – Pauline's Kitchen</title>
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
          background-color: #7ea16b;
          display: grid;
          grid-template-columns: 1fr auto 1fr;
          align-items: center;
          padding: 14px 60px;
          position: fixed;
          top: 0;
          left: 0;
          z-index: 999;
          border-bottom: 1px solid rgba(255, 255, 255, 0.25);
        }
        .logo-img {
          height: 70px;
          width: 70px;
          border-radius: 50%;
          object-fit: contain;
        }

        .nav-links {
          list-style: none;
          display: flex;
          gap: 36px;
          justify-content: center;
          align-items: center;
        }

        .nav-links a {
          text-decoration: none;
          color: #fff;
          font-size: 16px;
          font-weight: 500;
          letter-spacing: 1px;
          padding: 6px 14px;
          border-radius: 20px;
          transition: background-color 0.2s ease;
        }

        .nav-links a:hover {
          background-color: rgba(255, 255, 255, 0.18);
          color: #fff;
        }
        .nav-links a.nav-active {
          background-color: rgba(28, 49, 68, 0.3);
          color: #fff;
          font-weight: 700;
        }

        .nav-right {
          display: flex;
          align-items: center;
          gap: 22px;
          justify-content: flex-end;
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

        .cart-wrap {
          max-width: 1000px;
          margin: 50px auto;
          padding: 0 40px 80px;
        }

        .cart-empty {
          text-align: center;
          padding: 80px 0;
        }
        .cart-empty p {
          font-size: 20px;
          color: #aaa;
          margin-bottom: 24px;
        }
        .btn-back {
          display: inline-block;
          background: #7ea16b;
          color: #fff;
          padding: 14px 32px;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 700;
          font-size: 15px;
        }
        .btn-back:hover {
          background: #1c3144;
        }

        .cart-table {
          width: 100%;
          border-collapse: collapse;
          background: #fff;
          border-radius: 14px;
          overflow: hidden;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
          margin-bottom: 28px;
        }
        .cart-table th {
          background: #1c3144;
          color: #fff;
          padding: 16px 20px;
          text-align: left;
          font-size: 13px;
          letter-spacing: 1px;
          text-transform: uppercase;
        }
        .cart-table td {
          padding: 16px 20px;
          border-bottom: 1px solid #f0ebe0;
          vertical-align: middle;
        }
        .cart-table tr:last-child td {
          border-bottom: none;
        }
        .cart-table tr:hover td {
          background: #faf8f3;
        }

        .item-img-cell {
          display: flex;
          align-items: center;
          gap: 14px;
        }
        .item-thumb {
          width: 60px;
          height: 60px;
          border-radius: 8px;
          object-fit: cover;
          background: #e8f0e0;
        }
        .item-thumb-placeholder {
          width: 60px;
          height: 60px;
          border-radius: 8px;
          background: linear-gradient(135deg, #e8f0e0, #c3d898);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 28px;
          flex-shrink: 0;
        }
        .item-info strong {
          display: block;
          font-size: 15px;
          color: #1c3144;
        }
        .item-info small {
          font-size: 12px;
          color: #aaa;
        }

        .item-price {
          font-weight: 700;
          color: #70161e;
          font-size: 16px;
        }
        .item-subtotal {
          font-weight: 800;
          color: #1c3144;
          font-size: 16px;
        }

        .btn-remove {
          background: none;
          border: none;
          color: #ccc;
          font-size: 20px;
          cursor: pointer;
          transition: color 0.2s;
        }
        .btn-remove:hover {
          color: #70161e;
        }

        .cart-summary {
          background: #fff;
          border-radius: 14px;
          padding: 28px 32px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
          display: flex;
          justify-content: space-between;
          align-items: center;
          flex-wrap: wrap;
          gap: 20px;
        }
        .summary-total {
          font-size: 22px;
          font-weight: 800;
          color: #1c3144;
        }
        .summary-total span {
          color: #70161e;
        }
        .summary-actions {
          display: flex;
          gap: 12px;
          flex-wrap: wrap;
        }

        .btn-clear {
          padding: 12px 24px;
          background: none;
          border: 2px solid #ccc;
          border-radius: 8px;
          font-family: "Raleway", sans-serif;
          font-size: 14px;
          font-weight: 700;
          color: #aaa;
          cursor: pointer;
          text-decoration: none;
          transition: all 0.2s;
        }
        .btn-clear:hover {
          border-color: #70161e;
          color: #70161e;
        }
        .btn-checkout {
          padding: 12px 32px;
          background: #7ea16b;
          color: #fff;
          border: none;
          border-radius: 8px;
          font-family: "Raleway", sans-serif;
          font-size: 15px;
          font-weight: 700;
          cursor: pointer;
          text-decoration: none;
          transition: background 0.2s;
        }
        .btn-checkout:hover {
          background: #1c3144;
        }

        @media (max-width: 768px) {
          .page-header {
            padding: 20px 20px 28px;
          }
          .page-header h1 {
            font-size: 36px;
          }
          .cart-wrap {
            margin: 16px auto;
            padding: 0 14px 50px;
          }
          .cart-summary {
            flex-direction: column;
            align-items: flex-start;
          }
          .summary-actions {
            width: 100%;
            flex-direction: column;
          }
          .btn-clear,
          .btn-checkout {
            width: 100%;
            text-align: center;
            box-sizing: border-box;
          }

          /* Hide table, show cards */
          .cart-table {
            display: none;
          }
          .cart-cards {
            display: flex;
            flex-direction: column;
            gap: 12px;
            margin-bottom: 20px;
          }
          .cart-card {
            background: #fff;
            border-radius: 12px;
            padding: 14px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.07);
            display: flex;
            gap: 12px;
            align-items: flex-start;
          }
          .cart-card-img {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            object-fit: cover;
            flex-shrink: 0;
            background: #e8f0e0;
          }
          .cart-card-img-placeholder {
            width: 64px;
            height: 64px;
            border-radius: 8px;
            background: linear-gradient(135deg, #e8f0e0, #c3d898);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            flex-shrink: 0;
          }
          .cart-card-info {
            flex: 1;
            min-width: 0;
          }
          .cart-card-name {
            font-size: 14px;
            font-weight: 700;
            color: #1c3144;
          }
          .cart-card-meta {
            font-size: 11px;
            color: #aaa;
            margin-top: 2px;
          }
          .cart-card-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 8px;
          }
          .cart-card-price {
            font-size: 14px;
            font-weight: 800;
            color: #70161e;
          }
          .cart-card-qty {
            font-size: 12px;
            color: #888;
            font-weight: 600;
          }
          .cart-card-remove {
            background: none;
            border: none;
            color: #ccc;
            font-size: 18px;
            cursor: pointer;
            flex-shrink: 0;
            align-self: flex-start;
            padding: 0 4px;
          }
          .cart-card-remove:hover {
            color: #70161e;
          }
        }
        @media (min-width: 769px) {
          .cart-cards {
            display: none;
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
    <h1>My <span>Cart</span></h1>
    <div class="header-divider"></div>
</div>

<div class="cart-wrap">

    <?php if (empty($cart)): ?>
        <div class="cart-empty">
            <p style="color:#7EA16B;font-weight:700;">Your cart is empty!</p>
            <a href="menu.php" class="btn-back">Browse the Menu</a>
        </div>

    <?php else: ?>
        <!-- Desktop table -->
        <div class="table-responsive">
            <table class="cart-table">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Price</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cart as $i => $item):
                    $unit     = $item['price'] + $item['rice_addon'];
                    $subtotal = $unit * $item['quantity'];
                ?>
                <tr>
                    <td>
                        <div class="item-img-cell">
                            <?php if (!empty($item['image'])): ?>
                                <img src="<?= htmlspecialchars($item['image']) ?>" alt="" class="item-thumb">
                            <?php else: ?>
                                <div class="item-thumb-placeholder"><i class="icofont-restaurant"></i></div>
                            <?php endif; ?>
                            <div class="item-info">
                                <strong><?= htmlspecialchars($item['name']) ?></strong>
                                <small>
                                    <?php if ($item['pax']): ?><?= $item['pax'] ?> PAX<?php endif; ?>
                                    <?php if ($item['rice_addon'] > 0): ?> · +₱<?= number_format($item['rice_addon'], 2) ?> rice<?php endif; ?>
                                </small>
                            </div>
                        </div>
                    </td>
                    <td class="item-price">PHP <?= number_format($unit, 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td class="item-subtotal">PHP <?= number_format($subtotal, 2) ?></td>
                    <td>
                        <button class="btn-remove" onclick="location.href='cart.php?remove=<?= $i ?>'" title="Remove">✕</button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        </div>

        <!-- Mobile cards -->
        <div class="cart-cards">
            <?php foreach ($cart as $i => $item):
                $unit     = $item['price'] + $item['rice_addon'];
                $subtotal = $unit * $item['quantity'];
            ?>
            <div class="cart-card">
                <?php if (!empty($item['image'])): ?>
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="" class="cart-card-img">
                <?php else: ?>
                    <div class="cart-card-img-placeholder"><i class="icofont-restaurant"></i></div>
                <?php endif; ?>
                <div class="cart-card-info">
                    <div class="cart-card-name"><?= htmlspecialchars($item['name']) ?></div>
                    <div class="cart-card-meta">
                        <?php if ($item['pax']): ?><?= $item['pax'] ?> PAX<?php endif; ?>
                        <?php if ($item['rice_addon'] > 0): ?> &middot; +₱<?= number_format($item['rice_addon'], 2) ?> rice<?php endif; ?>
                    </div>
                    <div class="cart-card-row">
                        <div>
                            <span class="cart-card-price">PHP <?= number_format($subtotal, 2) ?></span>
                            <span class="cart-card-qty"> &times;<?= $item['quantity'] ?></span>
                        </div>
                    </div>
                </div>
                <button class="cart-card-remove" onclick="location.href='cart.php?remove=<?= $i ?>'" title="Remove">✕</button>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="cart-summary">
            <div class="summary-total">
                Total: <span>PHP <?= number_format($total, 2) ?></span>
            </div>
            <div class="summary-actions">
                <a href="cart.php?clear=1" class="btn-clear">Clear Cart</a>
                <a href="menu.php" class="btn-clear">Add More</a>
                <a href="checkout.php" class="btn-checkout">Proceed to Checkout →</a>
            </div>
        </div>

    <?php endif; ?>

</div>

</body>
</html>