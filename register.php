<?php
require_once __DIR__ . '/includes/config.php';

if (is_logged_in()) redirect('account.php');

$errors = [];
$name = $email = $phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $phone    = trim($_POST['phone'] ?? '');
    $pass     = $_POST['password'] ?? '';
    $pass2    = $_POST['password2'] ?? '';

    if (strlen($name) < 2)                    $errors[] = 'Please enter your full name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($pass) < 8)                    $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)                        $errors[] = 'Passwords do not match.';

    if (!$errors) {
        $pdo = db();
        if (!$pdo) {
            $errors[] = 'Could not connect to the database. Check your MySQL settings in includes/config.php, then open install.php once.';
        } else {
            $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'That email is already registered. Try logging in instead.';
            } else {
                $hash = password_hash($pass, PASSWORD_DEFAULT);
                $ins = $pdo->prepare('INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())');
                $ins->execute([$name, $email, $phone, $hash]);

                session_regenerate_id(true);
                $_SESSION['user_id']    = (int)$pdo->lastInsertId();
                $_SESSION['user_name']  = $name;
                $_SESSION['user_email'] = $email;
                flash('success', 'Account created — welcome to Shan\'s Guitar, ' . explode(' ', $name)[0] . '!');
                redirect('account.php');
            }
        }
    }
}

$pageTitle = 'Sign Up';
$activeNav = '';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <p class="eyebrow">Join the club</p>
    <h2>Create an <em>Account</em></h2>
    <p class="auth-sub">Save your cart, track orders and check out faster.</p>

    <?php if ($errors): ?>
      <ul class="form-errors">
        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
      </ul>
    <?php endif; ?>

    <form method="post" action="register.php" class="auth-form" novalidate>
      <?= csrf_field() ?>
      <label>
        Full name
        <input type="text" name="name" value="<?= e($name) ?>" placeholder="Juan Dela Cruz" required autofocus>
      </label>
      <label>
        Email address
        <input type="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required>
      </label>
      <label>
        Phone (optional)
        <input type="tel" name="phone" value="<?= e($phone) ?>" placeholder="+63 9XX XXX XXXX">
      </label>
      <label>
        Password
        <input type="password" name="password" placeholder="At least 8 characters" required>
      </label>
      <label>
        Confirm password
        <input type="password" name="password2" placeholder="Re-type your password" required>
      </label>
      <button class="btn btn-gold full" type="submit">CREATE ACCOUNT</button>
    </form>

    <p class="auth-alt">Already have an account? <a href="login.php">Log in</a>.</p>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
