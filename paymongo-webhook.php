<?php
// paymongo-webhook.php  (place in ECOMMERCE root)
// Receives PayMongo webhook events — NO session needed here

require_once 'config/db.php';
require_once 'vendor/autoload.php'; // Composer — PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ── CONFIG ──────────────────────────────────────────────
define('PAYMONGO_WEBHOOK_SECRET', 'whsec_placeholder');
define('SMTP_HOST',     'HOST HERE');
define('SMTP_PORT',     587);
define('SMTP_USER',     'EMAIL HERE');
define('SMTP_PASS',     'sarq axdh tcdu seaa');
define('PICKUP_ADDRESS','45 University Avenue, San Miguel, Bulacan');
// ────────────────────────────────────────────────────────

// ── READ RAW PAYLOAD ─────────────────────────────────────
$raw_body  = file_get_contents('php://input');
$headers   = getallheaders();
$signature = $headers['Paymongo-Signature'] ?? '';

// ── VERIFY WEBHOOK SIGNATURE ─────────────────────────────
// PayMongo sends: t=timestamp,te=sig,li=sig
// We verify using HMAC SHA256
function verifySignature(string $rawBody, string $signatureHeader, string $secret): bool {
    $parts = [];
    foreach (explode(',', $signatureHeader) as $part) {
        [$key, $val] = explode('=', $part, 2);
        $parts[$key] = $val;
    }

    if (!isset($parts['t'], $parts['te'])) return false;

    $message  = $parts['t'] . '.' . $rawBody;
    $computed = hash_hmac('sha256', $message, $secret);

    return hash_equals($computed, $parts['te']);
}

if (!verifySignature($raw_body, $signature, PAYMONGO_WEBHOOK_SECRET)) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid signature']);
    exit();
}

// ── PARSE EVENT ──────────────────────────────────────────
$event = json_decode($raw_body, true);
$type  = $event['data']['attributes']['type'] ?? '';

// We only care about checkout session completion
if ($type !== 'checkout_session.payment.paid') {
    http_response_code(200);
    echo json_encode(['received' => true]);
    exit();
}

$session_id = $event['data']['attributes']['data']['id'] ?? '';

if (!$session_id) {
    http_response_code(400);
    echo json_encode(['error' => 'No session ID']);
    exit();
}

// ── FIND ORDER IN DATABASE ───────────────────────────────
$stmt = $conn->prepare("SELECT * FROM orders WHERE paymongo_session_id = ? AND status = 'Pending Payment'");
$stmt->bind_param('s', $session_id);
$stmt->execute();
$result = $stmt->get_result();
$order  = $result->fetch_assoc();

if (!$order) {
    // Already processed or not found
    http_response_code(200);
    echo json_encode(['received' => true, 'note' => 'Order not found or already processed']);
    exit();
}

// ── UPDATE ORDER STATUS ──────────────────────────────────
$update = $conn->prepare("UPDATE orders SET status = 'Paid & Preparing' WHERE id = ?");
$update->bind_param('i', $order['id']);
$update->execute();

// ── CLEAR CUSTOMER CART ──────────────────────────────────
// Cart is session-based — nothing to clear server side
// The success page handles clearing $_SESSION['cart']

// ── FETCH ORDER ITEMS ────────────────────────────────────
$items_stmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
$items_stmt->bind_param('i', $order['id']);
$items_stmt->execute();
$items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// ── SEND CONFIRMATION EMAIL ──────────────────────────────
sendConfirmationEmail($order, $items);

http_response_code(200);
echo json_encode(['success' => true, 'reference' => $order['reference']]);
exit();


// ════════════════════════════════════════════════════════
//  EMAIL FUNCTION
// ════════════════════════════════════════════════════════
function sendConfirmationEmail(array $order, array $items): void {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        $mail->CharSet    = 'UTF-8';

        // Recipients
        $mail->setFrom(SMTP_USER, "Paulines' Kitchen");
        $mail->addAddress($order['email'], $order['fullname']);
        $mail->addReplyTo(SMTP_USER, "Paulines' Kitchen");

        // Content
        $mail->isHTML(true);
        $mail->Subject = '🍱 Order Confirmed! ' . $order['reference'] . ' – Paulines Kitchen';
        $mail->Body    = buildEmailHTML($order, $items);
        $mail->AltBody = buildEmailPlainText($order, $items);

        $mail->send();

    } catch (Exception $e) {
        error_log('PHPMailer error: ' . $mail->ErrorInfo);
    }
}


