/* 
   SHAN'S GUITAR — script.js (shared across pages)
*/

const PRODUCTS = window.SG_PRODUCTS || [];

/* ---------- helpers ---------- */
const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

const peso = n => "₱" + Number(n).toLocaleString("en-PH");

/* escape product data before it goes into innerHTML (XSS protection) */
const esc = s => String(s ?? "").replace(/[&<>"']/g, c =>
  ({ "&": "&amp;", "<": "&lt;", ">": "&gt;", '"': "&quot;", "'": "&#39;" })[c]);

const BASE = window.SG_BASE || "";   /* project root URL (set in header.php) */

const AVAIL_LABEL = {
  "in-store":    "In store now",
  "online":      "Online only",
  "pre-order":   "Pre-order",
  "coming-soon": "Coming soon"
};

function safe(name, fn) {
  try { fn(); }
  catch (err) { console.error(`[Shan's Guitar] "${name}" failed:`, err); }
}

/* dashed "GUITAR PNG" box when a photo is missing */
function applyImageFallbacks(container) {
  $$("img", container).forEach(img => {
    const fail = () => {
      const box = img.closest(".product-media, .brand-feature-media, .brand-tile");
      if (box) box.classList.add("is-fallback");
      img.style.visibility = "hidden";
    };
    if (img.complete && img.naturalWidth === 0) fail();
    img.addEventListener("error", fail, { once: true });
  });
}

/* ---------- boot ---------- */
document.addEventListener("DOMContentLoaded", () => {
  safe("reveal",      initReveal);
  safe("header",      initHeader);
  safe("bestsellers", initBestsellers);
  safe("shop",        initShop);
  safe("modal",       initModal);
  safe("toast",       initToast);
  safe("add-to-cart", initAddToCart);   /* NEW */
  safe("edit-profile-toggle", initEditProfileToggle);   /* Patch 7: account.php EDIT PROFILE */
});


/* 
   HEADER / NAV (hamburger + scroll shadow)
    */
function initHeader() {
  const header = $("#siteHeader");
  const nav    = $("#mainNav");
  const btn    = $("#menuBtn");
  if (!header || !nav || !btn) return;

  btn.addEventListener("click", () => {
    const open = nav.classList.toggle("open");
    btn.classList.toggle("open", open);
    btn.setAttribute("aria-expanded", String(open));
  });

  $$("#mainNav a").forEach(a => a.addEventListener("click", () => {
    nav.classList.remove("open");
    btn.classList.remove("open");
    btn.setAttribute("aria-expanded", "false");
  }));

  window.addEventListener("scroll", () => {
    header.classList.toggle("scrolled", window.scrollY > 20);
  }, { passive: true });
}


/* 
   BESTSELLERS in the homepage section
    */
function initBestsellers() {
  const track = $("#bestsellerTrack");
  if (!track) return;
  const prev = $("#bsPrev");
  const next = $("#bsNext");

  function render(filter = "all") {
    const list = PRODUCTS.filter(p =>
      p.featured && (filter === "all" || p.category === filter)
    );

    track.innerHTML = list.length
      ? list.map(cardHTML).join("")
      : `<p style="padding:40px 4px;opacity:.7">No products in this category yet.</p>`;

    applyImageFallbacks(track);
    track.scrollTo({ left: 0 });
    updateArrows();
  }

  const step = () => {
    const card = track.querySelector(".product-card");
    return card ? card.offsetWidth + 24 : 320;
  };

  function updateArrows() {
    if (!prev || !next) return;
    const max = track.scrollWidth - track.clientWidth - 2;
    prev.disabled = track.scrollLeft <= 2;
    next.disabled = track.scrollLeft >= max;
  }

  [prev, next].forEach(b => b && b.addEventListener("click", () => {
    track.scrollBy({ left: step() * Number(b.dataset.dir), behavior: "smooth" });
  }));

  track.addEventListener("scroll", updateArrows, { passive: true });
  window.addEventListener("resize", updateArrows);

  $$("#bestsellerTabs .tab").forEach(tab => {
    tab.addEventListener("click", () => {
      $$("#bestsellerTabs .tab").forEach(t => {
        t.classList.remove("active");
        t.setAttribute("aria-selected", "false");
      });
      tab.classList.add("active");
      tab.setAttribute("aria-selected", "true");
      render(tab.dataset.filter);
    });
  });

  render();
}


/* 
   3. CARD TEMPLATE
    */
function cardHTML(p) {
  const badge = p.badge ? `<span class="badge">${esc(p.badge)}</span>` : "";
  return `
    <article class="product-card" data-id="${p.id}">
      <div class="product-media">
        ${badge}
        <img src="${esc(p.image)}" alt="${esc(p.name)}" loading="lazy">
      </div>
      <div class="product-info">
        <p class="product-type">${esc(p.category)}</p>
        <h3>${esc(p.name)}</h3>
        <p class="price">${peso(p.price)}</p>
        <div class="card-actions">
          <button class="btn-view" type="button" data-view="${p.id}">VIEW</button>
          <a class="btn-cart" href="${BASE}/cart.php?action=add&amp;id=${p.id}" data-add-cart="${p.id}">ADD TO CART</a>
        </div>
      </div>
    </article>`;
}


/* 
   4. SHOP (filters, search, sort, load more)
    */
const PAGE_SIZE = 6;

function initShop() {
  const grid = $("#shopGrid");
  if (!grid) return;

  const empty    = $("#emptyState");
  const countEl  = $("#resultCount");
  const priceEl  = $("#priceRange");
  const priceOut = $("#priceOut");
  const loadMore = $("#loadMore");

  /* initial state from the URL (server sets the inputs, JS reads them) */
  const initialCategory = ($('input[name="category"]:checked') || {}).value || "all";

  const state = {
    category: initialCategory,
    maxPrice: 200000,
    avail: [],
    sort: "featured",
    search: ($("#searchInput") || {}).value || "",
    shown: PAGE_SIZE
  };

  function getList() {
    const list = PRODUCTS.filter(p =>
      (state.category === "all" || p.category === state.category) &&
      p.price <= state.maxPrice &&
      (state.avail.length === 0 || state.avail.includes(p.availability)) &&
      (p.name + " " + p.brand + " " + p.category).toLowerCase().includes(state.search.toLowerCase())
    );

    const sorters = {
      "price-asc":  (a, b) => a.price - b.price,
      "price-desc": (a, b) => b.price - a.price,
      "newest":     (a, b) => b.id - a.id,
      "name":       (a, b) => a.name.localeCompare(b.name),
      "featured":   (a, b) => (Number(b.featured) - Number(a.featured)) || a.id - b.id
    };
    return list.sort(sorters[state.sort] || sorters.featured);
  }

  function render() {
    const list    = getList();
    const visible = list.slice(0, state.shown);

    grid.innerHTML = visible.map(cardHTML).join("");
    applyImageFallbacks(grid);

    if (empty)   empty.hidden = list.length !== 0;
    if (countEl) countEl.textContent =
      `Showing ${visible.length} of ${list.length} item${list.length === 1 ? "" : "s"}`;
    if (loadMore) loadMore.style.display = state.shown < list.length ? "inline-flex" : "none";
  }

  $$('input[name="category"]').forEach(r => r.addEventListener("change", () => {
    state.category = r.value;
    state.shown = PAGE_SIZE;
    render();
  }));

  if (priceEl) priceEl.addEventListener("input", () => {
    state.maxPrice = Number(priceEl.value);
    if (priceOut) priceOut.textContent = peso(state.maxPrice);
    state.shown = PAGE_SIZE;
    render();
  });

  $$(".avail").forEach(c => c.addEventListener("change", () => {
    state.avail = $$(".avail:checked").map(x => x.value);
    state.shown = PAGE_SIZE;
    render();
  }));

  const sortSel = $("#sortSelect");
  if (sortSel) sortSel.addEventListener("change", e => {
    state.sort = e.target.value;
    render();
  });

  const searchEl = $("#searchInput");
  if (searchEl) {
    let t;
    searchEl.addEventListener("input", e => {
      clearTimeout(t);
      t = setTimeout(() => {
        state.search = e.target.value.trim();
        state.shown = PAGE_SIZE;
        render();
      }, 180);
    });
  }

  if (loadMore) loadMore.addEventListener("click", () => {
    state.shown += PAGE_SIZE;
    render();
  });

  const clearBtn = $("#clearFilters");
  if (clearBtn) clearBtn.addEventListener("click", () => {
    const allRadio = $('input[name="category"][value="all"]');
    if (allRadio) allRadio.checked = true;
    $$(".avail").forEach(c => (c.checked = false));
    if (priceEl)  priceEl.value = 200000;
    if (priceOut) priceOut.textContent = peso(200000);
    if (searchEl) searchEl.value = "";
    if (sortSel)  sortSel.value = "featured";

    Object.assign(state, {
      category: "all", maxPrice: 200000, avail: [],
      sort: "featured", search: "", shown: PAGE_SIZE
    });
    render();
    toast("Filters cleared");
  });

  render();
}


/* 
   5. QUICK-VIEW MODAL
    */
let currentProduct = null;

function initModal() {
  const modal = $("#quickView");
  if (!modal) return;

  document.addEventListener("click", e => {
    const btn = e.target.closest("[data-view]");
    if (btn) openModal(Number(btn.dataset.view));
    if (e.target.closest("[data-close]")) closeModal();
  });

  document.addEventListener("keydown", e => {
    if (e.key === "Escape") closeModal();
  });

  const addBtn = $("#qvAdd");                              /* NEW — AJAX instead of page reload */
  if (addBtn) addBtn.addEventListener("click", e => {
    e.preventDefault();
    if (currentProduct) addToCart(currentProduct.id, addBtn);
  });

  function openModal(id) {
    const p = PRODUCTS.find(x => x.id === id);
    if (!p) return;
    currentProduct = p;

    const img = $("#qvImg");
    img.src = p.image;
    img.alt = p.name;

    $("#qvCat").textContent   = p.category;
    $("#qvTitle").textContent = p.name;
    $("#qvPrice").textContent = peso(p.price);
    $("#qvDesc").textContent  = p.desc;
    $("#qvMeta").innerHTML = `
      <li><span>Brand</span><strong>${esc(p.brand)}</strong></li>
      <li><span>Category</span><strong>${esc(p.category)}</strong></li>
      <li><span>Availability</span><strong>${AVAIL_LABEL[p.availability] || "—"}</strong></li>
      <li><span>Item code</span><strong>${esc(p.item_code || "SG-" + String(p.id).padStart(4, "0"))}</strong></li>`;

    modal.hidden = false;
    document.body.classList.add("no-scroll");
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove("no-scroll");
  }
}


/* 
   6. TOAST + REVEAL
    */
function initToast() {
  /* hook so any script can call toast() */
  window.toast = toast;
}

let toastTimer;
function toast(msg) {
  const el = $("#toast");
  if (!el) return;
  el.textContent = msg;
  el.classList.add("show");
  clearTimeout(toastTimer);
  toastTimer = setTimeout(() => el.classList.remove("show"), 2600);
}

function initReveal() {
  const els = $$(".reveal");
  if (!els.length) return;

  const failsafe = setTimeout(() => els.forEach(el => el.classList.add("visible")), 2000);

  if (!("IntersectionObserver" in window)) {
    els.forEach(el => el.classList.add("visible"));
    return;
  }

  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) {
        e.target.classList.add("visible");
        io.unobserve(e.target);
      }
    });
    if (!$$(".reveal:not(.visible)").length) clearTimeout(failsafe);
  }, { threshold: 0.08 });

  els.forEach(el => io.observe(el));
}


