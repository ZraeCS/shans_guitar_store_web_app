<?php
require_once __DIR__ . '/../database/config.php';

/* ============================================================
   SHAN'S GUITAR — Forgot password (step 1 of 2)
   The customer types their email. If an account exists, we create a
   one-time reset link that works for 30 minutes and email it.

   Security:
   - The page ALWAYS shows the same message, so nobody can use it to
     find out which emails have an account.
   - Only a SHA-256 hash of the token is stored. Someone who can read
     the database still can't use the links.
   - Asking again cancels any older link for that account.
   ============================================================ */

if (is_logged_in()) redirect('customer/account.php');

$errors = [];
$email  = '';
$sent   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    } elseif (!($pdo = db())) {
        $errors[] = 'Could not connect to the database. Check that Docker is running (docker compose up -d in the admin project).';
    } else {
        $st = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ?');
        $st->execute([$email]);
        $user = $st->fetch();

        if ($user) {
            /* cancel older unused links for this account */
            $pdo->prepare('UPDATE password_reset_tokens SET used_at = NOW() WHERE user_id = ? AND used_at IS NULL')
                ->execute([$user['id']]);

            /* the secret goes in the email; only its hash goes in the database */
            $token = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_reset_tokens (user_id, token_hash, expires_at, created_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE), NOW())')
                ->execute([$user['id'], hash('sha256', $token)]);

            $link = SITE_URL . '/auth/reset.php?token=' . $token;
            $firstName = explode(' ', $user['name'])[0];

            $text = "Hi {$firstName},\n\n"
                  . "Someone asked to reset the password of your Shan's Guitar account.\n"
                  . "To choose a new password, open this link (it works once, for 30 minutes):\n\n"
                  . "{$link}\n\n"
                  . "If you didn't ask for this, you can ignore this email. Your password stays the same.\n\n"
                  . "— Shan's Guitar, Dumaguete City";

            if (!send_mail($user['email'], "Reset your Shan's Guitar password", $text)) {
                /* logged for the developer (see send_mail); the visitor still sees the same message */
                error_log('forgot.php: reset email could not be sent to ' . $user['email']);
            }
        }
        $sent = true;
    }
}

$pageTitle = 'Forgot Password';
$activeNav = '';
require __DIR__ . '/../includes/header.php';
?>

<section class="auth-page">
  <div class="auth-card">
    <p class="eyebrow">Account help</p>
    <h2>Forgot your <em>Password?</em></h2>

    <?php if ($sent): ?>
      <p class="auth-sub">If an account exists for <strong><?= e($email) ?></strong>, we've sent a link to reset its password. It works for 30 minutes.</p>
      <p class="auth-note">No email after a minute? Check your spam folder, or <a href="forgot.php">try again</a>.
        <?php if (in_array(MAIL_HOST, ['127.0.0.1', 'localhost'], true)): ?>
          <br><strong>Testing locally?</strong> Emails are caught by Mailpit: <a href="http://localhost:8025" target="_blank" rel="noopener">open the Mailpit inbox</a>.
        <?php endif; ?>
      </p>
      <p class="auth-alt"><a href="login.php">Back to log in</a></p>

    <?php else: ?>
      <p class="auth-sub">Type the email you signed up with and we'll send you a link to choose a new password.</p>

      <?php if ($errors): ?>
        <ul class="form-errors">
          <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
        </ul>
      <?php endif; ?>

      <form method="post" action="forgot.php" class="auth-form">
        <?= csrf_field() ?>
        <label>
          Email address
          <input type="email" name="email" value="<?= e($email) ?>" placeholder="you@example.com" required autofocus>
        </label>
        <button class="btn btn-gold full" type="submit">SEND RESET LINK</button>
      </form>

      <p class="auth-alt">Remembered it? <a href="login.php">Log in</a>.</p>
    <?php endif; ?>
  </div>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
