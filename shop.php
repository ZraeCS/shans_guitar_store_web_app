<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

$pageTitle = 'Shop';
$activeNav = 'shop';
require __DIR__ . '/includes/header.php';

/* initial filter state from URL (e.g. shop.php?category=electric&search=fender) */
$urlCategory = $_GET['category'] ?? 'all';
$urlSearch   = trim($_GET['search'] ?? '');

/* count per category for the sidebar */
$counts = ['all' => count($PRODUCTS)];
foreach ($PRODUCTS as $p) {
    $counts[$p['category']] = ($counts[$p['category']] ?? 0) + 1;
}
?>

<section class="shop-page">
  <header class="shop-title-section">
    <p class="eyebrow">Browse &amp; buy</p>
    <h2>Shop <em>All Guitars</em></h2>

    <div class="shop-toolbar">
      <span id="resultCount">Showing 0 items</span>

      <div class="toolbar-right">
        <input type="search" id="searchInput" class="search-input"
               placeholder="Search guitars…" aria-label="Search guitars"
               value="<?= e($urlSearch) ?>">
        <label class="sort-label">
          SORT:
          <select id="sortSelect">
            <option value="featured">Featured</option>
            <option value="price-asc">Price: Low to High</option>
            <option value="price-desc">Price: High to Low</option>
            <option value="newest">Newest first</option>
            <option value="name">Name: A–Z</option>
          </select>
        </label>
      </div>
    </div>
  </header>

  <div class="shop-layout">
    <aside class="filters" id="filters">
      <div class="filter-block">
        <h3>CATEGORIES</h3>
        <label><input type="radio" name="category" value="all" checked> Featured <span id="countAll"><?= $counts['all'] ?></span></label>
        <label><input type="radio" name="category" value="electric"    <?= $urlCategory === 'electric'    ? 'checked' : '' ?>> Electric <span data-count="electric"><?= $counts['electric']    ?? 0 ?></span></label>
        <label><input type="radio" name="category" value="acoustic"    <?= $urlCategory === 'acoustic'    ? 'checked' : '' ?>> Acoustic <span data-count="acoustic"><?= $counts['acoustic']    ?? 0 ?></span></label>
        <label><input type="radio" name="category" value="bass"        <?= $urlCategory === 'bass'        ? 'checked' : '' ?>> Bass <span data-count="bass"><?= $counts['bass']            ?? 0 ?></span></label>
        <label><input type="radio" name="category" value="classical"   <?= $urlCategory === 'classical'   ? 'checked' : '' ?>> Classical <span data-count="classical"><?= $counts['classical']   ?? 0 ?></span></label>
        <label><input type="radio" name="category" value="accessories" <?= $urlCategory === 'accessories' ? 'checked' : '' ?>> Accessories <span data-count="accessories"><?= $counts['accessories'] ?? 0 ?></span></label>
      </div>

      <div class="filter-block">
        <h3>MAX PRICE</h3>
        <div class="price-range">
          <span>₱0</span>
          <span id="priceOut">₱200,000</span>
        </div>
        <input class="range" type="range" id="priceRange" min="0" max="200000" step="500" value="200000">
      </div>

      <div class="filter-block">
        <h3>AVAILABILITY</h3>
        <label><input type="checkbox" class="avail" value="in-store"> In Store now</label>
        <label><input type="checkbox" class="avail" value="online"> Online Only</label>
        <label><input type="checkbox" class="avail" value="pre-order"> Pre-Order</label>
        <label><input type="checkbox" class="avail" value="coming-soon"> Coming Soon</label>
      </div>

      <button class="btn btn-outline-dark full" id="clearFilters">CLEAR FILTERS</button>
    </aside>

    <div class="shop-products">
      <div class="product-grid three-col" id="shopGrid"></div>

      <div class="empty-state" id="emptyState" hidden>
        <h3>No guitars found</h3>
        <p>Try widening your price range or clearing the filters.</p>
      </div>

      <div class="load-more-wrap">
        <button class="btn btn-outline-dark" id="loadMore">LOAD MORE</button>
      </div>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
