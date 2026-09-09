<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

/* ---- handle add / remove / update actions ---- */
$action = $_GET['action'] ?? '';
$id     = (int)($_GET['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action !== '' && $id) {
    if ($action === 'add') {
        $p = product_by_id($id);
        if ($p) {
            cart_add($id);
            flash('success', $p['name'] . ' added to your cart.');
        }
    } elseif ($action === 'remove') {
        cart_remove($id);
        flash('info', 'Item removed from your cart.');
    }
    redirect('cart.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach (($_POST['qty'] ?? []) as $pid => $qty) {
        cart_set((int)$pid, max(0, (int)$qty));
    }
    flash('success', 'Cart updated.');
    redirect('cart.php');
}

/* ---- build cart rows ---- */
$rows  = [];
$total = 0;
foreach (cart() as $pid => $qty) {
    $p = product_by_id((int)$pid);
    if (!$p) { cart_remove((int)$pid); continue; }
    $line = $p['price'] * $qty;
    $total += $line;
    $rows[] = ['p' => $p, 'qty' => $qty, 'line' => $line];
}

$pageTitle = 'Your Cart';
$activeNav = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="cart-page section-pad">
  <header class="page-head">
    <p class="eyebrow">Ready to check out?</p>
    <h2>Your <em>Cart</em></h2>
  </header>

  <?php if (!$rows): ?>
    <div class="empty-state">
      <h3>Your cart is empty</h3>
      <p>Looks like you haven't added any guitars yet.</p>
      <a class="btn btn-gold" href="shop.php" style="margin-top:22px">BROWSE THE SHOP</a>
    </div>
  <?php else: ?>

  <form method="post" action="cart.php">
    <?= csrf_field() ?>
    <div class="cart-layout">
      <div class="cart-items">
        <?php foreach ($rows as $r): $p = $r['p']; ?>
          <article class="cart-item">
            <div class="cart-media">
              <img src="<?= e($p['image']) ?>" alt="<?= e($p['name']) ?>">
            </div>
            <div class="cart-info">
              <p class="product-type"><?= e($p['category']) ?> · <?= e($p['brand']) ?></p>
              <h3><?= e($p['name']) ?></h3>
              <p class="price"><?= peso($p['price']) ?></p>
            </div>
            <div class="cart-qty">
              <label for="qty-<?= (int)$p['id'] ?>">Qty</label>
              <input type="number" name="qty[<?= (int)$p['id'] ?>]" id="qty-<?= (int)$p['id'] ?>"
                     value="<?= (int)$r['qty'] ?>" min="0" max="99">
            </div>
            <div class="cart-line"><?= peso($r['line']) ?></div>
            <a class="cart-remove" href="cart.php?action=remove&amp;id=<?= (int)$p['id'] ?>" aria-label="Remove <?= e($p['name']) ?>">&times;</a>
          </article>
        <?php endforeach; ?>

        <div class="cart-row-actions">
          <a class="btn btn-outline-dark" href="shop.php">CONTINUE SHOPPING</a>
          <button class="btn btn-outline-dark" type="submit">UPDATE QUANTITIES</button>
        </div>
      </div>

      <aside class="cart-summary">
        <h3>Order Summary</h3>
        <div class="sum-row"><span>Items</span><strong><?= cart_count() ?></strong></div>
        <div class="sum-row"><span>Shipping</span><strong>Calculated at checkout</strong></div>
        <div class="sum-row sum-total"><span>Total</span><strong><?= peso($total) ?></strong></div>
        <a class="btn btn-gold full" href="checkout.php">PROCEED TO CHECKOUT</a>
        <p class="sum-note">Taxes and delivery options are confirmed at checkout.</p>
      </aside>
    </div>
  </form>

  <?php endif; ?>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
