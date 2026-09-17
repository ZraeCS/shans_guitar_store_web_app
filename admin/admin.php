<?php
require_once __DIR__ . '/../database/config.php';

/* ============================================================
   SHAN'S GUITAR — Admin Panel
   Layout/CSS are its own, but the LOGIN IS SHARED with customers:
   everyone signs in on auth/login.php ("Admin / Staff" tab), and this
   page simply sends visitors who are not signed in back there.
   Admin session helpers (admin_logged_in, current_admin,
   logout_everything) live in database/config.php so the shared login
   page and this panel always agree on who is an admin.
   Reuses config.php's db(), e(), peso(), csrf_field(), csrf_check(),
   flash(), take_flash(), redirect() helpers.
   Adapted to match the actual database schema:
   orders (user_id, items, total, fulfillment, fullname,
   phone, address, city, notes, status) + users + guitars + admins.
   ============================================================ */

define('LOW_STOCK_THRESHOLD', 3);
define('UPLOAD_DIR',  __DIR__ . '/../images/products/');
define('UPLOAD_PATH', 'images/products/');

function admin_initial(string $name): string {
    $name = trim($name);
    if ($name === '') return 'A';
    return function_exists('mb_strtoupper')
        ? mb_strtoupper(mb_substr($name, 0, 1))
        : strtoupper(substr($name, 0, 1));
}

/* ---------- SMALL HELPERS ---------- */
function db_or_die(): PDO {
    $pdo = db();
    if (!$pdo) {
        http_response_code(500);
        exit('Database connection failed. Check your MySQL settings in includes/config.php and that MySQL is running in XAMPP.');
    }
    return $pdo;
}

function stock_class(int $stock): string {
    if ($stock <= 0) return 'stock-out';
    if ($stock <= LOW_STOCK_THRESHOLD) return 'stock-low';
    return 'stock-ok';
}

function stock_label(int $stock): string {
    if ($stock <= 0) return 'Out of stock';
    if ($stock <= LOW_STOCK_THRESHOLD) return $stock . ' left · Low';
    return $stock . ' in stock';
}

/* Upload a product image. Returns [path, error]: exactly one is non-null.
   Validates the PHP upload status, size, extension AND the real file content
   (finfo MIME + getimagesize) so a renamed non-image cannot sneak in. */
function handle_image_upload(?array $file): array {
    if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return [null, null];   /* nothing attempted - not an error */
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return [null, 'Upload failed (error code ' . $file['error'] . ') - try a smaller file.'];
    }
    if ($file['size'] > 4 * 1024 * 1024) {
        return [null, 'Image is larger than 4 MB.'];
    }
    $allowed = ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'webp' => 'image/webp'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        return [null, 'Only JPG, PNG or WEBP images are allowed.'];
    }
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if ($mime === false || !in_array($mime, $allowed, true)) {
        return [null, 'That file is not a real image (detected type: ' . ($mime ?: 'unknown') . ').'];
    }
    if (@getimagesize($file['tmp_name']) === false) {
        return [null, 'That file is not a readable image.'];
    }
    if (!is_dir(UPLOAD_DIR)) @mkdir(UPLOAD_DIR, 0755, true);
    $filename = 'gtr_' . bin2hex(random_bytes(6)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!move_uploaded_file($file['tmp_name'], UPLOAD_DIR . $filename)) {
        return [null, 'Could not save the image - check folder permissions for ' . UPLOAD_PATH . '.'];
    }
    return [UPLOAD_PATH . $filename, null];
}

/* ---------- ORDER ITEMS PARSER ----------
   Your checkout saves the cart as text in orders.items.
   Understands the common formats (JSON product list,
   JSON id => qty map, PHP serialize) with a raw fallback. */
function order_items_from_text(PDO $pdo, ?string $itemsText): array {
    $text = trim((string)$itemsText);
    if ($text === '') return [];

    $data = json_decode($text, true);
    if (!is_array($data) && $text[0] === 'a') {
        $data = @unserialize($text, ['allowed_classes' => false]);
    }
    if (!is_array($data) || $data === []) {
        return [['name' => $text, 'gid' => null, 'qty' => 1, 'price' => null]];
    }

    $rows = [];
    $needIds = [];

    foreach ($data as $key => $val) {
        if (is_string($val)) { // simple list of names
            $rows[] = ['name' => $val, 'gid' => null, 'qty' => 1, 'price' => null];
            continue;
        }
        $it  = is_array($val) || is_object($val) ? (array)$val : [];
        $qty = (int)($it['qty'] ?? $it['quantity'] ?? (is_scalar($val) ? $val : 1));
        $row = [
            'name'  => $it['name'] ?? $it['guitar_name'] ?? $it['title'] ?? null,
            'gid'   => null,
            'qty'   => max(1, $qty),
            'price' => isset($it['price']) ? (float)$it['price'] : null,
        ];
        if ($row['name'] === null) {
            $gid = (int)($it['id'] ?? $it['guitar_id'] ?? $key);
            if ($gid > 0) { $row['gid'] = $gid; $needIds[$gid] = true; }
            else { $row['name'] = 'Item'; }
        }
        $rows[] = $row;
    }

    /* Look up names/prices for rows that only stored a guitar id */
    if ($needIds) {
        $ids = array_keys($needIds);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price FROM guitars WHERE id IN ($in)");
        $stmt->execute($ids);
        $guitars = [];
        foreach ($stmt->fetchAll() as $g) $guitars[(int)$g['id']] = $g;

        foreach ($rows as &$row) {
            if ($row['name'] === null && $row['gid'] !== null) {
                $g = $guitars[$row['gid']] ?? null;
                $row['name'] = $g['name'] ?? ('Guitar #' . $row['gid']);
                if ($row['price'] === null) $row['price'] = $g['price'] ?? null;
            }
            unset($row['gid']);
        }
        unset($row);
    }

    return $rows;
}

 $pdo = db_or_die();