// ════════════════════════════════════════════════════════
//  HTML EMAIL TEMPLATE
// ════════════════════════════════════════════════════════
function buildEmailHTML(array $order, array $items): string {
    $is_courier   = $order['fulfillment'] === 'courier';
    $ref          = htmlspecialchars($order['reference']);
    $name         = htmlspecialchars($order['fullname']);
    $target_dt    = date('F j, Y \a\t g:i A', strtotime($order['target_datetime']));
    $total        = number_format($order['total'], 2);

    // Build itemized rows
    $item_rows = '';
    foreach ($items as $item) {
        $unit     = $item['unit_price'] + $item['rice_addon'];
        $subtotal = $unit * $item['quantity'];
        $meta     = [];
        if ($item['pax'])         $meta[] = $item['pax'] . ' pax';
        if ($item['rice_addon'] > 0) $meta[] = 'Rice +₱' . number_format($item['rice_addon'], 2);
        $meta_str = $meta ? '<br><span style="font-size:12px;color:#888;">' . implode(' · ', $meta) . '</span>' : '';

        $item_rows .= '
        <tr>
            <td style="padding:12px 0;border-bottom:1px solid #f0ebe0;">
                ' . htmlspecialchars($item['item_name']) . $meta_str . '
                <span style="color:#7EA16B;font-weight:700;"> ×' . (int)$item['quantity'] . '</span>
            </td>
            <td style="padding:12px 0;border-bottom:1px solid #f0ebe0;text-align:right;font-weight:700;color:#70161E;">
                PHP ' . number_format($subtotal, 2) . '
            </td>
        </tr>';
    }

    // Fulfillment-specific block
    if ($is_courier) {
        $fulfillment_block = '
        <div style="background:#fff8e6;border:1px solid #e6c96e;border-radius:10px;padding:16px 20px;margin-top:24px;">
            <p style="font-weight:700;color:#7a5c00;margin:0 0 6px;">🛵 Delivery Reminder</p>
            <p style="font-size:14px;color:#7a5c00;margin:0;line-height:1.6;">
                We will book the rider for you! The exact delivery fee will be based on the courier\'s rate and 
                must be <strong>paid directly to the rider upon arrival</strong>. Please prepare cash for this.
            </p>
        </div>';
    } else {
        $fulfillment_block = '
        <div style="background:#f4faf0;border:1px solid #C3D898;border-radius:10px;padding:16px 20px;margin-top:24px;">
            <p style="font-weight:700;color:#2E4A2E;margin:0 0 6px;">📍 Pick-up Address</p>
            <p style="font-size:14px;color:#2E4A2E;margin:0;line-height:1.6;">
                <strong>' . PICKUP_ADDRESS . '</strong><br>
                Please show this email or your reference number upon pick-up.
            </p>
        </div>';
    }

    return '
<!DOCTYPE html>
<html>
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#F5F0E8;font-family:\'Helvetica Neue\',Arial,sans-serif;">

<table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F0E8;padding:40px 20px;">
<tr><td align="center">
<table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">

    <!-- HEADER -->
    <tr>
        <td style="background:#2E4A2E;border-radius:14px 14px 0 0;padding:36px 40px;text-align:center;">
            <h1 style="margin:0;font-size:28px;font-weight:800;color:#fff;letter-spacing:1px;">
                Paulines\' Kitchen
            </h1>
            <p style="margin:8px 0 0;color:#C3D898;font-size:15px;font-style:italic;">
                Good Food = Good Mood
            </p>
        </td>
    </tr>

    <!-- GREEN BAR -->
    <tr><td style="height:6px;background:#7EA16B;"></td></tr>

    <!-- BODY -->
    <tr>
        <td style="background:#fff;padding:40px;">

            <p style="font-size:16px;color:#1C3144;margin:0 0 6px;">Hi <strong>' . $name . '</strong>! 👋</p>
            <p style="font-size:15px;color:#596F62;margin:0 0 28px;">
                Your order has been <strong style="color:#2E4A2E;">confirmed and paid</strong>. 
                We\'re already preparing your food! Here\'s your receipt.
            </p>

            <!-- REFERENCE BOX -->
            <div style="background:#F5F0E8;border-radius:10px;padding:16px 24px;margin-bottom:28px;text-align:center;">
                <p style="font-size:12px;color:#888;margin:0 0 4px;letter-spacing:1px;text-transform:uppercase;">Order Reference</p>
                <p style="font-size:28px;font-weight:800;color:#1C3144;margin:0;letter-spacing:2px;">' . $ref . '</p>
            </div>

            <!-- ITEMIZED RECEIPT -->
            <p style="font-weight:800;font-size:15px;color:#1C3144;margin:0 0 12px;">🧾 Your Order</p>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#1C3144;">
                ' . $item_rows . '
                <tr>
                    <td style="padding:16px 0 0;font-weight:800;font-size:16px;color:#1C3144;">Total Paid</td>
                    <td style="padding:16px 0 0;text-align:right;font-weight:800;font-size:18px;color:#70161E;">
                        PHP ' . $total . '
                    </td>
                </tr>
            </table>

            <!-- TARGET DATE -->
            <div style="background:#f4faf0;border-left:4px solid #7EA16B;border-radius:6px;padding:14px 18px;margin-top:28px;">
                <p style="font-size:12px;color:#888;margin:0 0 4px;text-transform:uppercase;letter-spacing:1px;">Target Date & Time</p>
                <p style="font-size:16px;font-weight:700;color:#1C3144;margin:0;">📅 ' . $target_dt . '</p>
            </div>

            ' . $fulfillment_block . '

            <!-- WHAT HAPPENS NEXT -->
            <p style="font-weight:800;font-size:15px;color:#1C3144;margin:32px 0 16px;">What Happens Next?</p>
            <table width="100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td width="44" valign="top">
                        <div style="width:36px;height:36px;background:#7EA16B;border-radius:50%;text-align:center;line-height:36px;color:#fff;font-weight:800;font-size:14px;">1</div>
                    </td>
                    <td style="padding-bottom:18px;">
                        <p style="font-weight:700;color:#1C3144;margin:0 0 2px;">✅ Order Confirmed</p>
                        <p style="font-size:13px;color:#888;margin:0;">We\'ve received your payment and logged your order.</p>
                    </td>
                </tr>
                <tr>
                    <td width="44" valign="top">
                        <div style="width:36px;height:36px;background:#7EA16B;border-radius:50%;text-align:center;line-height:36px;color:#fff;font-weight:800;font-size:14px;">2</div>
                    </td>
                    <td style="padding-bottom:18px;">
                        <p style="font-weight:700;color:#1C3144;margin:0 0 2px;">👩‍🍳 Kitchen Prep</p>
                        <p style="font-size:13px;color:#888;margin:0;">Our cooks are preparing your order fresh for your target date.</p>
                    </td>
                </tr>
                <tr>
                    <td width="44" valign="top">
                        <div style="width:36px;height:36px;background:#7EA16B;border-radius:50%;text-align:center;line-height:36px;color:#fff;font-weight:800;font-size:14px;">3</div>
                    </td>
                    <td>
                        <p style="font-weight:700;color:#1C3144;margin:0 0 2px;">' . ($is_courier ? '🛵 Dispatch' : '📦 Ready for Pick-up') . '</p>
                        <p style="font-size:13px;color:#888;margin:0;">' . ($is_courier ? 'We\'ll book a rider to deliver your order on your target date.' : 'Your order will be ready at our pick-up address on your target date.') . '</p>
                    </td>
                </tr>
            </table>

            <p style="font-size:13px;color:#aaa;margin:32px 0 0;text-align:center;">
                Questions? Message us on <a href="https://www.facebook.com/profile.php?id=61588361559105" style="color:#7EA16B;">Facebook</a> 
                or call <strong>0912 345 6789</strong>.
            </p>

        </td>
    </tr>

    <!-- RED BAR -->
    <tr><td style="height:6px;background:#70161E;border-radius:0 0 14px 14px;"></td></tr>

    <!-- FOOTER -->
    <tr>
        <td style="padding:24px;text-align:center;">
            <p style="font-size:12px;color:#aaa;margin:0;">
                &copy; 2026 Paulines\' Kitchen &nbsp;·&nbsp; San Miguel, Bulacan<br>
                This is an automated email. Please do not reply directly to this message.
            </p>
        </td>
    </tr>

