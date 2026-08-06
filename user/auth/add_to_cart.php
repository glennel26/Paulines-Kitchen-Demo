<?php
session_start();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../menu.php');
    exit();
}
$name      = trim($_POST['name']);
$price     = floatval($_POST['price']);
$quantity  = max(1, intval($_POST['quantity']));
$rice      = floatval($_POST['rice_addon'] ?? 0);
$pax       = !empty($_POST['pax']) ? intval($_POST['pax']) : null;
$image     = trim($_POST['image'] ?? '');
$item = [
    'name'       => $name,
    'price'      => $price,
    'quantity'   => $quantity,
    'rice_addon' => $rice,
    'pax'        => $pax,
    'image'      => $image,
];
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}
if (!empty($_GET['clear'])) {
    $_SESSION['cart'] = [];
}
$found = false;
foreach ($_SESSION['cart'] as &$cart_item) {
    if (
        $cart_item['name']       === $item['name'] &&
        $cart_item['rice_addon'] === $item['rice_addon'] &&
        $cart_item['pax']        === $item['pax']
    ) {
        $cart_item['quantity'] += $quantity;
        $found = true;
        break;
    }
}
unset($cart_item);
if (!$found) {
    $_SESSION['cart'][] = $item;
}
header('Location: ../../cart.php');
exit();
?>