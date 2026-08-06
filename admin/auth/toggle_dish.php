<?php
session_start();
if (!isset($_SESSION['admin_id'])) { header('Location: ../login.php'); exit(); }
require_once '../../config/db.php';
$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $conn->query("UPDATE dishes SET is_available = NOT is_available WHERE id=$id");
}
$conn->close();
header('Location: ../menu.php?msg=Availability+updated.');
exit();