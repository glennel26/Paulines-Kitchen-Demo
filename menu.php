<?php
session_start();

require_once 'config/db.php';

$res = $conn->query("SELECT * FROM menu WHERE available = 1 ORDER BY category, name ASC");
$dbDishes = [];
while ($row = $res->fetch_assoc()) {
    $dbDishes[] = $row;
}

$BEST_SELLERS_PHP = [];
$SNACKS_PHP       = [];
$SUBCATS_PHP      = [];

$RICE_CATS = ['chicken', 'pork', 'beef', 'seafood', 'ulam', 'silog meals'];

foreach ($dbDishes as $dish) {
    $cat   = strtolower(trim($dish['category']));
    $price = (float)$dish['price'];
    $desc  = $dish['description'] ?? '';

    $grams = 0;
    if (preg_match('/(\d+)\s*g/i', $desc, $m)) $grams = (int)$m[1];

    $hasRice = false;
    foreach ($RICE_CATS as $rc) {
        if (strpos($cat, $rc) !== false) { $hasRice = true; break; }
    }

    $paxPrices = ["5" => $price*5, "10" => $price*10, "15" => $price*15, "20" => $price*20];

    $item = [
        'name'      => $dish['name'],
        'image'     => 'images/menu/' . $dish['image'],
        'basePrice' => $price,
        'grams'     => $grams,
        'hasRice'   => $hasRice,
        'paxPrices' => $paxPrices,
    ];

    if (stripos($desc, 'bestseller') !== false) {
        $BEST_SELLERS_PHP[] = $item;
    }

    if ($cat === 'snacks' || $cat === 'desserts') {
        $SNACKS_PHP[] = $item;
    } else {
        $catKey = preg_replace('/[^a-z0-9]/', '', $cat);
        if (!isset($SUBCATS_PHP[$catKey])) {
            $SUBCATS_PHP[$catKey] = ['label' => ucwords($dish['category']), 'items' => []];
        }
        $SUBCATS_PHP[$catKey]['items'][] = $item;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Menu – Paulines' Kitchen</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/menu.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="icon" type="image/x-icon" href="images/logoo.png">
    <link rel="stylesheet" href="images/icons/icofont/icofont.min.css">
</head>
<body>

<nav class="navbar">
    <div class="nav-logo">
        <img src="images/logoo.png" alt="Paulines Kitchen" class="logo-img">
        <span class="nav-brand">Paulines' Kitchen</span>
    </div>
    <ul class="nav-links">
        <li><a href="index.php">Home</a></li>
        <li><a href="menu.php" class="nav-active">Menu</a></li>
        <li><a href="about.php">About</a></li>
        <li><a href="cart.php">Order</a></li>
        <li><a href="index.php#faqs">FAQs</a></li>
    </ul>
    <div class="nav-right">
        <a href="cart.php" class="cart-icon">
            <i class="icofont-food-basket"></i>
            <span class="badge"><?= count($_SESSION['cart'] ?? []) ?></span>
        </a>
        <?php if (isset($_SESSION['user_name'])): ?>
            <span style="color:#fff; font-weight:600;">Hi, <?= htmlspecialchars($_SESSION['user_name']) ?>!</span>
            <a href="user/auth/logout.php" class="login-btn">Logout</a>
        <?php else: ?>
            <a href="user/login.php" class="login-btn">Login</a>
        <?php endif; ?>
    </div>
</nav>
<?php $active_page='menu'; include 'includes/mobile_nav.php'; ?>

<div class="menu-page-header" style="background: linear-gradient(rgba(28,49,68,0.55), rgba(28,49,68,0.55)), url('dishes-photos/headermenuu.jpg') center/cover no-repeat;">
    <h1>Our <span>Menu</span></h1>
    <div class="header-divider"></div>
</div>

<div class="menu-tabs-wrap">
    <div class="menu-tabs">
        <button class="menu-tab active" onclick="switchTab('best-sellers', this)">Best Sellers</button>
        <button class="menu-tab" onclick="switchTab('packed-meals', this)">Packed Meals</button>
        <button class="menu-tab" onclick="switchTab('party-trays', this)">Party Trays</button>
        <button class="menu-tab" onclick="switchTab('snacks', this)">Snacks &amp; Desserts</button>
    </div>
</div>

<div class="search-wrap">
    <input class="search-input" type="text" id="searchInput" placeholder="Chicken Adobo" oninput="handleSearch(this.value)">
</div>

<section class="menu-section" id="search-results">
    <div class="section-title">Search <span>Results</span></div>
    <div class="section-divider"></div>
    <div class="items-grid" id="search-grid"></div>
    <p class="no-results" id="no-results" style="display:none;">No dishes found. Try another keyword or make a custom order below!</p>
</section>

<section class="menu-section active" id="best-sellers">
    <div class="section-title">Best <span>Sellers</span></div>
    <div class="section-divider"></div>
    <div class="items-grid" id="bs-grid"></div>
</section>

<section class="menu-section" id="packed-meals">
    <div class="section-title">Packed <span>Meals</span></div>
    <div class="section-divider"></div>
    <div class="sub-tabs-wrap" id="pm-sub-tabs"></div>
    <div id="pm-panels"></div>
</section>

<section class="menu-section" id="party-trays">
    <div class="section-title">Party <span>Trays</span></div>
    <div class="section-divider"></div>
    <div class="sub-tabs-wrap" id="pt-sub-tabs"></div>
    <div id="pt-panels"></div>
</section>

<section class="menu-section" id="snacks">
    <div class="section-title">Snacks <span>&amp; Desserts</span></div>
    <div class="section-divider"></div>
    <div class="items-grid" id="snacks-grid"></div>
</section>

<div class="custom-order-top-bar"></div>
<section class="custom-order-section">
    <div class="custom-order-inner">
        <div class="custom-order-header">
            <div class="custom-order-icon"><i class="icofont-pencil-alt-5"></i></div>
            <div>
                <h2 class="custom-order-title">Custom <span>Order</span></h2>
                <p class="custom-order-sub" id="custom">Any dish on mind? We got you! Just fill out this form and we'll get back to you.</p>
            </div>
        </div>

        <div class="co-form" id="co-form">
            <div class="co-fields">
                <div class="co-field">
                    <label class="co-label"><i class="icofont-user-alt-3"></i> Full Name</label>
                    <input class="co-input" id="co-name" type="text" placeholder="ex. Zaldy Co">
                </div>
                <div class="co-field">
                    <label class="co-label"><i class="icofont-phone"></i> Contact Number</label>
                    <input class="co-input" id="co-contact" type="tel" placeholder="ex. 0912 345 6789">
                </div>
                <div class="co-field full">
                    <label class="co-label"><i class="icofont-food-basket"></i> Custom dish you want to order</label>
                    <input class="co-input" id="co-dish" type="text" placeholder="ex. Sinampalukang Manok (with extra ginger.)">
                </div>
                <div class="co-field full">
                    <label class="co-label"><i class="icofont-people"></i> Good For (Pax)</label>
                    <div class="co-pax-row">
                        <button class="co-pax-btn active" onclick="setPax(this, 1)">1 PAX</button>
                        <button class="co-pax-btn" onclick="setPax(this, 5)">5 PAX</button>
                        <button class="co-pax-btn" onclick="setPax(this, 10)">10 PAX</button>
                        <button class="co-pax-btn" onclick="setPax(this, 20)">20 PAX</button>
                        <input class="co-input co-pax-custom" id="co-custom-pax" type="number" min="1" placeholder="Custom pax..." oninput="clearPaxBtns()">
                    </div>
                </div>
                <div class="co-field full">
                    <label class="co-label"><i class="icofont-box"></i> Quantity (trays / packs)</label>
                    <div class="co-qty-wrap">
                        <button class="co-qty-btn" onclick="changeCoQty(-1)">−</button>
                        <span class="co-qty-display" id="co-qty">1</span>
                        <button class="co-qty-btn" onclick="changeCoQty(1)">+</button>
                    </div>
                </div>
            </div>
            <div class="co-error" id="co-error">
                <i class="icofont-warning-alt"></i> Please fill in all fields before submitting.
            </div>
            <button class="co-submit-btn" onclick="submitCustomOrder()">
                <i class="icofont-send-mail"></i> Submit Request
            </button>
        </div>

        <div class="co-success" id="co-success">
            <div class="co-success-icon"><i class="icofont-check-circled"></i></div>
            <h3 class="co-success-title">Thank you for your interest!</h3>
            <p class="co-success-msg">We'll send you a text and a call soon!</p>
            <p class="co-success-note">Keep your lines open 😊</p>
            <button class="co-another-btn" onclick="resetCustomOrder()">Submit Another Request</button>
        </div>
    </div>
</section>

<div class="popup-overlay" id="loginPopup">
    <div class="popup-box">
        <button class="popup-close" onclick="closePopup()">✕</button>
        <img src="images/logoo.png" alt="Logo" class="popup-logo">
        <h2 class="popup-title">Sign in to Continue</h2>
        <p class="popup-sub">You need to be logged in to add items to cart or place an order.</p>
        <div class="popup-btns">
            <a href="user/login.php" class="popup-btn-login">Login</a>
            <a href="user/register.php" class="popup-btn-register">Create an Account</a>
        </div>
    </div>
</div>

<script>
var isLoggedIn = <?php echo isset($_SESSION['user_id']) ? 'true' : 'false'; ?>;

const BEST_SELLERS = <?php echo json_encode(array_values($BEST_SELLERS_PHP)); ?>;
const SNACKS       = <?php echo json_encode(array_values($SNACKS_PHP)); ?>;
const SUBCATS      = <?php echo json_encode($SUBCATS_PHP); ?>;

const ALL_ITEMS = [];
const seen = new Set();
BEST_SELLERS.forEach(d => { ALL_ITEMS.push({...d}); seen.add(d.name); });
SNACKS.forEach(d => { ALL_ITEMS.push({...d}); seen.add(d.name); });
Object.values(SUBCATS).forEach(cat => {
    cat.items.forEach(item => {
        if (!seen.has(item.name)) { ALL_ITEMS.push({...item}); seen.add(item.name); }
    });
});

function requireLogin(event) {
    if (!isLoggedIn) {
        event.preventDefault();
        event.stopPropagation();
        document.getElementById('loginPopup').classList.add('active');
    }
}

function closePopup() {
    document.getElementById('loginPopup').classList.remove('active');
}

window.addEventListener('click', function(e) {
    var popup = document.getElementById('loginPopup');
    if (e.target === popup) closePopup();
});

let lastActiveTab = 'best-sellers';
function switchTab(id, btn) {
    document.querySelectorAll('.menu-section').forEach(s => s.classList.remove('active'));
    document.querySelectorAll('.menu-tab').forEach(b => b.classList.remove('active'));
    document.getElementById(id).classList.add('active');
    btn.classList.add('active');
    lastActiveTab = id;
    document.getElementById('searchInput').value = '';
}

function buildCard({ name, image, basePrice, grams, hasRice = false, isParty = false, paxPrices = null }) {
    const partyData = isParty
        ? `data-is-party="true" data-pax-prices='${JSON.stringify(paxPrices)}'`
        : `data-base-price="${basePrice}" data-rice-addon="0"`;

    const priceHtml = isParty
        ? `<div class="item-price empty">— Select PAX —</div>`
        : `<div class="item-price">PHP ${Number(basePrice).toLocaleString()}.00</div>`;

    const gramsHtml = (!isParty && grams) ? `<div class="item-grams">~${grams}g per serving</div>` : '';

    const paxHtml = isParty ? `
        <div class="pax-label">PAX: <em>—</em></div>
        <div class="pax-row">
            <button class="pax-btn" data-pax="5"  onclick="selectPax(this)">5 PAX</button>
            <button class="pax-btn" data-pax="10" onclick="selectPax(this)">10 PAX</button>
            <button class="pax-btn" data-pax="15" onclick="selectPax(this)">15 PAX</button>
            <button class="pax-btn" data-pax="20" onclick="selectPax(this)">20 PAX</button>
        </div>` : '';

    const riceHtml = (!isParty && hasRice) ? `
        <div class="rice-row">
            <span class="rice-label">Rice:</span>
            <button class="rice-btn active" data-add="0"  onclick="selectRice(this)">None</button>
            <button class="rice-btn" data-add="25" onclick="selectRice(this)">1 Cup Rice +₱25</button>
            <button class="rice-btn" data-add="50" onclick="selectRice(this)">2 Cups Rice +₱50</button>
        </div>` : '';

    return `
    <div class="item-card" ${partyData}>
        <div class="item-img-wrap">
            <img src="${image}" alt="${name}" class="item-img">
        </div>
        <div class="item-body">
            <div class="item-name">${name}</div>
            ${gramsHtml}
            ${priceHtml}
            ${paxHtml}
            ${riceHtml}
            <div class="item-actions">
                <div class="qty-row">
                    <div class="qty-wrap">
                        <button class="qty-btn" onclick="changeQty(this,-1)">−</button>
                        <input class="qty-input" type="number" value="1" min="1" readonly>
                        <button class="qty-btn" onclick="changeQty(this,1)">+</button>
                    </div>
                    <button class="btn-heart" onclick="toggleHeart(this)">♡</button>
                </div>
                <button class="btn-cart" onclick="requireLogin(event); if(isLoggedIn) addToCart(this)">Add to Bag</button>
                <button class="btn-buy"  onclick="requireLogin(event); if(isLoggedIn) buyNow(this)">Buy Now</button>
            </div>
        </div>
    </div>`;
}

document.getElementById('bs-grid').innerHTML     = BEST_SELLERS.map(d => buildCard(d)).join('');
document.getElementById('snacks-grid').innerHTML = SNACKS.map(d => buildCard({...d, hasRice: false})).join('');

function buildSubSection(tabsId, panelsId, isParty) {
    const tabsEl   = document.getElementById(tabsId);
    const panelsEl = document.getElementById(panelsId);
    Object.entries(SUBCATS).forEach(([key, cat], i) => {
        const btn = document.createElement('button');
        btn.className = 'sub-tab' + (i === 0 ? ' active' : '');
        btn.textContent = cat.label;
        btn.onclick = () => {
            tabsEl.querySelectorAll('.sub-tab').forEach(b => b.classList.remove('active'));
            panelsEl.querySelectorAll('.sub-panel').forEach(p => p.classList.remove('active'));
            btn.classList.add('active');
            document.getElementById(`${tabsId}-${key}`).classList.add('active');
        };
        tabsEl.appendChild(btn);
        const cards = cat.items.map(item => buildCard({
            name: item.name, image: item.image, basePrice: item.basePrice,
            grams: item.grams, hasRice: item.hasRice,
            isParty, paxPrices: isParty ? item.paxPrices : null
        })).join('');
        const panel = document.createElement('div');
        panel.className = 'sub-panel' + (i === 0 ? ' active' : '');
        panel.id = `${tabsId}-${key}`;
        panel.innerHTML = `<div class="sub-panel-title">${cat.label}</div><div class="items-grid">${cards}</div>`;
        panelsEl.appendChild(panel);
    });
}

buildSubSection('pm-sub-tabs', 'pm-panels', false);
buildSubSection('pt-sub-tabs', 'pt-panels', true);

function updatePrice(card) {
    if (card.dataset.isParty === 'true') {
        const active = card.querySelector('.pax-btn.active');
        if (!active) return;
        const prices = JSON.parse(card.dataset.paxPrices);
        const qty    = parseInt(card.querySelector('.qty-input').value) || 1;
        const el     = card.querySelector('.item-price');
        el.classList.remove('empty');
        el.textContent = `PHP ${(prices[active.dataset.pax] * qty).toLocaleString()}.00`;
    } else {
        const base = parseInt(card.dataset.basePrice);
        const rice = parseInt(card.dataset.riceAddon) || 0;
        const qty  = parseInt(card.querySelector('.qty-input').value) || 1;
        card.querySelector('.item-price').textContent = `PHP ${((base + rice) * qty).toLocaleString()}.00`;
    }
}

function selectRice(btn) {
    const card = btn.closest('.item-card');
    btn.closest('.rice-row').querySelectorAll('.rice-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    card.dataset.riceAddon = btn.dataset.add;
    updatePrice(card);
}

function selectPax(btn) {
    const card = btn.closest('.item-card');
    btn.closest('.pax-row').querySelectorAll('.pax-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    card.querySelector('.pax-label em').textContent = btn.dataset.pax + ' PAX';
    updatePrice(card);
}

function changeQty(btn, delta) {
    const input = btn.parentElement.querySelector('.qty-input');
    input.value = Math.max(1, parseInt(input.value) + delta);
    updatePrice(btn.closest('.item-card'));
}

function handleSearch(val) {
    const q = val.trim().toLowerCase();
    const searchSection = document.getElementById('search-results');
    if (!q) {
        searchSection.classList.remove('active');
        document.getElementById(lastActiveTab).classList.add('active');
        return;
    }
    document.querySelectorAll('.menu-section').forEach(s => s.classList.remove('active'));
    searchSection.classList.add('active');
    const matches = ALL_ITEMS.filter(item => item.name.toLowerCase().includes(q));
    const grid  = document.getElementById('search-grid');
    const noRes = document.getElementById('no-results');
    if (matches.length === 0) { grid.innerHTML = ''; noRes.style.display = 'block'; }
    else { noRes.style.display = 'none'; grid.innerHTML = matches.map(item => buildCard(item)).join(''); }
}

function addToCart(btn) {
    const card      = btn.closest('.item-card');
    const name      = card.querySelector('.item-name').textContent.trim();
    const qty       = card.querySelector('.qty-input').value;
    const riceAddon = card.dataset.riceAddon || 0;
    const pax       = card.querySelector('.pax-btn.active')?.dataset.pax || '';
    const image     = card.querySelector('.item-img')?.src || '';
    const priceText = card.querySelector('.item-price').textContent.replace(/[^0-9.]/g, '');
    const unitPrice = parseFloat(priceText) / parseInt(qty);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'user/auth/add_to_cart.php';

    const fields = { name, price: unitPrice, quantity: qty, rice_addon: riceAddon, pax, image };
    for (const [key, val] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = key; input.value = val;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

function buyNow(btn) {
    const card      = btn.closest('.item-card');
    const name      = card.querySelector('.item-name').textContent.trim();
    const qty       = card.querySelector('.qty-input').value;
    const riceAddon = card.dataset.riceAddon || 0;
    const pax       = card.querySelector('.pax-btn.active')?.dataset.pax || '';
    const image     = card.querySelector('.item-img')?.src || '';
    const priceText = card.querySelector('.item-price').textContent.replace(/[^0-9.]/g, '');
    const unitPrice = parseFloat(priceText) / parseInt(qty);

    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'user/auth/add_to_cart.php?clear=1&redirect=checkout';

    const fields = { name, price: unitPrice, quantity: qty, rice_addon: riceAddon, pax, image };
    for (const [key, val] of Object.entries(fields)) {
        const input = document.createElement('input');
        input.type = 'hidden'; input.name = key; input.value = val;
        form.appendChild(input);
    }
    document.body.appendChild(form);
    form.submit();
}

function toggleHeart(btn) {
    btn.classList.toggle('liked');
    btn.textContent = btn.classList.contains('liked') ? '♥' : '♡';
}

let coQty = 1;
let coSelectedPax = 1;

function setPax(btn, val) {
    document.querySelectorAll('.co-pax-btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    coSelectedPax = val;
    document.getElementById('co-custom-pax').value = '';
}

function clearPaxBtns() {
    document.querySelectorAll('.co-pax-btn').forEach(b => b.classList.remove('active'));
    coSelectedPax = null;
}

function changeCoQty(delta) {
    coQty = Math.max(1, coQty + delta);
    document.getElementById('co-qty').textContent = coQty;
}

function submitCustomOrder() {
    const name    = document.getElementById('co-name').value.trim();
    const dish    = document.getElementById('co-dish').value.trim();
    const contact = document.getElementById('co-contact').value.trim();
    const custom  = document.getElementById('co-custom-pax').value.trim();
    const paxVal  = custom || coSelectedPax;
    const errEl   = document.getElementById('co-error');
    if (!name || !dish || !contact || !paxVal) { errEl.style.display = 'flex'; return; }
    errEl.style.display = 'none';

    const btn = document.querySelector('.co-submit-btn');
    const oldText = btn.innerHTML;
    btn.innerHTML = '<i class="icofont-spinner icofont-spin"></i> Submitting...';
    btn.disabled = true;

    const formData = new FormData();
    formData.append('name', name);
    formData.append('dish', dish);
    formData.append('contact', contact);
    formData.append('pax', paxVal);
    formData.append('qty', coQty);

    fetch('user/auth/submit_custom_order.php', { method: 'POST', body: formData })
    .then(r => r.json())
    .then(data => {
        btn.innerHTML = oldText;
        btn.disabled = false;
        if (data.success) {
            document.getElementById('co-form').style.display = 'none';
            document.getElementById('co-success').style.display = 'flex';
        } else {
            errEl.innerHTML = '<i class="icofont-warning-alt"></i> ' + (data.message || 'An error occurred.');
            errEl.style.display = 'flex';
        }
    })
    .catch(error => {
        btn.innerHTML = oldText;
        btn.disabled = false;
        errEl.innerHTML = '<i class="icofont-warning-alt"></i> Network error. Please try again.';
        errEl.style.display = 'flex';
    });
}

function resetCustomOrder() {
    document.getElementById('co-name').value    = '';
    document.getElementById('co-dish').value    = '';
    document.getElementById('co-contact').value = '';
    document.getElementById('co-custom-pax').value = '';
    coQty = 1; coSelectedPax = 1;
    document.getElementById('co-qty').textContent = '1';
    document.querySelectorAll('.co-pax-btn').forEach((b, i) => b.classList.toggle('active', i === 0));
    document.getElementById('co-form').style.display    = 'block';
    document.getElementById('co-success').style.display = 'none';
}

window.addEventListener('load', function() {
    var hash = window.location.hash.replace('#', '');
    if (hash) {
        document.querySelectorAll('.menu-tab').forEach(function(tab) {
            if (tab.getAttribute('onclick') && tab.getAttribute('onclick').includes(hash)) {
                switchTab(hash, tab);
            }
        });
    }
});
</script>
</body>
</html>