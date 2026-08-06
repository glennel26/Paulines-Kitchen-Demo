<?php require_once __DIR__ . '/auth.php'; ?>

<!-- Mobile Admin Top Bar -->
<div class="admin-mob-topbar">
    <div class="admin-mob-logo">
        <img src="../images/logoo.png" alt="Paulines Kitchen" onerror="this.style.display='none'">
        <div>
            <span style="display:block;font-size:14px;font-weight:800;color:#C3D898;line-height:1.1;">Paulines' Kitchen</span>
            <span style="font-size:10px;color:rgba(255,255,255,0.5);font-weight:600;letter-spacing:0.5px;">ADMIN PANEL</span>
        </div>
    </div>
    <button class="admin-mob-hamburger" onclick="openAdminSidebar()">&#9776;</button>
</div>

<!-- Overlay -->
<div class="admin-mob-overlay" id="adminOverlay" onclick="closeAdminSidebar()"></div>

<div class="sidebar" id="adminSidebar">
    <div class="sidebar-logo">
        <img src="../images/logoo.png" alt="Paulines Kitchen">
        <span>Pauline's Kitchen<br><small style="font-size:11px;color:#aaa;font-weight:400;">Admin Panel</small></span>
        <button class="sidebar-mob-close" onclick="closeAdminSidebar()">&#10005;</button>
    </div>
    <nav class="sidebar-nav">
    <a href="dashboard.php" class="<?= $current === 'dashboard.php' ? 'active' : '' ?>">
        <i class="icofont-dashboard-web"></i> &nbsp; Dashboard
    </a>
    <a href="orders.php" class="<?= $current === 'orders.php' ? 'active' : '' ?>">
        <i class="icofont-list"></i> &nbsp; Orders
    </a>
    <a href="menu.php" class="<?= $current === 'menu.php' ? 'active' : '' ?>">
        <i class="icofont-food-cart"></i> &nbsp; Menu Management
    </a>
    <a href="custom_orders.php" class="<?= $current === 'custom_orders.php' ? 'active' : '' ?>">
        <i class="icofont-hat-alt"></i> &nbsp; Custom Orders
    </a>
</nav>
    <div style="padding: 16px 4px; margin-top: auto; font-size: 13px; color: rgba(255,255,255,0.4); border-top: 1px solid rgba(255,255,255,0.1);">
        Logged in as <strong style="color:#C3D898;"><?= htmlspecialchars($_SESSION['admin_name']) ?></strong>
    </div>
    <a href="auth/admin_logout.php" class="sidebar-logout"><i class="icofont-logout"></i> &nbsp; Logout</a>
</div>

<script>
function openAdminSidebar()  { document.getElementById('adminSidebar').classList.add('open'); document.getElementById('adminOverlay').classList.add('open'); }
function closeAdminSidebar() { document.getElementById('adminSidebar').classList.remove('open'); document.getElementById('adminOverlay').classList.remove('open'); }
</script>