/* ============================================================
   ART TERRE CREATIONS — script.js
   Interactivity: nav, filters, cart, story toggles, forms
   ============================================================ */

"use strict";

/* ---------- Helpers ---------- */
const $ = (selector, scope = document) => scope.querySelector(selector);
const $$ = (selector, scope = document) => [...scope.querySelectorAll(selector)];

/* ============================================================
   1. MOBILE NAVIGATION (hamburger)
   ============================================================ */
const hamburger = $("#hamburger");
const navMenu = $("#nav-menu");

function closeMenu() {
  if (!hamburger || !navMenu) return;
  hamburger.classList.remove("active");
  navMenu.classList.remove("open");
  hamburger.setAttribute("aria-expanded", "false");
}

if (hamburger && navMenu) {
  hamburger.addEventListener("click", () => {
    const isOpen = navMenu.classList.toggle("open");
    hamburger.classList.toggle("active", isOpen);
    hamburger.setAttribute("aria-expanded", String(isOpen));
  });

  $$(".nav-link").forEach((link) =>
    link.addEventListener("click", closeMenu)
  );

  document.addEventListener("click", (e) => {
    if (
      navMenu.classList.contains("open") &&
      !navMenu.contains(e.target) &&
      !hamburger.contains(e.target)
    ) {
      closeMenu();
    }
  });
}

/* ============================================================
   1b. ACCOUNT DROPDOWN (avatar chip + three-line indicator)
   Click the "S Hi, Site" chip to open Manage orders / My orders /
   Sign out (plus Sell artwork for artist accounts).
   ============================================================ */
const accountMenu = $("#account-menu");
const accountToggle = $("#account-toggle");

function closeAccountMenu() {
  if (!accountMenu || !accountToggle) return;
  accountMenu.classList.remove("open");
  accountToggle.setAttribute("aria-expanded", "false");
}

if (accountMenu && accountToggle) {
  accountToggle.addEventListener("click", (e) => {
    e.stopPropagation();
    const isOpen = accountMenu.classList.toggle("open");
    accountToggle.setAttribute("aria-expanded", String(isOpen));
  });

  document.addEventListener("click", (e) => {
    if (
      accountMenu.classList.contains("open") &&
      !accountMenu.contains(e.target)
    ) {
      closeAccountMenu();
    }
  });

  document.addEventListener("keydown", (e) => {
    if (e.key === "Escape") {
      closeAccountMenu();
      closeMenu();
    }
  });
}

/* ============================================================
   2. ACTIVE NAV LINK ON SCROLL (scroll-spy)
   ============================================================ */
const sections = $$("main section[id], footer[id]");
const navLinks = $$(".nav-link");

const spy = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const id = entry.target.id;
        navLinks.forEach((link) => {
          const href = link.getAttribute("href") || "";
          /* Works for both "#artists" (landing page) and
             "index.php#artists" (sub-pages) */
          link.classList.toggle(
            "active",
            href === `#${id}` || href.endsWith(`#${id}`)
          );
        });
      }
    });
  },
  { rootMargin: "-45% 0px -50% 0px" }
);

sections.forEach((sec) => spy.observe(sec));

/* ============================================================
   3. ARTWORK CATEGORY FILTERS (All / Painting / Photography)
   ============================================================ */
const filterBtns = $$(".filter-btn");
const artworkCards = $$(".artwork-card");

filterBtns.forEach((btn) => {
  btn.addEventListener("click", () => {
    filterBtns.forEach((b) => b.classList.remove("active"));
    btn.classList.add("active");

    const filter = btn.dataset.filter;

    artworkCards.forEach((card) => {
      const match = filter === "all" || card.dataset.category === filter;
      card.classList.toggle("hide", !match);
    });
  });
});

/* ============================================================
   4. LIKE BUTTONS (artists + artworks)
   ============================================================ */
/* Likes persist in localStorage, keyed by the button's aria-label
   (e.g. "Like Alexander The Great"), so a heart stays filled after
   a reload or across pages. */
