<?php
/* ============================================================
   create_admin.php — Shan's Guitar Admin Account Creator
   ─────────────────────────────────────────────────────────
   HOW TO USE:
   1. Upload this file to your project root (same folder as admin.php)
   2. Visit it once in your browser:  http://yoursite.com/create_admin.php
   3. Log in at admin.php with the credentials shown
   4. 🔴 DELETE THIS FILE IMMEDIATELY AFTER USE
   ============================================================ */

require_once __DIR__ . '/includes/config.php';

/* ===== YOUR ADMIN CREDENTIALS — change these if you want ===== */
 $ADMIN_NAME     = 'Shan';
 $ADMIN_EMAIL    = 'admin@shansguitar.com';
 $ADMIN_PASSWORD = '12345678';
/* ============================================================= */

 $pdo = db();
if (!$pdo) {
    http_response_code(500);
    exit('❌ Database connection failed. Check includes/config.php.');
}

/* ---------- Step 1: Make sure the admins table exists ---------- */
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            email VARCHAR(150) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    $tableReady = true;
} catch (PDOException $ex) {
    $tableReady = false;
    $tableError = $ex->getMessage();
}

/* ---------- Step 2: Create or update the admin account ---------- */
 $hash = password_hash($ADMIN_PASSWORD, PASSWORD_DEFAULT);
 $message = '';
 $success = false;

if ($tableReady) {
    try {
        $stmt = $pdo->prepare('SELECT id FROM admins WHERE email = ?');
        $stmt->execute([$ADMIN_EMAIL]);

        if ($stmt->fetch()) {
            $stmt = $pdo->prepare('UPDATE admins SET name = ?, password = ? WHERE email = ?');
            $stmt->execute([$ADMIN_NAME, $hash, $ADMIN_EMAIL]);
            $message = '♻️ Admin account already existed — password was <strong>reset</strong>.';
        } else {
            $stmt = $pdo->prepare('INSERT INTO admins (name, email, password) VALUES (?,?,?)');
            $stmt->execute([$ADMIN_NAME, $ADMIN_EMAIL, $hash]);
            $message = '✅ Admin account <strong>created</strong> successfully.';
        }
        $success = true;
    } catch (PDOException $ex) {
        $message = '❌ Database error: ' . htmlspecialchars($ex->getMessage());
    }
} else {
    $message = '❌ Could not create table: ' . htmlspecialchars($tableError ?? 'unknown');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Create Admin — Shan's Guitar</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: system-ui, sans-serif; }
  body { background: #f6f2ec; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
  .card { background: #fff; border-radius: 16px; box-shadow: 0 10px 40px rgba(0,0,0,.08); padding: 40px; max-width: 460px; width: 100%; }
  h1 { font-size: 1.4rem; margin-bottom: 6px; }
  .sub { color: #888; font-size: .9rem; margin-bottom: 24px; }
  .msg { padding: 14px; border-radius: 10px; margin-bottom: 20px; font-size: .92rem; }
  .ok   { background: #e7f7ec; color: #1a7f3c; }
  .fail { background: #fdeaea; color: #b02a2a; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
  td { padding: 10px 12px; border: 1px solid #eee; font-size: .9rem; }
  td:first-child { background: #faf8f4; font-weight: 600; width: 35%; }
  code { background: #f1ede6; padding: 2px 7px; border-radius: 5px; font-size: .88rem; }
  .warn { background: #fff6e0; border-left: 4px solid #e0a800; padding: 14px; border-radius: 8px; font-size: .88rem; color: #7a5c00; }
  a.btn { display: block; text-align: center; background: #c9a24b; color: #fff; text-decoration: none; padding: 13px; border-radius: 10px; font-weight: 700; margin-top: 18px; letter-spacing: 1px; }
  a.btn:hover { background: #b58d38; }
</style>
</head>
<body>
  <div class="card">
    <h1>🎸 Shan's Guitar — Admin Setup</h1>
    <p class="sub">One-time admin account creation script</p>

    <div class="msg <?= $success ? 'ok' : 'fail' ?>"><?= $message ?></div>

    <?php if ($success): ?>
      <table>
        <tr><td>Login page</td><td><code>admin.php</code></td></tr>
        <tr><td>Email</td><td><code><?= htmlspecialchars($ADMIN_EMAIL) ?></code></td></tr>
        <tr><td>Password</td><td><code><?= htmlspecialchars($ADMIN_PASSWORD) ?></code></td></tr>
      </table>

      <div class="warn">
        ⚠️ <strong>Now delete this file!</strong><br>
        Remove <code>create_admin.php</code> from your server immediately —
        anyone who opens it could reset your admin password.
      </div>

      <a class="btn" href="admin.php">GO TO ADMIN LOGIN →</a>
    <?php endif; ?>
  </div>
</body>
</html>