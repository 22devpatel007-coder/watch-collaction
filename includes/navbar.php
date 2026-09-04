<?php
/**
 * Navigation Bar
 * Watch Collection - College Project
 */
?>
<header class="navbar">
    <div class="container navbar__inner">
        <a href="<?= base_url('index.php') ?>" class="navbar__logo"><?= SITE_NAME ?></a>

        <button type="button" class="navbar__toggle" aria-label="Toggle menu">&#9776;</button>

        <nav class="navbar__menu">

            <?php if (is_logged_in()): ?>
                <a href="<?= base_url('user/dashboard.php') ?>">Dashboard</a>
                <a href="<?= base_url('user/cart.php') ?>">Cart</a>
                <a href="<?= base_url('user/orders.php') ?>">Orders</a>
                <a href="<?= base_url('user/profile.php') ?>">Profile</a>
                <a href="<?= base_url('logout.php') ?>">Logout</a>
            <?php else: ?>
                <a href="<?= base_url('index.php') ?>">Home</a>
                <a href="<?= base_url('browse-watches.php') ?>">Browse Watches</a>
                <a href="<?= base_url('about.php') ?>">About</a>
                <a href="<?= base_url('contact.php') ?>">Contact</a>
                <a href="<?= base_url('login.php') ?>">Login</a>
                <a href="<?= base_url('signup.php') ?>">Sign Up</a>
            <?php endif; ?>
        </nav>
    </div>
</header>