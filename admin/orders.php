<?php
session_start();
require_once 'includes/auth.php';
require_once '../user/vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once '../config/db.php';
$msg     = '';
$msgType = '';
if (isset($_POST['update_status'])) {
    $id        = intval($_POST['order_id']);
    $newStatus = trim($_POST['status']);
    $upd = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
    $upd->bind_param('si', $newStatus, $id);
    if ($upd->execute()) {
        $ostmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
        $ostmt->bind_param('i', $id);
        $ostmt->execute();
        $order = $ostmt->get_result()->fetch_assoc();
        $istmt = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $istmt->bind_param('i', $id);
        $istmt->execute();
        $items = $istmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $emailSent = sendStatusEmail($order, $items, $newStatus);
        $msg     = "<i class=\"icofont-check\"></i> Status updated to <strong>{$newStatus}</strong>." . ($emailSent ? ' Email sent to customer.' : ' (Email failed — check SMTP)');
        $msgType = 'success';
    } else {
        $msg     = '<i class="icofont-close"></i> Failed to update status. Please try again.';
        $msgType = 'error';
    }
}
if (isset($_GET['delete'])) {
    $id  = intval($_GET['delete']);
    $del = $conn->prepare("DELETE FROM orders WHERE id = ?");
    $del->bind_param('i', $id);
    $del->execute();
    header('Location: orders.php?deleted=1');
    exit();
}
$fStatus = $_GET['status'] ?? 'all';
$search  = trim($_GET['search'] ?? '');
$where  = '1=1';
$params = [];
$types  = '';
if ($fStatus !== 'all') {
    $where   .= ' AND o.status = ?';
    $params[] = $fStatus;
    $types   .= 's';
}
if ($search) {
    $like     = '%' . $search . '%';
    $where   .= ' AND (o.fullname LIKE ? OR o.reference LIKE ? OR o.email LIKE ?)';
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types   .= 'sss';
}
$sql  = "SELECT o.* FROM orders o WHERE {$where} ORDER BY o.created_at DESC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$orders = $stmt->get_result();
$counts = [];
foreach (['all','Pending Payment','Paid & Preparing','Ready','Completed','Cancelled'] as $s) {
    $q  = $s === 'all' ? "SELECT COUNT(*) as c FROM orders" : "SELECT COUNT(*) as c FROM orders WHERE status = ?";
    $cs = $conn->prepare($q);
    if ($s !== 'all') $cs->bind_param('s', $s);
    $cs->execute();
    $counts[$s] = $cs->get_result()->fetch_assoc()['c'];
}
function sendStatusEmail(array $order, array $items, string $newStatus): bool {
    if ($newStatus === 'Pending Payment') return false;
    $statusMessages = [
        'Paid & Preparing' => ['subject' => 'Your order is being prepared!', 'headline' => "We're cooking your food!", 'body' => "Great news! Your payment has been confirmed and our kitchen is now preparing your order."],
        'Ready'            => ['subject' => 'Your order is Ready!',           'headline' => 'Your order is ready!',       'body' => $order['fulfillment'] === 'courier' ? "Your order is ready and a rider is on the way!" : "Your order is packed and ready for pick-up!"],
        'Completed'        => ['subject' => 'Order Completed - Thank you!',   'headline' => 'Order Completed!',           'body' => "Thank you for choosing Paulines' Kitchen! We hope you enjoyed your meal. See you again soon!"],
        'Cancelled'        => ['subject' => 'Your order has been cancelled',  'headline' => 'Order Cancelled',            'body' => "Unfortunately your order has been cancelled. Please contact us if you have questions or to place a new order."],
    ];
    if (!isset($statusMessages[$newStatus])) return false;
    $msg  = $statusMessages[$newStatus];
    $ref  = $order['reference'];
    $name = $order['fullname'];
    $item_rows = '';
    foreach ($items as $item) {
        $sub = ($item['unit_price'] + $item['rice_addon']) * $item['quantity'];
        $item_rows .= '<tr>
            <td style="padding:10px 0;border-bottom:1px solid #f0ebe0;color:#1C3144;">'
            . htmlspecialchars($item['item_name'])
            . ($item['pax'] ? ' (' . $item['pax'] . ' pax)' : '')
            . ' &times;' . $item['quantity'] . '</td>
            <td style="padding:10px 0;border-bottom:1px solid #f0ebe0;text-align:right;font-weight:700;color:#70161E;">PHP ' . number_format($sub, 2) . '</td>
        </tr>';
    }
    $fulfillment_note = '';
    if ($newStatus === 'Ready') {
        $fulfillment_note = $order['fulfillment'] === 'courier'
            ? '<div style="background:#fff8e6;border:1px solid #e6c96e;border-radius:8px;padding:14px 18px;margin-top:20px;font-size:14px;color:#7a5c00;">[Delivery] <strong>Delivery Reminder:</strong> Please prepare cash for the rider fee upon arrival.</div>'
            : '<div style="background:#f4faf0;border:1px solid #C3D898;border-radius:8px;padding:14px 18px;margin-top:20px;font-size:14px;color:#2E4A2E;">[Pick-up] <strong>Pick-up Address:</strong> San Miguel, Bulacan. Show reference <strong>' . $ref . '</strong> upon arrival.</div>';
    }
    $html = '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#F5F0E8;font-family:Arial,sans-serif;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background:#F5F0E8;padding:40px 20px;">
    <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;">
        <tr><td style="background:#2E4A2E;border-radius:14px 14px 0 0;padding:32px 40px;text-align:center;">
            <h1 style="margin:0;font-size:26px;font-weight:800;color:#fff;">Paulines\' Kitchen</h1>
            <p style="margin:6px 0 0;color:#C3D898;font-size:14px;font-style:italic;">Good Food = Good Mood</p>
        </td></tr>
        <tr><td style="height:5px;background:#7EA16B;"></td></tr>
        <tr><td style="background:#fff;padding:36px 40px;">
            <p style="font-size:15px;color:#1C3144;margin:0 0 6px;">Hi <strong>' . htmlspecialchars($name) . '</strong>! :)</p>
            <h2 style="font-size:22px;color:#1C3144;margin:0 0 16px;">' . $msg['headline'] . '</h2>
            <p style="font-size:15px;color:#596F62;margin:0 0 24px;line-height:1.6;">' . $msg['body'] . '</p>
            <div style="background:#F5F0E8;border-radius:10px;padding:14px 24px;margin-bottom:24px;text-align:center;">
                <p style="font-size:11px;color:#aaa;margin:0 0 4px;letter-spacing:1px;text-transform:uppercase;">Order Reference</p>
                <p style="font-size:24px;font-weight:800;color:#1C3144;margin:0;letter-spacing:2px;">' . $ref . '</p>
            </div>
            <table width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;">' . $item_rows . '
                <tr>
                    <td style="padding:14px 0 0;font-weight:800;font-size:15px;color:#1C3144;">Total</td>
                    <td style="padding:14px 0 0;text-align:right;font-weight:800;font-size:17px;color:#70161E;">PHP ' . number_format($order['total'], 2) . '</td>
                </tr>
            </table>
            ' . $fulfillment_note . '
            <p style="font-size:13px;color:#aaa;margin:28px 0 0;text-align:center;">
                Questions? Message us on <a href="https://www.facebook.com/profile.php?id=61588361559105" style="color:#7EA16B;">Facebook</a> or call <strong>0912 345 6789</strong>.
            </p>
        </td></tr>
        <tr><td style="height:5px;background:#70161E;border-radius:0 0 14px 14px;"></td></tr>
        <tr><td style="padding:20px;text-align:center;">
            <p style="font-size:12px;color:#aaa;margin:0;">&copy; 2026 Paulines\' Kitchen &nbsp;&middot;&nbsp; San Miguel, Bulacan</p>
        </td></tr>
    </table>
    </td></tr>
    </table>
    </body></html>';
    try {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host       = 'HOST HERE';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'YOUR EMAIL HERE';
        $mail->Password   = 'PASSWORD';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';
        $mail->setFrom('YOUR EMAIL HERE', "Paulines' Kitchen");
        $mail->addAddress($order['email'], $order['fullname']);
        $mail->isHTML(true);
        $mail->Subject = $msg['subject'] . ' - ' . $ref;
        $mail->Body    = $html;
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Admin status email error: ' . $e->getMessage());
        return false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orders – Admin | Paulines' Kitchen</title>
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
        .order-detail {
          font-size: 14px;
        }
        .detail-row {
          display: flex;
          justify-content: space-between;
          padding: 9px 0;
          border-bottom: 1px solid #f0ebe0;
        }
        .detail-row:last-child {
          border-bottom: none;
        }
        .detail-label {
          color: #888;
          font-weight: 600;
        }
        .detail-val {
          color: #1c3144;
          font-weight: 700;
          text-align: right;
        }
        .items-table {
          width: 100%;
          margin-top: 16px;
          border-collapse: collapse;
          font-size: 14px;
        }
        .items-table th {
          background: #f5f0e8;
          padding: 10px 14px;
          text-align: left;
          font-size: 12px;
          color: #596f62;
          text-transform: uppercase;
          letter-spacing: 1px;
        }
        .items-table td {
          padding: 10px 14px;
          border-bottom: 1px solid #f0ebe0;
        }
        .items-table tr:last-child td {
          border-bottom: none;
        }
        /* Mobile order cards override — more compact */
        @media (max-width: 768px) {
          .filter-bar {
            gap: 6px;
          }
          .filter-btn {
            padding: 6px 12px;
            font-size: 12px;
          }
          .search-box {
            margin-left: 0;
            width: 100%;
          }
          .search-box input {
            width: 100%;
          }
          /* Override global stacked-card style for orders — use 2-col grid inside each row */
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
          /* Actions td takes full width */
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
          .btn-sm {
            padding: 5px 10px;
            font-size: 11px;
          }
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1>Orders</h1>
            <span>View, update, and manage all customer orders.</span>
        </div>
    </div>
    <?php if ($msg): ?>
        <div class="admin-alert <?= $msgType ?>"><?= $msg ?></div>
    <?php endif; ?>
    <?php if (isset($_GET['deleted'])): ?>
        <div class="admin-alert success"><i class="icofont-trash"></i> Order deleted.</div>
    <?php endif; ?>
    <div class="filter-bar">
        <?php
        $statuses = [
            'all'             => 'All (' . $counts['all'] . ')',
            'Pending Payment' => 'Pending (' . $counts['Pending Payment'] . ')',
            'Paid & Preparing'=> 'Preparing (' . $counts['Paid & Preparing'] . ')',
            'Ready'           => 'Ready (' . $counts['Ready'] . ')',
            'Completed'       => 'Completed (' . $counts['Completed'] . ')',
            'Cancelled'       => 'Cancelled (' . $counts['Cancelled'] . ')',
        ];
        foreach ($statuses as $val => $label): ?>
            <a href="?status=<?= urlencode($val) ?>" class="filter-btn <?= $fStatus === $val ? 'active' : '' ?>">
                <?= $label ?>
            </a>
        <?php endforeach; ?>
        <div class="search-box">
            <form method="GET">
                <input type="hidden" name="status" value="<?= htmlspecialchars($fStatus) ?>">
                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search name, ref, email...">
            </form>
        </div>
    </div>
    <div class="admin-table-wrap table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Customer</th>
                    <th>Items</th>
                    <th>Total</th>
                    <th>Fulfillment</th>
                    <th>Target Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php if ($orders->num_rows > 0):
                while ($row = $orders->fetch_assoc()):
                    $iquery = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
                    $iquery->bind_param('i', $row['id']);
                    $iquery->execute();
                    $rowItems = $iquery->get_result()->fetch_all(MYSQLI_ASSOC);
                    $sc = match($row['status']) {
                        'Pending Payment'  => 's-pending',
                        'Pending'          => 's-pending',
                        'Paid & Preparing' => 's-paid',
                        'Ready'            => 's-ready',
                        'Completed'        => 's-completed',
                        'Cancelled'        => 's-cancelled',
                        default            => 's-pending'
                    };
            ?>
                <tr>
                    <td data-label="Reference">
                        <strong><?= htmlspecialchars($row['reference']) ?></strong><br>
                        <small style="color:#aaa;"><?= date('M j, Y g:i A', strtotime($row['created_at'])) ?></small>
                    </td>
                    <td data-label="Customer">
                        <strong><?= htmlspecialchars($row['fullname']) ?></strong><br>
                        <small style="color:#aaa;"><?= htmlspecialchars($row['email']) ?></small><br>
                        <small style="color:#aaa;"><?= htmlspecialchars($row['phone']) ?></small>
                    </td>
                    <td data-label="Items">
                        <?php foreach ($rowItems as $ri): ?>
                            <div style="font-size:13px;color:#1C3144;">
                                <?= htmlspecialchars($ri['item_name']) ?>
                                <?= $ri['pax'] ? ' (' . $ri['pax'] . ' pax)' : '' ?>
                                <span style="color:#7EA16B;">×<?= $ri['quantity'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    </td>
                    <td data-label="Total" style="font-weight:800;color:#70161E;white-space:nowrap;">₱<?= number_format($row['total'], 2) ?></td>
                    <td data-label="Type"><?= ucfirst($row['fulfillment']) ?></td>
                    <td data-label="Date" style="white-space:nowrap;"><?= date('M j, Y<\b\r>g:i A', strtotime($row['target_datetime'])) ?></td>
                    <td data-label="Status"><span class="badge-status <?= $sc ?>"><?= $row['status'] ?></span></td>
                    <td data-label="">
                        <form method="POST">
                            <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                            <select name="status" class="status-select">
                                <?php foreach (['Pending Payment','Pending','Paid & Preparing','Ready','Completed','Cancelled'] as $s): 
                                    $display = $s;
                                    if ($s === 'Pending') $display = 'Pending (COD)';
                                    if ($s === 'Pending Payment') $display = 'Pending (Online)';
                                ?>
                                    <option value="<?= $s ?>" <?= $row['status'] === $s ? 'selected' : '' ?>><?= $display ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="action-row">
                                <button type="submit" name="update_status" class="btn btn-primary btn-sm">Save + Email</button>
                                <button type="button" class="btn btn-outline btn-sm"
                                    onclick="viewOrder(<?= htmlspecialchars(json_encode($row)) ?>, <?= htmlspecialchars(json_encode($rowItems)) ?>)">
                                    View
                                </button>
                                <a href="orders.php?delete=<?= $row['id'] ?>"
                                   class="btn btn-danger btn-sm"
                                   onclick="return confirm('Delete order <?= $row['reference'] ?>?')">Del</a>
                            </div>
                        </form>
                    </td>
                </tr>
            <?php endwhile;
            else: ?>
                <tr><td colspan="8" style="text-align:center;padding:50px;color:#aaa;font-style:italic;">No orders found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<div class="modal-overlay" id="orderModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-ref">Order Details</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body" id="modal-body"></div>
    </div>
</div>
<script>
function viewOrder(order, items) {
    document.getElementById('modal-ref').textContent = order.reference;
    let itemRows = items.map(i => {
        const sub = (parseFloat(i.unit_price) + parseFloat(i.rice_addon)) * parseInt(i.quantity);
        return `<tr>
            <td>${i.item_name}${i.pax ? ' (' + i.pax + ' pax)' : ''}</td>
            <td>×${i.quantity}</td>
            <td style="text-align:right;font-weight:700;color:#70161E;">₱${sub.toFixed(2)}</td>
        </tr>`;
    }).join('');
    const fulfillment = order.fulfillment === 'courier'
        ? '<div style="background:#fff8e6;border:1px solid #e6c96e;border-radius:8px;padding:12px 16px;margin-top:16px;font-size:13px;color:#7a5c00;"><i class="icofont-fast-delivery"></i> Courier delivery — customer pays rider directly.</div>'
        : '<div style="background:#f4faf0;border:1px solid #C3D898;border-radius:8px;padding:12px 16px;margin-top:16px;font-size:13px;color:#2E4A2E;"><i class="icofont-location-pin"></i> Pick-up at San Miguel, Bulacan.</div>';
    document.getElementById('modal-body').innerHTML = `
        <div class="order-detail">
            <div class="detail-row"><span class="detail-label">Customer</span><span class="detail-val">${order.fullname}</span></div>
            <div class="detail-row"><span class="detail-label">Email</span><span class="detail-val">${order.email}</span></div>
            <div class="detail-row"><span class="detail-label">Phone</span><span class="detail-val">${order.phone}</span></div>
            <div class="detail-row"><span class="detail-label">Fulfillment</span><span class="detail-val">${order.fulfillment}</span></div>
            <div class="detail-row"><span class="detail-label">Target Date</span><span class="detail-val">${order.target_datetime}</span></div>
            <div class="detail-row"><span class="detail-label">Notes</span><span class="detail-val">${order.notes || '—'}</span></div>
            ${order.address ? `<div class="detail-row"><span class="detail-label">Address</span><span class="detail-val">${order.address}</span></div>` : ''}
            <div class="detail-row"><span class="detail-label">Status</span><span class="detail-val">${order.status}</span></div>
            <div class="detail-row"><span class="detail-label">Total Paid</span><span class="detail-val" style="color:#70161E;font-size:16px;">₱${parseFloat(order.total).toFixed(2)}</span></div>
        </div>
        <table class="items-table">
            <thead><tr><th>Item</th><th>Qty</th><th style="text-align:right;">Subtotal</th></tr></thead>
            <tbody>${itemRows}</tbody>
        </table>
        ${fulfillment}
        <div style="text-align:center;margin-top:20px;">
            <button onclick="window.print()" class="btn btn-outline"><i class="icofont-print"></i> Print Slip</button>
        </div>
    `;
    document.getElementById('orderModal').classList.add('open');
}
function closeModal() {
    document.getElementById('orderModal').classList.remove('open');
}
document.getElementById('orderModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});
</script>
</body>
</html>
<?php $conn->close(); ?>
