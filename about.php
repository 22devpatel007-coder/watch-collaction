<?php
require_once 'includes/config.php';
require_once 'includes/session.php';
require_once 'includes/database.php';
require_once 'includes/functions.php';
require_once 'includes/user-auth.php';

$page_title = 'About Us - ' . SITE_NAME;
require_once 'includes/header.php';
require_once 'includes/navbar.php';
?>

<style>
.about-wrap { max-width: 900px; margin: 40px auto; padding: 0 20px; }
.about-hero { text-align: center; margin-bottom: 40px; }
.about-hero h1 { font-family: 'DM Serif Display', serif; font-size: 2.2rem; color: var(--color-text-primary); margin-bottom: 12px; }
.about-hero p { color: var(--color-text-secondary); font-size: 1.05rem; }
.about-section { margin-bottom: 32px; }
.about-section h2 { font-family: 'DM Serif Display', serif; font-size: 1.4rem; color: var(--color-text-primary); margin-bottom: 10px; }
.about-section p { color: var(--color-text-secondary); line-height: 1.7; }
.about-values { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-top: 20px; }
.value-card { background: var(--color-card); border: 1px solid var(--color-border); border-radius: 12px; padding: 20px; text-align: center; }
.value-card h3 { font-size: 1rem; color: var(--color-text-primary); margin-bottom: 8px; }
.value-card p { font-size: 0.9rem; color: var(--color-text-secondary); }
@media (max-width: 767px) {
    .about-values { grid-template-columns: 1fr; }
}
</style>

<div class="about-wrap">
    <div class="about-hero">
        <h1>About <?php echo htmlspecialchars(SITE_NAME); ?></h1>
        <p>Curated timepieces, chosen with care.</p>
    </div>

    <div class="about-section">
        <h2>Who We Are</h2>
        <p><?php echo htmlspecialchars(SITE_NAME); ?> is an online destination for people who value craftsmanship and timeless design. We bring together a curated selection of watches across styles — luxury, sport, classic, casual, and smart — so you can find a piece that fits your life.</p>
    </div>

    <div class="about-section">
        <h2>Our Mission</h2>
        <p>We believe a watch is more than an accessory — it's a statement of taste and reliability. Our mission is to make quality timepieces accessible through a simple, trustworthy shopping experience.</p>
    </div>

    <div class="about-section">
        <h2>Why Choose Us</h2>
        <div class="about-values">
            <div class="value-card">
                <h3>Curated Selection</h3>
                <p>Every watch is chosen for quality and design.</p>
            </div>
            <div class="value-card">
                <h3>Secure Shopping</h3>
                <p>Your data and orders are handled safely.</p>
            </div>
            <div class="value-card">
                <h3>Reliable Support</h3>
                <p>We're here to help before and after your purchase.</p>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>