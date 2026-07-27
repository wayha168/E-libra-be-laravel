const BOOK_ICON = `<svg width="36" height="36" viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.8"/></svg>`;

const NAV_ICON = {
  home: `<svg viewBox="0 0 24 24" fill="none"><path d="m3 10.5 9-7 9 7" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.5 9.5V20a1 1 0 0 0 1 1H10v-6h4v6h3.5a1 1 0 0 0 1-1V9.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>`,
  explore: `<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1.8"/><path d="m16 8-2.5 6.5L7 17l2.5-6.5L16 8z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/></svg>`,
  library: `<svg viewBox="0 0 24 24" fill="none"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z" stroke="currentColor" stroke-width="1.8"/></svg>`,
  profile: `<svg viewBox="0 0 24 24" fill="none"><circle cx="12" cy="8" r="3.5" stroke="currentColor" stroke-width="1.8"/><path d="M5.5 19.5a6.5 6.5 0 0 1 13 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>`,
};

const books = [
  { id: "quiet-forest", title: "The Quiet Forest", author: "Maya Chen", category: "Fiction", tone: "", rating: "4.8", pages: 248, desc: "A lyrical story about finding stillness in a noisy world. Follow Lena as she rediscovers wonder in the woods behind her school." },
  { id: "atoms-stars", title: "Atoms & Stars", author: "Leo Park", category: "Science", tone: "tone2", rating: "4.6", pages: 312, desc: "An accessible journey from particles to galaxies — perfect for curious high-school minds." },
  { id: "river-stories", title: "River Stories", author: "A. Rivera", category: "Fiction", tone: "tone3", rating: "4.7", pages: 198, desc: "Interwoven short stories set along one river across seasons, cultures, and generations." },
  { id: "green-math", title: "Green Math", author: "Sofia Nguyen", category: "Science", tone: "tone4", rating: "4.5", pages: 220, desc: "Math problems rooted in nature, gardens, and everyday school life." },
  { id: "ancient-roads", title: "Ancient Roads", author: "James Okoro", category: "History", tone: "tone5", rating: "4.9", pages: 276, desc: "Trade routes that shaped civilizations — maps, myths, and memorable travelers." },
  { id: "tiny-heroes", title: "Tiny Heroes", author: "Kim Lee", category: "Kids", tone: "", rating: "4.8", pages: 96, desc: "Brave little creatures solve big playground problems with kindness." },
];

const schools = {
  "green-valley": { name: "Green Valley High", meta: "1,240 books · Digital library open", books: "1240", status: "Open now" },
  riverside: { name: "Riverside Academy", meta: "860 books · Closes 5 PM", books: "860", status: "Closes 5 PM" },
  oakwood: { name: "Oakwood School", meta: "2,100 books · Digital library open", books: "2100", status: "Open now" },
};

const TAB_SCREENS = new Set(["home", "explore", "library", "profile"]);
const THEMES = ["forest", "ocean", "sunset", "midnight"];
const THEME_LABELS = { forest: "Forest", ocean: "Ocean", sunset: "Sunset", midnight: "Midnight" };
const THEME_KEY = "elibra-mobile-theme";

let historyStack = ["login"];
let currentBookId = books[0].id;
let currentSchoolId = "green-valley";

function $(sel, root = document) { return root.querySelector(sel); }
function $all(sel, root = document) { return [...root.querySelectorAll(sel)]; }

function applyTheme(theme) {
  const next = THEMES.includes(theme) ? theme : "forest";
  document.body.dataset.theme = next;
  try { localStorage.setItem(THEME_KEY, next); } catch (_) {}
  $all("[data-theme-pick]").forEach((btn) => {
    const on = btn.dataset.themePick === next;
    btn.classList.toggle("active", on);
    btn.setAttribute("aria-selected", on ? "true" : "false");
  });
}

function initTheme() {
  let saved = "forest";
  try { saved = localStorage.getItem(THEME_KEY) || "forest"; } catch (_) {}
  applyTheme(saved);
}

function showToast(msg) {
  const el = $("#toast");
  el.textContent = msg;
  el.classList.add("show");
  clearTimeout(showToast._t);
  showToast._t = setTimeout(() => el.classList.remove("show"), 1800);
}

function bookCard(book) {
  return `<button class="book-card" type="button" data-go="book" data-book="${book.id}">
    <div class="cover ${book.tone}">${BOOK_ICON}</div>
    <h4>${book.title}</h4>
    <p>${book.author}</p>
  </button>`;
}

function listItem(book, meta) {
  return `<button class="list-item" type="button" data-go="book" data-book="${book.id}">
    <div class="mini-cover">${BOOK_ICON}</div>
    <div>
      <h4>${book.title}</h4>
      <p>${book.author}</p>
      ${meta ? `<div class="meta">${meta}</div>` : ""}
    </div>
  </button>`;
}

function renderBottomNav() {
  $all(".bottom-nav").forEach((nav) => {
    const active = nav.dataset.active;
    nav.innerHTML = ["home", "explore", "library", "profile"].map((key) => {
      const label = key[0].toUpperCase() + key.slice(1);
      return `<button class="nav-item ${key === active ? "active" : ""}" type="button" data-go="${key}">
        ${NAV_ICON[key]}
        <span>${label}</span>
      </button>`;
    }).join("");
  });
}

