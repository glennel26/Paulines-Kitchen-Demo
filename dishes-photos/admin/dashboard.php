<?php
require_once 'includes/auth.php';

$conn = new mysqli('localhost', 'root', '', 'paulines_kitchen');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$total     = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Pending Payment'")->fetch_assoc()['c'];
$preparing = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Paid & Preparing'")->fetch_assoc()['c'];
$ready     = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Ready'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Completed'")->fetch_assoc()['c'];
$revenue   = $conn->query("SELECT SUM(total) as r FROM orders WHERE status != 'Pending Payment' AND status != 'Cancelled'")->fetch_assoc()['r'] ?? 0;

$todayCount   = $conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$todayRev = $conn->query("SELECT SUM(total) as r FROM orders WHERE DATE(created_at) = CURDATE() AND status != 'Pending Payment'")->fetch_assoc()['r'] ?? 0;

$recent = $conn->query("SELECT * FROM orders ORDER BY created_at DESC LIMIT 5");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard – Admin | Paulines' Kitchen</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="../images/icons/icofont/icofont.min.css">
    
    <link rel="icon" href="../images/logoo.png">
    <style>
        .stats-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 20px;
          margin-bottom: 32px;
        }
        .stat-card {
          background: #fff;
          border-radius: 14px;
          padding: 24px;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
          border-top: 4px solid #7ea16b;
          text-align: center;
        }
        .stat-card.red {
          border-top-color: #70161e;
        }
        .stat-card.navy {
          border-top-color: #1c3144;
        }
        .stat-card.orange {
          border-top-color: #c3842a;
        }
        .stat-card.green {
          border-top-color: #2e4a2e;
        }
        .stat-num {
          font-size: 34px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 4px;
        }
        .stat-label {
          font-size: 13px;
          color: #888;
          font-weight: 600;
        }

        .section-title {
          font-size: 18px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 16px;
        }
        .today-bar {
          display: flex;
          gap: 20px;
          margin-bottom: 32px;
        }
        .today-card {
          flex: 1;
          background: #1c3144;
          border-radius: 14px;
          padding: 20px 24px;
          color: #fff;
          text-align: center;
        }
        .today-card .num {
          font-size: 28px;
          font-weight: 800;
          color: #c3d898;
        }
        .today-card .lbl {
          font-size: 13px;
          color: rgba(255, 255, 255, 0.6);
          margin-top: 4px;
        }

        .badge-status {
          display: inline-block;
          padding: 4px 10px;
          border-radius: 20px;
          font-size: 11px;
          font-weight: 700;
          white-space: nowrap;
        }
        .s-pending {
          background: #fff3cd;
          color: #856404;
        }
        .s-paid {
          background: #d4edda;
          color: #155724;
        }
        .s-ready {
          background: #cce5ff;
          color: #004085;
        }
        .s-completed {
          background: #c3d898;
          color: #1c3144;
        }
        .s-cancelled {
          background: #f8d7da;
          color: #70161e;
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Dashboard</h1>
            <span>Welcome back, <?= htmlspecialchars($_SESSION['admin_name']) ?>! Here's what's happening today.</span>
        </div>
    </div>

    <div class="today-bar">
        <div class="today-card">
            <div class="num"><?= $todayCount ?></div>
            <div class="lbl">Orders Today</div>
        </div>
        <div class="today-card">
            <div class="num">₱<?= number_format($todayRev, 2) ?></div>
            <div class="lbl">Revenue Today</div>
        </div>
        <div class="today-card">
            <div class="num">₱<?= number_format($revenue, 2) ?></div>
            <div class="lbl">Total Revenue (All Time)</div>
        </div>
    </div>

    <div class="stats-grid">
        <div class="stat-card navy">
            <div class="stat-num"><?= $total ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
        <div class="stat-card red">
            <div class="stat-num"><?= $pending ?></div>
            <div class="stat-label">Pending Payment</div>
        </div>
        <div class="stat-card orange">
            <div class="stat-num"><?= $preparing ?></div>
            <div class="stat-label">Paid & Preparing</div>
        </div>
        <div class="stat-card">
            <div class="stat-num"><?= $ready ?></div>
            <div class="stat-label">Ready</div>
        </div>
    </div>

    <div class="section-title">Recent Orders</div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Fulfillment</th>
                    <th>Target Date</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php if ($recent->num_rows > 0):
                while ($row = $recent->fetch_assoc()): ?>
                <tr>
                    <td data-label="Reference"><strong><?= htmlspecialchars($row['reference']) ?></strong></td>
                    <td data-label="Customer">
                        <?= htmlspecialchars($row['fullname']) ?><br>
                        <small style="color:#aaa;"><?= htmlspecialchars($row['email']) ?></small>
                    </td>
                    <td data-label="Total" style="font-weight:700;color:#70161E;">₱<?= number_format($row['total'], 2) ?></td>
                    <td data-label="Type"><?= ucfirst($row['fulfillment']) ?></td>
                    <td data-label="Date"><?= date('M j, Y g:i A', strtotime($row['target_datetime'])) ?></td>
                    <td data-label="Status">
                        <?php
                        $sc = match($row['status']) {
                            'Pending Payment' => 's-pending',
                            'Paid & Preparing' => 's-paid',
                            'Ready' => 's-ready',
                            'Completed' => 's-completed',
                            'Cancelled' => 's-cancelled',
                            default => 's-pending'
                        };
                        ?>
                        <span class="badge-status <?= $sc ?>"><?= $row['status'] ?></span>
                    </td>
                    <td data-label=""><a href="orders.php" class="btn btn-sm btn-outline">View All</a></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#aaa;font-style:italic;">No orders yet.</td></tr>
            <?php endif; ?>

            </tbody>
        </table>
    </div>

</div>
</body>
</html>
<?php $conn->close(); ?>