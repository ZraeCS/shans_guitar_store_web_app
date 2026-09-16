<?php
require_once __DIR__ . '/../database/config.php';

require_login();
 $user = current_user();
 $pdo  = db();

/* ---- handle profile update (EDIT PROFILE form) ---- */
 $errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- cancel an own order (allowed while it is still 'pending') ---- */
    if (($_POST['action'] ?? '') === 'cancel_order') {
        csrf_check();
        $orderId = (int)($_POST['order_id'] ?? 0);
        if (!$pdo) {
            flash('error', 'Could not connect to the database — check your MySQL settings in includes/config.php and that MySQL is running in XAMPP.');
        } else {
            try {
                $pdo->beginTransaction();
                $st = $pdo->prepare('SELECT status, items FROM orders WHERE id = ? AND user_id = ? FOR UPDATE');
                $st->execute([$orderId, $user['id']]);
                $order = $st->fetch();

                /* the WHERE status='pending' guard makes double-cancels and
                   races with an admin accept impossible */
                $up = $pdo->prepare("UPDATE orders SET status = 'cancelled', status_updated_at = NOW() WHERE id = ? AND user_id = ? AND status = 'pending'");
                $up->execute([$orderId, $user['id']]);
                if (!$order || $up->rowCount() !== 1) {
                    throw new RuntimeException('This order can no longer be cancelled.');
                }
                /* return every item to stock */
                foreach (json_decode($order['items'], true) ?: [] as $it) {
                    $rs = $pdo->prepare('UPDATE guitars SET stock = stock + ? WHERE id = ?');
                    $rs->execute([(int)($it['qty'] ?? 0), (int)($it['id'] ?? 0)]);
                }
                $pdo->commit();
                flash('success', 'Order #' . $orderId . ' cancelled — its items are back in stock.');
            } catch (RuntimeException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', $e->getMessage());
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', 'Could not cancel the order. Please try again.');
            }
        }
        redirect('customer/account.php#orders');
    }

    csrf_check();

    $name  = trim($_POST['name']  ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '')                                   $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL))     $errors[] = 'Please enter a valid email address.';
    if ($phone !== '' && !preg_match('/^[0-9+()\-\s]{7,20}$/', $phone))
                                                        $errors[] = 'Please enter a valid phone number.';

    /* email must not belong to a different account */
    if (!$errors && $pdo) {
        $st = $pdo->prepare('SELECT id FROM users WHERE email = ? AND id <> ?');
        $st->execute([$email, $user['id']]);
        if ($st->fetch()) $errors[] = 'That email is already used by another account.';
    }

    if (!$errors && $pdo) {
        $st = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ?');
        $st->execute([$name, $email, $phone, $user['id']]);

        $_SESSION['user_name']  = $name;   /* keep the header in sync */
        $_SESSION['user_email'] = $email;

        flash('success', 'Profile updated.');
        redirect('customer/account.php');
    }
}

/* ---- fresh profile data from the users table ---- */
 $profile = ['name' => $user['name'], 'email' => $user['email'], 'phone' => '', 'created_at' => null];
if ($pdo) {
    $st = $pdo->prepare('SELECT name, email, phone, created_at FROM users WHERE id = ?');
    $st->execute([$user['id']]);
    if ($row = $st->fetch()) $profile = array_merge($profile, $row);
}

/* form prefill: submitted values when there are errors, otherwise DB values */
 $formVals = $errors ? array_merge($profile, $_POST) : $profile;

/* ---- avatar initials ---- */
 $initials = '';
foreach (preg_split('/\s+/', trim($profile['name'])) as $w) {
    if ($w !== '') $initials .= mb_strtoupper(mb_substr($w, 0, 1));
    if (mb_strlen($initials) >= 2) break;
}
if ($initials === '') $initials = 'SG';

/* ---- orders + stats ---- */
 $orders = [];
 $stats  = ['orders' => 0, 'spent' => 0.0, 'items' => 0];
if ($pdo) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();

    foreach ($orders as $o) {
        if (($o['status'] ?? '') === 'cancelled') continue;
        $stats['orders']++;
        $stats['spent'] += (float)$o['total'];
        foreach (json_decode($o['items'], true) ?: [] as $it) {
            $stats['items'] += (int)($it['qty'] ?? 0);
        }
    }
}

 $memberSince = $profile['created_at'] ? date('F j, Y', strtotime($profile['created_at'])) : null;

 $pageTitle = 'My Account';
 $activeNav = '';