/* 
   7. ADD TO CART — records into $_SESSION['cart'] via cart.php   [NEW SECTION]
      One delegated listener covers: shop grid, homepage bestsellers, quick-view.
      The href stays as a no-JS fallback (a normal click on it still records the item).
*/
function initAddToCart() {
  document.addEventListener("click", e => {
    const btn = e.target.closest("[data-add-cart]");
    if (!btn) return;
    if (e.metaKey || e.ctrlKey || e.shiftKey) return;   /* let users open in a new tab */
    e.preventDefault();                                  /* stay on the page */
    addToCart(Number(btn.dataset.addCart), btn);
  });
}

async function addToCart(id, btn = null) {
  if (btn) btn.classList.add("is-loading");
  try {
    const res  = await fetch(`${BASE}/cart.php?action=add&id=${id}&ajax=1`, {
      headers: { "X-Requested-With": "fetch" }
    });
    const data = await res.json();

    if (data.ok) {
      $$("[data-cart-count]").forEach(el => {
        el.textContent = data.count;
        el.classList.toggle("is-empty", data.count === 0);
      });
      toast(data.message || "Added to your cart");
    } else {
      toast(data.message || "Could not add to cart");
    }
  } catch (err) {
    /* fetch/JSON failed → fall back to normal navigation (still records it) */
    window.location.href = `${BASE}/cart.php?action=add&id=${id}`;
  } finally {
    if (btn) btn.classList.remove("is-loading");
  }
}

/* 
   8. EDIT PROFILE - account.php   [Patch 7]
      The CSS sibling selector (.edit-details[open] + .info-grid ~ .profile-edit-form)
      can never match: .edit-details lives inside .profile-card-head while the form
      is a later child of .profile-card. Toggle the .force-open class the stylesheet
      already supports (assets/style.css: .profile-edit-form.force-open).
*/
function initEditProfileToggle() {
  $$(".edit-details").forEach(d => {
    d.addEventListener("toggle", () => {
      const card = d.closest(".profile-card");
      const form = card ? card.querySelector(".profile-edit-form") : null;
      if (form) form.classList.toggle("force-open", d.open);
    });
  });
}