<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pauline's Kitchen</title>
    <link rel="stylesheet" href="css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="images/icons/icofont/icofont.min.css">
    <link rel="icon" type="image/x-icon" href="images/logoo.png">
</head>
<body>

    <nav class="navbar">
        <div class="nav-logo">
            <img src="images/logoo.png" alt="Paulines Kitchen" class="logo-img">
            <span class="nav-brand">Paulines' Kitchen</span>
        </div>
        <ul class="nav-links">
            <li><a href="index.php" class="nav-active">Home</a></li>
            <li><a href="menu.php">Menu</a></li>
            <li><a href="about.php">About</a></li>
            <li><a href="cart.php" id="nav-order">Order</a></li>
            <li><a href="index.php#faqs" id="nav-faqs">FAQs</a></li>
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
<?php $active_page='home'; include 'includes/mobile_nav.php'; ?>

    <section class="hero">
        <div class="hero-overlay"></div>
        <div class="hero-text">
            <p class="pre-header">
                Because <span class="red-line">"kumain ka na ba?"</span> is the Filipino way of <span class="love-word">love</span>..
            </p>
            <h1>Experience the Classic<br><span>Pinoy Meals</span></h1>
            <p class="sub-text">Let us do the cooking for you</p>
            <div class="hero-btns">
                <a href="menu.php#packed-meals" class="btn-main">Discover our Packed Meals</a>
                <a href="menu.php#party-trays" class="btn-outline">Take a look at our Party Trays</a>
            </div>
            <div class="hero-btns-second">
                <p class="custom-label">Have some dish in mind?</p>
                <a href="menu.php#co-form" class="btn-custom">Request Custom Order</a>
            </div>
        </div>
    </section>

    <section class="reasons-section">
        <div class="reasons-top-bar"></div>
        <div class="reasons-inner">
            <div class="reasons-header">
                <h2>Why <span class="paulines">Paulines' Kitchen</span></h2>
                <img src="images/logoo.png" alt="logo" class="reasons-logo">
            </div>
            <div class="reasons-divider"></div>
            <p class="reasons-subtitle">We believe in a saying: Good Food = Good Mood</p>

            <div class="reasons-cards">
                <div class="reasons-card">
                    <p class="card-title">Solo or Salu-Salo</p>
                    <div class="card-img-wrap">
                        <img src="images/solo-salu.png" alt="Solo or Salu-Salo" class="card-img">
                    </div>
                    <p class="card-desc">Serving everything from solo daily meals to gatherings of up to 40 pax</p>
                </div>

                <div class="reasons-card card-center">
                    <p class="card-title">Made by Real Cooks</p>
                    <div class="card-img-wrap">
                        <img src="images/cooks.png" alt="Real Cooks" class="card-img">
                    </div>
                    <p class="card-desc">Made from scratch by hands that know the kitchen.</p>
                </div>

                <div class="reasons-card">
                    <p class="card-title">Friendly Prices</p>
                    <div class="card-img-wrap">
                        <img src="images/wallet.png" alt="Friendly Prices" class="card-img">
                    </div>
                    <p class="card-desc">High-quality ingredients and generous portions that won't empty your wallet.</p>
                </div>
            </div>
        </div>
        <div class="reasons-bottom-bar"></div>
    </section>

    <section class="reviews-section">
        <div class="reviews-top-accent"></div>
        <div class="reviews-content">
            <h2 class="reviews-heading">Watch Us Cook. Read Why They Love It.</h2>
            <div class="reviews-divider"></div>

            <div class="reviews-inner">
                <div class="video-side">
                    <p class="video-label"><i class="icofont-chef"></i> Watch us cook on TikTok <i class="icofont-chef"></i></p>
                    <iframe
                        class="tiktok-video"
                        src="https://drive.google.com/file/d/1l-GxiSYUylg5QaOiVttTccFw9Cuw_YXb/preview"
                        frameborder="0"
                        allow="autoplay"
                        allowfullscreen
                        loading="lazy">
                    </iframe>
                </div>

                <div class="carousel-side">
                    <p class="carousel-header"><i class="icofont-star" style="color:#f5c518;"></i> Customer Feedbacks <i class="icofont-star" style="color:#f5c518;"></i></p>
                    <div class="carousel-wrapper">
                        <div class="carousel-track" id="carouselTrack">
                            <div class="review-card active"><img src="images/1.png" alt="Review 1" class="review-img"></div>
                            <div class="review-card"><img src="images/2.png" alt="Review 2" class="review-img"></div>
                            <div class="review-card"><img src="images/3.png" alt="Review 3" class="review-img"></div>
                            <div class="review-card"><img src="images/4.png" alt="Review 4" class="review-img"></div>
                            <div class="review-card"><img src="images/5.png" alt="Review 5" class="review-img"></div>
                        </div>
                    </div>
                    <div class="carousel-controls">
                        <button class="carousel-btn" id="prevBtn"> Prev</button>
                        <span class="carousel-count" id="carouselCount">1 / 5</span>
                        <button class="carousel-btn" id="nextBtn">Next</button>
                    </div>
                </div>
            </div>
        </div>
        <div class="reviews-bottom-accent"></div>
    </section>

    <section class="faq-section" id="faqs">
        <h2 class="faq-section-title">FAQs</h2>
        <div class="faq-inner">

            <div class="faq-col">
                <h2 class="faq-heading">
                    <span class="faq-heading-icon"><i class="icofont-tomato"></i></span>
                    Food FAQs
                    <span class="faq-heading-line"></span>
                </h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">What should I do if an ingredient is damaged or missing?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Contact us immediately with a photo and we will replace or refund the missing item.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">For how long will my food remain fresh?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Most meals stay fresh for up to 2 days when refrigerated properly.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">Do you provide food calories and macros?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Yes, we can! If you are tracking your diet, simply leave a message in the "Order Notes" section during checkout and we will include the information for you.</p></div>
                    </div>
                </div>
            </div>

            <div class="faq-divider-vertical"></div>

            <div class="faq-col">
                <h2 class="faq-heading">
                    <span class="faq-heading-icon"><i class="icofont-motor-biker"></i></span>
                    Delivery FAQs
                    <span class="faq-heading-line"></span>
                </h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">Where are the areas you deliver?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Currently, we proudly serve and deliver our food trays to the following areas: San Miguel (Bulacan), San Ildefonso (Bulacan), and Gapan (Nueva Ecija). We do not encourage very far areas as the food will decrease its freshness.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">Will my food stay fresh in transit?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Yes. Orders are packed securely to maintain freshness.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">Can I change my delivery time?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>It depends, but please notify us as soon as possible! Because our food is prepared fresh to ensure maximum quality, any time adjustments depend on our current kitchen schedule. Please message our Facebook page immediately.</p></div>
                    </div>
                </div>
            </div>

            <div class="faq-divider-vertical"></div>

            <div class="faq-col">
                <h2 class="faq-heading">
                    <span class="faq-heading-icon"><i class="icofont-money"></i></span>
                    Payment FAQs
                    <span class="faq-heading-line"></span>
                </h2>
                <div class="faq-list">
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">What payment methods do you accept?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>For your convenience, we accept secure online payments via GCash and Credit/Debit Cards directly through our website. We also offer Cash on Delivery (COD) for eligible orders.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">What if my payment fails?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>Contact us immediately and we will help complete the payment.</p></div>
                    </div>
                    <div class="faq-item">
                        <button class="faq-question" onclick="toggleFaq(this)">Do you accept refunds?<span class="faq-icon">+</span></button>
                        <div class="faq-answer"><p>We do not offer refunds for packed meals once they have been delivered. For large food trays, We can process a refund as long as you request it well in advance of your scheduled date and time.</p></div>
                    </div>
                </div>
            </div>

        </div>
    </section>

    <footer class="footer">
        <div class="footer-top">
            <div class="footer-brand">
                <img src="images/logoo.png" alt="Paulines Kitchen" class="footer-logo">
                <p class="footer-brand-name">Paulines' Kitchen</p>
                <p class="footer-brand-tagline">Good Food, Good Mood</p>
                <p class="footer-contact-detail"> San Miguel, Bulacan</p>
                <p class="footer-contact-detail">0912 345 678</p>
                <p class="footer-contact-detail">paulineskitchen@gmail.com</p>
                <p class="footer-contact-detail">Mon - Sat: 7AM - 7PM</p>
            </div>
            <div class="footer-col">
                <h4 class="footer-col-title">SHOP</h4>
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="menu.php">Menu</a></li>
                    <li><a href="menu.php#packed-meals">Packed Meals</a></li>
                    <li><a href="menu.php#party-trays">Party Trays</a></li>
                    <li><a href="menu.php#co-form">Custom Orders</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4 class="footer-col-title">SUPPORT</h4>
                <ul class="footer-links">
                    <li><a href="index.php#faqs">FAQs</a></li>
                    <li><a href="about.html#contact">Contact Us</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4 class="footer-col-title">FOLLOW US</h4>
                <ul class="footer-links">
                    <li><a href="https://www.facebook.com/profile.php?id=61588361559105">Facebook</a></li>
                    <li><a href="https://www.tiktok.com/@paulineskitchen">TikTok</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; 2026 Paulines' Kitchen. All rights reserved.</p>
        </div>
    </footer>

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

        const hero = document.querySelector('.hero');
        window.addEventListener('scroll', function() {
            let offset = window.scrollY;
            hero.style.backgroundPositionY = (offset * 0.4) + 'px';
        });

        const track   = document.getElementById('carouselTrack');
        const countEl = document.getElementById('carouselCount');
        let cards = Array.from(document.querySelectorAll('.review-card'));

        const firstClone = cards[0].cloneNode(true);
        const lastClone  = cards[cards.length - 1].cloneNode(true);
        track.appendChild(firstClone);
        track.insertBefore(lastClone, cards[0]);
        cards = Array.from(document.querySelectorAll('.review-card'));

        let current = 1;

        function updateCarousel(animated = true) {
            track.style.transition = animated ? 'transform 0.5s ease' : 'none';
            const wrapper       = document.querySelector('.carousel-wrapper');
            const wrapperCenter = wrapper.offsetWidth / 2;
            const cardWidth     = cards[0].offsetWidth + 18;
            const activeOffset  = current * cardWidth;
            const centered      = activeOffset - wrapperCenter + (cards[0].offsetWidth / 2);
            track.style.transform = `translateX(-${centered}px)`;
            cards.forEach(card => card.classList.remove('active'));
            cards[current].classList.add('active');
            let realIndex = current - 1;
            if (realIndex < 0) realIndex = cards.length - 3;
            if (realIndex > cards.length - 3) realIndex = 0;
            countEl.textContent = `${realIndex + 1} / ${cards.length - 2}`;
        }

        function nextSlide() {
            current++;
            updateCarousel();
            if (current === cards.length - 1) {
                setTimeout(() => { current = 1; updateCarousel(false); }, 500);
            }
        }

        function prevSlide() {
            current--;
            updateCarousel();
            if (current === 0) {
                setTimeout(() => { current = cards.length - 2; updateCarousel(false); }, 500);
            }
        }

        document.getElementById('nextBtn').addEventListener('click', () => { resetAuto(); nextSlide(); });
        document.getElementById('prevBtn').addEventListener('click', () => { resetAuto(); prevSlide(); });

        let autoSlide = setInterval(nextSlide, 3000);
        function resetAuto() { clearInterval(autoSlide); autoSlide = setInterval(nextSlide, 3000); }

        updateCarousel(false);

        function toggleFaq(btn) {
            var item = btn.parentElement;
            item.classList.toggle('open');
        }
    </script>

</body>
</html>