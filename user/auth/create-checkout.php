<?php
session_start();
require_once '../../config/db.php';
define('PAYMONGO_SECRET_KEY', 'SECRET_KEY');
$_protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
define('SITE_URL', $_protocol . '://' . $_SERVER['HTTP_HOST']);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../checkout.php');
    exit();
}
if (empty($_SESSION['cart']) || !isset($_SESSION['user_id'])) {
    header('Location: ../../menu.php');
    exit();
}
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
    header('Location: ../../checkout.php?error=missing_fields');
    exit();
}
$cart       = $_SESSION['cart'];
$total      = 0;
$line_items = [];
foreach ($cart as $item) {
    $unit_price   = ($item['price'] + ($item['rice_addon'] ?? 0));
    $subtotal     = $unit_price * $item['quantity'];
    $total       += $subtotal;
    $line_items[] = [
        'currency'    => 'PHP',
        'amount'      => (int) round($unit_price * 100),
        'name'        => $item['name'] . (!empty($item['pax']) ? ' (' . $item['pax'] . ' pax)' : ''),
        'quantity'    => (int) $item['quantity'],
        'description' => ($item['rice_addon'] ?? 0) > 0 ? 'Includes rice add-on' : '',
    ];
}
$ref_query = $conn->query("SELECT COUNT(*) as cnt FROM orders");
if (!$ref_query) {
    error_log('orders table query failed: ' . $conn->error);
    header('Location: ../../checkout.php?error=db_error');
    exit();
}
$ref_row   = $ref_query->fetch_assoc();
$order_num = ($ref_row['cnt'] ?? 0) + 1;
$reference = 'PK-' . str_pad($order_num, 5, '0', STR_PAD_LEFT);
$initial_status = ($payment_method === 'cod') ? 'Pending' : 'Pending Payment';
$stmt = $conn->prepare("
    INSERT INTO orders
    (reference, user_id, fullname, phone, email, fulfillment, target_datetime, notes, address, landmark, total, status, created_at)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
");
if (!$stmt) {
    error_log('Order prepare failed: ' . $conn->error);
    header('Location: ../../checkout.php?error=db_error');
    exit();
}
$stmt->bind_param(
    'sissssssssds',
    $reference,
    $user_id,
    $fullname,
    $phone,
    $email,
    $fulfillment,
    $datetime,
    $notes,
    $address,
    $landmark,
    $total,
    $initial_status
);
if (!$stmt->execute()) {
    error_log('Order INSERT failed: ' . $stmt->error);
    header('Location: ../../checkout.php?error=db_error');
    exit();
}
$order_id = $conn->insert_id;
foreach ($cart as $item) {
    $istmt = $conn->prepare("
        INSERT INTO order_items (order_id, item_name, pax, quantity, unit_price, rice_addon)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$istmt) {
        error_log('Order item prepare failed: ' . $conn->error);
        continue;
    }
    $istmt->bind_param(
        'issidd',
        $order_id,
        $item['name'],
        $item['pax'],
        $item['quantity'],
        $item['price'],
        $item['rice_addon']
    );
    if (!$istmt->execute()) {
        error_log('Order item INSERT failed: ' . $istmt->error);
    }
}
$_SESSION['last_order_ref'] = $reference;
if ($payment_method === 'cod') {
    unset($_SESSION['cart']);
    header('Location: ../../order-success.php?ref=' . $reference);
    exit();
}
$payload = [
    'data' => [
        'attributes' => [
            'billing'              => [
                'name'  => $fullname,
                'phone' => $phone,
                'email' => $email,
            ],
            'line_items'           => $line_items,
            'payment_method_types' => ['gcash', 'paymaya', 'card'],
            'success_url'          => SITE_URL . '/order-success.php?ref=' . $reference,
            'cancel_url'           => SITE_URL . '/checkout.php?cancelled=1',
            'description'          => 'Paulines Kitchen Order ' . $reference,
            'reference_number'     => $reference,
            'metadata'             => [
                'order_id'  => $order_id,
                'reference' => $reference,
            ],
        ]
    ]
];
$ch = curl_init('https://api.paymongo.com/v1/checkout_sessions');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode(PAYMONGO_SECRET_KEY . ':'),
    ],
    CURLOPT_TIMEOUT        => 30,
    CURLOPT_SSL_VERIFYPEER => true,
]);
$response = curl_exec($ch);
$curl_err  = curl_error($ch);
curl_close($ch);
if ($curl_err) {
    error_log('PayMongo cURL error: ' . $curl_err);
    header('Location: ../../checkout.php?error=payment_init_failed');
    exit();
}
$result = json_decode($response, true);
if (!isset($result['data']['attributes']['checkout_url'])) {
    error_log('PayMongo response error: ' . $response);
    header('Location: ../../checkout.php?error=payment_init_failed');
    exit();
}
$session_id = $result['data']['id'];
$sstmt = $conn->prepare("UPDATE orders SET paymongo_session_id = ? WHERE id = ?");
if ($sstmt) {
    $sstmt->bind_param('si', $session_id, $order_id);
    $sstmt->execute();
}
unset($_SESSION['cart']);
$checkout_url = $result['data']['attributes']['checkout_url'];
header('Location: ' . $checkout_url);
exit();