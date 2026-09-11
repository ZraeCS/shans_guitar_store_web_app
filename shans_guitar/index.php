<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'Home';
$activeNav = 'home';
require __DIR__ . '/includes/header.php';

$featured = array_values(array_filter($PRODUCTS, fn($p) => $p['featured']));
?>

<!--  HERO / HOME  -->
<section class="hero">
  <div class="hero-copy">
    <p class="eyebrow">Shan's premiere guitar store</p>
    <h1>Find Your<br><em>Perfect Sound.</em></h1>
    <p class="hero-text">
      From hand-crafted acoustics to vintage electrics — every guitar at
      Shan's is hand-picked, professionally set up, and ready to play.
    </p>
    <div class="hero-actions">
      <a class="btn btn-gold" href="shop.php">SHOP GUITARS</a>
      <a class="btn btn-outline" href="about.php">OUR STORY</a>
    </div>
  </div>

  <div class="hero-image">
    <img src="images/guitar_background_image1.webp" alt="Wall of guitars at Shan's Guitar store">
  </div>
</section>

<!--  BESTSELLERS  -->
<section class="bestsellers section-pad reveal">
  <div class="section-heading">
    <div>
      <p class="eyebrow">Handpicked for you</p>
      <h2>Check Our <em>Bestsellers!</em></h2>
    </div>
    <p class="section-intro">
      Our most-loved guitars, chosen by musicians just like you.
      Every model is in stock and ready to play today.
    </p>
  </div>

  <div class="tabs-row">
    <div class="category-tabs" id="bestsellerTabs" role="tablist">
      <button class="tab active" data-filter="all"         role="tab" aria-selected="true">ALL</button>
      <button class="tab"        data-filter="electric"    role="tab" aria-selected="false">ELECTRIC</button>
      <button class="tab"        data-filter="acoustic"    role="tab" aria-selected="false">ACOUSTIC</button>
      <button class="tab"        data-filter="bass"        role="tab" aria-selected="false">BASS</button>
      <button class="tab"        data-filter="accessories" role="tab" aria-selected="false">ACCESSORIES</button>
    </div>

    <div class="carousel-nav">
      <button class="carousel-btn" id="bsPrev" data-dir="-1" aria-label="Previous guitars">&#8249;</button>
      <button class="carousel-btn" id="bsNext" data-dir="1"  aria-label="Next guitars">&#8250;</button>
    </div>
  </div>

  <div class="carousel">
    <div class="carousel-track" id="bestsellerTrack">
      <?php foreach ($featured as $p) echo product_card($p); ?>
    </div>
  </div>
</section>

<!--  OUR STORY (teaser)  -->
<section class="story section-pad reveal">
  <div class="story-image">
    <img src="images/ELECTRIC_G3.jpg" alt="Inside the Shan's Guitar showroom">
  </div>

  <div class="story-copy">
    <p class="eyebrow">Our story</p>
    <h2>About <em>Shan's Guitar</em></h2>
    <p>
      Shan's Guitar started as one person's obsession with finding the
      perfect acoustic. What began as a tiny workshop has grown into one
      of the Philippines' most-loved guitar specialist stores — built on
      honesty, tone, and craft.
    </p>
    <p>
      We don't sell keyboards or drum kits. We know guitars deeply.
      Every instrument on our floor has been played and approved by our
      team before it reaches yours.
    </p>
    <a class="btn btn-gold" href="about.php">MORE ABOUT US</a>

    <div class="location-note">
      <strong>Est. 2026</strong>
      <span>Dumaguete, Philippines</span>
    </div>
  </div>
</section>

<!--  OUR BRANDS (teaser)  -->
<section class="brands reveal">
  <div class="brands-inner">
    <p class="eyebrow">Authorized dealer</p>
    <h2>Our <em>Brands</em></h2>
    <p class="brands-intro">
      We carry only the guitars we believe in — eight of the world's finest
      makers, every one an authorised partnership.
    </p>

    <div class="brand-grid">
      <?php foreach ($BRANDS as $b): ?>
        <a class="brand-tile" href="brands.php#<?= e($b['id']) ?>" aria-label="View <?= e($b['name']) ?>">
          <img src="<?= e($b['image']) ?>" alt="<?= e($b['name']) ?> guitars" loading="lazy">
          <div class="brand-tile-text">
            <strong><?= e($b['name']) ?></strong>
            <span>Est. <?= (int)$b['est'] ?></span>
          </div>
        </a>
      <?php endforeach; ?>
    </div>

    <div class="brands-cta">
      <a class="btn btn-gold" href="brands.php">EXPLORE ALL BRANDS</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
