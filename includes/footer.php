<?php
/**
 * Footer
 * Watch Collection - College Project
 */
?>
<footer class="footer">
    <div class="container footer__grid">
        <div class="footer__col">
            <h3><?= SITE_NAME ?></h3>
            <p>Premium watches for every occasion.</p>
        </div>

        <div class="footer__col">
            <h4>Quick Links</h4>
            <a href="<?= base_url('index.php') ?>">Home</a>
            <a href="<?= base_url('browse-watches.php') ?>">Browse Watches</a>
            <a href="<?= base_url('about.php') ?>">About</a>
            <a href="<?= base_url('contact.php') ?>">Contact</a>
        </div>

        <div class="footer__col">
            <h4>Contact</h4>
            <p>Email: support@watchcollection.test</p>
            <p>Phone: +91 00000 00000</p>
        </div>

        <div class="footer__col">
            <h4>Follow Us</h4>
            <a href="#">Instagram</a>
            <a href="#">Facebook</a>
            <a href="#">Twitter</a>
        </div>
    </div>

    <div class="footer__bottom">
        &copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.
    </div>
</footer>
<script src="<?= base_url('assets/js/script.js') ?>"></script>
</body>
</html>