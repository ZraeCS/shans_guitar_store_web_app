# Shan's Guitar — Change Log

Running record of feature batches and fixes. Commits are on `main`; newest batch first.

---

## Project reorganization (2026-09-16)

**Goal:** match the rubric's "organized structure" expectation — pages grouped by role, database files visible.

- **New structure:**
  ```
  admin/       admin.php
  auth/        login.php, register.php, logout.php
  customer/    account.php, cart.php, checkout.php
  database/    config.php (credentials + helpers), schema.sql (full table export)
  includes/    header.php, footer.php, products.php (shared templates + catalogue)
  assets/, images/, uploads/   unchanged
  root         index.php, shop.php, about.php, brands.php
  ```
- **How nothing broke:** every file was moved with `git mv` (history preserved), then all references rewritten:
  - `BASE_URL` constant (computed in `database/config.php` from the web root) — used by `redirect()`, `img_src()` and all header/footer links, so pages work from any folder depth
  - all `require`s became `__DIR__`-relative
  - admin redirects target `admin/admin.php`; auth redirects target `auth/…`; customer redirects target `customer/…`
  - script.js cards/quick-view/AJAX use `window.SG_BASE` (injected from header) for cart links
- **Also:** added `.gitignore` (OS junk, editor folders, logs, dumps) and `database/schema.sql` (mysqldump of the 4 tables).
- **Full regression tested:** all pages 200 from new locations; register → login → cart → checkout → cancel (stock restored) → admin login → orders tab — every flow verified through the new paths.

---

## Batch D — Richer product fields (2026-09-16)

**Goal:** admin controls "Mark as NEW", a real Item Code (SG-0016 style) and Availability per guitar.

- **Database:** `guitars` gains three columns (keep this SQL for other machines):
  ```sql
  ALTER TABLE guitars
    ADD COLUMN is_new TINYINT(1) NOT NULL DEFAULT 0 AFTER is_bestseller,
    ADD COLUMN item_code VARCHAR(20) NULL AFTER is_new,
    ADD COLUMN availability ENUM('in-store','online','pre-order','coming-soon') NOT NULL DEFAULT 'in-store' AFTER item_code;
  UPDATE guitars SET item_code = CONCAT('SG-', LPAD(id, 4, '0')) WHERE item_code IS NULL;
  ```
- **admin.php:** guitar form gains **Mark as NEW** checkbox, **Item code** input and **Availability** select; the guitars table now shows each product's item code + NEW/Bestseller flags. Save handler whitelists everything:
  - `item_code` sanitized to `[A-Z0-9-]` (max 20) — invalid input stores empty and the shop falls back to the auto `SG-000N` code
  - `availability` strict-whitelisted against the enum — tampered values fall back to `in-store`
- **includes/products.php:** loader exposes `is_new` (badge "New" wins over "Bestseller"), `item_code` and the DB availability; seed products get auto item codes when the DB is down.
- **shop.php / script.js:** availability filter gains a **Coming Soon** option; quick-view now shows the *real* admin-set item code instead of the old fake `SG-000id`.
- Item codes backfilled for all 15 existing guitars (`SG-0002`…; id 1 was an old test row).

**Tested:** E2E admin save (NEW + `SG-TEST99` + coming-soon), injection string rejected to empty, tampered availability fell back to `in-store`, shop JSON renders badge/code/availability, restore verified.

---

## Batch P — Payment method selection (2026-09-16)

**Goal:** make checkout's payment explicit and selectable, ready for GCash later. No online payment is taken yet.

- **Database:** `orders` gains `payment_method ENUM('cod','pickup_pay') NOT NULL DEFAULT 'cod'` (existing orders = `cod`). Keep this SQL for other machines:
  ```sql
  ALTER TABLE orders
    ADD COLUMN payment_method ENUM('cod','pickup_pay') NOT NULL DEFAULT 'cod' AFTER fulfillment;
  ```
- **checkout.php:** Section 3 is now two radio cards — **Cash on Delivery** / **Pay on Pickup** — with a "GCash is coming soon" note. Choice is retained on failed validation. Small JS suggests Pay-on-Pickup when Store pickup is chosen (cosmetic only).
- **Security (server-enforced):** strict whitelist `in_array(..., ['cod','pickup_pay'], true)` — tampered values (`gcash`, HTML injection, arrays, empty) all fall back to `cod`; DB ENUM blocks anything else at the schema level; POST requires the session CSRF token (403 otherwise); value saved via prepared statement inside the checkout transaction; displayed XSS-escaped via `e()`.
- **account.php / admin.php:** both show the payment method per order ("Cash on Delivery" / "Pay on Pickup").
- **GCash later:** add a third radio value + optional `payment_ref` column — designed in, no rework.