</table>
</td></tr>
</table>

</body>
</html>';
}


// ════════════════════════════════════════════════════════
//  PLAIN TEXT FALLBACK
// ════════════════════════════════════════════════════════
function buildEmailPlainText(array $order, array $items): string {
    $is_courier = $order['fulfillment'] === 'courier';
    $target_dt  = date('F j, Y \a\t g:i A', strtotime($order['target_datetime']));
    $lines      = [];

    $lines[] = "PAULINES' KITCHEN — ORDER CONFIRMED";
    $lines[] = "=====================================";
    $lines[] = "Hi {$order['fullname']}!";
    $lines[] = "Your order is confirmed and paid.";
    $lines[] = "";
    $lines[] = "Order Reference: {$order['reference']}";
    $lines[] = "Target Date & Time: {$target_dt}";
    $lines[] = "";
    $lines[] = "YOUR ORDER:";
    $lines[] = "------------";

    foreach ($items as $item) {
        $subtotal = ($item['unit_price'] + $item['rice_addon']) * $item['quantity'];
        $lines[]  = "- {$item['item_name']} x{$item['quantity']} — PHP " . number_format($subtotal, 2);
    }

    $lines[] = "";
    $lines[] = "TOTAL PAID: PHP " . number_format($order['total'], 2);
    $lines[] = "";

    if ($is_courier) {
        $lines[] = "DELIVERY REMINDER:";
        $lines[] = "We will book the rider for you. Please prepare cash for the delivery fee — to be paid directly to the rider upon arrival.";
    } else {
        $lines[] = "PICK-UP ADDRESS:";
        $lines[] = PICKUP_ADDRESS;
    }

    $lines[] = "";
    $lines[] = "WHAT HAPPENS NEXT:";
    $lines[] = "1. Order Confirmed — We've received your payment.";
    $lines[] = "2. Kitchen Prep — Our cooks are preparing your food.";
    $lines[] = "3. " . ($is_courier ? "Dispatch — We'll book a rider for your order." : "Ready for Pick-up — Your order will be ready on your target date.");
    $lines[] = "";
    $lines[] = "Questions? Message us on Facebook or call 0912 345 6789.";
    $lines[] = "";
    $lines[] = "© 2026 Paulines' Kitchen · San Miguel, Bulacan";

    return implode("\n", $lines);
}
