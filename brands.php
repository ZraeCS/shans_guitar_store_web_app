<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'Our Brands';
$activeNav = 'brands';
require __DIR__ . '/includes/header.php';

$activeBrand = brand_by_id($_GET['brand'] ?? $BRANDS[0]['id'] ?? '');
?>

<!--  BRANDS HERO  -->
<section class="brands-hero">
  <div class="brands-hero-inner">
    <p class="eyebrow">Authorized dealer</p>
    <h1>Our <em>Brands</em></h1>
    <p>
      We carry only the guitars we believe in — eight of the world's finest
      makers, every one an authorised partnership.
    </p>
  </div>
</section>

<section class="brands-page section-pad reveal">
  <div class="brand-tabs" id="brandTabs" role="tablist">
    <?php foreach ($BRANDS as $b): ?>
      <a class="brand-pill<?= $b['id'] === $activeBrand['id'] ? ' active' : '' ?>"
         href="brands.php?brand=<?= e($b['id']) ?>#<?= e($b['id']) ?>">
        <?= e(strtoupper($b['name'])) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php foreach ($BRANDS as $b): ?>
    <article class="brand-feature" id="<?= e($b['id']) ?>">
      <div class="brand-feature-media">
        <img src="<?= e($b['image']) ?>" alt="<?= e($b['name']) ?> guitars" loading="lazy">
      </div>
      <div class="brand-feature-body">
        <h3><?= e($b['name']) ?></h3>
        <p class="brand-est">Est. <?= (int)$b['est'] ?> · <?= e($b['country']) ?></p>
        <p class="brand-models"><?= e($b['models']) ?></p>
        <p class="brand-blurb"><?= e($b['blurb']) ?></p>
        <a class="btn btn-gold" href="shop.php?search=<?= urlencode($b['name']) ?>">
          SHOP <?= e(strtoupper($b['name'])) ?>
        </a>
      </div>
    </article>
  <?php endforeach; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
