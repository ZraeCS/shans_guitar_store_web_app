/* ============================================================
   SHAN'S GUITAR — script.js  (complete file)
   ============================================================ */

/* ------------------------------------------------------------
   DATA — swap for PHP later:
   const PRODUCTS = await (await fetch('api/products.php')).json();
   ------------------------------------------------------------ */

const PRODUCTS = [
  { id:1,  name:"Fender Stratocaster II",     brand:"Fender",     category:"electric",    price:35500,  badge:"Bestseller", availability:"in-store",  featured:true,  image:"images/products/strat-ii.jpg",     desc:"Alder body, maple neck and three single-coils — the classic bell-like Strat chime, professionally set up in store." },
  { id:2,  name:"Yamaha FG800",               brand:"Yamaha",     category:"acoustic",    price:15500,  badge:"Staff Pick", availability:"in-store",  featured:true,  image:"images/products/fg800.jpg",        desc:"Solid spruce top dreadnought with a big, warm voice. The best first serious acoustic you can buy." },
  { id:3,  name:"Fender Stratocaster White",  brand:"Fender",     category:"electric",    price:45000,  badge:"Popular",    availability:"in-store",  featured:true,  image:"images/products/strat-white.jpg",  desc:"Olympic White finish with a rosewood fingerboard — bright, articulate and endlessly versatile." },
  { id:4,  name:"PRS Custom 24 10-Top",       brand:"PRS",        category:"electric",    price:96500,  badge:"Bestseller", availability:"online",    featured:true,  image:"images/products/prs-custom24.jpg", desc:"Flame maple 10-Top, 85/15 pickups and a 5-way blade — modern precision with vintage soul." },
  { id:5,  name:"Gibson Les Paul Standard",   brand:"Gibson",     category:"electric",    price:105500, badge:"Bestseller", availability:"in-store",  featured:true,  image:"images/products/les-paul.jpg",     desc:"Mahogany body, carved maple cap and Burstbucker humbuckers. Thick, singing sustain." },
  { id:6,  name:"Epiphone SG Standard",       brand:"Epiphone",   category:"electric",    price:25000,  badge:"Bestseller", availability:"in-store",  featured:true,  image:"images/products/sg-standard.jpg",  desc:"Lightweight double-cutaway with fierce mid-range bite — a rock machine at a fair price." },
  { id:7,  name:"Martin D-28",                brand:"Martin",     category:"acoustic",    price:178000, badge:"",           availability:"pre-order", featured:true,  image:"images/products/d28.jpg",          desc:"The benchmark dreadnought since 1931. East Indian rosewood back and sides, Sitka spruce top." },
  { id:8,  name:"Taylor GS Mini Mahogany",    brand:"Taylor",     category:"acoustic",    price:39500,  badge:"Popular",    availability:"online",    featured:true,  image:"images/products/gs-mini.jpg",      desc:"Compact scaled-down Grand Symphony with surprising volume. Travel-ready with a gig bag." },
  { id:9,  name:"Fender Player Jazz Bass",    brand:"Fender",     category:"bass",        price:48000,  badge:"",           availability:"in-store",  featured:true,  image:"images/products/jazz-bass.jpg",    desc:"Two Player Series single-coils and a slim neck — the go-to bass for every genre." },
  { id:10, name:"Yamaha TRBX304",             brand:"Yamaha",     category:"bass",        price:22500,  badge:"",           availability:"in-store",  featured:false, image:"images/products/trbx304.jpg",      desc:"Mahogany body, active 2-band EQ and a 5-way Performance EQ switch." },
  { id:11, name:"Cordoba C5 Classical",       brand:"Cordoba",    category:"classical",   price:18500,  badge:"",           availability:"online",    featured:false, image:"images/products/c5.jpg",           desc:"Solid cedar top nylon-string with a wide, comfortable neck for fingerstyle players." },
  { id:12, name:"Gretsch G2622 Streamliner",  brand:"Gretsch",    category:"electric",    price:42500,  badge:"Staff Pick", availability:"in-store",  featured:false, image:"images/products/g2622.jpg",        desc:"Centre-block semi-hollow with Broad'Tron pickups — jangle, twang and feedback control." },
  { id:13, name:"Ernie Ball Slinky (3-Pack)", brand:"Ernie Ball", category:"accessories", price:1450,   badge:"",           availability:"in-store",  featured:true,  image:"images/products/strings.jpg",      desc:"Regular Slinky 10-46 nickel wound strings. Three sets, the industry standard." },
  { id:14, name:"Fender Deluxe Gig Bag",      brand:"Fender",     category:"accessories", price:3500,   badge:"",           availability:"online",    featured:false, image:"images/products/gigbag.jpg",       desc:"25 mm padding, dual shoulder straps and an accessory pocket. Fits most electrics." }
];

