<?php
require_once 'includes/auth.php';
require_once '../config/db.php';
$conn->query("SET time_zone = '+08:00'");
$total     = $conn->query("SELECT COUNT(*) as c FROM orders")->fetch_assoc()['c'];
$pending   = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status IN ('Pending Payment', 'Pending')")->fetch_assoc()['c'];
$preparing = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Paid & Preparing'")->fetch_assoc()['c'];
$ready     = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Ready'")->fetch_assoc()['c'];
$completed = $conn->query("SELECT COUNT(*) as c FROM orders WHERE status = 'Completed'")->fetch_assoc()['c'];
$revenue   = $conn->query("SELECT SUM(total) as r FROM orders WHERE status NOT IN ('Pending Payment', 'Pending', 'Cancelled')")->fetch_assoc()['r'] ?? 0;
$todayCount = $conn->query("SELECT COUNT(*) as c FROM orders WHERE DATE(created_at) = CURDATE()")->fetch_assoc()['c'];
$todayRev   = $conn->query("SELECT SUM(total) as r FROM orders WHERE DATE(created_at) = CURDATE() AND status NOT IN ('Pending Payment', 'Pending', 'Cancelled')")->fetch_assoc()['r'] ?? 0;
$recent = $conn->query("SELECT * FROM orders ORDER BY id DESC LIMIT 5");
if (!$recent) {
    error_log('Dashboard recent orders query failed: ' . $conn->error);
    $recent = false;
}
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
        .stat-card.red   { border-top-color: #70161e; }
        .stat-card.navy  { border-top-color: #1c3144; }
        .stat-card.orange{ border-top-color: #c3842a; }
        .stat-card.green { border-top-color: #2e4a2e; }
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
        .s-pending   { background: #fff3cd; color: #856404; }
        .s-paid      { background: #d4edda; color: #155724; }
        .s-ready     { background: #cce5ff; color: #004085; }
        .s-completed { background: #c3d898; color: #1c3144; }
        .s-cancelled { background: #f8d7da; color: #70161e; }

        /* ── SALES REPORT ── */
        .sales-report-card {
          background: #fff;
          border-radius: 16px;
          box-shadow: 0 4px 20px rgba(0,0,0,0.08);
          margin-bottom: 32px;
          overflow: hidden;
        }
        .sales-report-header {
          display: flex;
          align-items: center;
          justify-content: space-between;
          padding: 22px 28px 16px;
          border-bottom: 1px solid #f0ebe0;
          flex-wrap: wrap;
          gap: 12px;
        }
        .sales-report-title {
          font-size: 18px;
          font-weight: 800;
          color: #1c3144;
          display: flex;
          align-items: center;
          gap: 10px;
        }
        .sales-report-title .icon {
          width: 36px;
          height: 36px;
          background: linear-gradient(135deg, #1c3144, #2e5180);
          border-radius: 10px;
          display: flex;
          align-items: center;
          justify-content: center;
          color: #c3d898;
          font-size: 17px;
        }
        .period-dropdown {
          display: flex;
          align-items: center;
          gap: 8px;
        }
        .period-dropdown label {
          font-size: 13px;
          font-weight: 700;
          color: #888;
        }
        .period-select {
          appearance: none;
          background: #f5f0e8 url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%231c3144' stroke-width='1.8' fill='none' stroke-linecap='round'/%3E%3C/svg%3E") no-repeat right 12px center;
          border: 1.5px solid #c3d898;
          border-radius: 10px;
          padding: 9px 36px 9px 14px;
          font-family: 'Raleway', sans-serif;
          font-size: 14px;
          font-weight: 700;
          color: #1c3144;
          cursor: pointer;
          outline: none;
          transition: border-color 0.2s, box-shadow 0.2s;
        }
        .period-select:hover,
        .period-select:focus {
          border-color: #7ea16b;
          box-shadow: 0 0 0 3px rgba(126,161,107,0.15);
        }
        .sales-kpi-row {
          display: flex;
          gap: 0;
          border-bottom: 1px solid #f0ebe0;
        }
        .sales-kpi {
          flex: 1;
          padding: 18px 28px;
          border-right: 1px solid #f0ebe0;
          text-align: center;
        }
        .sales-kpi:last-child { border-right: none; }
        .sales-kpi .kpi-val {
          font-size: 22px;
          font-weight: 800;
          color: #1c3144;
          letter-spacing: -0.5px;
        }
        .sales-kpi .kpi-val.green { color: #2e7d32; }
        .sales-kpi .kpi-val.red   { color: #70161e; }
        .sales-kpi .kpi-lbl {
          font-size: 12px;
          font-weight: 600;
          color: #aaa;
          margin-top: 4px;
          text-transform: uppercase;
          letter-spacing: 0.5px;
        }
        .sales-chart-area {
          padding: 24px 28px 28px;
          position: relative;
        }
        .sales-chart-area canvas {
          max-height: 300px;
        }
        .chart-loader {
          display: flex;
          align-items: center;
          justify-content: center;
          height: 260px;
          color: #bbb;
          font-size: 14px;
          font-weight: 600;
          gap: 10px;
        }
        .spinner {
          width: 22px;
          height: 22px;
          border: 3px solid #e0dcd0;
          border-top-color: #7ea16b;
          border-radius: 50%;
          animation: spin 0.7s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }

        @media (max-width: 768px) {
          .sales-report-header { padding: 16px 18px 12px; }
          .sales-kpi-row { flex-wrap: wrap; }
          .sales-kpi { min-width: 50%; border-bottom: 1px solid #f0ebe0; }
          .sales-chart-area { padding: 16px 14px 20px; }
        }
    </style>
</head>
<body>
<?php include 'includes/sidebar.php'; ?>
<div class="admin-main">
    <div class="admin-topbar">
        <div>
            <h1>Dashboard</h1>
            <span>Welcome back, Admin!.</span>
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
            <div class="stat-label">Pending Orders</div>
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
    <!-- ══ SALES REPORT ══ -->
    <div class="sales-report-card" id="salesReportCard">
        <div class="sales-report-header">
            <div class="sales-report-title">
                <div class="icon"><i class="icofont-chart-bar-graph"></i></div>
                Sales Report
            </div>
            <div class="period-dropdown">
                <label for="periodSelect">Period:</label>
                <select id="periodSelect" class="period-select">
                    <option value="weekly">Weekly (Last 7 Days)</option>
                    <option value="monthly">Monthly (Last 12 Months)</option>
                    <option value="yearly">Yearly (Last 5 Years)</option>
                </select>
            </div>
        </div>
        <div class="sales-kpi-row" id="salesKpiRow">
            <div class="sales-kpi">
                <div class="kpi-val green" id="kpiRevenue">—</div>
                <div class="kpi-lbl">Total Revenue</div>
            </div>
            <div class="sales-kpi">
                <div class="kpi-val" id="kpiOrders">—</div>
                <div class="kpi-lbl">Total Orders</div>
            </div>
            <div class="sales-kpi">
                <div class="kpi-val" id="kpiAvg">—</div>
                <div class="kpi-lbl">Avg. Order Value</div>
            </div>
        </div>
        <div class="sales-chart-area">
            <div class="chart-loader" id="chartLoader">
                <div class="spinner"></div> Loading report…
            </div>
            <canvas id="salesChart" style="display:none;"></canvas>
        </div>
    </div>
    <!-- ══ END SALES REPORT ══ -->

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
            <?php if ($recent && $recent->num_rows > 0):
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
                            'Pending Payment'  => 's-pending',
                            'Pending'          => 's-pending',
                            'Paid & Preparing' => 's-paid',
                            'Ready'            => 's-ready',
                            'Completed'        => 's-completed',
                            'Cancelled'        => 's-cancelled',
                            default            => 's-pending'
                        };
                        ?>
                        <span class="badge-status <?= $sc ?>"><?= $row['status'] ?></span>
                    </td>
                    <td data-label=""><a href="orders.php" class="btn btn-sm btn-outline">View All</a></td>
                </tr>
                <?php endwhile;
            else: ?>
                <tr><td colspan="7" style="text-align:center;padding:40px;color:#aaa;font-style:italic;">
                    <?= $recent === false ? 'Error loading orders — check DB connection.' : 'No orders yet.' ?>
                </td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
(function () {
    const select  = document.getElementById('periodSelect');
    const canvas  = document.getElementById('salesChart');
    const loader  = document.getElementById('chartLoader');
    const kpiRev  = document.getElementById('kpiRevenue');
    const kpiOrd  = document.getElementById('kpiOrders');
    const kpiAvg  = document.getElementById('kpiAvg');
    let chart     = null;

    function formatPeso(n) {
        return '₱' + Number(n).toLocaleString('en-PH', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function loadReport(period) {
        loader.style.display  = 'flex';
        canvas.style.display  = 'none';

        fetch('sales_report_data.php?period=' + period)
            .then(r => r.json())
            .then(data => {
                // Update KPIs
                kpiRev.textContent = formatPeso(data.totalRev);
                kpiOrd.textContent = data.totalOrders;
                kpiAvg.textContent = formatPeso(data.avgOrder);

                // Destroy old chart
                if (chart) { chart.destroy(); chart = null; }

                loader.style.display = 'none';
                canvas.style.display = 'block';

                const ctx = canvas.getContext('2d');
                chart = new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: data.labels,
                        datasets: [
                            {
                                type: 'line',
                                label: 'Revenue (₱)',
                                data: data.revenue,
                                borderColor: '#7ea16b',
                                backgroundColor: 'rgba(126,161,107,0.10)',
                                borderWidth: 2.5,
                                pointBackgroundColor: '#7ea16b',
                                pointRadius: 5,
                                pointHoverRadius: 7,
                                tension: 0.4,
                                fill: true,
                                yAxisID: 'yRev',
                            },
                            {
                                type: 'bar',
                                label: 'Orders',
                                data: data.orders,
                                backgroundColor: 'rgba(28,49,68,0.12)',
                                borderColor: '#1c3144',
                                borderWidth: 1.5,
                                borderRadius: 6,
                                yAxisID: 'yOrd',
                            },
                        ],
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: true,
                        interaction: { mode: 'index', intersect: false },
                        plugins: {
                            legend: {
                                position: 'top',
                                labels: {
                                    font: { family: 'Raleway', size: 13, weight: '700' },
                                    color: '#1c3144',
                                    usePointStyle: true,
                                    padding: 20,
                                },
                            },
                            tooltip: {
                                backgroundColor: '#1c3144',
                                titleFont: { family: 'Raleway', size: 13, weight: '700' },
                                bodyFont:  { family: 'Raleway', size: 13 },
                                padding: 12,
                                cornerRadius: 10,
                                callbacks: {
                                    label: function(ctx) {
                                        if (ctx.dataset.label.includes('Revenue')) {
                                            return ' Revenue: ' + formatPeso(ctx.parsed.y);
                                        }
                                        return ' Orders: ' + ctx.parsed.y;
                                    }
                                }
                            }
                        },
                        scales: {
                            x: {
                                grid: { color: 'rgba(0,0,0,0.04)' },
                                ticks: {
                                    font: { family: 'Raleway', size: 12, weight: '600' },
                                    color: '#888',
                                    maxRotation: 45,
                                }
                            },
                            yRev: {
                                type: 'linear',
                                position: 'left',
                                grid: { color: 'rgba(0,0,0,0.05)' },
                                ticks: {
                                    font: { family: 'Raleway', size: 12 },
                                    color: '#7ea16b',
                                    callback: v => '₱' + Number(v).toLocaleString()
                                },
                                title: { display: true, text: 'Revenue (₱)', color: '#7ea16b', font: { family: 'Raleway', weight: '700', size: 12 } }
                            },
                            yOrd: {
                                type: 'linear',
                                position: 'right',
                                grid: { drawOnChartArea: false },
                                ticks: {
                                    font: { family: 'Raleway', size: 12 },
                                    color: '#1c3144',
                                    precision: 0,
                                },
                                title: { display: true, text: 'Orders', color: '#1c3144', font: { family: 'Raleway', weight: '700', size: 12 } }
                            }
                        }
                    }
                });
            })
            .catch(() => {
                loader.innerHTML = '<span style="color:#70161e;">⚠ Failed to load report data.</span>';
                loader.style.display = 'flex';
                canvas.style.display = 'none';
            });
    }

    // Initial load
    loadReport(select.value);

    // On dropdown change
    select.addEventListener('change', () => loadReport(select.value));
})();
</script>
</body>
</html>
<?php $conn->close(); ?>