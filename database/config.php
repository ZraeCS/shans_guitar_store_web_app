<?php
/* 
   SHAN'S GUITAR — database/config.php
 */

/* ----------  DATABASE ----------
   Since the Symfony admin (Nov 2026) the store and the admin panel share ONE
   database: the MySQL container from the admin project's compose.yaml
   (start it with `docker compose up -d` in the admin project folder).
   XAMPP's own MySQL is no longer used by the store. */
define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3313');          // the Docker MySQL port ("3313:3306" in compose.yaml)
define('DB_NAME', 'shansguitar');
define('DB_USER', 'root');
define('DB_PASS', 'root');          // MYSQL_ROOT_PASSWORD in compose.yaml

/* ----------  LINKS ----------
   SITE_URL: the store's own address, used to build links inside emails.
   It is FIXED on purpose: building it from the browser's Host header would
   let an attacker make reset emails point to their own website.
   ADMIN_PANEL_URL: the Symfony admin's login page. */
define('SITE_URL', 'http://localhost/shans_guitar');
define('ADMIN_PANEL_URL', 'http://127.0.0.1:8000/login');

/* ----------  EMAIL (forgot password) ----------
   Mailpit (a Docker container) catches every email the store sends, so
   nothing reaches real inboxes while developing. Read them at
   http://localhost:8025 */
define('MAIL_HOST', '127.0.0.1');
define('MAIL_PORT', 1025);
define('MAIL_FROM', 'no-reply@shansguitar.com');
define('MAIL_FROM_NAME', "Shan's Guitar");

/* ----------  SITE SETTINGS ---------- */
define('SITE_NAME', "Shan's Guitar");
define('SITE_CURRENCY', '₱');

/* ----------  GCASH (manual verification) ----------
   EDIT THIS to your real GCash-registered name and number.
   This is shown PUBLICLY on checkout so customers know where to send
   payment - that's normal, it's the same as posting a GCash QR in a shop. */
define('GCASH_NAME',   'Shan\'s Guitar');
define('GCASH_NUMBER', '0936 486 4726');

/* ---------- PROJECT URL ROOT ----------
   Pages live in subfolders (admin/, auth/, customer/) but assets, links and
   redirects must always resolve from the project root. Computed from where
   this file sits on disk relative to the web server's document root. */
$sgRoot = str_replace('\\', '/', realpath(__DIR__ . '/..'));
$sgDoc  = str_replace('\\', '/', rtrim($_SERVER['DOCUMENT_ROOT'] ?? '', '/\\'));
define('BASE_URL', ($sgDoc !== '' && strpos($sgRoot, $sgDoc) === 0)
    ? rtrim(substr($sgRoot, strlen($sgDoc)), '/')
    : '');   /* fallback for CLI / unusual hosting: relative URLs keep working */

/* resolve a stored image/upload path against the project root (for <img src>) */
function img_src(string $path): string {
    if ($path === '' || preg_match('#^(https?://|/)#', $path)) return $path;
    return BASE_URL . '/' . ltrim($path, '/');
}

/* ----------  SESSION ---------- */
if (session_status() === PHP_SESSION_NONE) {
    session_name('sg_session');
    session_set_cookie_params([
        'httponly' => true,   // JavaScript can't steal the session cookie
        'samesite' => 'Lax',  // blocks cross-site requests from other websites
    ]);
    session_start();
}

/* ---------- FLASH MESSAGE ---------- */
function flash(string $type, string $msg): void {
    $_SESSION['flash'] = ['type' => $type, 'msg' => $msg];
}

function take_flash(): ?array {
    if (!empty($_SESSION['flash'])) {
        $f = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $f;
    }
    return null;
}

/* ---------- DATABASE  ---------- */
function db(): ?PDO {
    static $pdo = null;
    static $tried = false;
    if ($pdo) return $pdo;
    if ($tried) return null;               // already failed once — don't spam errors
    $tried = true;
    $dsn = 'mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        $pdo = null;
    }
    return $pdo;
}

/* ---------- Authentication ---------- */
function is_logged_in(): bool {
    return !empty($_SESSION['user_id']);
}

function current_user(): ?array {
    if (!is_logged_in()) return null;
    return [
        'id'    => (int)$_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
    ];
}

