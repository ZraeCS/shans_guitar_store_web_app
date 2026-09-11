<?php
/* includes/footer.php — shared footer + JS for every page. */
if (!defined('SITE_NAME')) { require_once __DIR__ . '/config.php'; }
$base = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
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
    <h4>GET THE APP</h4>
    <p>Browse our full catalogue, book setups, and manage your membership from your phone.</p>
    <div class="app-buttons">
      <button type="button">Google Play</button>
      <button type="button">App Store</button>
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

<script>
  /* products + brands injected by PHP so quick-view works on every page */
  window.SG_PRODUCTS = <?= json_encode($GLOBALS['PRODUCTS'] ?? []) ?>;
</script>
<script src="<?= $base ?>/assets/script.js"></script>
</body>
</html>
