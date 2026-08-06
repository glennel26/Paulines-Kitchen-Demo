<?php
$cart_count = count($_SESSION['cart'] ?? []);
$active = $active_page ?? '';
?>
<!-- Mobile Top Bar -->
<div class="mob-topbar">
    <div class="mob-logo">
        <a href="<?= isset($base) ? $base : '' ?>index.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
            <img src="<?= isset($base) ? $base : '' ?>images/logoo.png" alt="Paulines Kitchen" onerror="this.style.display='none'">
            <span style="color:#fff;font-weight:800;font-size:15px;">Paulines' Kitchen</span>
        </a>
    </div>
    <button class="mob-hamburger" onclick="openMobSidebar()" aria-label="Menu">&#9776;</button>
</div>

<div class="mob-sidebar-overlay" id="mobOverlay" onclick="closeMobSidebar()"></div>

<div class="mob-sidebar" id="mobSidebar">
    <div class="mob-sidebar-header">
        <img src="<?= isset($base) ? $base : '' ?>images/logoo.png" alt="Logo">
        <span>Pauline's Kitchen</span>
        <button class="mob-sidebar-close" onclick="closeMobSidebar()">&#10005;</button>
    </div>
    <nav>
        <a href="<?= isset($base) ? $base : '' ?>index.php" class="<?= $active==='home'?'nav-active':'' ?>"><i class="icofont-home"></i>&nbsp; Home</a>
        <a href="<?= isset($base) ? $base : '' ?>menu.php" class="<?= $active==='menu'?'nav-active':'' ?>"><i class="icofont-food-cart"></i>&nbsp; Menu</a>
        <a href="<?= isset($base) ? $base : '' ?>about.php" class="<?= $active==='about'?'nav-active':'' ?>"><i class="icofont-info-circle"></i>&nbsp; About</a>
        <a href="<?= isset($base) ? $base : '' ?>cart.php" class="<?= $active==='cart'?'nav-active':'' ?>"><i class="icofont-food-basket"></i>&nbsp; Order</a>
        <a href="<?= isset($base) ? $base : '' ?>index.php#faqs"><i class="icofont-question-circle"></i>&nbsp; FAQs</a>
    </nav>
    <div class="mob-cart-row">
        <a href="<?= isset($base) ? $base : '' ?>cart.php">
            <i class="icofont-food-basket"></i>
            <span class="badge"><?= $cart_count ?></span>
        </a>
        <span style="color:rgba(255,255,255,0.6); font-size:13px;">Your bag (<?= $cart_count ?> item<?= $cart_count!=1?'s':'' ?>)</span>
    </div>
    <div class="mob-sidebar-user">
        <?php if (isset($_SESSION['user_name'])): ?>
            <span><i class="icofont-user-alt-3"></i> Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="<?= isset($base) ? $base : '' ?>user/auth/logout.php" class="logout"><i class="icofont-logout"></i> Logout</a>
        <?php else: ?>
            <a href="<?= isset($base) ? $base : '' ?>user/login.php"><i class="icofont-login"></i> Login</a>
            <a href="<?= isset($base) ? $base : '' ?>user/register.php" style="background:transparent;border:2px solid #C3D898;color:#C3D898;"><i class="icofont-ui-user"></i> Register</a>
        <?php endif; ?>
    </div>
</div>

<script>
function openMobSidebar()  { document.getElementById('mobSidebar').classList.add('open'); document.getElementById('mobOverlay').classList.add('open'); }
function closeMobSidebar() { document.getElementById('mobSidebar').classList.remove('open'); document.getElementById('mobOverlay').classList.remove('open'); }
</script>
