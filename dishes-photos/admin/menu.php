<?php
session_start();
require_once 'includes/auth.php';

$conn = new mysqli('localhost', 'root', '', 'paulines_kitchen');
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$msg      = '';
$msgType = '';

if (isset($_GET['toggle'])) {
    $id  = intval($_GET['toggle']);
    $conn->prepare("UPDATE menu SET available = NOT available WHERE id = ?")->bind_param('i', $id) && true;
    $stmt = $conn->prepare("UPDATE menu SET available = NOT available WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: menu.php?toggled=1');
    exit();
}

if (isset($_GET['delete'])) {
    $id   = intval($_GET['delete']);
    
    $row  = $conn->query("SELECT image FROM menu WHERE id = $id")->fetch_assoc();
    if ($row && $row['image'] && file_exists('../images/menu/' . $row['image'])) {
        unlink('../images/menu/' . $row['image']);
    }
    $stmt = $conn->prepare("DELETE FROM menu WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    header('Location: menu.php?deleted=1');
    exit();
}

$fCat = $_GET['category'] ?? 'all';
$search     = trim($_GET['search'] ?? '');

$where  = '1=1';
$params = [];
$types  = '';

if ($fCat !== 'all') {
    $where   .= ' AND category = ?';
    $params[] = $fCat;
    $types   .= 's';
}
if ($search) {
    $like     = '%' . $search . '%';
    $where   .= ' AND (name LIKE ? OR description LIKE ?)';
    $params[] = $like; $params[] = $like;
    $types   .= 'ss';
}

$sql  = "SELECT * FROM menu WHERE {$where} ORDER BY category, name ASC";
$stmt = $conn->prepare($sql);
if ($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$dishes = $stmt->get_result();

$categories = [];
$catRes = $conn->query("SELECT category, COUNT(*) as c FROM menu GROUP BY category ORDER BY category");
while ($r = $catRes->fetch_assoc()) $categories[$r['category']] = $r['c'];

$totalDishes    = $conn->query("SELECT COUNT(*) as c FROM menu")->fetch_assoc()['c'];
$availCount = $conn->query("SELECT COUNT(*) as c FROM menu WHERE available = 1")->fetch_assoc()['c'];

$editDish = null;
if (isset($_GET['edit'])) {
    $es = $conn->prepare("SELECT * FROM menu WHERE id = ?");
    $es->execute();
    $editDish = $es->get_result()->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management – Admin | Paulines' Kitchen</title>
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/admin.css">
    <link rel="icon" href="../images/logoo.png">
    <link rel="stylesheet" href="../images/icons/icofont/icofont.min.css">
    <style>
        .menu-stats {
          display: flex;
          gap: 16px;
          margin-bottom: 28px;
        }
        .menu-stat {
          background: #fff;
          border-radius: 12px;
          padding: 18px 24px;
          box-shadow: 0 4px 14px rgba(0, 0, 0, 0.07);
          border-top: 4px solid #7ea16b;
          flex: 1;
          text-align: center;
        }
        .menu-stat.red {
          border-top-color: #70161e;
        }
        .menu-stat.navy {
          border-top-color: #1c3144;
        }
        .menu-stat .num {
          font-size: 28px;
          font-weight: 800;
          color: #1c3144;
        }
        .menu-stat .lbl {
          font-size: 12px;
          color: #888;
          font-weight: 600;
          margin-top: 2px;
        }

        .menu-toolbar {
          display: flex;
          align-items: center;
          gap: 12px;
          margin-bottom: 20px;
          flex-wrap: wrap;
        }
        .menu-toolbar .search-wrap {
          position: relative;
          flex: 1;
          min-width: 200px;
        }
        .menu-toolbar .search-wrap input {
          width: 100%;
          padding: 10px 14px 10px 38px;
          border: 1px solid #e0e0e0;
          border-radius: 8px;
          font-family: inherit;
          font-size: 14px;
          outline: none;
          transition: border 0.2s;
        }
        .menu-toolbar .search-wrap input:focus {
          border-color: #7ea16b;
        }
        .menu-toolbar .search-wrap .ico {
          position: absolute;
          left: 12px;
          top: 50%;
          transform: translateY(-50%);
          color: #aaa;
          font-size: 16px;
        }
        .cat-tabs {
          display: flex;
          gap: 8px;
          flex-wrap: wrap;
          margin-bottom: 20px;
        }
        .cat-tab {
          padding: 6px 16px;
          border-radius: 20px;
          border: 1.5px solid #e0e0e0;
          font-size: 13px;
          font-weight: 600;
          color: #666;
          cursor: pointer;
          text-decoration: none;
          transition: all 0.2s;
        }
        .cat-tab.active,
        .cat-tab:hover {
          background: #1c3144;
          border-color: #1c3144;
          color: #fff;
        }

        .dishes-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
          gap: 20px;
        }
        .dish-card {
          background: #fff;
          border-radius: 14px;
          overflow: hidden;
          box-shadow: 0 4px 16px rgba(0, 0, 0, 0.07);
          transition:
            transform 0.2s,
            box-shadow 0.2s;
          display: flex;
          flex-direction: column;
        }
        .dish-card:hover {
          transform: translateY(-3px);
          box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
        }
        .dish-card.unavailable {
          opacity: 0.65;
        }
        .dish-img {
          width: 100%;
          height: 170px;
          object-fit: cover;
          background: #f5f0e8;
        }
        .dish-img-placeholder {
          width: 100%;
          height: 170px;
          background: linear-gradient(135deg, #f5f0e8, #e8e0d0);
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 40px;
        }
        .dish-body {
          padding: 16px;
          flex: 1;
          display: flex;
          flex-direction: column;
        }
        .dish-cat {
          font-size: 11px;
          font-weight: 700;
          letter-spacing: 0.8px;
          text-transform: uppercase;
          color: #7ea16b;
          margin-bottom: 4px;
        }
        .dish-name {
          font-size: 16px;
          font-weight: 800;
          color: #1c3144;
          margin-bottom: 4px;
        }
        .dish-desc {
          font-size: 13px;
          color: #888;
          line-height: 1.5;
          flex: 1;
          margin-bottom: 10px;
        }
        .dish-price {
          font-size: 18px;
          font-weight: 800;
          color: #70161e;
        }
        .dish-pax {
          font-size: 12px;
          color: #aaa;
          margin-left: 6px;
        }
        .dish-footer {
          padding: 12px 16px;
          border-top: 1px solid #f0ebe0;
          display: flex;
          gap: 8px;
          align-items: center;
          flex-wrap: wrap;
        }
        .avail-badge {
          font-size: 11px;
          font-weight: 700;
          padding: 3px 10px;
          border-radius: 20px;
        }
        .avail-on {
          background: #d4edda;
          color: #155724;
        }
        .avail-off {
          background: #f8d7da;
          color: #70161e;
        }

        .modal-overlay {
          position: fixed;
          inset: 0;
          background: rgba(0, 0, 0, 0.5);
          z-index: 1100;
          display: none;
          align-items: center;
          justify-content: center;
          padding: 20px;
        }
        .modal-overlay.open {
          display: flex;
        }
        .modal {
          background: #fff;
          border-radius: 18px;
          width: 100%;
          max-width: 540px;
          max-height: 90vh;
          overflow-y: auto;
          box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
          margin: auto;
        }
        .modal-header {
          padding: 24px 28px 0;
          display: flex;
          justify-content: space-between;
          align-items: center;
        }
        .modal-header h2 {
          font-size: 20px;
          font-weight: 800;
          color: #1c3144;
          margin: 0;
        }
        .modal-close {
          background: none;
          border: none;
          font-size: 24px;
          cursor: pointer;
          color: #aaa;
          line-height: 1;
        }
        .modal-close:hover {
          color: #70161e;
        }
        .modal-body {
          padding: 24px 28px 28px;
        }
        .form-group {
          margin-bottom: 18px;
        }
        .form-group label {
          display: block;
          font-size: 13px;
          font-weight: 700;
          color: #555;
          margin-bottom: 6px;
        }
        .form-group input,
        .form-group textarea,
        .form-group select {
          width: 100%;
          padding: 10px 14px;
          border: 1.5px solid #e0e0e0;
          border-radius: 8px;
          font-family: inherit;
          font-size: 14px;
          outline: none;
          transition: border 0.2s;
          box-sizing: border-box;
        }
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
          border-color: #7ea16b;
        }
        .form-group textarea {
          resize: vertical;
          min-height: 80px;
        }
        .form-row {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 14px;
        }
        .form-check {
          display: flex;
          align-items: center;
          gap: 10px;
          font-size: 14px;
          color: #555;
        }
        .form-check input[type="checkbox"] {
          width: 18px;
          height: 18px;
          accent-color: #7ea16b;
        }
        .modal-actions {
          display: flex;
          gap: 10px;
          justify-content: flex-end;
          margin-top: 24px;
        }

        .btn-add {
          background: #1c3144;
          color: #fff;
          border: none;
          padding: 10px 20px;
          border-radius: 8px;
          font-family: inherit;
          font-size: 14px;
          font-weight: 700;
          cursor: pointer;
          transition: background 0.2s;
        }
        .btn-add:hover {
          background: #2a4a6a;
        }

        @media (max-width: 768px) {
          .form-row {
            grid-template-columns: 1fr;
          }
          .modal {
            max-width: 100%;
            border-radius: 14px;
          }
          .modal-body {
            padding: 16px 18px 20px;
          }
          .modal-header {
            padding: 18px 18px 0;
          }
          .dishes-grid {
            grid-template-columns: 1fr 1fr;
            gap: 12px;
          }
          .dish-img {
            height: 130px;
          }
          .dish-img-placeholder {
            height: 130px;
          }
          .dish-name {
            font-size: 13px;
          }
          .dish-body {
            padding: 10px;
          }
          .menu-toolbar {
            flex-direction: column;
            gap: 10px;
          }
          .menu-toolbar .search-wrap {
            width: 100%;
          }
          .cat-tabs {
            gap: 6px;
          }
        }
    </style>
</head>
<body>

<?php include 'includes/sidebar.php'; ?>

<div class="admin-main">

    <div class="admin-topbar">
        <div>
            <h1>Menu Management</h1>
            <span>Add, edit, or toggle availability of dishes.</span>
        </div>
        <button class="btn btn-primary" onclick="openAddModal()">+ Add Dish</button>
    </div>

    <?php if (isset($_GET['toggled'])): ?>
        <div class="alert alert-success"><i class="icofont-check"></i> Dish availability updated.</div>
    <?php elseif (isset($_GET['deleted'])): ?>
        <div class="alert alert-success"><i class="icofont-trash"></i> Dish deleted.</div>
    <?php elseif (isset($_GET['saved'])): ?>
        <div class="alert alert-success"><i class="icofont-check"></i> Dish saved successfully.</div>
    <?php endif; ?>

    <div class="menu-stats">
        <div class="menu-stat navy">
            <div class="num"><?= $totalDishes ?></div>
            <div class="lbl">Total Dishes</div>
        </div>
        <div class="menu-stat">
            <div class="num"><?= $availCount ?></div>
            <div class="lbl">Available</div>
        </div>
        <div class="menu-stat red">
            <div class="num"><?= $totalDishes - $availCount ?></div>
            <div class="lbl">Unavailable</div>
        </div>
        <div class="menu-stat">
            <div class="num"><?= count($categories) ?></div>
            <div class="lbl">Categories</div>
        </div>
    </div>

    <div class="menu-toolbar">
        <form method="GET" style="display:contents;">
            <?php if ($fCat !== 'all'): ?>
                <input type="hidden" name="category" value="<?= htmlspecialchars($fCat) ?>">
            <?php endif; ?>
            <div class="search-wrap">
                <span class="ico"><i class="icofont-search"></i></span>
                <input type="text" name="search" placeholder="Search dishes…" value="<?= htmlspecialchars($search) ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Search</button>
            <?php if ($search): ?>
                <a href="menu.php" class="btn btn-outline btn-sm">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="cat-tabs">
        <a href="menu.php<?= $search ? '?search=' . urlencode($search) : '' ?>"
           class="cat-tab <?= $fCat === 'all' ? 'active' : '' ?>">
            All (<?= $totalDishes ?>)
        </a>
        <?php foreach ($categories as $cat => $cnt): ?>
            <a href="menu.php?category=<?= urlencode($cat) ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
               class="cat-tab <?= $fCat === $cat ? 'active' : '' ?>">
                <?= htmlspecialchars($cat) ?> (<?= $cnt ?>)
            </a>
        <?php endforeach; ?>
    </div>

    <div class="dishes-grid">
    <?php if ($dishes->num_rows > 0):
        while ($dish = $dishes->fetch_assoc()): ?>
        <div class="dish-card <?= $dish['available'] ? '' : 'unavailable' ?>">
            <?php if (!empty($dish['image']) && file_exists('../images/menu/' . $dish['image'])): ?>
                <img src="../images/menu/<?= htmlspecialchars($dish['image']) ?>" class="dish-img" alt="<?= htmlspecialchars($dish['name']) ?>">
            <?php else: ?>
                <div class="dish-img-placeholder"><i class="icofont-restaurant"></i></div>
            <?php endif; ?>

            <div class="dish-body">
                <div class="dish-cat"><?= htmlspecialchars($dish['category']) ?></div>
                <div class="dish-name"><?= htmlspecialchars($dish['name']) ?></div>
                <div class="dish-desc"><?= htmlspecialchars($dish['description'] ?? '') ?></div>
                <div>
                    <span class="dish-price">₱<?= number_format($dish['price'], 2) ?></span>
                    <?php if (!empty($dish['pax'])): ?>
                        <span class="dish-pax">(<?= htmlspecialchars($dish['pax']) ?> pax)</span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="dish-footer">
                <span class="avail-badge <?= $dish['available'] ? 'avail-on' : 'avail-off' ?>">
                    <?= $dish['available'] ? 'Available' : 'Unavailable' ?>
                </span>
                <div style="margin-left:auto;display:flex;gap:6px;">
                    <button class="btn btn-outline btn-sm"
                        onclick='openEditModal(<?= htmlspecialchars(json_encode($dish)) ?>)'>
                        <i class="icofont-ui-edit"></i> Edit
                    </button>
                    <a href="menu.php?toggle=<?= $dish['id'] ?>" class="btn btn-sm <?= $dish['available'] ? 'btn-danger' : 'btn-primary' ?>">
                        <?= $dish['available'] ? 'Hide' : 'Show' ?>
                    </a>
                    <a href="menu.php?delete=<?= $dish['id'] ?>"
                       class="btn btn-danger btn-sm"
                       onclick="return confirm('Delete <?= htmlspecialchars(addslashes($dish['name'])) ?>?')"><i class="icofont-trash"></i></a>
                </div>
            </div>
        </div>
    <?php endwhile;
    else: ?>
        <div style="grid-column:1/-1;text-align:center;padding:60px;color:#aaa;font-style:italic;">
            No dishes found. <a href="#" onclick="openAddModal()" style="color:#7EA16B;">Add one!</a>
        </div>
    <?php endif; ?>
    </div>

</div>

<div class="modal-overlay" id="dishModal">
    <div class="modal">
        <div class="modal-header">
            <h2 id="modal-title">Add Dish</h2>
            <button class="modal-close" onclick="closeModal()">×</button>
        </div>
        <div class="modal-body">
            <form method="POST" action="auth/save_dish.php" enctype="multipart/form-data">
                <input type="hidden" name="dish_id" id="dish_id" value="">

                <div class="form-group">
                    <label>Dish Name *</label>
                    <input type="text" name="name" id="f_name" required placeholder="e.g. Chicken Adobo">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Category *</label>
                        <select name="category" id="f_category" required>
                            <option value="">— Select —</option>
                            <?php
                            $existing_cats = array_keys($categories);
                            $suggested = array_merge($existing_cats, ['Silog Meals', 'Ulam', 'Snacks', 'Drinks', 'Desserts', 'Packages']);
                            $suggested = array_unique($suggested);
                            foreach ($suggested as $c): ?>
                                <option value="<?= htmlspecialchars($c) ?>"><?= htmlspecialchars($c) ?></option>
                            <?php endforeach; ?>
                            <option value="__custom__">+ New category…</option>
                        </select>
                    </div>
                    <div class="form-group" id="custom_cat_wrap" style="display:none;">
                        <label>New Category Name</label>
                        <input type="text" name="custom_category" id="f_custom_category" placeholder="e.g. Breakfast">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Price (₱) *</label>
                        <input type="number" name="price" id="f_price" step="0.01" min="0" required placeholder="0.00">
                    </div>
                    <div class="form-group">
                        <label>Pax / Serving size</label>
                        <input type="text" name="pax" id="f_pax" placeholder="e.g. 1, 2–3, Family">
                    </div>
                </div>

                <div class="form-group">
                    <label>Description</label>
                    <textarea name="description" id="f_description" placeholder="Short description of the dish…"></textarea>
                </div>

                <div class="form-group">
                    <label>Dish Image</label>
                    <input type="file" name="image" id="f_image" accept="image/*">
                    <small style="color:#aaa;font-size:12px;">Leave blank to keep existing image.</small>
                    <div id="current_img_wrap" style="margin-top:8px;display:none;">
                        <img id="current_img" src="" style="width:80px;height:60px;object-fit:cover;border-radius:6px;">
                        <span id="current_img_name" style="font-size:12px;color:#aaa;margin-left:8px;"></span>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-check">
                        <input type="checkbox" name="available" id="f_available" value="1" checked>
                        Available for ordering
                    </label>
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="modal-save-btn">Save Dish</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('modal-title').textContent  = 'Add Dish';
    document.getElementById('modal-save-btn').textContent = 'Add Dish';
    document.getElementById('dish_id').value    = '';
    document.getElementById('f_name').value     = '';
    document.getElementById('f_category').value = '';
    document.getElementById('f_price').value    = '';
    document.getElementById('f_pax').value      = '';
    document.getElementById('f_description').value = '';
    document.getElementById('f_available').checked  = true;
    document.getElementById('current_img_wrap').style.display = 'none';
    document.getElementById('dishModal').classList.add('open');
}

function openEditModal(dish) {
    document.getElementById('modal-title').textContent  = 'Edit Dish';
    document.getElementById('modal-save-btn').textContent = 'Save Changes';
    document.getElementById('dish_id').value    = dish.id;
    document.getElementById('f_name').value     = dish.name;
    document.getElementById('f_price').value    = dish.price;
    document.getElementById('f_pax').value      = dish.pax || '';
    document.getElementById('f_description').value = dish.description || '';
    document.getElementById('f_available').checked  = dish.available == 1;

    const sel = document.getElementById('f_category');
    let found = false;
    for (let opt of sel.options) {
        if (opt.value === dish.category) { sel.value = dish.category; found = true; break; }
    }
    if (!found) { sel.value = '__custom__'; document.getElementById('custom_cat_wrap').style.display = 'block'; document.getElementById('f_custom_category').value = dish.category; }

    if (dish.image) {
        document.getElementById('current_img').src = '../images/menu/' + dish.image;
        document.getElementById('current_img_name').textContent = dish.image;
        document.getElementById('current_img_wrap').style.display = 'flex';
        document.getElementById('current_img_wrap').style.alignItems = 'center';
    } else {
        document.getElementById('current_img_wrap').style.display = 'none';
    }

    document.getElementById('dishModal').classList.add('open');
}

function closeModal() {
    document.getElementById('dishModal').classList.remove('open');
}

document.getElementById('f_category').addEventListener('change', function() {
    const wrap = document.getElementById('custom_cat_wrap');
    wrap.style.display = this.value === '__custom__' ? 'block' : 'none';
});

document.getElementById('dishModal').addEventListener('click', function(e) {
    if (e.target === this) closeModal();
});

<?php if ($editDish): ?>
openEditModal(<?= json_encode($editDish) ?>);
<?php endif; ?>
</script>

</body>
</html>
<?php $conn->close(); ?>