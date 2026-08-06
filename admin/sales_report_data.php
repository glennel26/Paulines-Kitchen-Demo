<?php
require_once 'includes/auth.php';
require_once '../config/db.php';
$conn->query("SET time_zone = '+08:00'");

header('Content-Type: application/json');

$period = $_GET['period'] ?? 'weekly';

$labels  = [];
$revenue = [];
$orders  = [];

if ($period === 'weekly') {
    // Last 7 days
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('D, M j', strtotime($date));

        $rev = $conn->query("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE DATE(created_at)='$date' AND status NOT IN ('Pending Payment','Pending','Cancelled')")->fetch_assoc()['r'];
        $cnt = $conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at)='$date'")->fetch_assoc()['c'];

        $labels[]  = $label;
        $revenue[] = (float)$rev;
        $orders[]  = (int)$cnt;
    }
} elseif ($period === 'monthly') {
    // Last 12 months
    for ($i = 11; $i >= 0; $i--) {
        $year  = date('Y', strtotime("-$i months"));
        $month = date('m', strtotime("-$i months"));
        $label = date('M Y', strtotime("-$i months"));

        $rev = $conn->query("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE YEAR(created_at)=$year AND MONTH(created_at)=$month AND status NOT IN ('Pending Payment','Pending','Cancelled')")->fetch_assoc()['r'];
        $cnt = $conn->query("SELECT COUNT(*) as c FROM orders WHERE YEAR(created_at)=$year AND MONTH(created_at)=$month")->fetch_assoc()['c'];

        $labels[]  = $label;
        $revenue[] = (float)$rev;
        $orders[]  = (int)$cnt;
    }
} elseif ($period === 'yearly') {
    // Last 5 years
    for ($i = 4; $i >= 0; $i--) {
        $year  = date('Y') - $i;
        $label = (string)$year;

        $rev = $conn->query("SELECT COALESCE(SUM(total),0) as r FROM orders WHERE YEAR(created_at)=$year AND status NOT IN ('Pending Payment','Pending','Cancelled')")->fetch_assoc()['r'];
        $cnt = $conn->query("SELECT COUNT(*) as c FROM orders WHERE YEAR(created_at)=$year")->fetch_assoc()['c'];

        $labels[]  = $label;
        $revenue[] = (float)$rev;
        $orders[]  = (int)$cnt;
    }
}

// Summary totals for the selected period
$totalRev    = array_sum($revenue);
$totalOrders = array_sum($orders);
$avgOrder    = $totalOrders > 0 ? $totalRev / $totalOrders : 0;

echo json_encode([
    'labels'      => $labels,
    'revenue'     => $revenue,
    'orders'      => $orders,
    'totalRev'    => $totalRev,
    'totalOrders' => $totalOrders,
    'avgOrder'    => $avgOrder,
]);

$conn->close();