const LIKES_KEY = "art-terre-likes";

let likedItems = [];
try {
  likedItems = JSON.parse(localStorage.getItem(LIKES_KEY) || "[]");
} catch { likedItems = []; }
const likedSet = new Set(Array.isArray(likedItems) ? likedItems : []);

const saveLikes = () =>
  localStorage.setItem(LIKES_KEY, JSON.stringify([...likedSet]));

$$(".like-btn").forEach((btn) => {
  const key = btn.getAttribute("aria-label") || "";
  const apply = () => {
    const liked = likedSet.has(key);
    btn.classList.toggle("liked", liked);
    btn.setAttribute("aria-pressed", String(liked));
  };

  apply();

  btn.addEventListener("click", () => {
    likedSet.has(key) ? likedSet.delete(key) : likedSet.add(key);
    saveLikes();
    apply();
  });
});

/* ============================================================
   5. STORY TOGGLES ("Story." pill on artwork cards)
   ============================================================ */
$$(".story-toggle").forEach((toggle) => {
  toggle.addEventListener("click", () => {
    const card = toggle.closest(".artwork-card");
    const story = $(".artwork-story", card);
    const expanded = toggle.getAttribute("aria-expanded") === "true";

    /* Collapsed: 3-line scrollable preview (default CSS).
       Expanded: whole story, no limit — this card only. */
    story.classList.toggle("expanded", !expanded);
    toggle.setAttribute("aria-expanded", String(!expanded));
    toggle.textContent = expanded ? "Story." : "Close ✕";
  });
});

/* ============================================================
   6. SHOPPING CART (localStorage — shared by every page)
   Items: { artwork_id (0 for catalogue placeholders), title, artist, price, qty }
   artwork_id pins DB pieces to their seller so checkout.php can store
   order_items.artist_id — ONLY that seller (or an admin) may later
   update the order's status; the buyer stays view-only.
   ============================================================ */
const CART_KEY = "art-terre-cart";
const cartCount = $("#cart-count");

const money = (n) => "$" + n.toFixed(2);

function loadCart() {
  try {
    const parsed = JSON.parse(localStorage.getItem(CART_KEY));
    return Array.isArray(parsed) ? parsed : [];
  } catch {
    return [];
  }
}

const saveCart = (items) => {
  localStorage.setItem(CART_KEY, JSON.stringify(items));
  /* Push the change to the saved cart for this account (section 6c) */
  if (typeof queueCartSave === "function") queueCartSave();
};

const cartQuantity = (items) => items.reduce((sum, item) => sum + item.qty, 0);
const cartSubtotal = (items) =>
  items.reduce((sum, item) => sum + item.price * item.qty, 0);

function updateCartCount() {
  if (cartCount) cartCount.textContent = cartQuantity(loadCart());
}

/* Read title / artist / price + artwork_id straight off the artwork card */
function readCardInfo(card) {
  return {
    artwork_id: Number(card?.dataset?.artworkId || 0) || 0,
    title: ($(".artwork-title", card)?.textContent || "Artwork").trim(),
    artist: ($(".artwork-artist strong", card)?.textContent || "").trim(),
    price:
      parseFloat(
        ($(".artwork-price", card)?.textContent || "0").replace(/[^0-9.]/g, "")
      ) || 0,
  };
}

function cartKey(item) {
  return item.artwork_id > 0 ? "id:" + item.artwork_id : "t:" + item.title;
}

function addToCart(card) {
  const items = loadCart();
  const info = readCardInfo(card);

  const existing = items.find((item) => cartKey(item) === cartKey(info));
  if (existing) existing.qty += 1;
  else items.push({ ...info, qty: 1 });

  saveCart(items);
  updateCartCount();
}

/* Cart icon on each artwork card */
$$(".card-cart").forEach((btn) => {
  btn.addEventListener("click", () => {
    const card = btn.closest(".artwork-card");
    if (!card) return;

    addToCart(card);

    /* "Added" pop on the button + header badge bounce */
    btn.classList.add("added");
    setTimeout(() => btn.classList.remove("added"), 900);
    cartCount?.animate(
      [
        { transform: "scale(1)" },
        { transform: "scale(1.3)" },
        { transform: "scale(1)" },
      ],
      { duration: 300, easing: "ease" }
    );
  });
});

