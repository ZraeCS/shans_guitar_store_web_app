<?php
/* 
   SHAN'S GUITAR — includes/config.php
 */

/* ----------  YOUR MYSQL DETAILS ---------- */
define('DB_HOST', 'localhost');
define('DB_NAME', 'shansguitar');   // create this DB
define('DB_USER', 'root');          // XAMPP default is "root"
define('DB_PASS', '');              // XAMPP default password is empty

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
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
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
