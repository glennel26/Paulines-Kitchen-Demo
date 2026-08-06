<?php
session_start();
require_once '../../config/db.php';
$id          = intval($_POST['dish_id'] ?? 0);
$name        = trim($_POST['name'] ?? '');
$category    = trim($_POST['category'] ?? '');
if ($category === '__custom__') {
    $category = trim($_POST['custom_category'] ?? '');
}
$description = trim($_POST['description'] ?? '');
$price       = floatval($_POST['price'] ?? 0);
$pax         = trim($_POST['pax'] ?? '');
$available   = isset($_POST['available']) ? 1 : 0;
$image = null;
if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../../images/menu/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    $ext = strtolower(pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION));
    $image = uniqid('dish_') . '.' . $ext;
    move_uploaded_file($_FILES['image']['tmp_name'], $upload_dir . $image);
}
if ($id > 0) {
    if ($image) {
        $stmt = $conn->prepare("
            UPDATE menu 
            SET name=?, category=?, description=?, price=?, pax=?, image=?, available=?
            WHERE id=?
        ");
        $stmt->bind_param("sssdssii", $name, $category, $description, $price, $pax, $image, $available, $id);
    } else {
        $stmt = $conn->prepare("
            UPDATE menu 
            SET name=?, category=?, description=?, price=?, pax=?, available=?
            WHERE id=?
        ");
        $stmt->bind_param("sssdsii", $name, $category, $description, $price, $pax, $available, $id);
    }
} else {
    $stmt = $conn->prepare("
        INSERT INTO menu
        (name, category, description, price, pax, image, available)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("sssdssi", $name, $category, $description, $price, $pax, $image, $available);
}
if ($stmt) {
    $stmt->execute();
}
header('Location: ../menu.php?saved=1');
exit();