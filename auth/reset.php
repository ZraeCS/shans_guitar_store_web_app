<?php
require_once __DIR__ . '/../database/config.php';

/* ============================================================
   SHAN'S GUITAR — Reset password (step 2 of 2)
   Opened from the link in the email: reset.php?token=<64 characters>
   The link is valid only if its hash is in password_reset_tokens,
   it hasn't been used, and it hasn't expired (30 minutes).
   ============================================================ */

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$pdo   = db();

/* find the matching, still-usable token (NOW() is checked by MySQL itself) */
$row = null;
if ($pdo && preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = $pdo->prepare(
        'SELECT t.id AS token_id, u.id AS user_id, u.name, u.role
           FROM password_reset_tokens t JOIN users u ON u.id = t.user_id
          WHERE t.token_hash = ? AND t.used_at IS NULL AND t.expires_at > NOW()'
    );
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch() ?: null;
}

$errors = [];

if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $pass  = $_POST['password'] ?? '';
    $pass2 = $_POST['password2'] ?? '';

    if (strlen($pass) < 8)  $errors[] = 'Password must be at least 8 characters.';
    if ($pass !== $pass2)   $errors[] = 'Passwords do not match.';

    if (!$errors) {
        try {
            $pdo->beginTransaction();
            $pdo->prepare('UPDATE users SET password = ? WHERE id = ?')
                ->execute([password_hash($pass, PASSWORD_DEFAULT), $row['user_id']]);
            /* this link (and any other open link for the account) can't be used again */
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
                ->execute([$row['user_id']]);
            $pdo->commit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            $errors[] = 'Something went wrong saving your new password. Please try again.';
        }

        if (!$errors) {
            if ($row['role'] === 'admin') {
                flash('success', 'Password changed. Staff: sign in on the admin panel with your new password.');
                redirect('auth/login.php?tab=admin');
            }
            flash('success', 'Password changed. You can log in with your new password now.');
            redirect('auth/login.php');
        }
    }
}

$pageTitle = 'Choose a New Password';
$activeNav = '';
require __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <p class="eyebrow">Account help</p>

    <?php if (!$row): ?>
      <h2>Link <em>Expired</em></h2>
      <p class="auth-sub">This reset link isn't valid anymore. Links work once, for 30 minutes, and asking for a new one cancels the old one.</p>
      <a class="btn btn-gold full" href="forgot.php">SEND A NEW LINK</a>
      <p class="auth-alt"><a href="login.php">Back to log in</a></p>

    <?php else: ?>
      <h2>Choose a new <em>Password</em></h2>
      <p class="auth-sub">For the account of <?= e(explode(' ', $row['name'])[0]) ?>. Use at least 8 characters.</p>

      <?php if ($errors): ?>
        <ul class="form-errors">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="reset.php" class="auth-form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <label>
          New password
          <input type="password" name="password" placeholder="At least 8 characters" required autofocus>
        </label>
        <label>
          Confirm new password
          <input type="password" name="password2" placeholder="Re-type your new password" required>
        </label>
        <button class="btn btn-gold full" type="submit">SAVE NEW PASSWORD</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