function renderCollections() {
  $("#home-books").innerHTML = books.slice(0, 4).map(bookCard).join("");
  $("#explore-grid").innerHTML = books.map(bookCard).join("");
  $("#library-list").innerHTML = [
    listItem(books[0], "Due in 5 days"),
    listItem(books[1], "Due in 12 days"),
    listItem(books[4], "Due tomorrow"),
  ].join("");
  $("#library-saved").innerHTML = books.slice(2, 5).map(bookCard).join("");
  $("#book-related").innerHTML = books.filter((b) => b.id !== currentBookId).slice(0, 4).map(bookCard).join("");
  $("#search-results").innerHTML = books.map((b) => listItem(b, b.category)).join("");
  $("#school-books-list").innerHTML = books.slice(0, 4).map((b) => listItem(b, "Available")).join("");
}

function updateBookScreen() {
  const book = books.find((b) => b.id === currentBookId) || books[0];
  $("#book-title").textContent = book.title;
  $("#book-author").textContent = book.author;
  $("#book-rating").textContent = `★ ${book.rating} · ${book.category} · ${book.pages} pages`;
  $("#book-desc").textContent = book.desc;
  $("#book-cover").className = `detail-cover ${book.tone}`;
  $("#book-related").innerHTML = books.filter((b) => b.id !== book.id).slice(0, 4).map(bookCard).join("");
}

function updateSchoolScreen() {
  const school = schools[currentSchoolId] || schools["green-valley"];
  $("#school-name").textContent = school.name;
  $("#school-meta").textContent = school.meta;
  $("#school-books").textContent = school.books;
  $("#school-hero .badge").textContent = school.status;
}

function go(screen, { push = true } = {}) {
  const target = document.querySelector(`[data-screen="${screen}"]`);
  if (!target) return;

  $all(".screen").forEach((s) => s.classList.remove("active"));
  target.classList.add("active");

  if (push) {
    const last = historyStack[historyStack.length - 1];
    if (last !== screen) historyStack.push(screen);
  }

  if (screen === "book") updateBookScreen();
  if (screen === "school") updateSchoolScreen();
  if (screen === "search") setTimeout(() => $("#search-input")?.focus(), 50);

  // keep bottom-nav highlight in sync when landing on tabs
  if (TAB_SCREENS.has(screen)) {
    $all(".bottom-nav").forEach((nav) => {
      nav.dataset.active = screen;
    });
    renderBottomNav();
  }

  const scroll = target.querySelector(".scroll");
  if (scroll) scroll.scrollTop = 0;
}

function back() {
  if (historyStack.length > 1) historyStack.pop();
  const prev = historyStack[historyStack.length - 1] || "home";
  go(prev, { push: false });
}

function wireChips(containerId) {
  const root = document.getElementById(containerId);
  if (!root) return;
  root.addEventListener("click", (e) => {
    const chip = e.target.closest(".chip");
    if (!chip) return;
    $all(".chip", root).forEach((c) => c.classList.remove("active"));
    chip.classList.add("active");
  });
}

function wireCarouselDots() {
  const carousel = $("#school-carousel");
  const dots = $all("#school-dots span");
  if (!carousel) return;
  carousel.addEventListener("scroll", () => {
    const i = Math.round(carousel.scrollLeft / (carousel.firstElementChild?.offsetWidth || 300));
    dots.forEach((d, idx) => d.classList.toggle("on", idx === i));
  });
}

function filterSearch(q) {
  const query = q.trim().toLowerCase();
  const filtered = !query
    ? books
    : books.filter((b) =>
        b.title.toLowerCase().includes(query) ||
        b.author.toLowerCase().includes(query) ||
        b.category.toLowerCase().includes(query)
      );
  $("#search-results").innerHTML = filtered.length
    ? filtered.map((b) => listItem(b, b.category)).join("")
    : `<div class="empty"><div class="logo">${BOOK_ICON}</div><p>No books match “${q}”</p></div>`;
}

document.addEventListener("click", (e) => {
  const themeBtn = e.target.closest("[data-theme-pick]");
  if (themeBtn) {
    const theme = themeBtn.dataset.themePick;
    applyTheme(theme);
    showToast(`${THEME_LABELS[theme] || theme} theme on`);
    return;
  }

  const backBtn = e.target.closest("[data-back]");
  if (backBtn) {
    back();
    return;
  }

  const toastBtn = e.target.closest("[data-toast]");
  if (toastBtn && !toastBtn.dataset.go) {
    showToast(toastBtn.dataset.toast);
  }

  const goBtn = e.target.closest("[data-go]");
  if (!goBtn) return;

  if (goBtn.dataset.book) currentBookId = goBtn.dataset.book;
  if (goBtn.dataset.school) currentSchoolId = goBtn.dataset.school;

  if (goBtn.dataset.toast) showToast(goBtn.dataset.toast);
  go(goBtn.dataset.go);
});

$("#search-input")?.addEventListener("input", (e) => filterSearch(e.target.value));

initTheme();
renderBottomNav();
renderCollections();
wireChips("home-chips");
wireChips("explore-chips");
wireCarouselDots();
go("login", { push: false });