/* "Checkout" on a card: add the piece, then go straight to checkout */
$$(".card-checkout").forEach((btn) => {
  btn.addEventListener("click", () => {
    const card = btn.closest(".artwork-card");
    if (card) addToCart(card);
    window.location.href = "/checkout";
  });
});

updateCartCount();

/* ============================================================
   6c. CART PERSISTENCE (cart survives sign-out / sign-in)
   While signed in, every cart change is pushed to the
   cart_items table via pages/cart-sync.php. On sign-out the
   cart is saved once more and the browser cart is emptied;
   on sign-in the saved cart is merged back into localStorage.
   ============================================================ */
const isLoggedIn = !!document.getElementById("account-menu");
let cartSaveTimer = null;
const CART_SYNC_URL = (window.ART_BASE || "") + "/cart-sync";

function queueCartSave() {
  if (!isLoggedIn) return;
  if (cartSaveTimer) clearTimeout(cartSaveTimer);
  cartSaveTimer = setTimeout(saveCartToDB, 600);
}

function saveCartToDB() {
  const body =
    "action=save&items=" + encodeURIComponent(JSON.stringify(loadCart()));
  fetch(CART_SYNC_URL, {
    method: "POST",
    credentials: "same-origin",
    keepalive: true, /* survives the sign-out redirect */
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: body,
  }).catch(() => {});
}

function loadCartFromDB() {
  fetch(CART_SYNC_URL, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: "action=load",
  })
    .then((r) => (r.ok ? r.json() : { items: [] }))
    .then((data) => {
      const dbItems = data && Array.isArray(data.items) ? data.items : [];
      if (!dbItems.length) return;
      const local = loadCart();
      const map = new Map(local.map((i) => [cartKey(i), i]));
      dbItems.forEach((dbItem) => {
        const key = cartKey(dbItem);
        if (map.has(key)) {
          map.get(key).qty = Math.max(map.get(key).qty, dbItem.qty || 1);
        } else {
          local.push(dbItem);
          map.set(key, dbItem);
        }
      });
      saveCart(local);
      updateCartCount();
      renderCart();
    })
    .catch(() => {});
}

/* Sign out: push the cart to the account, then empty the browser cart */
const signoutLink = document.querySelector(".account-signout");
if (signoutLink) {
  signoutLink.addEventListener("click", () => {
    if (isLoggedIn) {
      if (cartSaveTimer) { clearTimeout(cartSaveTimer); cartSaveTimer = null; }
      saveCartToDB(); /* keepalive delivers it during the redirect */
      localStorage.removeItem(CART_KEY); /* leaves nothing in the browser */
      updateCartCount();
    }
  });
}

/* Signed in: restore the saved cart on page load */
if (isLoggedIn) loadCartFromDB();
/* ============================================================
   7. FORM VALIDATION (sign-in, newsletter, contact)
   ============================================================ */
const EMAIL_RE = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

function setMsg(el, text, type) {
  if (!el) return;
  el.textContent = text;
  el.classList.remove("success", "error");
  if (type) el.classList.add(type);
}

/* --- 7a. Login page (login.php): tabs + client-side checks --- */
const authTabs = $$(".auth-tab");

