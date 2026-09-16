<?php
require_once __DIR__ . '/database/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'More About Us';
$activeNav = 'about';
require __DIR__ . '/includes/header.php';
?>

<!-- ABOUT HERO  -->
<section class="about-hero">
  <div class="about-hero-inner">
    <p class="eyebrow">More about us</p>
    <h1>The Story Behind<br><em>Shan's Guitar</em></h1>
    <p class="about-hero-text">
      What started as one musician's search for the perfect acoustic has become
      a home for every player in the Philippines who cares about tone, honesty,
      and craft.
    </p>
  </div>
</section>

<!--  FOUNDING STORY  -->
<section class="about-story section-pad reveal">
  <div class="about-grid">
    <div class="about-copy">
      <p class="eyebrow">Est. 2026</p>
      <h2>It began with <em>one guitar</em></h2>
      <p>
        Our founder spent months hunting for an acoustic that felt right — and kept
        being disappointed by shops that stocked instruments they didn't understand.
        So instead of settling, we opened our own.
      </p>
      <p>
        Shan's Guitar started as a single workbench in Dumaguete City. The promise
        was simple: never sell an instrument we wouldn't proudly play ourselves.
        That promise still hangs above the door today.
      </p>
      <p>
        Every guitar on our floor is unboxed, inspected, set up, and played by our
        team before it ever reaches a customer. If it doesn't make us want to write
        a song, it doesn't go on the wall.
      </p>
    </div>
    <div class="about-image">
      <img src="images/ELECTRIC_G3.jpg" alt="Shan's Guitar showroom">
    </div>
  </div>
</section>

<!-- VALUES  -->
<section class="values section-pad reveal">
  <div class="section-heading">
    <div>
      <p class="eyebrow">What we stand for</p>
      <h2>Our <em>Values</em></h2>
    </div>
  </div>

  <div class="values-grid">
    <article class="value-card">
      <h3>We only sell guitars</h3>
      <p>No keyboards, no drum kits, no compromises. Focus lets us know our instruments — and our craft — deeper than anyone else.</p>
    </article>
    <article class="value-card">
      <h3>Every guitar is set up</h3>
      <p>String height, intonation, fret polish. Each instrument gets a professional setup before it's sold, not just a box off a shelf.</p>
    </article>
    <article class="value-card">
      <h3>Honest advice, always</h3>
      <p>We'd rather sell you the cheaper guitar that fits your hands than the expensive one that doesn't. Your next guitar should feel right.</p>
    </article>
    <article class="value-card">
      <h3>For players, by players</h3>
      <p>Our team gigs, records, and rehearses. When we recommend something, it's because we'd reach for it ourselves.</p>
    </article>
  </div>
</section>

<!--  TIMELINE  -->
<section class="timeline section-pad reveal">
  <div class="section-heading">
    <div>
      <p class="eyebrow">How we got here</p>
      <h2>Our <em>Journey</em></h2>
    </div>
  </div>

  <ol class="timeline-list">
    <li><span class="tl-year">2024</span><p>The hunt for a perfect acoustic — and the frustration that sparked the idea.</p></li>
    <li><span class="tl-year">2025</span><p>A single workbench in Dumaguete, setting up guitars for friends and local players.</p></li>
    <li><span class="tl-year">2026</span><p>Shan's Guitar opens its doors as an authorised dealer for eight of the world's finest brands.</p></li>
    <li><span class="tl-year">Today</span><p>Serving musicians across the Philippines — online and in store — one great guitar at a time.</p></li>
  </ol>
</section>

<!--  CTA  -->
<section class="about-cta reveal">
  <div class="about-cta-inner">
    <h2>Come find <em>your sound</em></h2>
    <p>Visit us in Dumaguete City, or browse the full catalogue online.</p>
    <div class="about-cta-actions">
      <a class="btn btn-gold" href="shop.php">SHOP GUITARS</a>
      <a class="btn btn-outline-dark" href="index.php#contact">CONTACT US</a>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