require __DIR__ . '/../includes/header.php';
?>

<section class="account-page section-pad">

  <!-- ============ PROFILE HERO ============ -->
  <div class="profile-hero reveal">
    <div class="profile-id">
      <div class="avatar" aria-hidden="true"><?= e($initials) ?></div>
      <div>
        <p class="eyebrow">My account</p>
        <h2><?= e($profile['name']) ?></h2>
        <p class="profile-email"><?= e($profile['email']) ?></p>
        <div class="profile-badges">
          <?php if ($memberSince): ?>
            <span class="pill-badge gold">Member since <?= e(date('M Y', strtotime($profile['created_at']))) ?></span>
          <?php endif; ?>
          <span class="pill-badge">● Active</span>
        </div>
      </div>
    </div>

    <div class="profile-hero-actions">
      <a class="btn btn-gold" href="<?= e($base) ?>/shop.php">CONTINUE SHOPPING</a>
      <a class="btn btn-outline" href="cart.php">VIEW CART (<?= cart_count() ?>)</a>
    </div>
  </div>

  <!-- ============ STATS ============ -->
  <div class="profile-stats reveal">
    <div class="stat-card">
      <div class="stat-num"><?= (int)$stats['orders'] ?></div>
      <div class="stat-label">Orders placed</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= peso($stats['spent']) ?></div>
      <div class="stat-label">Total spent</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= (int)$stats['items'] ?></div>
      <div class="stat-label">Items purchased</div>
    </div>
    <div class="stat-card">
      <div class="stat-num"><?= cart_count() ?></div>
      <div class="stat-label">In cart now</div>
    </div>
  </div>

  <div class="account-layout">
    <aside class="account-nav">
      <h4>MY ACCOUNT</h4>
      <a href="#profile" class="active">Profile details</a>
      <a href="#orders">Order history</a>
      <a href="cart.php">Your cart</a>
      <a href="<?= e($base) ?>/auth/logout.php" class="danger">Log out</a>
    </aside>

    <div class="account-main">

      <!-- ============ PROFILE DETAILS ============ -->
      <section id="profile" class="profile-card reveal">
        <header class="profile-card-head">
          <h3>Account information</h3>
          <details class="edit-details" <?= $errors ? 'open' : '' ?>>
            <summary class="btn btn-outline-dark">
              <span class="when-closed">EDIT PROFILE</span>
              <span class="when-open">CLOSE</span>
            </summary>
          </details>
        </header>

        <?php if ($errors): ?>
          <ul class="form-errors" style="margin:20px 24px 0">
            <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <dl class="info-grid">
          <div class="info-item">
            <dt>Full name</dt>
            <dd><?= e($profile['name']) ?></dd>
          </div>
          <div class="info-item">
            <dt>Email</dt>
            <dd><?= e($profile['email']) ?></dd>
          </div>
          <div class="info-item">
            <dt>Phone</dt>
            <dd><?= $profile['phone'] !== '' ? e($profile['phone']) : '—' ?><small><?= $profile['phone'] !== '' ? '' : 'Not provided yet' ?></small></dd>
          </div>
          <div class="info-item">
            <dt>Member since</dt>
            <dd><?= $memberSince ? e($memberSince) : '—' ?></dd>
          </div>
        </dl>

        <!-- edit form (opens via the details toggle, no JS needed) -->
        <form class="checkout-form profile-edit-form" method="post" action="account.php">
          <?= csrf_field() ?>
          <div class="form-grid">
            <label>Full name
              <input type="text" name="name" maxlength="120" required value="<?= e($formVals['name']) ?>">
            </label>
            <label>Email
              <input type="email" name="email" maxlength="190" required value="<?= e($formVals['email']) ?>">
            </label>
            <label>Phone <span style="opacity:.55;font-weight:400">(optional)</span>
              <input type="tel" name="phone" maxlength="40" placeholder="0917 123 4567" value="<?= e($formVals['phone']) ?>">
            </label>
          </div>
          <button class="btn btn-gold" type="submit" style="margin-top:18px">SAVE CHANGES</button>
        </form>
      </section>

      <!-- ============ ORDER HISTORY ============ -->
      <section id="orders">
        <h3 style="margin-bottom:20px;font-size:1.5rem">Order history</h3>

        <?php if (!$pdo): ?>
          <div class="empty-state">
            <h3>Database not connected</h3>
            <p>Check your MySQL settings in <code>includes/config.php</code> and that MySQL is running in the XAMPP Control Panel.</p>
          </div>

        <?php elseif (!$orders): ?>
          <div class="empty-state">
            <h3>No orders yet</h3>
            <p>When you check out, your orders will appear here.</p>
            <a class="btn btn-gold" href="<?= e($base) ?>/shop.php" style="margin-top:22px">START SHOPPING</a>
          </div>

        <?php else: ?>
          <div class="orders-list">
            <?php foreach ($orders as $o): ?>
              <?php
                $items     = json_decode($o['items'], true) ?: [];
                $status    = ucfirst($o['status']);
                $isPending = ($o['status'] === 'pending');
                $cancelled = ($o['status'] === 'cancelled');
                $stepIndex = ['pending' => 0, 'confirmed' => 1, 'shipped' => 2, 'delivered' => 3][$o['status']] ?? null;
                $placedAt  = date('M j, Y · g:i A', strtotime($o['created_at']));
                $updatedAt = !empty($o['status_updated_at']) ? date('M j, Y · g:i A', strtotime($o['status_updated_at'])) : null;
              ?>
              <article class="order-card<?= $cancelled ? ' order-cancelled' : '' ?>">
                <div class="order-head">
                  <div>
                    <strong>Order #<?= (int)$o['id'] ?></strong>
                    <span class="order-date"><?= $placedAt ?></span>
                  </div>
                  <div class="order-actions">
                    <span class="order-status status-<?= e($o['status']) ?>"><?= e($status) ?></span>
                    <?php if ($isPending): ?>
                      <form method="post" action="account.php" class="cancel-order-form" onsubmit="return confirm('Cancel Order #<?= (int)$o['id'] ?>? Its items will be returned to stock.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="cancel_order">
                        <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                        <button type="submit" class="btn-cancel-order">CANCEL ORDER</button>
                      </form>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="order-body">
                  <?php if ($cancelled): ?>
                    <p class="order-cancelled-note">This order was cancelled<?= $updatedAt ? ' on ' . e($updatedAt) : '' ?>. Any items were returned to stock.</p>
                  <?php else: ?>
                    <ol class="order-timeline" aria-label="Order progress">
                      <?php foreach (['Placed', 'Confirmed', 'Shipped', 'Delivered'] as $i => $label): ?>
                        <li class="<?= ($stepIndex !== null && $i <= $stepIndex) ? 'done' : '' ?><?= ($stepIndex === $i) ? ' current' : '' ?>"><?= $label ?></li>
                      <?php endforeach; ?>
                    </ol>
                    <p class="order-updated">Placed <?= e($placedAt) ?><?= ($stepIndex > 0 && $updatedAt) ? ' · Last update ' . e($updatedAt) : '' ?></p>
                  <?php endif; ?>
                  <ul class="order-items">
                    <?php foreach ($items as $it): ?>
                      <li>
                        <span><?= e($it['name']) ?> × <?= (int)$it['qty'] ?></span>
                        <strong><?= peso($it['price'] * $it['qty']) ?></strong>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                  <div class="order-total">Total: <strong><?= peso($o['total']) ?></strong></div>
                  <p class="order-detail"><strong>Fulfillment:</strong> <?= e(ucfirst($o['fulfillment'])) ?> ·
                     <strong>Payment:</strong> <?php
                       if (($o['payment_method'] ?? '') === 'gcash') {
                           echo 'GCash · Ref #' . e($o['payment_ref'] ?: '—');
                           echo !empty($o['payment_verified_at'])
                               ? ' · <span style="color:#2a7a2a;">Verified ✔</span>'
                               : ' · <span style="color:#b8860b;">Awaiting verification</span>';
                       } else {
                           echo e(($o['payment_method'] ?? 'cod') === 'pickup_pay' ? 'Pay on Pickup' : 'Cash on Delivery');
                       }
                     ?> ·
                     <strong>Address:</strong> <?= e($o['address']) ?>, <?= e($o['city']) ?></p>
                </div>
              </article>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