**Tested:** cod + pickup_pay orders end-to-end; 3 tamper vectors rejected; missing CSRF → 403; retention verified; both display locations verified.

---

## Batch C — Order cancel + status timeline (2026-09-16)

- **Database:** `orders.status_updated_at DATETIME NULL` added; existing rows backfilled to `created_at`.
  ```sql
  ALTER TABLE orders ADD COLUMN status_updated_at DATETIME NULL DEFAULT NULL AFTER status;
  UPDATE orders SET status_updated_at = created_at WHERE status_updated_at IS NULL;
  ```
- **account.php:** customer **CANCEL ORDER** button on *pending* orders only (confirm dialog, CSRF). Transactional: verifies ownership + `status='pending'` with `FOR UPDATE`, sets `cancelled`, stamps time, **returns every item to stock**.
- **Status timeline:** every order card shows a Placed → Confirmed → Shipped → Delivered stepper with timestamps; cancelled orders get a red note + muted card.
- **admin.php:** every status change (dropdown / ACCEPT / REJECT) stamps `status_updated_at`; **cancelling returns items to stock exactly once** (transition guard prevents double refunds).
- **Public cache-busters:** `style.css` / `script.js` now load with `?v=<filemtime>` (same pattern the admin panel already had) — no more stale CSS/JS after updates.

**Rules:** customer may cancel only while pending; only the *transition into* cancelled restocks. Known limitation: re-opening a cancelled order does not re-deduct stock.

**Tested:** E2E cancel (stock 10→9→10), double-cancel blocked, admin reject restock verified, timestamps verified.

---

## Batch B — Inventory integrity (2026-09-15/16)

- `includes/products.php`: all 14 fallback products expose real `stock` values (DB values always win when MySQL is up).
- `cart.php`: adding/updating quantities is **capped at real stock** (flash message when capped, `max` on the input).
- `checkout.php`: fresh stock re-check inside a **transaction**; `stock = stock - ? WHERE stock >= ?` per item — a rival buyer taking the last unit rolls the whole order back. Totals stored as float (old int-cast removed).
- `admin.php`: **hardened image uploads** — PHP upload-status check, 4 MB cap, extension whitelist, `finfo` MIME whitelist (jpeg/png/webp) + `getimagesize()` content check; explicit error for every failure (no more silent failures). Uploads land in `images/products/` (single convention). Warns when a typed local URL doesn't exist on disk.
- Admin UI: text-only one-line brand, compact logout icon (inline 16px sizing), admin CSS cache-buster.

---

## Batch A — Quick wins (2026-09-15)

- Footer: removed the "Get the App" dead block → **Follow Us** with Facebook / Instagram / TikTok links (generic platform URLs).
- Checkout: typed `phone`, `address`, `city`, `notes` and the fulfillment choice survive validation errors.
- Removed all misleading "run install.php / admin_schema.sql" messages → real hints (check `includes/config.php`, MySQL in XAMPP).
- account.php: removed an unused require.

---

## Seed / asset fixes (2026-09-15)

- 4 product photos added (Cordoba C5, Gretsch G2622, Fender gig bag, strings); `gretsch_g2622_streamliner .webp` renamed to drop the space (that was the "webp doesn't work" bug — a 404, not a format issue).
- Social links pointed at the generic platform sites.

---

## Earlier security hardening (2026-09-15)

Session cookie flags (`httponly`, `samesite=Lax`) · login open-redirect closed (`?next` must start with a single `/`) · dead `cart_before_login` merge removed · registration password policy raised to 8 chars (matching admin) · unsafe duplicate `SG_PRODUCTS` injection removed from the footer.

---

## Database changes summary (for rebuilding elsewhere)

1. Import `schema.sql` or recreate the four tables (`users`, `guitars`, `orders`, `admins` — see phpMyAdmin structure).
2. Apply the two batch migrations above (`status_updated_at`, `payment_method`).
3. Seed guitars via Admin → Add Guitar (or restore a `mysqldump` backup from `C:\xampp\`).

---

## Redirect stubs for old URLs (2026-09-16)

After the reorganization, the seven old root-level addresses (`login.php`, `register.php`, `logout.php`, `admin.php`, `account.php`, `cart.php`, `checkout.php`) now issue permanent 301 redirects to their new homes (`auth/…`, `admin/admin.php`, `customer/…`) so old bookmarks and external links never 404.

## Fix: add-to-cart after reorganization (2026-09-16)

`script.js` still pointed its add-to-cart links/AJAX at the pre-reorganization root `cart.php`; the 301 stub consumed the query string, so the cart page opened empty. Links now target `customer/cart.php` directly, the seven stubs forward query strings, and cart thumbnails resolve via `img_src()`.
