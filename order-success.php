<?php
session_start();
require_once 'config/db.php';

$ref   = $_GET['ref'] ?? $_SESSION['last_order_ref'] ?? '';
$order = null;

if ($ref) {
    $stmt = $conn->prepare("SELECT * FROM orders WHERE reference = ? LIMIT 1");
    $stmt->bind_param('s', $ref);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();
}

unset($_SESSION['cart']);
unset($_SESSION['last_order_ref']);

$items = [];
if ($order) {
    $istmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $istmt->bind_param('i', $order['id']);
    $istmt->execute();
    $items = $istmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$isCourier   = $order && $order['fulfillment'] === 'courier';
$fEmoji = $isCourier ? '<i class="icofont-fast-delivery"></i>' : '<i class="icofont-package"></i>';
$fStep  = $isCourier ? 'Contacting Rider...' : 'Packing your order...';
$fDesc  = $isCourier ? 'We are booking a rider for your target date.' : 'Your order will be ready for pick-up.';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmed – Paulines' Kitchen</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@400;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="images/icons/icofont/icofont.min.css">
    <link rel="icon" href="images/logoo.png">
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
          min-height: 100vh;
        }

        .progress-wrap {
          min-height: 100vh;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: center;
          padding: 40px 20px;
          background: linear-gradient(135deg, #2e4a2e 0%, #7ea16b 100%);
        }
        .progress-card {
          background: #fff;
          border-radius: 24px;
          padding: 52px 48px;
          max-width: 540px;
          width: 100%;
          text-align: center;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
        }
        .progress-emoji {
          font-size: 80px;
          margin-bottom: 24px;
          animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
          0%,
          100% {
            transform: translateY(0);
          }
          50% {
            transform: translateY(-10px);
          }
        }
        @keyframes wiggle {
          0%,
          100% {
            transform: rotate(-8deg) translateY(0);
          }
          50% {
            transform: rotate(8deg) translateY(-8px);
          }
        }
        .animated-icons {
          display: flex;
          justify-content: center;
          flex-wrap: wrap;
          max-width: 300px;
          margin: 0 auto;
        }
        .animated-icons i {
          font-size: 32px;
          margin: 4px;
          display: inline-block;
          animation: wiggle 2s ease-in-out infinite;
        }
        .animated-icons i:nth-child(even) {
          animation-delay: 0.3s;
          color: #7ea16b;
        }
        .animated-icons i:nth-child(3n) {
          animation-delay: 0.6s;
          color: #70161e;
        }
        .animated-icons i:nth-child(4n) {
          animation-delay: 0.9s;
          color: #c3d898;
        }

        .progress-icon {
          font-size: 64px;
          margin-bottom: 16px;
          animation: pulse 1.5s ease infinite;
        }
        @keyframes pulse {
          0%,
          100% {
            transform: scale(1);
          }
          50% {
            transform: scale(1.1);
          }
        }
        .progress-title {
          font-size: 26px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 8px;
        }
        .progress-sub {
          font-size: 15px;
          color: #888;
          margin-bottom: 36px;
          line-height: 1.6;
        }

        .progress-steps {
          display: flex;
          flex-direction: column;
          margin-bottom: 36px;
          text-align: left;
        }
        .progress-step {
          display: flex;
          align-items: flex-start;
          gap: 16px;
          padding: 16px 0;
          position: relative;
        }
        .progress-step:not(:last-child)::after {
          content: "";
          position: absolute;
          left: 19px;
          top: 52px;
          width: 2px;
          height: calc(100% - 36px);
          background: #e0dbd0;
        }
        .step-dot {
          width: 40px;
          height: 40px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 16px;
          flex-shrink: 0;
          font-weight: 800;
          background: #e0dbd0;
          color: #aaa;
          position: relative;
          z-index: 1;
          transition: all 0.5s ease;
        }
        .step-dot.done {
          background: #7ea16b;
          color: #fff;
        }
        .step-dot.active {
          background: #1c3144;
          color: #fff;
          box-shadow: 0 0 0 4px rgba(28, 49, 68, 0.15);
        }
        .step-dot.spinning::after {
          content: "";
          position: absolute;
          width: 100%;
          height: 100%;
          border-radius: 50%;
          border: 3px solid transparent;
          border-top-color: #c3d898;
          animation: spin 0.8s linear infinite;
        }
        @keyframes spin {
          to {
            transform: rotate(360deg);
          }
        }
        .step-label {
          font-size: 16px;
          font-weight: 700;
          color: #1c3144;
          margin-bottom: 3px;
        }
        .step-label.muted {
          color: #aaa;
        }
        .step-desc {
          font-size: 13px;
          color: #888;
          line-height: 1.5;
        }

        .progress-bar-wrap {
          background: #e0dbd0;
          border-radius: 8px;
          height: 8px;
          overflow: hidden;
          margin-bottom: 10px;
        }
        .progress-bar-fill {
          height: 100%;
          background: linear-gradient(to right, #7ea16b, #c3d898);
          border-radius: 8px;
          width: 0%;
          transition: width 0.6s ease;
        }
        .progress-bar-label {
          font-size: 13px;
          color: #888;
          text-align: right;
          margin-bottom: 28px;
        }

        .success-wrap {
          min-height: 100vh;
          display: flex;
          align-items: center;
          justify-content: center;
          padding: 40px 20px;
        }
        .success-card {
          background: #fff;
          border-radius: 20px;
          padding: 52px 48px;
          max-width: 580px;
          width: 100%;
          text-align: center;
          box-shadow: 0 12px 40px rgba(0, 0, 0, 0.1);
        }
        .success-icon {
          font-size: 72px;
          color: #7ea16b;
          margin-bottom: 20px;
          animation: popIn 0.5s ease;
        }
        @keyframes popIn {
          from {
            transform: scale(0.5);
            opacity: 0;
          }
          to {
            transform: scale(1);
            opacity: 1;
          }
        }
        .success-title {
          font-size: 30px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 10px;
        }
        .success-sub {
          font-size: 15px;
          color: #596f62;
          margin-bottom: 28px;
          line-height: 1.6;
        }
        .ref-box {
          background: #f5f0e8;
          border-radius: 10px;
          padding: 14px 24px;
          margin-bottom: 24px;
        }
        .ref-label {
          font-size: 11px;
          color: #aaa;
          letter-spacing: 1px;
          text-transform: uppercase;
          margin-bottom: 4px;
        }
        .ref-number {
          font-size: 26px;
          font-weight: 800;
          color: #1c3144;
          letter-spacing: 2px;
        }
        .info-row {
          display: flex;
          justify-content: space-between;
          font-size: 14px;
          padding: 10px 0;
          border-bottom: 1px solid #f0ebe0;
          text-align: left;
        }
        .info-row:last-child {
          border-bottom: none;
        }
        .info-row span:first-child {
          color: #888;
          font-weight: 600;
        }
        .info-row span:last-child {
          color: #1c3144;
          font-weight: 700;
        }
        .items-list {
          text-align: left;
          margin: 20px 0;
        }
        .items-list-title {
          font-size: 12px;
          font-weight: 700;
          color: #aaa;
          letter-spacing: 1px;
          text-transform: uppercase;
          margin-bottom: 10px;
        }
        .item-row {
          display: flex;
          justify-content: space-between;
          font-size: 14px;
          padding: 8px 0;
          border-bottom: 1px solid #f0ebe0;
        }
        .item-row:last-child {
          border-bottom: none;
        }
        .notice {
          border-radius: 10px;
          padding: 14px 18px;
          font-size: 13px;
          line-height: 1.6;
          margin-top: 20px;
          text-align: left;
        }
        .notice.courier {
          background: #fff8e6;
          border: 1px solid #e6c96e;
          color: #7a5c00;
        }
        .notice.pickup {
          background: #f4faf0;
          border: 1px solid #c3d898;
          color: #2e4a2e;
        }
        .btn-home {
          display: inline-block;
          margin-top: 28px;
          background: #7ea16b;
          color: #fff;
          padding: 14px 40px;
          border-radius: 8px;
          text-decoration: none;
          font-weight: 700;
          font-size: 15px;
          transition: background 0.2s;
        }
        .btn-home:hover {
          background: #1c3144;
        }
        .hidden {
          display: none !important;
        }
    </style>
</head>
<body>

<?php if ($order): ?>

<div class="progress-wrap" id="progress-page">
    <div class="progress-card">
        <div class="progress-icon" id="progress-emoji">⏳</div>
        <h2 class="progress-title" id="progress-title">Confirming your order...</h2>
        <p class="progress-sub" id="progress-sub">Please wait while we secure your order. This only takes a moment.</p>

        <div class="progress-bar-wrap">
            <div class="progress-bar-fill" id="progress-bar"></div>
        </div>
        <div class="progress-bar-label" id="progress-pct">0%</div>

        <div class="progress-steps">
            <div class="progress-step">
                <div class="step-dot active spinning" id="dot-1">1</div>
                <div class="step-info">
                    <div class="step-label" id="lbl-1">Confirming Order</div>
                    <div class="step-desc">Verifying your payment and locking in your order.</div>
                </div>
            </div>
            <div class="progress-step">
                <div class="step-dot" id="dot-2">2</div>
                <div class="step-info">
                    <div class="step-label muted" id="lbl-2">Kitchen Preparing</div>
                    <div class="step-desc">Our cooks are getting your food ready fresh.</div>
                </div>
            </div>
            <div class="progress-step">
                <div class="step-dot" id="dot-3">3</div>
                <div class="step-info">
                    <div class="step-label muted" id="lbl-3"><?php echo $fStep; ?></div>
                    <div class="step-desc"><?php echo $fDesc; ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="success-wrap hidden" id="success-page">
    <div class="success-card">
        <div class="success-icon"><i class="icofont-check-circled"></i></div>
        <h1 class="success-title">Order Confirmed! 🎉</h1>
        <p class="success-sub">
            Thank you, <strong><?php echo htmlspecialchars($order['fullname']); ?></strong>!<br>
            Your payment was successful and we're getting your food ready.<br>
            A confirmation email has been sent to <strong><?php echo htmlspecialchars($order['email']); ?></strong>.
        </p>

        <div class="ref-box">
            <div class="ref-label">Order Reference</div>
            <div class="ref-number"><?php echo htmlspecialchars($order['reference']); ?></div>
        </div>

        <?php if (!empty($items)): ?>
        <div class="items-list">
            <div class="items-list-title">Your Order</div>
            <?php foreach ($items as $item):
                $subtotal = ($item['unit_price'] + $item['rice_addon']) * $item['quantity'];
            ?>
            <div class="item-row">
                <span><?php echo htmlspecialchars($item['item_name']); ?><?php echo $item['pax'] ? ' (' . $item['pax'] . ' pax)' : ''; ?> &times;<?php echo $item['quantity']; ?></span>
                <span style="color:#70161E;font-weight:700;">PHP <?php echo number_format($subtotal, 2); ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <div class="info-row">
            <span>Target Date</span>
            <span><?php echo date('M j, Y g:i A', strtotime($order['target_datetime'])); ?></span>
        </div>
        <div class="info-row">
            <span>Fulfillment</span>
            <span><?php echo ucfirst($order['fulfillment']); ?></span>
        </div>
        <div class="info-row">
            <span>Total Paid</span>
            <span style="color:#70161E;font-size:16px;">PHP <?php echo number_format($order['total'], 2); ?></span>
        </div>

        <?php if ($isCourier): ?>
        <div class="notice courier">
            🛵 <strong>Delivery Reminder:</strong> We'll book a rider for you. Please prepare cash for the delivery fee — paid directly to the rider upon arrival.
        </div>
        <?php else: ?>
        <div class="notice pickup">
            📍 <strong>Pick-up Address:</strong> San Miguel, Bulacan.<br>
            Show your reference number <strong><?php echo htmlspecialchars($order['reference']); ?></strong> when you arrive.
        </div>
        <?php endif; ?>

        <a href="index.php" class="btn-home">Back to Home</a>
    </div>
</div>

<script>
const ORDER_REF         = '<?php echo addslashes($order["reference"]); ?>';
const FULFILLMENT_EMOJI = '<?php echo addslashes($fEmoji ?? ""); ?>';
const FULFILLMENT_STEP  = '<?php echo addslashes($fStep ?? ""); ?>';
const FULFILLMENT_DESC  = '<?php echo addslashes($fDesc ?? ""); ?>';

function setDot(i, state) {
    const d = document.getElementById('dot-' + i);
    const l = document.getElementById('lbl-' + i);
    if (!d || !l) return;
    d.classList.remove('active', 'spinning', 'done');
    if (state === 'active') {
        d.classList.add('active', 'spinning');
        d.innerHTML = i;
        l.classList.remove('muted');
    } else if (state === 'done') {
        d.classList.add('done');
        d.innerHTML = '✓';
        l.classList.remove('muted');
    } else {
        d.innerHTML = i;
        l.classList.add('muted');
    }
}

function setHeader(emoji, title, sub, pct) {
    document.getElementById('progress-emoji').innerHTML = emoji;
    document.getElementById('progress-title').textContent = title;
    document.getElementById('progress-sub').textContent   = sub;
    document.getElementById('progress-bar').style.width   = pct + '%';
    document.getElementById('progress-pct').textContent   = pct + '%';
}

function showSuccess() {
    console.log('showSuccess fired');
    const pp = document.getElementById('progress-page');
    const sp = document.getElementById('success-page');
    if (pp) pp.classList.add('hidden');
    if (sp) sp.classList.remove('hidden');
    
    if (appliedLevel === STATUS_LEVELS['completed']) {
        const title = sp.querySelector('.success-title');
        const sub = sp.querySelector('.success-sub');
        if (title) title.innerHTML = 'Order Completed! 🎉';
        if (sub) sub.innerHTML = 'Thank you, <strong><?php echo addslashes(htmlspecialchars($order["fullname"])); ?></strong>!<br>Your order is fully completed. Enjoy your meal!';
    }
    
    window.scrollTo(0, 0);
}

const STATUS_LEVELS = { pending: 0, preparing: 1, ready: 2, completed: 3 };
let appliedLevel = -1;

function applyStatus(status) {
    const level = STATUS_LEVELS[status] ?? -1;
    if (level <= appliedLevel) return; 
    appliedLevel = level;

    console.log('Applying status:', status);

    if (status === 'pending') {
        setDot(1, 'active'); setDot(2, 'idle'); setDot(3, 'idle');
        setHeader('<i class="icofont-check-alt"></i>', 'Confirming your order...', 'Please wait while we lock in your order.', 33);

    } else if (status === 'preparing') {
        setDot(1, 'done'); setDot(2, 'active'); setDot(3, 'idle');
        setHeader('<div class="animated-icons"><i class="icofont-bell-pepper-capsicum"></i><i class="icofont-cauli-flower"></i><i class="icofont-chicken-fry"></i><i class="icofont-culinary"></i><i class="icofont-egg-plant"></i><i class="icofont-onion"></i><i class="icofont-tomato"></i></div>', 'Kitchen is Preparing...', 'Our cooks are getting your food ready fresh.', 66);

    } else if (status === 'ready') {
        setDot(1, 'done'); setDot(2, 'done'); setDot(3, 'active');
        setHeader(FULFILLMENT_EMOJI, FULFILLMENT_STEP, FULFILLMENT_DESC, 100);

    } else if (status === 'completed') {
        showSuccess();
    }
}

function poll() {
    fetch('check_order_status.php?ref=' + ORDER_REF + '&t=' + Date.now())
        .then(r => r.json())
        .then(data => {
            console.log('Poll result:', data);
            const status = (data.status || '').toLowerCase(); 
            applyStatus(status);
            if (!['completed', 'cancelled'].includes(status)) {
                setTimeout(poll, 4000);
            }
        })
        .catch(err => {
            console.error('Poll error:', err);
            setTimeout(poll, 5000);
        });
}

applyStatus('pending');
poll();
</script>
<?php else: ?>
<div class="success-wrap">
    <div class="success-card">
        <div class="success-icon"><i class="icofont-restaurant"></i></div>
        <h1 class="success-title">Payment Received!</h1>
        <p class="success-sub">Your payment was successful! A confirmation email will be sent shortly.<br>Questions? Contact us on Facebook or call <strong>0912 345 6789</strong>.</p>
        <a href="index.php" class="btn-home">Back to Home</a>
    </div>
</div>
<?php endif; ?>

</body>
</html>