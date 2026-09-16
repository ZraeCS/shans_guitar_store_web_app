<?php
/* includes/footer.php — shared footer + JS for every page.
   (Patched 2026-09: the second window.SG_PRODUCTS injection was removed —
   header.php already injects it safely with JSON_HEX_TAG flags.) */
if (!defined('SITE_NAME')) { require_once __DIR__ . '/../database/config.php'; }
 $base = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<footer id="contact" class="site-footer">
  <div class="footer-brand">
    <div class="logo">shan's guitar</div>
    <p>+63 96 745 8686</p>
    <p>business@shansguitar.com.ph</p>
    <p>Dumaguete City, Negros Oriental</p>
  </div>

  <div>
    <h4>SHOP</h4>
    <a href="<?= $base ?>/shop.php?category=electric">Electric Guitars</a>
    <a href="<?= $base ?>/shop.php?category=acoustic">Acoustic Guitars</a>
    <a href="<?= $base ?>/shop.php?category=classical">Classical Guitars</a>
    <a href="<?= $base ?>/shop.php?category=bass">Bass Guitars</a>
    <a href="<?= $base ?>/shop.php?category=accessories">Guitar Accessories</a>
  </div>

  <div>
    <h4>QUICK LINKS</h4>
    <a href="<?= $base ?>/about.php">About Us</a>
    <a href="<?= $base ?>/index.php#contact">Contact</a>
    <a href="<?= $base ?>/brands.php">Our Brands</a>
    <a href="<?= $base ?>/cart.php">Your Cart</a>
    <?php if (is_logged_in()): ?>
      <a href="<?= $base ?>/account.php">My Account</a>
    <?php else: ?>
      <a href="<?= $base ?>/login.php">Log In / Sign Up</a>
    <?php endif; ?>
  </div>

  <div class="footer-app">
    <h4>FOLLOW US</h4>
    <p>New arrivals, restocks and store news - follow us on social media.</p>
    <!-- TODO: replace the page URLs below with your real handles -->
    <div class="social-links">
      <a href="https://facebook.com" target="_blank" rel="noopener" aria-label="Facebook">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M22 12.06C22 6.5 17.52 2 12 2S2 6.5 2 12.06c0 5.02 3.66 9.18 8.44 9.94v-7.03H7.9v-2.9h2.54V9.85c0-2.5 1.49-3.89 3.77-3.89 1.09 0 2.23.2 2.23.2v2.46h-1.26c-1.24 0-1.63.77-1.63 2.52v2.02h2.78l-.44 2.9h-2.34V22c4.78-.76 8.44-4.92 8.44-9.94Z"/></svg>
      </a>
      <a href="https://instagram.com" target="_blank" rel="noopener" aria-label="Instagram">
        <svg viewBox="0 0 24 24" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="1.8"><rect x="2.8" y="2.8" width="18.4" height="18.4" rx="5"/><circle cx="12" cy="12" r="4.2"/><circle cx="17.3" cy="6.7" r="1.15" fill="currentColor" stroke="none"/></svg>
      </a>
      <a href="https://tiktok.com" target="_blank" rel="noopener" aria-label="TikTok">
        <svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M16.6 5.82A4.28 4.28 0 0 1 15.54 3h-3.09v12.4a2.59 2.59 0 1 1-2.59-2.59c.27 0 .53.04.78.12V9.77a5.76 5.76 0 0 0-.78-.05 5.66 5.66 0 1 0 5.66 5.66V9.01a7.35 7.35 0 0 0 4.3 1.38V7.3a4.28 4.28 0 0 1-3.31-1.48Z"/></svg>
      </a>
    </div>
  </div>

  <div class="copyright">
    © <span id="year"><?= date('Y') ?></span> Shan's Guitar. All rights reserved.
    <span>Privacy Policy</span>
    <span>Terms of Service</span>
  </div>
</footer>

<!-- QUICK VIEW MODAL -->
<div class="modal" id="quickView" hidden>
  <div class="modal-backdrop" data-close></div>
  <div class="modal-card" role="dialog" aria-modal="true" aria-labelledby="qvTitle">
    <button class="modal-close" data-close aria-label="Close">&times;</button>
    <div class="modal-media"><img id="qvImg" src="" alt=""></div>
    <div class="modal-body">
      <p class="product-type" id="qvCat"></p>
      <h3 id="qvTitle"></h3>
      <p class="price" id="qvPrice"></p>
      <p class="qv-desc" id="qvDesc"></p>
      <ul class="qv-meta" id="qvMeta"></ul>
      <div class="modal-actions">
        <a class="btn btn-gold" id="qvAdd" href="#">ADD TO CART</a>
        <button class="btn btn-outline-dark" data-close>CONTINUE BROWSING</button>
      </div>
    </div>
  </div>
</div>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<script src="<?= $base ?>/assets/script.js?v=<?= filemtime(__DIR__ . '/../assets/script.js') ?>"></script>
</body>
</html>
