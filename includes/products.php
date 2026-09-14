<?php
/*
   SHAN'S GUITAR — includes/products.php

   STEP 1 CHANGE — DB-FIRST CATALOGUE
   Products are now loaded from the `guitars` MySQL table FIRST, so anything
   added / edited / deleted in admin.php appears on the website immediately.

   The hard-coded array below is kept for two reasons:
     1. FALLBACK — used automatically when MySQL is down, the `guitars`
        table doesn't exist yet, or the table is empty. The site can never
        white-screen or show an empty shop because of this file.
     2. SEED DATA — step 2 (seed.php) will insert these same 14 rows into
        the `guitars` table so the admin panel starts with real data.

   Each product keeps EXACTLY the same shape as before:
     id, name, brand, category, price, badge, availability,
     featured (shown at homepage), image, desc
   so header.php, footer.php, script.js, shop.php, cart.php, checkout.php
   and the helpers at the bottom all keep working with ZERO changes.

   Categories: electric , acoustic , bass , classical , accessories
   Availability: in-store , online , pre-order

   Brands stay hard-coded on purpose (marketing content, not inventory).
*/

/* ---------- SEED / FALLBACK CATALOGUE ----------
   Also read by seed.php (step 2) to fill the guitars table once. */
$SEED_PRODUCTS = [
    ['id' => 1,  'name' => 'Fender Stratocaster II',    'brand' => 'Fender',    'category' => 'electric',    'price' => 35500,  'badge' => 'Bestseller', 'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/Fender_Strat_2_CoralRed.webp',   'desc' => 'Alder body, maple neck and three single-coils — the classic bell-like Strat chime, professionally set up in store.'],
    ['id' => 2,  'name' => 'Yamaha FG800',              'brand' => 'Yamaha',    'category' => 'acoustic',    'price' => 15500,  'badge' => 'Staff Pick', 'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/Yamaha_FG800.jpg',      'desc' => 'Solid spruce top dreadnought with a big, warm voice. The best first serious acoustic you can buy.'],
    ['id' => 3,  'name' => 'Fender Stratocaster White', 'brand' => 'Fender',    'category' => 'electric',    'price' => 45000,  'badge' => 'Popular',    'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/Fender-player-ii-stratocaster-wh.jpg', 'desc' => 'Olympic White finish with a rosewood fingerboard — bright, articulate and endlessly versatile.'],
    ['id' => 4,  'name' => 'PRS Custom 24 10-Top',      'brand' => 'PRS',       'category' => 'electric',    'price' => 96500,  'badge' => 'Bestseller', 'availability' => 'online',    'featured' => true,  'image' => 'images/products/prs_custom_24_10_top.jpg', 'desc' => 'Flame maple 10-Top, 85/15 pickups and a 5-way blade — modern precision with vintage soul.'],
    ['id' => 5,  'name' => 'Gibson Les Paul Standard',  'brand' => 'Gibson',    'category' => 'electric',    'price' => 105500, 'badge' => 'Bestseller', 'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/2019-Gibson-LP-Std-a-1.webp',    'desc' => 'Mahogany body, carved maple cap and Burstbucker humbuckers. Thick, singing sustain.'],
    ['id' => 6,  'name' => 'Epiphone SG Standard',      'brand' => 'Epiphone', 'category' => 'electric',    'price' => 25000,  'badge' => 'Bestseller', 'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/epiphone_sg_standard.jpg', 'desc' => 'Lightweight double-cutaway with fierce mid-range bite — a rock machine at a fair price.'],
    ['id' => 7,  'name' => 'Martin D-28',               'brand' => 'Martin',    'category' => 'acoustic',    'price' => 178000, 'badge' => '',           'availability' => 'pre-order', 'featured' => true,  'image' => 'images/products/Martin_D28.jpg',         'desc' => 'The benchmark dreadnought since 1931. East Indian rosewood back and sides, Sitka spruce top.'],
    ['id' => 8,  'name' => 'Taylor GS Mini Mahogany',   'brand' => 'Taylor',    'category' => 'acoustic',    'price' => 39500,  'badge' => 'Popular',    'availability' => 'online',    'featured' => true,  'image' => 'images/products/Taylor_GS_Mini_Mahogany_Acoustic.webp',     'desc' => 'Compact scaled-down Grand Symphony with surprising volume. Travel-ready with a gig bag.'],
    ['id' => 9,  'name' => 'Fender Player Jazz Bass',   'brand' => 'Fender',    'category' => 'bass',        'price' => 48000,  'badge' => '',           'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/Fender_Player_Jazz_Bazz.jpg',   'desc' => 'Two Player Series single-coils and a slim neck — the go-to bass for every genre.'],
    ['id' => 10, 'name' => 'Yamaha TRBX304',            'brand' => 'Yamaha',    'category' => 'bass',        'price' => 22500,  'badge' => '',           'availability' => 'in-store',  'featured' => false, 'image' => 'images/products/Yamaha_TRBX304_FactoryBlue.jpg',     'desc' => 'Mahogany body, active 2-band EQ and a 5-way Performance EQ switch.'],
    ['id' => 11, 'name' => 'Cordoba C5 Classical',      'brand' => 'Cordoba',   'category' => 'classical',   'price' => 18500,  'badge' => '',           'availability' => 'online',    'featured' => false, 'image' => 'images/products/c5.jpg',          'desc' => 'Solid cedar top nylon-string with a wide, comfortable neck for fingerstyle players.'],
    ['id' => 12, 'name' => 'Gretsch G2622 Streamliner', 'brand' => 'Gretsch',   'category' => 'electric',    'price' => 42500,  'badge' => 'Staff Pick', 'availability' => 'in-store',  'featured' => false, 'image' => 'images/products/g2622.jpg',       'desc' => 'Centre-block semi-hollow with Broad\'Tron pickups — jangle, twang and feedback control.'],
    ['id' => 13, 'name' => 'Ernie Ball Slinky (3-Pack)','brand' => 'Ernie Ball','category' => 'accessories', 'price' => 1450,   'badge' => '',           'availability' => 'in-store',  'featured' => true,  'image' => 'images/products/ernie_ball_slinky_electric_9_42_3pack.jpg',     'desc' => 'Hybrid Slinky 9-46 nickel wound strings. Three sets, the industry standard.'],
    ['id' => 14, 'name' => 'Fender Deluxe Gig Bag',     'brand' => 'Fender',    'category' => 'accessories', 'price' => 3500,   'badge' => '',           'availability' => 'online',    'featured' => false, 'image' => 'images/products/gigbag.jpg',      'desc' => '25 mm padding, dual shoulder straps and an accessory pocket. Fits most electrics.'],
];