/* ============================================================
   HANDLE POST ACTIONS (before any output, so redirect() can fire)
   ============================================================ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* NOTE: there is no admin_login branch here any more — logging in happens
       on the SHARED page (auth/login.php → "Admin / Staff" tab). */

    /* Everything below requires an authenticated admin (see the shared login) */
    if (!admin_logged_in()) redirect('auth/login.php?tab=admin');

    /* ---- Logout ---- */
    if ($action === 'admin_logout') {
        csrf_check();
        logout_everything();          /* COMPLETE logout: customer + staff + cookie */
        redirect('auth/login.php?tab=admin');
    }

    /* ---- Add / edit guitar ---- */
    if ($action === 'save_guitar') {
        csrf_check();
        $id          = (int)($_POST['guitar_id'] ?? 0);
        $name        = trim($_POST['name'] ?? '');
        $brand       = trim($_POST['brand'] ?? '');
        $category    = trim($_POST['category'] ?? 'Acoustic');
        $price       = (float)($_POST['price'] ?? 0);
        $stock       = max(0, (int)($_POST['stock'] ?? 0));
        $description = trim($_POST['description'] ?? '');
        $bestseller  = isset($_POST['is_bestseller']) ? 1 : 0;
        $new         = isset($_POST['is_new']) ? 1 : 0;
        /* item code: admin-typable but sanitized — caps, digits and dashes only */
        $itemCode    = strtoupper(trim($_POST['item_code'] ?? ''));
        if ($itemCode !== '' && !preg_match('/^[A-Z0-9\-]{1,20}$/', $itemCode)) $itemCode = '';
        $availInput  = $_POST['availability'] ?? 'in-store';
        $availability= in_array($availInput, ['in-store', 'online', 'pre-order', 'coming-soon'], true) ? $availInput : 'in-store';
        $imageUrl    = trim($_POST['image_url'] ?? '');

        if ($name === '') {
            flash('error', 'Guitar name is required.');
            redirect('admin/admin.php?tab=guitars');
        }

        [$upPath, $upError] = handle_image_upload($_FILES['image'] ?? null);
        $notice = '';
        if ($upError !== null) {
            $notice = 'Image not saved: ' . $upError;
            $image = $imageUrl;
        } elseif (is_string($upPath)) {
            $image = $upPath;
        } else {
            $image = $imageUrl;
            if ($image !== '' && !preg_match('#^https?://#i', $image) && !is_file(__DIR__ . '/' . $image)) {
                $notice = 'Saved, but the image path does not exist on the server (' . $image . ') - the shop will show a placeholder.';
            }
        }

        if ($id > 0) {
            if ($image === '') {
                $stmt = $pdo->prepare('SELECT image FROM guitars WHERE id = ?');
                $stmt->execute([$id]);
                $image = (string)$stmt->fetchColumn();
            }
            $stmt = $pdo->prepare('UPDATE guitars SET name=?, brand=?, category=?, price=?, stock=?, image=?, description=?, is_bestseller=?, is_new=?, item_code=?, availability=? WHERE id=?');
            $stmt->execute([$name, $brand, $category, $price, $stock, $image, $description, $bestseller, $new, $itemCode, $availability, $id]);
            flash($notice !== '' ? 'error' : 'success', $notice !== '' ? $notice : '"' . $name . '" was updated.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO guitars (name, brand, category, price, stock, image, description, is_bestseller, is_new, item_code, availability) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$name, $brand, $category, $price, $stock, $image, $description, $bestseller, $new, $itemCode, $availability]);
            flash($notice !== '' ? 'error' : 'success', $notice !== '' ? $notice : '"' . $name . '" was added.');
        }
        redirect('admin/admin.php?tab=guitars');
    }

    /* ---- Delete guitar ---- */
    if ($action === 'delete_guitar') {
        csrf_check();
        $id = (int)($_POST['guitar_id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM guitars WHERE id = ?');
        $stmt->execute([$id]);
        flash('success', 'Guitar removed.');
        redirect('admin/admin.php?tab=guitars');
    }

    /* ---- Adjust stock (+ / -) ---- */
    if ($action === 'adjust_stock') {
        csrf_check();
        $id    = (int)($_POST['guitar_id'] ?? 0);
        $delta = (int)($_POST['delta'] ?? 0);
        $stmt = $pdo->prepare('UPDATE guitars SET stock = GREATEST(0, stock + ?) WHERE id = ?');
        $stmt->execute([$delta, $id]);
        $qs = !empty($_POST['q']) ? '&q=' . urlencode($_POST['q']) : '';
        redirect('admin/admin.php?tab=guitars' . $qs);
    }

    /* ---- Verify a GCash payment (the ONLY action that can confirm a GCash
       order). Requires an authenticated admin session (already enforced
       above) + CSRF. Stamps who verified it and when - an audit trail a
       customer or attacker can never write to themselves. ---- */
    if ($action === 'verify_gcash_payment') {
        csrf_check();
        $id = (int)($_POST['order_id'] ?? 0);
        try {
            $pdo->beginTransaction();
            $cur = $pdo->prepare('SELECT status, payment_method FROM orders WHERE id = ? FOR UPDATE');
            $cur->execute([$id]);
            $row = $cur->fetch();
            if (!$row) {
                throw new RuntimeException('Order #' . $id . ' was not found.');
            }
            if ($row['payment_method'] !== 'gcash') {
                throw new RuntimeException('Order #' . $id . ' is not a GCash order.');
            }
            $up = $pdo->prepare(
                'UPDATE orders SET status = ?, status_updated_at = NOW(),
                 payment_verified_at = NOW(), payment_verified_by = ? WHERE id = ?'
            );
            $up->execute(['confirmed', (int)$_SESSION['admin_id'], $id]);
            $pdo->commit();
            flash('success', 'Order #' . $id . ' — GCash payment verified ✔ and marked Confirmed.');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('error', $e instanceof RuntimeException ? $e->getMessage() : 'Could not verify order #' . $id . '. Please try again.');
        }
        redirect('admin/admin.php?tab=orders');
    }

    /* ---- Update order status (also used by Accept / Reject buttons).
       Every change is stamped with status_updated_at; moving an order
       into 'cancelled' returns its items to stock exactly once. ---- */
    if ($action === 'update_order_status') {
        csrf_check();
        $id     = (int)($_POST['order_id'] ?? 0);
        $status = $_POST['status'] ?? '';
        $allowedStatus = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
        if (in_array($status, $allowedStatus, true)) {
            try {
                $pdo->beginTransaction();
                $cur = $pdo->prepare('SELECT status, items, payment_method, payment_verified_at FROM orders WHERE id = ? FOR UPDATE');
                $cur->execute([$id]);
                $row = $cur->fetch();
                if (!$row) {
                    throw new RuntimeException('Order #' . $id . ' was not found.');
                }
                /* a GCash order can only become "confirmed" through the
                   dedicated Verify Payment action above - never via this
                   generic dropdown, so there is exactly one door to "paid" */
                if ($status === 'confirmed' && $row['payment_method'] === 'gcash' && empty($row['payment_verified_at'])) {
                    throw new RuntimeException('Order #' . $id . ' is GCash and not yet verified. Use "Verify Payment" instead.');
                }
                if ($row['status'] !== $status) {
                    $up = $pdo->prepare('UPDATE orders SET status = ?, status_updated_at = NOW() WHERE id = ?');
                    $up->execute([$status, $id]);
                    if ($status === 'cancelled') {
                        /* restock only on the transition INTO cancelled (no double refunds) */
                        foreach (json_decode($row['items'], true) ?: [] as $it) {
                            $rs = $pdo->prepare('UPDATE guitars SET stock = stock + ? WHERE id = ?');
                            $rs->execute([(int)($it['qty'] ?? 0), (int)($it['id'] ?? 0)]);
                        }
                    }
                }
                $pdo->commit();
                if ($status === 'confirmed') {
                    flash('success', 'Order #' . $id . ' accepted ✔ — marked Confirmed.');
                } elseif ($status === 'cancelled') {
                    flash('error', 'Order #' . $id . ' rejected ✖ — marked Cancelled, items returned to stock.');
                } else {
                    flash('success', 'Order #' . $id . ' marked ' . ucfirst($status) . '.');
                }
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) $pdo->rollBack();
                flash('error', 'Could not update order #' . $id . '. Please try again.');
            }
        }
        redirect('admin/admin.php?tab=orders');
    }

    /* ---- Change admin password ---- */
    if ($action === 'change_password') {
        csrf_check();
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $adminId = (int)$_SESSION['admin_id'];
        $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$adminId]);
        $adminRow = $stmt->fetch();
        if (!$adminRow || !password_verify($current, $adminRow['password'])) {
            flash('error', 'Current password is incorrect.');
        } elseif (strlen($new) < 8) {
            flash('error', 'New password must be at least 8 characters.');
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare('UPDATE admins SET password = ? WHERE id = ?');
            $stmt->execute([$hash, $adminId]);
            flash('success', 'Password updated.');
        }
        redirect('admin/admin.php?tab=settings');
    }
}

