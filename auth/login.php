<?php
require_once __DIR__ . '/../database/config.php';

/* ============================================================
   SHAN'S GUITAR — SHARED login page (customers + staff)
   One page, two tabs:
     CUSTOMER      -> signs in against the `users`  table
     ADMIN / STAFF -> signs in against the `admins` table
   The `admins` table is ALWAYS checked first, so an admin account
   always lands in the admin panel — even if it is typed on the
   customer tab. The admin panel (admin/admin.php) sends visitors who
   are not signed in here with ?tab=admin, so this is the one and
   only login form in the project.
   ============================================================ */

/* which tab is showing — ?tab=admin on the URL (tab links) or POSTed by the form */
$tab = (($_GET['tab'] ?? '') === 'admin') ? 'admin' : 'customer';

/* already signed in? go straight to the matching area — admins first */
if (admin_logged_in()) redirect('admin/admin.php');
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

/* Sanitize ?next= ONCE and reuse the clean value everywhere (tab links, form
   action and redirects). Inside-site paths only, so ?next=//evil.com or
   ?next=https://evil.com can neither be echoed back nor redirect off-site. */
$requestedNext = $safe_next((string)($_GET['next'] ?? ''), '');
$isAdminNext   = ($requestedNext !== '' && strpos($requestedNext, '/admin/admin.php') !== false);

$nextQuery    = ($requestedNext !== '') ? 'next=' . urlencode($requestedNext) : '';
$customerUrl  = 'login.php' . ($nextQuery !== '' ? '?' . $nextQuery : '');
$adminUrl     = 'login.php?tab=admin' . ($nextQuery !== '' ? '&' . $nextQuery : '');
$nextCustomer = ($requestedNext !== '' && !$isAdminNext) ? $requestedNext : 'customer/account.php';
$nextAdmin    = $isAdminNext ? $requestedNext : 'admin/admin.php';

$errors = [];
$email  = '';            /* refilled into the form after a failed attempt */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $tab   = (($_POST['tab'] ?? '') === 'admin') ? 'admin' : 'customer';
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
            $errors[] = 'Could not connect to the database. Check your MySQL settings in database/config.php and that MySQL is running in XAMPP.';
        } else {
            /* ---- 1) STAFF FIRST: the admins table always wins ---- */
            $stmt = $pdo->prepare('SELECT * FROM admins WHERE email = ?');
            $stmt->execute([$email]);
            $adminRow = $stmt->fetch();

            if ($adminRow && password_verify($pass, $adminRow['password'])) {
                /* one identity per session — drop any customer keys */
                unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
                session_regenerate_id(true);
                $_SESSION['admin_id']    = (int)$adminRow['id'];
                $_SESSION['admin_name']  = $adminRow['name'];
                $_SESSION['admin_email'] = $adminRow['email'];
                flash('success', 'Welcome back, ' . explode(' ', $adminRow['name'])[0] . '!');
                /* deep-link back into the panel when they came from a panel URL */
                redirect($nextAdmin);
            }

            /* ---- 2) CUSTOMERS: the users table ---- */
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                /* one identity per session — drop any staff keys */
                unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
                redirect($nextCustomer);
            }

            $errors[] = 'Incorrect email or password.';
        }
    }
}

/* the active tab stays active after a failed attempt */
$selfUrl   = $tab === 'admin' ? $adminUrl : $customerUrl;
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

    <?php if ($errors): ?>
      <ul class="form-errors">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="<?= e($selfUrl) ?>" class="auth-form">
      <?= csrf_field() ?>
      <input type="hidden" name="tab" value="<?= e($tab) ?>">
      <label>
        Email address
        <input type="email" name="email" value="<?= e($email) ?>"
               placeholder="<?= $tab === 'admin' ? 'admin@shansguitar.com' : 'you@example.com' ?>" required autofocus>
      </label>
      <label>
        Password
        <input type="password" name="password" placeholder="••••••••" required>
      </label>
      <button class="btn btn-gold full" type="submit"><?= $tab === 'admin' ? 'LOG IN AS ADMIN' : 'LOG IN' ?></button>
    </form>

    <?php if ($tab === 'admin'): ?>
      <p class="auth-note">Staff accounts only — this opens the admin panel. Are you a customer? Use the
        <a href="<?= e($customerUrl) ?>">Customer</a> tab above.</p>
    <?php else: ?>
      <p class="auth-note">Shan's Guitar staff can sign in with the
        <a href="<?= e($adminUrl) ?>">Admin / Staff</a> tab above.</p>
      <p class="auth-alt">No account yet? <a href="register.php">Create one — it's free</a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>