/* The shop starts from the seed; the DB switch below replaces it
   whenever the guitars table has data. */
$PRODUCTS = $SEED_PRODUCTS;

/* ---------- NEW: read the guitars table ---------- */
function load_products_from_db(): array {
    if (!function_exists('db')) return [];        // safety if ever included standalone
    $pdo = db();
    if (!$pdo) return [];                         // MySQL down → use fallback

    try {
        $rows = $pdo->query('SELECT * FROM guitars ORDER BY id')->fetchAll();
    } catch (PDOException $e) {
        return [];                                // table missing → use fallback
    }
    if (!$rows) return [];                        // table empty → use fallback

    $out = [];
    foreach ($rows as $g) {
        /* admin stores 'Acoustic' etc. — the shop filters and script.js
           compare lowercase, so normalize here in one place */
        $cat = strtolower(trim((string)($g['category'] ?? '')));
        if ($cat === 'accessory') $cat = 'accessories';   // admin singular → shop plural

        $stock = (int)($g['stock'] ?? 0);

        $out[] = [
            'id'           => (int)$g['id'],
            'name'         => (string)$g['name'],
            'brand'        => (string)$g['brand'],
            'category'     => $cat,
            'price'        => (float)$g['price'],
            'stock'        => $stock,
            /* the admin's "Mark as bestseller" checkbox now controls the
               homepage carousel and the card badge */
            'badge'        => !empty($g['is_bestseller']) ? 'Bestseller' : '',
            'featured'     => !empty($g['is_bestseller']),
            /* no availability column in the DB yet — derive it from stock so
               the shop's availability filter keeps working */
            'availability' => $stock > 0 ? 'in-store' : 'pre-order',
            /* empty image → path that 404s → your existing dashed
               "GUITAR PNG" fallback box takes over (script.js) */
            'image'        => trim((string)($g['image'] ?? '')) !== ''
                                  ? $g['image'] : 'images/products/placeholder.jpg',
            'desc'         => (string)($g['description'] ?? ''),
        ];
    }
    return $out;
}

/* NEW: the database wins whenever it has data; otherwise the seed array above
   is used, exactly like before this change. */
$dbProducts = load_products_from_db();
if ($dbProducts !== []) {
    $PRODUCTS = $dbProducts;
}

