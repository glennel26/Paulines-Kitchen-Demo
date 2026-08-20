<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/db.php';
$msg = '';
$msgType = '';
require_once '../user/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (isset($_POST['update_status'])) {
    $id = intval($_POST['request_id']);
    $newStatus = trim($_POST['status']);
    $upd = $conn->prepare("UPDATE custom_requests SET status = ? WHERE id = ?");
    $upd->bind_param('si', $newStatus, $id);
    if ($upd->execute()) {
        $msg = "<i class=\"icofont-check\"></i> Status updated to <strong>" . htmlspecialchars($newStatus) . "</strong>.";
        $msgType = 'success';
        $req_stmt = $conn->prepare("SELECT email, full_name, dish_name FROM custom_requests WHERE id = ?");
        $req_stmt->bind_param('i', $id);
        $req_stmt->execute();
        $req = $req_stmt->get_result()->fetch_assoc();
        if ($req && !empty($req['email'])) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = 'HOST HERE';
                $mail->SMTPAuth   = true;
                $mail->Username   = 'YOUR EMAIL HERE';
                $mail->Password   = 'PASSWORD HERE';
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;
                $mail->CharSet    = 'UTF-8';
                $mail->setFrom('YOUR EMAIL HERE', "Paulines' Kitchen");
                $mail->addAddress($req['email'], $req['full_name']);
                $mail->isHTML(true);
                $mail->Subject = 'Custom Order Update - Paulines\' Kitchen';
                $statusMsg = '';
                if ($newStatus == 'quoted') {
                    $statusMsg = 'We have prepared a quote for your request. Please contact us to proceed. :)';
                } elseif ($newStatus == 'completed') {
                    $statusMsg = 'Your custom order is complete and ready! :)';
                } elseif ($newStatus == 'cancelled') {
                    $statusMsg = 'Your custom order has been cancelled. Please contact us if you have questions. :(';
                } else {
                    $statusMsg = 'The status of your custom order has been updated to: ' . ucfirst($newStatus) . '.';
                }
                $mail->Body = "
                <div style='font-family: Arial, sans-serif; color: #1C3144;'>
                    <h3>Hi {$req['full_name']},</h3>
                    <p>There is an update regarding your custom request for <strong>{$req['dish_name']}</strong>.</p>
                    <p>{$statusMsg}</p>
                    <br>
                    <p>Thank you,<br>Paulines' Kitchen Team</p>
                </div>
                ";
                $mail->send();
                $msg .= " Email notification sent.";
            } catch (Exception $e) {
                error_log("Mailer Error: {$mail->ErrorInfo}");
            }
        }
    } else {
        $msg = '<i class="icofont-close"></i> Failed to update status.';
        $msgType = 'error';
    }
}
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM custom_requests WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    header('Location: custom_orders.php?deleted=1');
    exit();
}
$fStatus = $_GET['status'] ?? 'all';
$search = trim($_GET['search'] ?? '');
$where = "1=1";
$params = [];
$types = "";
if ($fStatus !== 'all') {
    $where .= " AND status = ?";
    $params[] = $fStatus;
    $types .= "s";
}
if ($search) {
    $like = "%$search%";
    $where .= " AND (full_name LIKE ? OR dish_name LIKE ? OR contact LIKE ?)";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
$sql = "SELECT * FROM custom_requests WHERE $where ORDER BY created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$requests = $stmt->get_result();
$counts = [];
foreach (['all', 'pending', 'quoted', 'completed', 'cancelled'] as $s) {
    $q = $s === 'all' ? "SELECT COUNT(*) as c FROM custom_requests" : "SELECT COUNT(*) as c FROM custom_requests WHERE status = ?";
    $cs = $conn->prepare($q);
    if ($s !== 'all') $cs->bind_param('s', $s);
    $cs->execute();
    $counts[$s] = $cs->get_result()->fetch_assoc()['c'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Orders – Admin | Paulines' Kitchen</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="stylesheet" href="../images/icons/icofont/icofont.min.css">
    <link rel="icon" href="../images/logoo.png">
    <style>
        .filter-bar {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          margin-bottom: 24px;
          align-items: center;
        }
        .filter-btn {
          padding: 8px 18px;
          border-radius: 20px;
          border: 2px solid #e0dbd0;
          background: #fff;
          font-family: "Raleway", sans-serif;
          font-size: 13px;
          font-weight: 700;
          color: #888;
          cursor: pointer;
          text-decoration: none;
          transition: all 0.2s;
          white-space: nowrap;
        }
        .filter-btn:hover {
          border-color: #7ea16b;
          color: #7ea16b;
        }
        .filter-btn.active {
          background: #1c3144;
          border-color: #1c3144;
          color: #fff;
        }
        .search-box {
          margin-left: auto;
        }
        .search-box input {
          padding: 9px 16px;
          border: 1.5px solid #c3d898;
          border-radius: 8px;
          font-family: "Raleway", sans-serif;
          font-size: 14px;
          outline: none;
          width: 240px;
        }
        .search-box input:focus {
          border-color: #7ea16b;
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
        .s-quoted {
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
        .status-select {
          padding: 7px 10px;
          border: 1.5px solid #c3d898;
          border-radius: 6px;
          font-family: "Raleway", sans-serif;
          font-size: 13px;
          color: #1c3144;
          outline: none;
        }
        .action-row {
          display: flex;
          gap: 6px;
          align-items: center;
          flex-wrap: wrap;
          margin-top: 6px;
        }
        @media (max-width: 768px) {
          /* Filter bar: pills wrap in a row, search goes below */
          .filter-bar {
            flex-direction: row;
            flex-wrap: wrap;
            gap: 6px;
          }
          .filter-btn {
            padding: 6px 13px;
            font-size: 12px;
          }
          .search-box {
            margin-left: 0;
            width: 100%;
            order: 99;
          }
          .search-box input {
            width: 100%;
            box-sizing: border-box;
          }
          /* Compact 2-col grid cards for custom orders */
          .admin-table tr {
            display: grid !important;
            grid-template-columns: 1fr 1fr;
            gap: 4px 12px;
            padding: 12px 14px;
          }
          .admin-table td {
            display: flex !important;
            flex-direction: column;
            padding: 4px 0;
            font-size: 12px;
            overflow: hidden;
          }
          .admin-table td::before {
            font-size: 9px;
            min-width: unset;
            margin-bottom: 2px;
          }
          /* Actions cell full width */
          .admin-table td:last-child {
            grid-column: 1 / -1;
            flex-direction: row;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 4px;
            padding-top: 8px;
            border-top: 1px solid #f0ebe0;
          }
          .admin-table td:last-child::before {
            display: none;
          }
          .status-select {
            width: 100%;
            font-size: 12px;
          }
          .action-row {
            gap: 4px;
          }
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1>Custom Orders</h1>
            <span>Review inquiries for custom dishes and contact customers.</span>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="admin-alert <?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="admin-alert success"><i class="icofont-trash"></i> Request deleted.</div>
    <?php endif; ?>
    <div class="filter-bar">
        <?php
        $statuses = [
            'all'       => 'All (' . $counts['all'] . ')',
            'pending'   => 'Pending (' . $counts['pending'] . ')',
            'quoted'    => 'Quoted (' . $counts['quoted'] . ')',
            'completed' => 'Completed (' . $counts['completed'] . ')',
            'cancelled' => 'Cancelled (' . $counts['cancelled'] . ')',
        ];
        foreach ($statuses as $val => $label): ?>
            <a href="?status=<?= urlencode($val) ?>" class="filter-btn <?= $fStatus === $val ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
        <div class="search-box">
            <form method="GET">
                <input type="hidden" name="status" value="<?= htmlspecialchars($fStatus) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, dish, contact...">
            </form>
        </div>
    </div>
    <div class="admin-table-wrap table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Dish Requested</th>
                    <th>Pax / Qty</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($requests->num_rows > 0):
                while ($row = $requests->fetch_assoc()):
                    $sc = match($row['status']) {
                        'pending'   => 's-pending',
                        'quoted'    => 's-quoted',
                        'completed' => 's-completed',
                        'cancelled' => 's-cancelled',
                        default     => 's-pending'
                    };
            ?>
                <tr>
                    <td data-label="Date"><?= date('M j, Y<\b\r>g:i A', strtotime($row['created_at'])) ?></td>
                    <td data-label="Customer">
                        <strong><?= htmlspecialchars($row['full_name']) ?></strong><br>
                        <small style="color:#aaa;"><i class="icofont-phone"></i> <?= htmlspecialchars($row['contact']) ?></small>
                    </td>
                    <td data-label="Dish">
                        <strong style="color:#1C3144;"><?= htmlspecialchars($row['dish_name']) ?></strong>
                    </td>
                    <td data-label="Pax/Qty">
                        <?= $row['pax'] ?> Pax<br>
                        <span style="color:#7EA16B;">×<?= $row['quantity'] ?></span>
                    </td>
                    <td data-label="Status"><span class="badge-status <?= $sc ?>"><?= ucfirst($row['status']) ?></span></td>
                    <td data-label="">
                        <form method="POST">
                            <input type="hidden" name="request_id" value="<?= $row['id'] ?>">
                            <select name="status" class="status-select">
                                <?php foreach (['pending', 'quoted', 'completed', 'cancelled'] as $s): ?>
                                    <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="action-row">
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save + Email</button>
                                <a href="custom_orders.php?delete=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this request?')">Del</a>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endwhile;
            else: ?>
                <tr><td colspan="6" style="text-align:center;padding:50px;color:#aaa;font-style:italic;">No custom requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
<?php $conn->close(); ?>
