<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Pauline's Kitchen</title>
    <link rel="stylesheet" href="css/style.css?v=2">
    <link rel="stylesheet" href="css/about.css?v=2">
    <link href="https://fonts.googleapis.com/css2?family=Raleway:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        <li><a href="index.php">Home</a></li>
        <li><a href="menu.php">Menu</a></li>
        <li><a href="about.php" class="nav-active">About</a></li>
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
<?php $active_page='about'; include 'includes/mobile_nav.php'; ?>

    <section class="about-hero">
        <div class="about-hero-overlay"></div>
        <div class="about-hero-box">
            <h1>A KITCHEN <br>BUILT BY TWO <i class="icofont-flora-flower"></i></h1>
            <p>Serving Filipino Cuisine Since Day One</p>
        </div>
    </section>

    <section class="about-story">
        <h2 class="about-story-title">OUR STORY</h2>
        <p class="about-story-sub">How we started the <strong>Pauline's Kitchen.</strong></p>

        <div class="about-story-content">
            <h3 class="about-story-chapter">IT ALL STARTED HERE...</h3>

            <p class="highlight"><span>We officially opened Pauline's Kitchen at the start of 2026, but we have been cooking together for much longer.</span></p>

            <p>My business partner and I actually spent years working together in a 4-star hotel in Manila.</p>
            <p>I am a Culinary Graduate at University of the East and my business partner is a Hospitality Management Graduate at Bulacan Agricultural State University. We loved what we did, but when the hotel suddenly had to close its doors, we were left wondering what to do next.</p>

            <p>Instead of starting over somewhere else, we decided to take a leap of faith. We took all that professional experience; the high standards, the careful prep, the love for really good food and brought it back home to San Miguel. We officially opened up shop at the start of 2026. Our goal is simple: to give you that same hotel-quality dining experience, but with the heart of home cooking and at a price that makes sense. Because at the end of the day, we just believe that good food makes for a good mood.</p>
        </div>
    </section>

    <section class="about-mid-banner">
        <video autoplay muted loop playsinline class="about-mid-video">
            <source src="images/videos/video2.mp4" type="video/mp4">
        </video>
        <div class="about-mid-overlay"></div>
        <div class="about-mid-text">
            <h2>FOOD MADE WITH LOVE.</h2>
            <p>Ready to cook for you.</p>
        </div>
    </section>

    <section class="about-story">
        <div class="about-story-content">
            <p><span class="highlight">Cooking in our kitchen is a privilege, and cooking for <strong>YOU</strong> is what completes our mission.</span></p>
        </div>
        <div class="about-cta">
            <p>Wanna taste what's cooking?</p>
            <a href="menu.php" class="btn-cta">Order Now</a>
        </div>
    </section>

    <section class="contact-section" id="contact">
        <h2 class="contact-title">Get in Touch</h2>
        <p class="contact-sub">Have questions or want to place an order? Reach us through:</p>

        <div class="contact-cards">

            <a href="https://www.facebook.com/profile.php?id=61588361559105" target="_blank" class="contact-card">
                <i class="icofont-facebook-messenger"></i>
                <p class="contact-handle">Pauline's Kitchen</p>
            </a>

            <a href="https://www.tiktok.com/@paulineskitchen" target="_blank" class="contact-card">
                <svg class="icofont-tiktok" role="img" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" style="width: 1em; height: 1em; fill: currentColor; display: inline-block; vertical-align: middle;"><title>TikTok</title><path d="M12.525.02c1.31-.02 2.61-.01 3.91-.02.08 1.53.63 3.09 1.75 4.17 1.12 1.11 2.7 1.62 4.24 1.79v4.03c-1.44-.05-2.89-.35-4.2-.97-.57-.26-1.1-.59-1.62-.93-.01 2.92.01 5.84-.02 8.75-.08 1.4-.54 2.79-1.35 3.94-1.31 1.92-3.58 3.17-5.91 3.21-1.43.08-2.86-.31-4.08-1.03-2.02-1.19-3.44-3.37-3.65-5.71-.02-.5-.03-1-.01-1.49.18-1.9 1.12-3.72 2.58-4.96 1.66-1.44 3.98-2.13 6.15-1.72.02 1.48-.04 2.96-.04 4.44-.99-.32-2.15-.23-3.02.37-.63.41-1.11 1.04-1.36 1.75-.21.51-.15 1.07-.14 1.61.24 1.64 1.82 3.02 3.5 2.87 1.12-.01 2.19-.66 2.77-1.61.19-.33.4-.67.41-1.06.1-1.79.06-3.57.07-5.36.01-4.03-.01-8.05.02-12.07z"/></svg>
                <p class="contact-handle">@paulineskitchen</p>
            </a>

            <div class="contact-card">
                <i class="icofont-phone"></i>
                <p class="contact-handle">0912 345 6789</p>
            </div>

        </div>
    </section>

    <footer class="footer">
        <div class="footer-top">

            <div class="footer-brand">
                <img src="images/logoo.png" alt="Paulines Kitchen" class="footer-logo">
                <p class="footer-brand-name">Paulines' Kitchen</p>
                <p class="footer-brand-tagline">Good Food, Good Mood</p>
                <p class="footer-contact-detail">San Miguel, Bulacan</p>
                <p class="footer-contact-detail">0912 345 678</p>
                <p class="footer-contact-detail">paulineskitchen@gmail.com</p>
                <p class="footer-contact-detail">Mon - Sat: 7AM - 7PM</p>
            </div>

            <div class="footer-col">
                <h4 class="footer-col-title">SHOP</h4>
                <ul class="footer-links">
                    <li><a href="index.php">Home</a></li>
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
                    <li><a href="about.php#contact">Contact Us</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <h4 class="footer-col-title">FOLLOW US</h4>
                <ul class="footer-links">
                    <li><a href="https://www.facebook.com/profile.php?id=61588361559105" target="_blank">Facebook</a></li>
                    <li><a href="https://www.tiktok.com/@paulineskitchen" target="_blank">TikTok</a></li>
                </ul>
            </div>

        </div>

        <div class="footer-bottom">
            <p>&copy; 2026 Paulines' Kitchen. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>