$BRANDS = [
    ['id' => 'fender',   'name' => 'Fender',   'est' => 1946, 'country' => 'USA',   'models' => 'Stratocaster · Telecaster · Jazzmaster', 'image' => 'images/brands/fenderlogo.webp',   'blurb' => 'The name that defined electric guitar. Fender\'s bolt-on neck designs and single-coil pickups remain the gold standard for surf, blues, and country.'],
    ['id' => 'gibson',   'name' => 'Gibson',   'est' => 1902, 'country' => 'USA',   'models' => 'Les Paul · SG · ES-335',                 'image' => 'images/brands/gibsonbrand1.webp',   'blurb' => 'Set-neck mahogany, carved maple tops and humbuckers. Gibson built the sound of rock and roll and still hand-finishes every instrument in Nashville.'],
    ['id' => 'martin',   'name' => 'Martin',   'est' => 1833, 'country' => 'USA',   'models' => 'D-28 · 000-15M · Road Series',           'image' => 'images/brands/martinbrand1.webp',   'blurb' => 'Nearly two centuries of acoustic craft. Martin invented the dreadnought and their X-bracing is still copied by every acoustic maker on earth.'],
    ['id' => 'taylor',   'name' => 'Taylor',   'est' => 1974, 'country' => 'USA',   'models' => '814ce · GS Mini · Academy Series',       'image' => 'images/brands/taylor_brandpic.webp',   'blurb' => 'Modern acoustic engineering at its finest — V-Class bracing, slim necks and famously consistent playability straight out of the case.'],
    ['id' => 'prs',      'name' => 'PRS',      'est' => 1985, 'country' => 'USA',   'models' => 'Custom 24 · SE Series · McCarty',        'image' => 'images/brands/prs_guitars.jpg',      'blurb' => 'Paul Reed Smith blends Fender clarity with Gibson warmth. Flawless figured maple tops and the most stable tuning in the business.'],
    ['id' => 'yamaha',   'name' => 'Yamaha',   'est' => 1887, 'country' => 'Japan', 'models' => 'FG Series · Pacifica · SILENT Guitar',   'image' => 'images/brands/yamaha-guitars1.jpg',   'blurb' => 'Unbeatable value and legendary quality control. From the FG800 to the Pacifica, Yamaha has started more players than any other brand.'],
    ['id' => 'gretsch',  'name' => 'Gretsch',  'est' => 1883, 'country' => 'USA',   'models' => 'White Falcon · Duo Jet · Streamliner',   'image' => 'images/brands/gretsch-guitars.jpg',  'blurb' => 'That great Gretsch sound — hollow bodies, Filter\'Tron pickups and Bigsby vibratos. Rockabilly twang with unmistakable style.'],
    ['id' => 'epiphone', 'name' => 'Epiphone', 'est' => 1946, 'country' => 'USA',   'models' => 'Les Paul · SG · Casino',                 'image' => 'images/brands/epiphone_guitars.jpg', 'blurb' => 'Gibson\'s sister company since 1957. Real Gibson designs, ProBucker pickups and prices that put an icon within reach of every player.'],
];

/* HELPERS -----------------------------------*/

function product_by_id(int $id): ?array {
    global $PRODUCTS;
    foreach ($PRODUCTS as $p) {
        if ($p['id'] === $id) return $p;
    }
    return null;
}

function brand_by_id(string $id): ?array {
    global $BRANDS;
    foreach ($BRANDS as $b) {
        if ($b['id'] === $id) return $b;
    }
    return $BRANDS[0] ?? null;
}

function avail_label(string $k): string {
    $map = [
        'in-store'  => 'In store now',
        'online'    => 'Online only',
        'pre-order' => 'Pre-order',
    ];
    return $map[$k] ?? '—';
}

/* Renders one product card (used on Home and Shop) */
function product_card(array $p): string {
    $badge = $p['badge'] ? '<span class="badge">' . e($p['badge']) . '</span>' : '';
    $name  = e($p['name']);
    $cat   = e($p['category']);
    $img   = e($p['image']);
    $price = peso($p['price']);
    $id    = (int)$p['id'];

    return <<<HTML
<article class="product-card" data-id="$id">
  <div class="product-media">
    $badge
    <img src="$img" alt="$name" loading="lazy">
  </div>
  <div class="product-info">
    <p class="product-type">$cat</p>
    <h3>$name</h3>
    <p class="price">$price</p>
    <div class="card-actions">
      <button class="btn-view" type="button" data-view="$id">VIEW</button>
      <a class="btn-cart" href="cart.php?action=add&amp;id=$id">ADD TO CART</a>
    </div>
  </div>
</article>
HTML;
}
