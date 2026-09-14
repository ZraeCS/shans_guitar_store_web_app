<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

/* ---- build cart rows (server-side) ---- */
$rows  = [];
$total = 0;
foreach (cart() as $pid => $qty) {
    $p = product_by_id((int)$pid);
    if (!$p) continue;
    $line = $p['price'] * $qty;
    $total += $line;
    $rows[] = ['p' => $p, 'qty' => $qty, 'line' => $line];
}

/* ---- empty cart → bounce back ---- */
if (!$rows) {
    flash('info', 'Your cart is empty — add a guitar first.');
    redirect('shop.php');
}

require_login();
$user = current_user();

$errors = [];
$fullname = $user['name'] ?? '';
$phone = $address = $city = $notes = '';
$fulfillment = 'delivery';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $fullname    = trim($_POST['fullname'] ?? '');
    $phone       = trim($_POST['phone'] ?? '');
    $address     = trim($_POST['address'] ?? '');
    $city        = trim($_POST['city'] ?? '');
    $fulfillment = $_POST['fulfillment'] ?? 'delivery';
    $notes       = trim($_POST['notes'] ?? '');

    if ($fullname === '')  $errors[] = 'Please enter your full name.';
    if ($phone === '')     $errors[] = 'Please enter a contact number.';
    if ($address === '')   $errors[] = 'Please enter your delivery / pickup address.';
    if ($city === '')      $errors[] = 'Please enter your city / province.';
    if (!in_array($fulfillment, ['delivery', 'pickup'])) $fulfillment = 'delivery';

    if (!$errors) {
        $pdo = db();
        if (!$pdo) {
            $errors[] = 'Could not connect to the database — check your MySQL settings in includes/config.php and that MySQL is running in XAMPP.';
        } else {
            try {
                /* fresh stock re-check inside a transaction - two buyers can
                   never oversell the last unit */
                $pdo->beginTransaction();
                foreach ($rows as $r) {
                    $chk = $pdo->prepare('SELECT stock FROM guitars WHERE id = ?');
                    $chk->execute([(int)$r['p']['id']]);
                    $have = $chk->fetchColumn();
                    if ($have === false || (int)$have < (int)$r['qty']) {
                        throw new RuntimeException($r['p']['name'] . ' - only ' . (int)$have . ' left. Please update your cart.');
                    }
                }
                $items = array_map(fn($r) => [
                    'id'    => (int)$r['p']['id'],
                    'name'  => $r['p']['name'],
                    'price' => (int)$r['p']['price'],
                    'qty'   => (int)$r['qty'],
                ], $rows);
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, items, total, fulfillment, fullname, phone, address, city, notes, status, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
                );
                $stmt->execute([
                    $user['id'], json_encode($items), (float)$total, $fulfillment,
                    $fullname, $phone, $address, $city, $notes, 'pending'
                ]);
                foreach ($rows as $r) {
                    $dec = $pdo->prepare('UPDATE guitars SET stock = stock - ? WHERE id = ? AND stock >= ?');
                    $dec->execute([(int)$r['qty'], (int)$r['p']['id'], (int)$r['qty']]);
                    if ($dec->rowCount() !== 1) {
                        throw new RuntimeException('Stock changed while placing your order - nothing was saved. Please review your cart and try again.');
                    }
                }
                $pdo->commit();
                cart_clear();
                flash('success', 'Thank you! Your order has been placed - we\'ll contact you shortly to confirm.');
                redirect('account.php');
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = $e->getMessage();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                $errors[] = 'Something went wrong saving your order - please try again.';
            }
        }
    }
}

$pageTitle = 'Checkout';
$activeNav = 'shop';
require __DIR__ . '/includes/header.php';
?>

<section class="checkout-page section-pad">
  <header class="page-head">
    <p class="eyebrow">Almost there</p>
    <h2>Check<em>out</em></h2>
  </header>

  <?php if ($errors): ?>
    <ul class="form-errors">
      <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
    </ul>
  <?php endif; ?>

  <form method="post" action="checkout.php" class="checkout-form" novalidate>
    <?= csrf_field() ?>
    <div class="checkout-layout">
      <div class="checkout-main">
        <h3>1 · Your details</h3>
        <div class="form-grid">
          <label>Full name
            <input type="text" name="fullname" value="<?= e($fullname) ?>" required>
          </label>
          <label>Contact number
            <input type="tel" name="phone" placeholder="+63 9XX XXX XXXX" value="<?= e($phone) ?>" required>
          </label>
        </div>

        <h3>2 · Fulfillment</h3>
        <div class="fulfill-row">
          <label class="fulfill-option">
            <input type="radio" name="fulfillment" value="delivery" <?= $fulfillment === 'delivery' ? 'checked' : '' ?>>
            <span><strong>Delivery</strong><small>We ship to your address</small></span>
          </label>
          <label class="fulfill-option">
            <input type="radio" name="fulfillment" value="pickup" <?= $fulfillment === 'pickup' ? 'checked' : '' ?>>
            <span><strong>Store pickup</strong><small>Dumaguete City, Negros Oriental</small></span>
          </label>
        </div>

        <div class="form-grid">
          <label>Delivery / pickup address
            <input type="text" name="address" placeholder="House no., street, barangay" value="<?= e($address) ?>" required>
          </label>
          <label>City / province
            <input type="text" name="city" placeholder="e.g. Dumaguete City, Negros Oriental" value="<?= e($city) ?>" required>
          </label>
        </div>

        <label>Order notes (optional)
          <textarea name="notes" rows="3" placeholder="Anything we should know?"><?= e($notes) ?></textarea>
        </label>

        <h3>3 · Payment</h3>
        <div class="payment-box">
          <strong>Cash on delivery / payment on pickup</strong>
          <p>No payment is taken online. We'll confirm your order and payment details by phone.</p>
        </div>
      </div>

      <aside class="cart-summary checkout-summary">
        <h3>Your Order</h3>
        <?php foreach ($rows as $r): ?>
          <div class="sum-row">
            <span><?= e($r['p']['name']) ?> × <?= (int)$r['qty'] ?></span>
            <strong><?= peso($r['line']) ?></strong>
          </div>
        <?php endforeach; ?>
        <div class="sum-row sum-total"><span>Total</span><strong><?= peso($total) ?></strong></div>
        <button class="btn btn-gold full" type="submit">PLACE ORDER</button>
        <p class="sum-note">By placing an order you agree to be contacted to confirm it.</p>
      </aside>
    </div>
  </form>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