if (authTabs.length) {
  const authForms = {
    signin: $("#form-signin"),
    register: $("#form-register"),
  };

  const switchTab = (tab) => {
    authTabs.forEach((t) => {
      const active = t.dataset.tab === tab;
      t.classList.toggle("active", active);
      t.setAttribute("aria-selected", String(active));
    });
    Object.entries(authForms).forEach(([key, form]) => {
      if (form) form.hidden = key !== tab;
    });
  };

  authTabs.forEach((t) =>
    t.addEventListener("click", () => switchTab(t.dataset.tab))
  );

  /* "Create an account" / "Sign in" inline links */
  $$("[data-switch]").forEach((a) =>
    a.addEventListener("click", (e) => {
      e.preventDefault();
      switchTab(a.dataset.switch);
    })
  );

  /* Client-side checks that mirror the server rules */
  const regForm = authForms.register;
  regForm?.addEventListener("submit", (e) => {
    const err = $("#reg-error");
    const pass = $("#reg-password").value;
    const pass2 = $("#reg-password2").value;

    if (pass.length < 8) {
      e.preventDefault();
      err.hidden = false;
      err.textContent = "Password must be at least 8 characters.";
      return;
    }
    if (pass !== pass2) {
      e.preventDefault();
      err.hidden = false;
      err.textContent = "Passwords do not match.";
      return;
    }
    err.hidden = true;
    err.textContent = "";
  });

  /* Lockout countdown: Login button is unpressable while locked out */
  const siSubmit = $("#si-submit");
  const lockUntil = Number(siSubmit?.dataset.lockUntil || 0);

  if (siSubmit && lockUntil > Date.now() / 1000) {
    const siError = $(".auth-error");
    const originalLabel = siSubmit.textContent;
    const fmt = (s) => `${Math.floor(s / 60)}:${String(s % 60).padStart(2, "0")}`;

    const tick = () => {
      const left = Math.ceil(lockUntil - Date.now() / 1000);

      if (left <= 0) {
        siSubmit.disabled = false;
        siSubmit.textContent = originalLabel;
        if (siError) siError.textContent = "";
        return;
      }

      siSubmit.textContent = `Locked — retry in ${fmt(left)}`;
      setTimeout(tick, 1000);
    };

    siSubmit.disabled = true;
    tick();
  }
}

/* --- 7b. Newsletter (Join Our Creative Community) ---
   Only exists on the landing page — skip on the other pages. */
const newsForm = $("#newsletter-form");
const newsMsg = $("#newsletter-msg");

if (newsForm) {
  newsForm.addEventListener("submit", (e) => {
    e.preventDefault();
    const email = $("#newsletter-email").value.trim();

    if (!EMAIL_RE.test(email)) {
      setMsg(newsMsg, "Please enter a valid email address.", "error");
      return;
    }

    setMsg(newsMsg, "You're in! Watch your inbox for new artworks. 🎨", "success");
    newsForm.reset();
  });
}

/* --- 7c. Contact form (footer) — posts to contact.php, saved for admins --- */
const contactForm = $("#contact-form");
const contactMsg = $("#contact-msg");

if (contactForm) {
  contactForm.addEventListener("submit", (e) => {
    const name = $("#c-name").value.trim();
    const email = $("#c-email").value.trim();
    const message = $("#c-message").value.trim();

    /* Client-side checks mirror the server rules; a valid form
       submits naturally so the message is stored for the admin. */
    if (name.length < 2) {
      e.preventDefault();
      setMsg(contactMsg, "Please enter your name.", "error");
      return;
    }
    if (!EMAIL_RE.test(email)) {
      e.preventDefault();
      setMsg(contactMsg, "Please enter a valid email address.", "error");
      return;
    }
    if (message.length < 10) {
      e.preventDefault();
      setMsg(contactMsg, "Message should be at least 10 characters.", "error");
      return;
    }

    contactMsg.textContent = "";
  });
}

/* ============================================================
   7d. CART PAGE (cart.php)
   ============================================================ */
const cartList = $("#cart-items");
const cartEmpty = $("#cart-empty");
const cartSubtotalEl = $("#cart-subtotal");
const cartTotalEl = $("#cart-total");
const toCheckoutBtn = $("#to-checkout");

