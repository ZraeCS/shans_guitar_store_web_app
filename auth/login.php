<?php
require_once __DIR__ . '/../database/config.php';

/* ============================================================
   SHAN'S GUITAR — login page
   One page, two tabs:
     CUSTOMER      -> signs in against `users` (role = 'user')
     ADMIN / STAFF -> a doorway to the Symfony admin panel.
                      Staff are `users` rows with role = 'admin' and
                      they sign in there (ADMIN_PANEL_URL), not here.
   ============================================================ */

/* which tab is showing — ?tab=admin on the URL */
$tab = (($_GET['tab'] ?? '') === 'admin') ? 'admin' : 'customer';

/* already signed in? go straight to the account page */
if (is_logged_in() && $tab !== 'admin') redirect('customer/account.php');

/* Keep a safe in-site destination across tab switches and the form post.
   Only paths that start with a single "/" are accepted, so
   ?next=//evil.com or ?next=https://evil.com can never leave the site. */
$safe_next = static function (string $next, string $fallback): string {
    if ($next === '' || $next[0] !== '/' || strpos($next, '//') === 0
        || !preg_match('#^/[A-Za-z0-9_./%?&=:+~-]+$#', $next)) {
        return $fallback;
    }
    return $next;
};

$requestedNext = $safe_next((string)($_GET['next'] ?? ''), '');
$nextQuery     = ($requestedNext !== '') ? 'next=' . urlencode($requestedNext) : '';
$customerUrl   = 'login.php' . ($nextQuery !== '' ? '?' . $nextQuery : '');
$adminUrl      = 'login.php?tab=admin';
$nextCustomer  = ($requestedNext !== '') ? $requestedNext : 'customer/account.php';

$errors = [];
$email  = '';            /* refilled into the form after a failed attempt */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $tab === 'customer') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($pass === '') {
        $errors[] = 'Please enter your password.';
    }

    if (!$errors) {
        $pdo = db();
        if (!$pdo) {
            $errors[] = 'Could not connect to the database. Check that Docker is running (docker compose up -d in the admin project) and the settings in database/config.php.';
        } else {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                if (($user['role'] ?? 'user') === 'admin') {
                    /* staff account: right password, wrong door */
                    $errors[] = 'This is a staff account. Use the Admin / Staff tab to open the admin panel.';
                } else {
                    session_regenerate_id(true);
                    $_SESSION['user_id']    = (int)$user['id'];
                    $_SESSION['user_name']  = $user['name'];
                    $_SESSION['user_email'] = $user['email'];
                    flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
                    redirect($nextCustomer);
                }
            } else {
                $errors[] = 'Incorrect email or password.';
            }
        }
    }
}

$pageTitle = $tab === 'admin' ? 'Staff Log In' : 'Log In';
$activeNav = '';
require __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <p class="eyebrow"><?= $tab === 'admin' ? 'Staff access' : 'Welcome back' ?></p>
    <h2><?= $tab === 'admin' ? 'Admin <em>Log In</em>' : 'Log <em>In</em>' ?></h2>
    <p class="auth-sub"><?= $tab === 'admin'
        ? 'Staff accounts manage inventory and orders.'
        : 'Access your account, saved cart and order history.' ?></p>

    <!-- one login page, two tabs: customers and staff -->
    <nav class="auth-tabs" aria-label="Choose account type">
      <a class="tab<?= $tab === 'customer' ? ' active' : '' ?>" href="<?= e($customerUrl) ?>"
         <?= $tab === 'customer' ? 'aria-current="page"' : '' ?>>CUSTOMER</a>
      <a class="tab<?= $tab === 'admin' ? ' active' : '' ?>" href="<?= e($adminUrl) ?>"
         <?= $tab === 'admin' ? 'aria-current="page"' : '' ?>>ADMIN / STAFF</a>
    </nav>

    <?php if ($tab === 'admin'): ?>
      <!-- Staff sign in on the admin panel itself -->
      <p class="auth-sub" style="margin-top:6px">The admin panel has its own secure sign-in page. Use your staff email and password there.</p>
      <a class="btn btn-gold full" href="<?= e(ADMIN_PANEL_URL) ?>">OPEN THE ADMIN PANEL</a>
      <p class="auth-note">Staff accounts only. Are you a customer? Use the
        <a href="<?= e($customerUrl) ?>">Customer</a> tab above.</p>

    <?php else: ?>
      <?php if ($errors): ?>
        <ul class="form-errors">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="<?= e($customerUrl) ?>" class="auth-form">
        <?= csrf_field() ?>
        <label>
          Email address
          <input type="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required autofocus>
        </label>
        <label>
          Password
          <input type="password" name="password" placeholder="••••••••" required>
        </label>
        <p style="margin:-4px 0 4px;text-align:right;font-size:13px"><a href="forgot.php">Forgot password?</a></p>
        <button class="btn btn-gold full" type="submit">LOG IN</button>
      </form>

      <p class="auth-note">Shan's Guitar staff can sign in with the
        <a href="<?= e($adminUrl) ?>">Admin / Staff</a> tab above.</p>
      <p class="auth-alt">No account yet? <a href="register.php">Create one — it's free</a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
