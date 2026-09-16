
<?php
require_once __DIR__ . '/../database/config.php';

if (is_logged_in()) redirect('customer/account.php');

$errors = [];
$email  = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
        if ($pdo) {
            $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ?');
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($pass, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                flash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');

                /* PATCH 4 — only allow redirects to pages inside this site.
                   Blocks login.php?next=https://evil.com (open redirect / phishing). */
                $next = $_GET['next'] ?? 'account.php';
                if (strpos($next, '/') !== 0 || strpos($next, '//') === 0 || strpos($next, '/\\') === 0) {
                    $next = 'account.php';
                }
                redirect($next);
            }
        }
        $errors[] = 'Incorrect email or password.';
    }
}

$pageTitle = 'Log In';
$activeNav = '';
require __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <p class="eyebrow">Welcome back</p>
    <h2>Log <em>In</em></h2>
    <p class="auth-sub">Access your account, saved cart and order history.</p>

    <?php if ($errors): ?>
      <ul class="form-errors">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="login.php<?= isset($_GET['next']) ? '?next=' . urlencode($_GET['next']) : '' ?>" class="auth-form">
      <?= csrf_field() ?>
      <label>
        Email address
        <input type="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required autofocus>
      </label>
      <label>
        Password
        <input type="password" name="password" placeholder="••••••••" required>
      </label>
      <button class="btn btn-gold full" type="submit">LOG IN</button>
    </form>

    <p class="auth-alt">No account yet? <a href="register.php">Create one — it's free</a>.</p>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>


