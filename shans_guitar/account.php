<?php
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/products.php';

require_login();
$user = current_user();

$orders = [];
$pdo = db();
if ($pdo) {
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE user_id = ? ORDER BY created_at DESC LIMIT 50');
    $stmt->execute([$user['id']]);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'My Account';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<section class="account-page section-pad">
  <header class="page-head">
    <p class="eyebrow">Signed in as <?= e($user['email']) ?></p>
    <h2>Welcome, <em><?= e(explode(' ', $user['name'])[0]) ?></em></h2>
  </header>

  <div class="account-layout">
    <aside class="account-nav">
      <h4>MY ACCOUNT</h4>
      <a href="account.php" class="active">Order history</a>
      <a href="cart.php">Your cart</a>
      <a href="logout.php" class="danger">Log out</a>
    </aside>

    <div class="account-main">
      <h3>Order History</h3>

      <?php if (!$pdo): ?>
        <div class="empty-state">
          <h3>Database not connected</h3>
          <p>Check your MySQL settings in <code>includes/config.php</code> and run <code>install.php</code> once.</p>
        </div>
      <?php elseif (!$orders): ?>
        <div class="empty-state">
          <h3>No orders yet</h3>
          <p>When you check out, your orders will appear here.</p>
          <a class="btn btn-gold" href="shop.php" style="margin-top:22px">START SHOPPING</a>
        </div>
      <?php else: ?>
        <div class="orders-list">
          <?php foreach ($orders as $o): ?>
            <?php
              $items  = json_decode($o['items'], true) ?: [];
              $status = ucfirst($o['status']);
            ?>
            <article class="order-card">
              <div class="order-head">
                <div>
                  <strong>Order #<?= (int)$o['id'] ?></strong>
                  <span class="order-date"><?= date('M j, Y · g:i A', strtotime($o['created_at'])) ?></span>
                </div>
                <span class="order-status status-<?= e($o['status']) ?>"><?= e($status) ?></span>
              </div>
              <div class="order-body">
                <ul class="order-items">
                  <?php foreach ($items as $it): ?>
                    <li>
                      <span><?= e($it['name']) ?> × <?= (int)$it['qty'] ?></span>
                      <strong><?= peso($it['price'] * $it['qty']) ?></strong>
                    </li>
                  <?php endforeach; ?>
                </ul>
                <div class="order-total">Total: <strong><?= peso($o['total']) ?></strong></div>
                <p class="order-detail"><strong>Fulfillment:</strong> <?= e($o['fulfillment']) ?> ·
                   <strong>Address:</strong> <?= e($o['address']) ?>, <?= e($o['city']) ?></p>
              </div>
            </article>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