function renderCart() {
  if (!cartList) return;

  const items = loadCart();
  cartList.replaceChildren();

  if (items.length === 0) {
    if (cartEmpty) cartEmpty.hidden = false;
    if (toCheckoutBtn) toCheckoutBtn.setAttribute("aria-disabled", "true");
    if (cartSubtotalEl) cartSubtotalEl.textContent = money(0);
    if (cartTotalEl) cartTotalEl.textContent = money(0);
    return;
  }

  if (cartEmpty) cartEmpty.hidden = true;
  if (toCheckoutBtn) toCheckoutBtn.removeAttribute("aria-disabled");

  items.forEach((item, index) => {
    const row = document.createElement("article");
    row.className = "cart-row";

    const info = document.createElement("div");
    info.className = "cart-row-info";
    const title = document.createElement("h3");
    title.className = "cart-row-title";
    title.textContent = item.title;
    const artist = document.createElement("p");
    artist.className = "cart-row-artist";
    artist.textContent = item.artist ? "by " + item.artist : "";
    info.append(title, artist);

    const qty = document.createElement("div");
    qty.className = "cart-qty";
    const dec = document.createElement("button");
    dec.type = "button";
    dec.textContent = "−";
    dec.dataset.action = "dec";
    dec.dataset.index = index;
    dec.setAttribute("aria-label", "Decrease quantity of " + item.title);
    const num = document.createElement("span");
    num.className = "cart-qty-num";
    num.textContent = item.qty;
    const inc = document.createElement("button");
    inc.type = "button";
    inc.textContent = "+";
    inc.dataset.action = "inc";
    inc.dataset.index = index;
    inc.setAttribute("aria-label", "Increase quantity of " + item.title);
    qty.append(dec, num, inc);

    const price = document.createElement("span");
    price.className = "cart-row-price";
    price.textContent = money(item.price * item.qty);

    const remove = document.createElement("button");
    remove.type = "button";
    remove.className = "cart-remove";
    remove.textContent = "×";
    remove.dataset.action = "remove";
    remove.dataset.index = index;
    remove.setAttribute("aria-label", "Remove " + item.title + " from cart");

    row.append(info, qty, price, remove);
    cartList.append(row);
  });

  const subtotal = cartSubtotal(items);
  if (cartSubtotalEl) cartSubtotalEl.textContent = money(subtotal);
  if (cartTotalEl) cartTotalEl.textContent = money(subtotal);
}

if (cartList) {
  renderCart();

  /* Quantity +/- and remove (event delegation) */
  cartList.addEventListener("click", (e) => {
    const btn = e.target.closest("[data-action]");
    if (!btn) return;

    const items = loadCart();
    const index = Number(btn.dataset.index);
    const item = items[index];
    if (!item) return;

    if (btn.dataset.action === "inc") item.qty += 1;
    if (btn.dataset.action === "dec") item.qty -= 1;
    if (btn.dataset.action === "remove" || item.qty <= 0) {
      items.splice(index, 1);
    }

    saveCart(items);
    updateCartCount();
    renderCart();
  });

  /* Don't go to checkout with an empty cart */
  if (toCheckoutBtn) {
    toCheckoutBtn.addEventListener("click", (e) => {
      if (loadCart().length === 0) e.preventDefault();
    });
  }
}

/* ============================================================
   7e. CHECKOUT PAGE (checkout.php — demo, no real payment)
   ============================================================ */
const checkoutForm = $("#checkout-form");
const checkoutMsg = $("#checkout-msg");