/* ============================================================
   GATE: the login form is SHARED with customers now
   (auth/login.php → the "Admin / Staff" tab), so visitors who are
   not signed in are simply sent there. Nothing is rendered here.
   ============================================================ */
if (!admin_logged_in()) {
    flash('info', 'Please sign in with a staff account to open the admin panel.');
    redirect('auth/login.php?tab=admin');
}
/* ============================================================
   AUTHENTICATED — GATHER DATA FOR THE CURRENT TAB
   ============================================================ */
 $admin = current_admin();
 $flash = take_flash();
 $tab   = $_GET['tab'] ?? 'dashboard';
 $allowedTabs = ['dashboard', 'guitars', 'orders', 'settings'];
if (!in_array($tab, $allowedTabs, true)) $tab = 'dashboard';

/* dashboard stats (cheap — small tables, fine to always compute for the sidebar badge) */
 $stats = [
    'guitars'     => (int)$pdo->query('SELECT COUNT(*) FROM guitars')->fetchColumn(),
    'stock_units' => (int)$pdo->query('SELECT COALESCE(SUM(stock),0) FROM guitars')->fetchColumn(),
    'low_stock'   => (int)$pdo->query('SELECT COUNT(*) FROM guitars WHERE stock <= ' . LOW_STOCK_THRESHOLD)->fetchColumn(),
    'orders'      => (int)$pdo->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
    'pending'     => (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn(),
    'revenue'     => (float)$pdo->query("SELECT COALESCE(SUM(total),0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
];

if ($tab === 'dashboard') {
    $recentOrders = $pdo->query('SELECT * FROM orders ORDER BY created_at DESC LIMIT 6')->fetchAll();
    $lowStockList = $pdo->query('SELECT * FROM guitars WHERE stock <= ' . LOW_STOCK_THRESHOLD . ' ORDER BY stock ASC LIMIT 6')->fetchAll();
}

 $q = trim($_GET['q'] ?? '');
 $formMode = '';
 $editGuitar = null;
if ($tab === 'guitars') {
    if ($q !== '') {
        $stmt = $pdo->prepare('SELECT * FROM guitars WHERE name LIKE ? OR brand LIKE ? ORDER BY created_at DESC');
        $like = '%' . $q . '%';
        $stmt->execute([$like, $like]);
        $guitars = $stmt->fetchAll();
    } else {
        $guitars = $pdo->query('SELECT * FROM guitars ORDER BY created_at DESC')->fetchAll();
    }

    $formMode = $_GET['form'] ?? '';
    if ($formMode === 'edit' && !empty($_GET['id'])) {
        $stmt = $pdo->prepare('SELECT * FROM guitars WHERE id = ?');
        $stmt->execute([(int)$_GET['id']]);
        $editGuitar = $stmt->fetch() ?: null;
        if (!$editGuitar) $formMode = '';
    }
}

 $statusFilter = '';
if ($tab === 'orders') {
    $statusFilter = $_GET['status'] ?? '';
    $allowedStatus = ['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'];
    if ($statusFilter && in_array($statusFilter, $allowedStatus, true)) {
        $stmt = $pdo->prepare('SELECT o.*, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id WHERE o.status = ? ORDER BY o.created_at DESC');
        $stmt->execute([$statusFilter]);
        $orders = $stmt->fetchAll();
    } else {
        $statusFilter = '';
        $orders = $pdo->query('SELECT o.*, u.email AS user_email FROM orders o LEFT JOIN users u ON u.id = o.user_id ORDER BY o.created_at DESC')->fetchAll();
    }
}

 $pageTitles = [
    'dashboard' => 'Dashboard',
    'guitars'   => 'Guitars & Stock',
    'orders'    => 'Orders',
    'settings'  => 'Settings',
];
 $pageTitle = $pageTitles[$tab];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> — Admin · Shan's Guitar</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,wght@0,600;0,700;1,500&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/admin.css?v=<?= filemtime(__DIR__ . '/../assets/admin.css') ?>">
<style>
  /* Accept / Reject buttons */
  .btn-accept {
    background:#1e8e3e; color:#fff; border:0; padding:9px 22px;
    border-radius:8px; font-weight:700; font-size:.78rem; cursor:pointer;
    letter-spacing:1px; font-family:inherit; transition:background .15s;
  }
  .btn-accept:hover { background:#156a2e; }
  .btn-reject {
    background:transparent; color:#c0392b; border:1.5px solid #c0392b;
    padding:7.5px 22px; border-radius:8px; font-weight:700; font-size:.78rem;
    cursor:pointer; letter-spacing:1px; font-family:inherit; transition:all .15s;
  }
  .btn-reject:hover { background:#c0392b; color:#fff; }
</style>
</head>
<body class="admin-body">

<?php if ($flash): ?>
  <div class="admin-flash flash-<?= e($flash['type']) ?>" id="adminFlash"><?= e($flash['msg']) ?></div>
<?php endif; ?>

<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="admin-brand"><span class="brand-text">shan's guitar</span><small>Admin</small></div>

    <a href="admin.php?tab=dashboard" class="admin-nav-link <?= $tab === 'dashboard' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
      Dashboard
    </a>
    <a href="admin.php?tab=guitars" class="admin-nav-link <?= $tab === 'guitars' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 3 10.5 13.5"/><path d="M14.5 6.5 3 18a2.1 2.1 0 0 0 3 3L17.5 9.5"/><circle cx="8.5" cy="17.5" r="2"/></svg>
      Guitars
    </a>
    <a href="admin.php?tab=orders" class="admin-nav-link <?= $tab === 'orders' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 3h12l1 5H5l1-5Z"/><path d="M5 8h14l-1.2 11a2 2 0 0 1-2 1.8H8.2a2 2 0 0 1-2-1.8L5 8Z"/><path d="M9 12v2M15 12v2"/></svg>
      Orders
      <?php if ($stats['pending'] > 0): ?><span style="margin-left:auto;background:var(--gold);color:#fff;font-size:.65rem;padding:2px 8px;border-radius:20px;"><?= $stats['pending'] ?></span><?php endif; ?>
    </a>
    <a href="admin.php?tab=settings" class="admin-nav-link <?= $tab === 'settings' ? 'active' : '' ?>">
      <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .34 1.87l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.7 1.7 0 0 0-1.87-.34 1.7 1.7 0 0 0-1.04 1.56V21a2 2 0 1 1-4 0v-.09A1.7 1.7 0 0 0 9 19.37a1.7 1.7 0 0 0-1.87.34l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06A1.7 1.7 0 0 0 4.64 15a1.7 1.7 0 0 0-1.56-1.04H3a2 2 0 1 1 0-4h.09A1.7 1.7 0 0 0 4.63 9a1.7 1.7 0 0 0-.34-1.87l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06A1.7 1.7 0 0 0 9 4.64a1.7 1.7 0 0 0 1.04-1.56V3a2 2 0 1 1 4 0v.09a1.7 1.7 0 0 0 1.04 1.56 1.7 1.7 0 0 0 1.87-.34l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06A1.7 1.7 0 0 0 19.37 9a1.7 1.7 0 0 0 1.56 1.04H21a2 2 0 1 1 0 4h-.09a1.7 1.7 0 0 0-1.51 1Z"/></svg>
      Settings
    </a>

    <div class="admin-sidebar-foot">
      <form method="post" action="admin.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="admin_logout">
        <button class="admin-logout" type="submit" style="width:100%; border:0; background:transparent; cursor:pointer; text-align:left;">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px;flex-shrink:0"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><path d="M16 17l5-5-5-5"/><path d="M21 12H9"/></svg>
          Log out
        </button>
      </form>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <p class="eyebrow"><?= $tab === 'dashboard' ? 'Overview' : 'Manage' ?></p>
        <h1><?= e($pageTitle) ?></h1>
      </div>
      <div class="admin-who">
        <span class="admin-avatar"><?= e(admin_initial($admin['name'])) ?></span>
        <?= e($admin['name']) ?>
      </div>
    </div>

    <?php if ($tab === 'dashboard'): ?>

      <div class="stat-grid">
        <div class="stat-card">
          <p class="stat-label">Total Guitars</p>
          <p class="stat-value"><?= $stats['guitars'] ?></p>
          <p class="stat-sub"><?= $stats['stock_units'] ?> units in stock</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Low Stock</p>
          <p class="stat-value"><?= $stats['low_stock'] ?></p>
          <p class="stat-sub">&le; <?= LOW_STOCK_THRESHOLD ?> units remaining</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Orders</p>
          <p class="stat-value"><?= $stats['orders'] ?></p>
          <p class="stat-sub"><?= $stats['pending'] ?> pending</p>
        </div>
        <div class="stat-card">
          <p class="stat-label">Revenue</p>
          <p class="stat-value"><?= peso($stats['revenue']) ?></p>
          <p class="stat-sub">All non-cancelled orders</p>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <div><h2>Recent Orders</h2><p class="sub">Latest 6 orders</p></div>
          <a href="admin.php?tab=orders" class="btn btn-outline btn-sm">View all</a>
        </div>
        <div class="panel-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Order</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
              <?php if (!$recentOrders): ?>
                <tr class="empty-row"><td colspan="5">No orders yet.</td></tr>
              <?php else: foreach ($recentOrders as $o): ?>
                <tr>
                  <td>#<?= (int)$o['id'] ?></td>
                  <td><?= e($o['fullname'] ?? 'Guest') ?></td>
                  <td><?= peso($o['total']) ?></td>
                  <td><span class="order-status-badge status-<?= e($o['status']) ?>"><?= e(ucfirst($o['status'])) ?></span></td>
                  <td><?= e(date('M j, Y', strtotime($o['created_at']))) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="panel">
        <div class="panel-head">
          <div><h2>Low Stock Alerts</h2><p class="sub">Guitars at or below <?= LOW_STOCK_THRESHOLD ?> units</p></div>
          <a href="admin.php?tab=guitars" class="btn btn-outline btn-sm">Manage stock</a>
        </div>
        <div class="panel-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Guitar</th><th>Brand</th><th>Stock</th></tr></thead>
            <tbody>
              <?php if (!$lowStockList): ?>
                <tr class="empty-row"><td colspan="3">All good — nothing low on stock.</td></tr>
              <?php else: foreach ($lowStockList as $g): ?>
                <tr>
                  <td class="cell-product">
                    <div class="cell-thumb"><?php if ($g['image']): ?><img src="<?= e(img_src((string)$g['image'])) ?>" alt=""><?php endif; ?></div>
                    <div><strong><?= e($g['name']) ?></strong><span><?= e($g['category']) ?></span></div>
                  </td>
                  <td><?= e($g['brand']) ?></td>
                  <td><span class="stock-pill <?= stock_class((int)$g['stock']) ?>"><?= e(stock_label((int)$g['stock'])) ?></span></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

    <?php elseif ($tab === 'guitars'): ?>

      <div class="panel">
        <div class="panel-head">
          <div><h2>All Guitars</h2><p class="sub"><?= count($guitars) ?> total</p></div>
          <div class="panel-toolbar">
            <form method="get" action="admin.php" style="display:flex; gap:8px;">
              <input type="hidden" name="tab" value="guitars">
              <input class="search-box" type="text" name="q" value="<?= e($q) ?>" placeholder="Search name or brand…">
            </form>
            <a href="admin.php?tab=guitars&form=add" class="btn btn-gold btn-sm">
              <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4"><path d="M12 5v14M5 12h14"/></svg>
              Add Guitar
            </a>
          </div>
        </div>
        <div class="panel-body admin-table-wrap">
          <table class="admin-table">
            <thead><tr><th>Guitar</th><th>Brand</th><th>Category</th><th>Price</th><th>Stock</th><th></th></tr></thead>
            <tbody>
              <?php if (!$guitars): ?>
                <tr class="empty-row"><td colspan="6">No guitars found<?= $q !== '' ? ' for "' . e($q) . '"' : '' ?>.</td></tr>
              <?php else: foreach ($guitars as $g): ?>
                <tr>
                  <td class="cell-product">
                    <div class="cell-thumb"><?php if ($g['image']): ?><img src="<?= e(img_src((string)$g['image'])) ?>" alt=""><?php endif; ?></div>
                    <div><strong><?= e($g['name']) ?></strong><span><?= e($g['item_code'] ?? ('SG-' . str_pad((string)$g['id'], 4, '0', STR_PAD_LEFT))) ?><?= $g['is_new'] ? ' · NEW' : '' ?><?= $g['is_bestseller'] ? ' · Bestseller' : '' ?></span></div>
                  </td>
                  <td><?= e($g['brand']) ?></td>
                  <td><?= e($g['category']) ?></td>
                  <td><?= peso($g['price']) ?></td>
                  <td>
                    <div class="stock-adjust">
                      <form method="post" action="admin.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="guitar_id" value="<?= (int)$g['id'] ?>">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <input type="hidden" name="delta" value="-1">
                        <button class="stock-btn" type="submit" title="Minus 1" <?= $g['stock'] <= 0 ? 'disabled' : '' ?>>&minus;</button>
                      </form>
                      <span class="stock-pill <?= stock_class((int)$g['stock']) ?>"><?= e(stock_label((int)$g['stock'])) ?></span>
                      <form method="post" action="admin.php">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="adjust_stock">
                        <input type="hidden" name="guitar_id" value="<?= (int)$g['id'] ?>">
                        <input type="hidden" name="q" value="<?= e($q) ?>">
                        <input type="hidden" name="delta" value="1">
                        <button class="stock-btn" type="submit" title="Add 1">+</button>
                      </form>
                    </div>
                  </td>
                  <td>
                    <div class="row-actions">
                      <a class="icon-btn" href="admin.php?tab=guitars&form=edit&id=<?= (int)$g['id'] ?>" title="Edit">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                      </a>
                      <form method="post" action="admin.php" onsubmit="return confirm('Delete “<?= e(addslashes($g['name'])) ?>”? This can\'t be undone.');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete_guitar">
                        <input type="hidden" name="guitar_id" value="<?= (int)$g['id'] ?>">
                        <button class="icon-btn danger" type="submit" title="Delete">
                          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18"/><path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/><path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"/></svg>
                        </button>
                      </form>
                    </div>
                  </td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <?php if ($formMode === 'add' || $formMode === 'edit'):
        $imgVal = '';
        if ($editGuitar && !empty($editGuitar['image']) && strpos($editGuitar['image'], UPLOAD_PATH) !== 0) {
            $imgVal = $editGuitar['image'];
        }
      ?>
      <div class="admin-modal-overlay">
        <div class="admin-modal">
          <div class="admin-modal-head">
            <h2><?= $formMode === 'edit' ? 'Edit Guitar' : 'Add New Guitar' ?></h2>
            <a href="admin.php?tab=guitars" class="admin-modal-close">&times;</a>
          </div>
          <form method="post" action="admin.php" enctype="multipart/form-data" class="admin-form">
            <div class="admin-modal-body">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="save_guitar">
              <input type="hidden" name="guitar_id" value="<?= $editGuitar['id'] ?? '' ?>">

              <?php if ($formMode === 'edit' && !empty($editGuitar['image'])): ?>
                <div class="current-image">
                  <img src="<?= e(img_src((string)$editGuitar['image'])) ?>" alt="">
                  <span>Current image — upload a new one below to replace it.</span>
                </div>
              <?php endif; ?>

              <label>Guitar name
                <input type="text" name="name" required value="<?= e($editGuitar['name'] ?? '') ?>" placeholder="e.g. Gibson Les Paul Standard">
              </label>

              <div class="form-grid-2">
                <label>Brand
                  <input type="text" name="brand" value="<?= e($editGuitar['brand'] ?? '') ?>" placeholder="e.g. Gibson">
                </label>
                <label>Category
                  <select name="category">
                    <?php foreach (['Acoustic', 'Electric', 'Bass', 'Classical', 'Amplifier', 'Accessory'] as $cat): ?>
                      <option value="<?= e($cat) ?>" <?= (($editGuitar['category'] ?? 'Acoustic') === $cat) ? 'selected' : '' ?>><?= e($cat) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </div>

              <div class="form-grid-2">
                <label>Price (₱)
                  <input type="number" step="0.01" min="0" name="price" required value="<?= e($editGuitar['price'] ?? '') ?>">
                </label>
                <label>Stock quantity
                  <input type="number" min="0" name="stock" required value="<?= e($editGuitar['stock'] ?? 0) ?>">
                </label>
              </div>

              <div class="form-grid-2">
                <label>Item code (shown on the product page)
                  <input type="text" name="item_code" maxlength="20" value="<?= e($editGuitar['item_code'] ?? '') ?>" placeholder="leave blank → auto SG-000N">
                </label>
                <label>Availability
                  <select name="availability">
                    <?php foreach (['in-store' => 'In store now', 'online' => 'Online only', 'pre-order' => 'Pre-order', 'coming-soon' => 'Coming soon'] as $avKey => $avLabel): ?>
                      <option value="<?= e($avKey) ?>" <?= (($editGuitar['availability'] ?? 'in-store') === $avKey) ? 'selected' : '' ?>><?= e($avLabel) ?></option>
                    <?php endforeach; ?>
                  </select>
                </label>
              </div>

              <label>Image upload
                <input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">
              </label>
              <label>...or image URL
                <input type="text" name="image_url" placeholder="images/products/your-photo.jpg" value="<?= e($imgVal) ?>">
              </label>

              <label>Description
                <textarea name="description" rows="4" placeholder="Short description shown on the product page…"><?= e($editGuitar['description'] ?? '') ?></textarea>
              </label>

              <label class="checkbox-row" style="flex-direction:row;">
                <input type="checkbox" name="is_new" <?= !empty($editGuitar['is_new']) ? 'checked' : '' ?>>
                Mark as NEW (shows a "New" badge on the card)
              </label>
              <label class="checkbox-row" style="flex-direction:row;">
                <input type="checkbox" name="is_bestseller" <?= !empty($editGuitar['is_bestseller']) ? 'checked' : '' ?>>
                Mark as bestseller
              </label>
            </div>
            <div class="admin-modal-foot">
              <a href="admin.php?tab=guitars" class="btn btn-outline">Cancel</a>
              <button class="btn btn-gold" type="submit"><?= $formMode === 'edit' ? 'Save Changes' : 'Add Guitar' ?></button>
            </div>
          </form>
        </div>
      </div>
      <?php endif; ?>

    <?php elseif ($tab === 'orders'): ?>

      <div class="panel">
        <div class="panel-head">
          <div><h2>All Orders</h2><p class="sub"><?= count($orders) ?> total</p></div>
          <div class="panel-toolbar">
            <a href="admin.php?tab=orders" class="btn btn-sm <?= $statusFilter === '' ? 'btn-gold' : 'btn-outline' ?>">All</a>
            <?php foreach (['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $s): ?>
              <a href="admin.php?tab=orders&status=<?= $s ?>" class="btn btn-sm <?= $statusFilter === $s ? 'btn-gold' : 'btn-outline' ?>"><?= ucfirst($s) ?></a>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="panel-body">
          <?php if (!$orders): ?>
            <div style="padding:50px 0; text-align:center; opacity:.5; font-size:.9rem;">
              No orders<?= $statusFilter !== '' ? ' with status "' . e($statusFilter) . '"' : ' yet' ?>.
            </div>
          <?php else: foreach ($orders as $o):
              $items = order_items_from_text($pdo, $o['items'] ?? null);

              $isGcash       = ($o['payment_method'] ?? '') === 'gcash';
              $gcashVerified = $isGcash && !empty($o['payment_verified_at']);

              /* which quick-action buttons apply to this order?
                 an unverified GCash order shows "Verify Payment" instead of
                 the plain Accept button - that is the only path to confirmed */
              $showAccept = in_array($o['status'], ['pending', 'cancelled'], true) && !($isGcash && !$gcashVerified);
              $showVerify = $isGcash && !$gcashVerified && in_array($o['status'], ['pending', 'cancelled'], true);
              $showReject = in_array($o['status'], ['pending', 'confirmed'], true);
          ?>
            <div class="panel" style="margin-bottom:16px; box-shadow:none;">
              <div class="panel-head" style="background:var(--beige);">
                <div>
                  <strong>Order #<?= (int)$o['id'] ?></strong> — <?= e($o['fullname'] ?? 'Guest') ?>
                  <p class="sub"><?= !empty($o['user_email']) ? e($o['user_email']) : 'no email on file' ?><?= !empty($o['phone']) ? ' · ' . e($o['phone']) : '' ?> · <?= e(date('M j, Y g:ia', strtotime($o['created_at']))) ?></p>
                </div>
                <form method="post" action="admin.php" style="display:flex; align-items:center; gap:10px;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update_order_status">
                  <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                  <select name="status" class="status-select order-status-badge status-<?= e($o['status']) ?>">
                    <?php foreach (['pending', 'confirmed', 'shipped', 'delivered', 'cancelled'] as $s): ?>
                      <?php if ($s === 'confirmed' && $isGcash && !$gcashVerified) continue; /* use Verify Payment instead */ ?>
                      <option value="<?= $s ?>" <?= $o['status'] === $s ? 'selected' : '' ?>><?= ucfirst($s) ?></option>
                    <?php endforeach; ?>
                  </select>
                </form>
              </div>
              <div class="panel-body">
                <table class="admin-table">
                  <thead><tr><th>Item</th><th>Qty</th><th>Price</th><th>Subtotal</th></tr></thead>
                  <tbody>
                    <?php if (!$items): ?>
                      <tr class="empty-row"><td colspan="4">No item details recorded.</td></tr>
                    <?php else: foreach ($items as $it): ?>
                      <tr>
                        <td><?= e($it['name']) ?></td>
                        <td><?= (int)$it['qty'] ?></td>
                        <td><?= $it['price'] !== null ? peso($it['price']) : '—' ?></td>
                        <td><?= $it['price'] !== null ? peso($it['price'] * $it['qty']) : '—' ?></td>
                      </tr>
                    <?php endforeach; endif; ?>
                  </tbody>
                </table>
                <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:14px; gap:20px; flex-wrap:wrap;">
                  <div style="font-size:.8rem; opacity:.65; max-width:420px;">
                    <?= ($o['fulfillment'] ?? '') === 'delivery'
                        ? 'Delivery to: ' . e(trim(($o['address'] ?? '') . ', ' . ($o['city'] ?? ''), ' ,'))
                        : 'In-store pickup' ?>
                    · <strong>Payment:</strong> <?php
                      if ($isGcash) {
                          echo 'GCash · Ref: <strong>' . e($o['payment_ref'] ?: '—') . '</strong>';
                          echo $gcashVerified
                              ? ' <span style="color:#2a7a2a; font-weight:700;">✔ Verified ' . e(date('M j, g:ia', strtotime($o['payment_verified_at']))) . '</span>'
                              : ' <span style="color:#b8860b; font-weight:700;">⏳ Awaiting verification</span>';
                      } else {
                          echo e(($o['payment_method'] ?? 'cod') === 'pickup_pay' ? 'Pay on Pickup' : 'Cash on Delivery');
                      }
                    ?>
                    <?= !empty($o['notes']) ? '<br>Notes: ' . e($o['notes']) : '' ?>
                  </div>
                  <div style="display:flex; flex-direction:column; align-items:flex-end; gap:10px;">
                    <?php if ($showAccept || $showVerify || $showReject): ?>
                      <div style="display:flex; gap:8px;">
                        <?php if ($showVerify): ?>
                          <form method="post" action="admin.php" onsubmit="return confirm('Confirm: does your GCash app show a payment of <?= e(peso($o['total'])) ?> matching reference <?= e(addslashes($o['payment_ref'] ?? '')) ?>? This cannot be undone by mistake.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="verify_gcash_payment">
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <button class="btn-accept" type="submit">✓ VERIFY PAYMENT</button>
                          </form>
                        <?php endif; ?>
                        <?php if ($showAccept): ?>
                          <form method="post" action="admin.php" onsubmit="return confirm('Accept Order #<?= (int)$o['id'] ?> from <?= e(addslashes($o['fullname'] ?? 'Guest')) ?>?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <input type="hidden" name="status" value="confirmed">
                            <button class="btn-accept" type="submit">✓ ACCEPT</button>
                          </form>
                        <?php endif; ?>
                        <?php if ($showReject): ?>
                          <form method="post" action="admin.php" onsubmit="return confirm('Reject Order #<?= (int)$o['id'] ?>? It will be marked as cancelled.');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_order_status">
                            <input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>">
                            <input type="hidden" name="status" value="cancelled">
                            <button class="btn-reject" type="submit">✕ REJECT</button>
                          </form>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    <div style="font-weight:700; font-size:1.05rem;">Total: <?= peso($o['total']) ?></div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; endif; ?>
        </div>
      </div>

    <?php elseif ($tab === 'settings'): ?>

      <div class="panel">
        <div class="panel-head"><div><h2>Account</h2><p class="sub">Signed in as <?= e($admin['email']) ?></p></div></div>
        <div class="panel-body">
          <form method="post" action="admin.php" class="admin-form" style="max-width:420px;">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="change_password">
            <label>Current password
              <input type="password" name="current_password" required>
            </label>
            <label>New password
              <input type="password" name="new_password" required minlength="8">
            </label>
            <button class="btn btn-gold" type="submit">Update Password</button>
          </form>
        </div>
      </div>

    <?php endif; ?>

  </main>
</div>

<script>
  const flashEl = document.getElementById('adminFlash');
  if (flashEl) setTimeout(() => flashEl.remove(), 3500);

  document.querySelectorAll('.status-select').forEach(sel => {
    sel.addEventListener('change', () => sel.closest('form').submit());
  });
</script>
</body>
</html>

