<?php
/* includes/header.php — shared <head> + header/nav for every page.
   Set $pageTitle and $activeNav BEFORE including this file. */
if (!defined('SITE_NAME')) { require_once __DIR__ . '/../database/config.php'; }
if (!isset($PRODUCTS))     { require_once __DIR__ . '/products.php'; }   /* NEW — so every page can feed script.js */

 $pageTitle   = $pageTitle   ?? SITE_NAME;
 $activeNav   = $activeNav   ?? '';
 $pageDesc    = $pageDesc    ?? 'Guitar specialist store — hand-picked acoustics, electrics and basses.';
 $user        = is_logged_in() ? current_user() : null;
 $cartTotal   = cart_count();
 $flashMsg    = take_flash();
 $base        = defined('BASE_URL') ? BASE_URL : rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
  <meta name="description" content="<?= e($pageDesc) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,400..700;1,9..144,400..700&family=Outfit:wght@300..700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= $base ?>/assets/style.css?v=<?= filemtime(__DIR__ . '/../assets/style.css') ?>">
</head>
<body>

<header class="site-header" id="siteHeader">
  <a href="<?= $base ?>/index.php" class="logo">shan's guitar</a>

  <nav class="main-nav" id="mainNav" aria-label="Main navigation">
    <a href="<?= $base ?>/index.php"  class="<?= $activeNav === 'home'   ? 'active' : '' ?>">HOME</a>
    <a href="<?= $base ?>/shop.php"   class="<?= $activeNav === 'shop'   ? 'active' : '' ?>">SHOP</a>
    <a href="<?= $base ?>/about.php"  class="<?= $activeNav === 'about'  ? 'active' : '' ?>">OUR STORY</a>
    <a href="<?= $base ?>/brands.php" class="<?= $activeNav === 'brands' ? 'active' : '' ?>">BRANDS</a>
    <a href="<?= $base ?>/index.php#contact" class="<?= $activeNav === 'contact' ? 'active' : '' ?>">CONTACT</a>
  </nav>

  <div class="header-tools">
    <?php if ($user): ?>
      <a class="account-btn" href="<?= $base ?>/account.php" aria-label="My account">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"
             stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          <circle cx="12" cy="8" r="3.6"></circle>
          <path d="M4.5 20c1.2-3.4 4-5 7.5-5s6.3 1.6 7.5 5"></path>
        </svg>
        <span class="account-name"><?= e(explode(' ', $user['name'])[0]) ?></span>
      </a>
      <a class="logout-link" href="<?= $base ?>/logout.php">Log out</a>
    <?php else: ?>
      <a class="login-link" href="<?= $base ?>/login.php">LOG IN</a>
      <a class="register-link" href="<?= $base ?>/register.php">SIGN UP</a>
    <?php endif; ?>

    <a class="cart-btn" id="cartBtn" href="<?= $base ?>/cart.php" aria-label="View cart">
      <svg class="cart-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor"
           stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="20" r="1.6"></circle>
        <circle cx="18" cy="20" r="1.6"></circle>
        <path d="M1.5 2h3.2l2.4 12.1a1.9 1.9 0 0 0 1.9 1.5h8.6a1.9 1.9 0 0 0 1.9-1.5L21.5 6H5.2"></path>
      </svg>
      <span class="cart-count<?= $cartTotal === 0 ? ' is-empty' : '' ?>" data-cart-count><?= $cartTotal ?></span>  <!-- NEW: data-cart-count -->
    </a>

    <button class="menu-btn" id="menuBtn" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
  </div>
</header>

<?php if ($flashMsg): ?>
  <div class="flash flash-<?= e($flashMsg['type']) ?>" role="status"><?= e($flashMsg['msg']) ?></div>
<?php endif; ?>

<!-- NEW: product data for script.js (shop grid, bestsellers, quick-view) -->
<script>
window.SG_BASE = "<?= e($base) ?>";
window.SG_PRODUCTS = <?= json_encode($PRODUCTS, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>