if (checkoutForm) {
  const checkoutItems = $("#checkout-items");
  const checkoutTotal = $("#checkout-total");
  const cartJsonField = $("#cart-json");
  const cardFields = $("#card-fields");
  const paymentOptions = $$("[data-payment-option]");
  const CARD_RE = /^[0-9]{13,19}$/;

  /* Card details only apply when paying by card */
  const syncPaymentUI = () => {
    const method =
      paymentOptions.find((r) => r.checked)?.value || "cod";
    if (cardFields) cardFields.hidden = method !== "card";
    $$("#card-fields input").forEach((input) => {
      input.required = method === "card";
    });
  };
  paymentOptions.forEach((radio) =>
    radio.addEventListener("change", syncPaymentUI)
  );
  syncPaymentUI();

  const renderCheckoutSummary = () => {
    const items = loadCart();
    checkoutItems.replaceChildren();

    if (items.length === 0) {
      const p = document.createElement("p");
      p.className = "checkout-empty";
      p.textContent = "Your cart is empty — add an artwork first.";
      checkoutItems.append(p);
    } else {
      items.forEach((item) => {
        const line = document.createElement("div");
        line.className = "checkout-line";
        const name = document.createElement("span");
        name.textContent = item.title + " × " + item.qty;
        name.title = item.title;
        const price = document.createElement("span");
        price.textContent = money(item.price * item.qty);
        line.append(name, price);
        checkoutItems.append(line);
      });
    }

    checkoutTotal.textContent = money(cartSubtotal(items));
  };

  renderCheckoutSummary();

  /* Placing the order posts to the server, which stores it and
     redirects to order-pending.php (status: Pending). */
  checkoutForm.addEventListener("submit", (e) => {
    const items = loadCart();

    if (items.length === 0) {
      e.preventDefault();
      setMsg(checkoutMsg, "Your cart is empty — add an artwork first.", "error");
      return;
    }

    /* Send the cart contents with the order */
    if (cartJsonField) cartJsonField.value = JSON.stringify(items);

    /* Client-side checks that mirror the server rules */
    const name = $("#co-name").value.trim();
    const email = $("#co-email").value.trim();
    const address = $("#co-address").value.trim();
    const method =
      paymentOptions.find((r) => r.checked)?.value || "cod";

    if (name.length < 2) {
      e.preventDefault();
      setMsg(checkoutMsg, "Please enter your full name.", "error");
      return;
    }
    if (!EMAIL_RE.test(email)) {
      e.preventDefault();
      setMsg(checkoutMsg, "Please enter a valid email address.", "error");
      return;
    }
    if (address.length < 5) {
      e.preventDefault();
      setMsg(checkoutMsg, "Please enter your delivery address.", "error");
      return;
    }
    if (method === "card") {
      const cardNumber = $("#co-card").value.replace(/\s+/g, "");
      const expiry = $("#co-expiry").value.trim();
      const cvv = $("#co-cvv").value.trim();

      if (!CARD_RE.test(cardNumber)) {
        e.preventDefault();
        setMsg(checkoutMsg, "Please enter a valid card number (13–19 digits).", "error");
        return;
      }
      if (!/^(0[1-9]|1[0-2])\/\d{2}$/.test(expiry)) {
        e.preventDefault();
        setMsg(checkoutMsg, "Expiry must be in MM/YY format.", "error");
        return;
      }
      if (!/^\d{3,4}$/.test(cvv)) {
        e.preventDefault();
        setMsg(checkoutMsg, "CVV must be 3 or 4 digits.", "error");
        return;
      }
    }

    /* Valid — let the form submit naturally */
    checkoutMsg.textContent = "";
  });

  /* Clear the message as soon as the user types again */
  checkoutForm.addEventListener("input", () => {
    checkoutMsg.textContent = "";
    checkoutMsg.classList.remove("success", "error");
  });
}

/* ============================================================
   8. SCROLL REVEAL ANIMATIONS
   ============================================================ */
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("visible");
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12 }
);

$$(".reveal").forEach((el) => revealObserver.observe(el));

/* ============================================================
   9. BACK TO TOP
   ============================================================ */
const toTop = $("#to-top");

if (toTop) {
  window.addEventListener(
    "scroll",
    () => toTop.classList.toggle("show", window.scrollY > 500),
    { passive: true }
  );

  toTop.addEventListener("click", () => {
    window.scrollTo({ top: 0, behavior: "smooth" });
  });
}

/* ============================================================
   NEXT STEPS (prepared for future work)
   ------------------------------------------------------------
   - Replace .art CSS placeholders with real <img> artwork files
   - Replace artist initials with real portrait photos
   - Connect forms to a backend / email service
   - Connect the checkout form to a real payment backend
   - Move cart & likes from localStorage into the user accounts
   ============================================================ */