const BRANDS = [
  { id:"fender",   name:"Fender",   est:1946, country:"USA",   models:"Stratocaster · Telecaster · Jazzmaster", image:"images/brands/fender.jpg",   blurb:"The name that defined electric guitar. Fender's bolt-on neck designs and single-coil pickups remain the gold standard for surf, blues, and country." },
  { id:"gibson",   name:"Gibson",   est:1902, country:"USA",   models:"Les Paul · SG · ES-335",                 image:"images/brands/gibson.jpg",   blurb:"Set-neck mahogany, carved maple tops and humbuckers. Gibson built the sound of rock and roll and still hand-finishes every instrument in Nashville." },
  { id:"martin",   name:"Martin",   est:1833, country:"USA",   models:"D-28 · 000-15M · Road Series",           image:"images/brands/martin.jpg",   blurb:"Nearly two centuries of acoustic craft. Martin invented the dreadnought and their X-bracing is still copied by every acoustic maker on earth." },
  { id:"taylor",   name:"Taylor",   est:1974, country:"USA",   models:"814ce · GS Mini · Academy Series",       image:"images/brands/taylor.jpg",   blurb:"Modern acoustic engineering at its finest — V-Class bracing, slim necks and famously consistent playability straight out of the case." },
  { id:"prs",      name:"PRS",      est:1985, country:"USA",   models:"Custom 24 · SE Series · McCarty",        image:"images/brands/prs.jpg",      blurb:"Paul Reed Smith blends Fender clarity with Gibson warmth. Flawless figured maple tops and the most stable tuning in the business." },
  { id:"yamaha",   name:"Yamaha",   est:1887, country:"Japan", models:"FG Series · Pacifica · SILENT Guitar",   image:"images/brands/yamaha.jpg",   blurb:"Unbeatable value and legendary quality control. From the FG800 to the Pacifica, Yamaha has started more players than any other brand." },
  { id:"gretsch",  name:"Gretsch",  est:1883, country:"USA",   models:"White Falcon · Duo Jet · Streamliner",   image:"images/brands/gretsch.jpg",  blurb:"That great Gretsch sound — hollow bodies, Filter'Tron pickups and Bigsby vibratos. Rockabilly twang with unmistakable style." },
  { id:"epiphone", name:"Epiphone", est:1946, country:"USA",   models:"Les Paul · SG · Casino",                 image:"images/brands/epiphone.jpg", blurb:"Gibson's sister company since 1957. Real Gibson designs, ProBucker pickups and prices that put an icon within reach of every player." }
];


/* ------------------------------------------------------------
   HELPERS
   ------------------------------------------------------------ */

const $  = (sel, ctx = document) => ctx.querySelector(sel);
const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));

const peso = n => "₱" + Number(n).toLocaleString("en-PH");

const AVAIL_LABEL = {
  "in-store":  "In store now",
  "online":    "Online only",
  "pre-order": "Pre-order"
};

/* runs a module without letting it crash the rest of the page */
function safe(name, fn) {
  try {
    fn();
  } catch (err) {
    console.error(`[Shan's Guitar] "${name}" failed:`, err);
  }
}

function productCardHTML(p) {
  return `
    <article class="product-card" data-id="${p.id}">
      <div class="product-media">
        ${p.badge ? `<span class="badge">${p.badge}</span>` : ""}
        <img src="${p.image}" alt="${p.name}" loading="lazy">
      </div>
      <div class="product-info">
        <p class="product-type">${p.category}</p>
        <h3>${p.name}</h3>
        <p class="price">${peso(p.price)}</p>
        <button class="btn-view" type="button" data-view="${p.id}">VIEW GUITAR</button>
      </div>
    </article>`;
}

/* shows the dashed "GUITAR PNG" box when a photo is missing */
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


/* ------------------------------------------------------------
   BOOT
   ------------------------------------------------------------ */

document.addEventListener("DOMContentLoaded", () => {
  console.log("[Shan's Guitar] JS loaded ✓  products:", PRODUCTS.length);

  safe("reveal",      initReveal);      // FIRST, so sections are never stuck invisible
  safe("header",      initHeader);
  safe("bestsellers", initBestsellers);
  safe("brands",      initBrands);
  safe("shop",        initShop);
  safe("modal",       initModal);
  safe("cart",        initCart);

  const yr = $("#year");
  if (yr) yr.textContent = new Date().getFullYear();
});