function require_login(): void {
    if (!is_logged_in()) {
        flash('info', 'Please log in to continue.');
        redirect('auth/login.php?next=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/* ---------- ADMIN / STAFF ----------
   Staff are rows in `users` with role = 'admin' and they sign in on the
   Symfony admin panel (ADMIN_PANEL_URL), not on the store. The store's
   "Admin / Staff" tab is just a doorway there. */

/* ---------- COMPLETE LOGOUT ----------
   Wipes the WHOLE session — customer keys, admin keys, session storage and
   the old cookie — then starts a brand-new empty session so "log out" can
   never leave half a login behind.
   The fresh session cookie is re-issued by hand because PHP sends the
   session cookie only once per request: without that extra header the
   browser would end up with no cookie at all and the goodbye flash below
   would be lost. Used by auth/logout.php, admin/logout.php and the admin
   panel's logout button. */
function logout_everything(): void {
    $_SESSION = [];

    $p = session_get_cookie_params();
    $cookie = [
        'expires'  => time() - 42000,                    /* delete the old cookie */
        'path'     => ($p['path'] ?? '') !== '' ? $p['path'] : '/',
        'domain'   => $p['domain'] ?? '',
        'secure'   => !empty($p['secure']),
        'httponly' => !empty($p['httponly']),
        'samesite' => ($p['samesite'] ?? '') !== '' ? $p['samesite'] : 'Lax',
    ];
    if (ini_get('session.use_cookies')) setcookie(session_name(), '', $cookie);

    session_destroy();
    session_start();
    session_regenerate_id(true);                         /* brand-new id, old one is dead */

    if (ini_get('session.use_cookies')) {
        $cookie['expires'] = 0;                          /* keep the fresh, empty session */
        setcookie(session_name(), session_id(), $cookie);
    }
    flash('info', 'You have been logged out.');
}

/* ---------- ORDER LINES ----------
   An order's items live in the `order_items` table (one row per guitar).
   Returns [order_id => [ ['id', 'name', 'price', 'qty'], ... ], ...]
   - the same shape the old `items` JSON column had, so pages barely change. */
function order_lines(PDO $pdo, array $orderIds): array {
    $orderIds = array_values(array_unique(array_map('intval', $orderIds)));
    if (!$orderIds) return [];
    $marks = implode(',', array_fill(0, count($orderIds), '?'));
    $st = $pdo->prepare(
        "SELECT oi.order_id, oi.guitar_id, oi.quantity, oi.unit_price, g.name
           FROM order_items oi JOIN guitars g ON g.id = oi.guitar_id
          WHERE oi.order_id IN ($marks)
          ORDER BY oi.id"
    );
    $st->execute($orderIds);
    $lines = array_fill_keys($orderIds, []);
    foreach ($st->fetchAll() as $r) {
        $lines[(int)$r['order_id']][] = [
            'id'    => (int)$r['guitar_id'],
            'name'  => (string)$r['name'],
            'price' => (float)$r['unit_price'],
            'qty'   => (int)$r['quantity'],
        ];
    }
    return $lines;
}

/* ---------- SEND AN EMAIL ----------
   A tiny SMTP client (plain text emails only), enough to talk to Mailpit.
   No extra library and no php.ini changes needed. Returns true when the
   mail server accepted the message. */
function send_mail(string $to, string $subject, string $text): bool {
    $fp = @fsockopen(MAIL_HOST, MAIL_PORT, $errno, $errstr, 5);
    if (!$fp) {
        error_log("send_mail: cannot reach " . MAIL_HOST . ':' . MAIL_PORT . " ($errstr)");
        return false;
    }
    stream_set_timeout($fp, 5);

    /* read one SMTP reply (it can span several "250-..." lines) */
    $reply = function () use ($fp): string {
        $data = '';
        while (($line = fgets($fp, 515)) !== false) {
            $data .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') break;
        }
        return $data;
    };
    $say = function (string $command, string $expect) use ($fp, $reply): bool {
        fwrite($fp, $command . "\r\n");
        return strncmp($reply(), $expect, 3) === 0;
    };

    $ok = strncmp($reply(), '220', 3) === 0
        && $say('EHLO localhost', '250')
        && $say('MAIL FROM:<' . MAIL_FROM . '>', '250')
        && $say('RCPT TO:<' . $to . '>', '250')
        && $say('DATA', '354');

    if ($ok) {
        $headers = implode("\r\n", [
            'Date: ' . date(DATE_RFC2822),
            'From: =?UTF-8?B?' . base64_encode(MAIL_FROM_NAME) . '?= <' . MAIL_FROM . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'Message-ID: <' . bin2hex(random_bytes(8)) . '@shansguitar.local>',
            'MIME-Version: 1.0',
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ]);
        /* SMTP rules: CRLF line endings, and a line starting with "." gets a second "." */
        $body = preg_replace('/^\./m', '..', str_replace(["\r\n", "\r", "\n"], "\r\n", $text));
        fwrite($fp, $headers . "\r\n\r\n" . $body . "\r\n.\r\n");
        $ok = strncmp($reply(), '250', 3) === 0;
    }

    fwrite($fp, "QUIT\r\n");
    fclose($fp);
    if (!$ok) error_log('send_mail: the mail server refused the message to ' . $to);
    return $ok;
}

/* ---------- CART (bas on session) ---------- */
function cart(): array {
    return $_SESSION['cart'] ?? [];
}

function cart_count(): int {
    return array_sum(cart());
}

function cart_add(int $id, int $qty = 1): void {
    $_SESSION['cart'][$id] = ($_SESSION['cart'][$id] ?? 0) + $qty;
    if ($_SESSION['cart'][$id] <= 0) unset($_SESSION['cart'][$id]);
}

function cart_set(int $id, int $qty): void {
    if ($qty <= 0) unset($_SESSION['cart'][$id]);
    else $_SESSION['cart'][$id] = $qty;
}

function cart_remove(int $id): void {
    unset($_SESSION['cart'][$id]);
}

function cart_clear(): void {
    unset($_SESSION['cart']);
}

/* ---------- CSRF PROTECTION ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) {
        http_response_code(403);
        exit('Invalid security token. Please go back and try again.');
    }
}

/* ---------- OUTPUT HELPERS ---------- */
function e($s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

function peso($n): string {
    return SITE_CURRENCY . number_format((float)$n, 0, '.', ',');
}

function redirect(string $url): void {
    /* pages live in subfolders — a bare relative target ("account.php") must
       be resolved against the project root, never the current folder */
    if ($url !== '' && !preg_match('#^(https?://|/)#', $url)) {
        $url = BASE_URL . '/' . $url;
    }
    header('Location: ' . $url);
    exit;
}
