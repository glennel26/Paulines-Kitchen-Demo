<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit(); }
require_once '../../config/db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $row = $conn->query("SELECT image FROM dishes WHERE id=$id")->fetch_assoc();
    if ($row && $row['image'] && file_exists('../../' . $row['image'])) {
        unlink('../../' . $row['image']);
    }
    $conn->query("DELETE FROM dishes WHERE id=$id");
}
$conn->close();
header('Location: ../menu.php?msg=Dish+deleted.');
exit();