/* ------------------------------------------------------------
   1. HEADER / NAV
   ------------------------------------------------------------ */

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

  initScrollSpy();
}

function initScrollSpy() {
  const links = $$('#mainNav a[href^="#"]').filter(a => a.getAttribute("href").length > 1);

  const items = links
    .map(link => ({ link, el: document.querySelector(link.getAttribute("href")) }))
    .filter(item => item.el)
    .sort((a, b) => a.el.offsetTop - b.el.offsetTop);

  if (!items.length) return;

  function setActive() {
    const offset = parseInt(getComputedStyle(document.documentElement)
                     .getPropertyValue("--header-h")) || 88;
    const y = window.scrollY + offset + 12;

    let current = items[0];                 // default = HOME
    items.forEach(item => { if (item.el.offsetTop <= y) current = item; });

    if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 4) {
      current = items[items.length - 1];    // bottom of page = CONTACT
    }

    links.forEach(l => l.classList.remove("active"));
    current.link.classList.add("active");
  }

  setActive();
  window.addEventListener("scroll", setActive, { passive: true });
  window.addEventListener("resize", setActive);
  window.addEventListener("load", setActive);
}


/* ------------------------------------------------------------
   2. BESTSELLERS CAROUSEL
   ------------------------------------------------------------ */

function initBestsellers() {
  const track = $("#bestsellerTrack");
  const prev  = $("#bsPrev");
  const next  = $("#bsNext");
  if (!track) return;

  function render(filter = "all") {
    const list = PRODUCTS.filter(p =>
      p.featured && (filter === "all" || p.category === filter)
    );

    track.innerHTML = list.length
      ? list.map(productCardHTML).join("")
      : `<p style="padding:40px 4px;opacity:.7">No products in this category yet.</p>`;

    applyImageFallbacks(track);
    track.scrollTo({ left: 0 });
    updateArrows();
  }

  function step() {
    const card = track.querySelector(".product-card");
    return card ? card.offsetWidth + 24 : 320;
  }

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


/* ------------------------------------------------------------
   3. OUR BRANDS
   ------------------------------------------------------------ */

function initBrands() {
  const tabs    = $("#brandTabs");
  const feature = $("#brandFeature");
  const grid    = $("#brandGrid");
  if (!tabs || !feature || !grid) return;

  tabs.innerHTML = BRANDS.map((b, i) => `
    <button class="brand-pill ${i === 0 ? "active" : ""}" type="button"
            data-brand="${b.id}" role="tab" aria-selected="${i === 0}">
      ${b.name.toUpperCase()}
    </button>`).join("");

  grid.innerHTML = BRANDS.map((b, i) => `
    <div class="brand-tile ${i === 0 ? "active" : ""}" data-brand="${b.id}"
         tabindex="0" role="button" aria-label="View ${b.name}">
      <img src="${b.image}" alt="${b.name} guitars" loading="lazy">
      <div class="brand-tile-text">
        <strong>${b.name}</strong>
        <span>Est. ${b.est}</span>
      </div>
    </div>`).join("");

  applyImageFallbacks(grid);

  function show(id) {
    const b = BRANDS.find(x => x.id === id) || BRANDS[0];

    feature.classList.add("fading");
    setTimeout(() => {
      feature.innerHTML = `
        <div class="brand-feature-media">
          <img src="${b.image}" alt="${b.name} guitars">
        </div>
        <div class="brand-feature-body">
          <h3>${b.name}</h3>
          <p class="brand-est">Est. ${b.est} · ${b.country}</p>
          <p class="brand-models">${b.models}</p>
          <p class="brand-blurb">${b.blurb}</p>
          <a href="#shop" class="btn btn-gold" data-shop-brand="${b.name}">
            SHOP ${b.name.toUpperCase()}
          </a>
        </div>`;
      applyImageFallbacks(feature);
      feature.classList.remove("fading");
    }, 200);

    $$(".brand-pill").forEach(p => {
      const on = p.dataset.brand === id;
      p.classList.toggle("active", on);
      p.setAttribute("aria-selected", String(on));
    });
    $$(".brand-tile").forEach(t => t.classList.toggle("active", t.dataset.brand === id));
  }

  tabs.addEventListener("click", e => {
    const pill = e.target.closest(".brand-pill");
    if (pill) show(pill.dataset.brand);
  });

  grid.addEventListener("click", e => {
    const tile = e.target.closest(".brand-tile");
    if (!tile) return;
    show(tile.dataset.brand);
    feature.scrollIntoView({ behavior: "smooth", block: "center" });
  });

  grid.addEventListener("keydown", e => {
    if ((e.key === "Enter" || e.key === " ") && e.target.classList.contains("brand-tile")) {
      e.preventDefault();
      e.target.click();
    }
  });

  feature.addEventListener("click", e => {
    const link = e.target.closest("[data-shop-brand]");
    if (!link) return;
    const input = $("#searchInput");
    if (input) {
      input.value = link.dataset.shopBrand;
      input.dispatchEvent(new Event("input"));
    }
  });

  show(BRANDS[0].id);
}


/* ------------------------------------------------------------
   4. SHOP
   ------------------------------------------------------------ */

const PAGE_SIZE = 6;

function initShop() {
  const grid     = $("#shopGrid");
  const empty    = $("#emptyState");
  const countEl  = $("#resultCount");
  const priceEl  = $("#priceRange");
  const priceOut = $("#priceOut");
  const loadMore = $("#loadMore");
  if (!grid) return;

  const state = {
    category: "all",
    maxPrice: 200000,
    avail: [],
    sort: "featured",
    search: "",
    shown: PAGE_SIZE
  };

  const allCount = $("#countAll");
  if (allCount) allCount.textContent = PRODUCTS.length;
  $$("[data-count]").forEach(s => {
    s.textContent = PRODUCTS.filter(p => p.category === s.dataset.count).length;
  });

  function getList() {
    const list = PRODUCTS.filter(p =>
      (state.category === "all" || p.category === state.category) &&
      p.price <= state.maxPrice &&
      (state.avail.length === 0 || state.avail.includes(p.availability)) &&
      (p.name + " " + p.brand + " " + p.category).toLowerCase().includes(state.search)
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

    grid.innerHTML = visible.map(productCardHTML).join("");
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
        state.search = e.target.value.trim().toLowerCase();
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

  $$("[data-shop-cat]").forEach(a => a.addEventListener("click", () => {
    const radio = $(`input[name="category"][value="${a.dataset.shopCat}"]`);
    if (radio) {
      radio.checked = true;
      radio.dispatchEvent(new Event("change"));
    }
  }));

  render();
}


/* ------------------------------------------------------------
   5. QUICK-VIEW MODAL
   ------------------------------------------------------------ */

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

  const addBtn = $("#qvAdd");
  if (addBtn) addBtn.addEventListener("click", () => {
    if (currentProduct) {
      addToCart(currentProduct);
      closeModal();
    }
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
      <li><span>Brand</span><strong>${p.brand}</strong></li>
      <li><span>Category</span><strong>${p.category}</strong></li>
      <li><span>Availability</span><strong>${AVAIL_LABEL[p.availability] || "—"}</strong></li>
      <li><span>Item code</span><strong>SG-${String(p.id).padStart(4, "0")}</strong></li>`;

    modal.hidden = false;
    document.body.classList.add("no-scroll");
  }

  function closeModal() {
    modal.hidden = true;
    document.body.classList.remove("no-scroll");
  }
}


/* ------------------------------------------------------------
   6. CART
   ------------------------------------------------------------ */

let cart = [];

function initCart() {
  try {
    cart = JSON.parse(localStorage.getItem("sg-cart")) || [];
  } catch {
    cart = [];
  }
  updateCartCount();

  const btn = $("#cartBtn");
  if (btn) btn.addEventListener("click", () => {
    if (!cart.length) return toast("Your cart is empty");
    const total = cart.reduce((s, i) => s + i.price * i.qty, 0);
    toast(`${cart.length} item(s) · ${peso(total)} total`);
  });
}

function addToCart(p) {
  const found = cart.find(i => i.id === p.id);
  found ? found.qty++ : cart.push({ id: p.id, name: p.name, price: p.price, qty: 1 });
  localStorage.setItem("sg-cart", JSON.stringify(cart));
  updateCartCount();
  toast(`${p.name} added to cart`);
}

function updateCartCount() {
  const el = $("#cartCount");
  if (!el) return;
  const total = cart.reduce((s, i) => s + i.qty, 0);
  el.textContent = total;
  el.classList.toggle("is-empty", total === 0);
}


/* ------------------------------------------------------------
   7. TOAST + REVEAL
   ------------------------------------------------------------ */

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

  // safety net: if anything goes wrong, show everything after 2s
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