const THEME_KEY = "elibra-theme";
const MODE_KEY = "elibra-mode";
const LANG_KEY = "elibra-lang";

const THEMES = ["emerald", "ocean", "sunset", "violet"];
const MODES = ["light", "dark"];
const LANGS = ["en", "km"];

const I18N = {
    en: {
        dashboard: "Dashboard",
        overview: "Overview",
        categories: "Categories",
        authors: "Authors",
        books: "Books",
        my_books: "My Books",
        promotions: "Promotions",
        my_income: "My Income",
        book_sales: "Book Sales",
        purchases: "Purchases",
        images: "Images",
        account_settings: "Account Settings",
        bank_details: "Bank Details",
        aba_payway: "ABA PayWay",
        notifications: "Notifications",
        user_activity: "User Activity",
        users: "Users",
        all_users: "All Users",
        add_user: "Add User",
        permissions: "Permissions",
        all_permissions: "All Permissions",
        add_permission: "Add Permission",
        logout: "Logout",
        profile: "Profile",
        signed_in_as: "Signed in as",
        open_notifications: "Open notifications",
        open_account: "Open account menu",
        open_preferences: "Appearance & language",
        appearance: "Appearance",
        theme_color: "Theme color",
        display_mode: "Display",
        language: "Language",
        light: "Light",
        dark: "Dark",
        english: "English",
        khmer: "ខ្មែរ",
        notifications_title: "Notifications",
        notifications_sub: "Orders, recommendations & alerts",
        unread: "Unread",
        read: "Read",
        mark_all_read: "Mark all read",
        close_notifications: "Close notifications",
        see_more: "See more",
        view_all_notifications: "View all notifications",
        close_sidebar: "Close sidebar",
    },
    km: {
        dashboard: "ផ្ទាំងគ្រប់គ្រង",
        overview: "ទិដ្ឋភាពទូទៅ",
        categories: "ប្រភេទ",
        authors: "អ្នកនិពន្ធ",
        books: "សៀវភៅ",
        my_books: "សៀវភៅរបស់ខ្ញុំ",
        promotions: "ការផ្សព្វផ្សាយ",
        my_income: "ចំណូលរបស់ខ្ញុំ",
        book_sales: "ការលក់សៀវភៅ",
        purchases: "ការទិញ",
        images: "រូបភាព",
        account_settings: "ការកំណត់គណនី",
        bank_details: "ព័ត៌មានធនាគារ",
        aba_payway: "ABA PayWay",
        notifications: "ការជូនដំណឹង",
        user_activity: "សកម្មភាពអ្នកប្រើ",
        users: "អ្នកប្រើប្រាស់",
        all_users: "អ្នកប្រើទាំងអស់",
        add_user: "បន្ថែមអ្នកប្រើ",
        permissions: "សិទ្ធិ",
        all_permissions: "សិទ្ធិទាំងអស់",
        add_permission: "បន្ថែមសិទ្ធិ",
        logout: "ចាកចេញ",
        profile: "ប្រវត្តិរូប",
        signed_in_as: "បានចូលជា",
        open_notifications: "បើកការជូនដំណឹង",
        open_account: "បើកម៉ឺនុយគណនី",
        open_preferences: "រូបរាង និងភាសា",
        appearance: "រូបរាង",
        theme_color: "ពណ៌ស្បែក",
        display_mode: "របៀបបង្ហាញ",
        language: "ភាសា",
        light: "ភ្លឺ",
        dark: "ងងឹត",
        english: "English",
        khmer: "ខ្មែរ",
        notifications_title: "ការជូនដំណឹង",
        notifications_sub: "ការបញ្ជាទិញ ការណែនាំ និងការជូនដំណឹង",
        unread: "មិនទាន់អាន",
        read: "បានអាន",
        mark_all_read: "សម្គាល់ថាបានអានទាំងអស់",
        close_notifications: "បិទការជូនដំណឹង",
        see_more: "មើលបន្ថែម",
        view_all_notifications: "មើលការជូនដំណឹងទាំងអស់",
        close_sidebar: "បិទរបារចំហៀង",
    },
};

function safeGet(key, fallback) {
    try {
        return localStorage.getItem(key) || fallback;
    } catch (_) {
        return fallback;
    }
}

function safeSet(key, value) {
    try {
        localStorage.setItem(key, value);
    } catch (_) {}
}

export function getPrefs() {
    const rawTheme = safeGet(THEME_KEY, "emerald");
    const rawMode = safeGet(MODE_KEY, "light");
    const rawLang = safeGet(LANG_KEY, "en");
    return {
        theme: THEMES.includes(rawTheme) ? rawTheme : "emerald",
        mode: MODES.includes(rawMode) ? rawMode : "light",
        lang: LANGS.includes(rawLang) ? rawLang : "en",
    };
}

export function applyTheme(theme) {
    const next = THEMES.includes(theme) ? theme : "emerald";
    document.documentElement.setAttribute("data-theme", next);
    safeSet(THEME_KEY, next);
    syncPrefsUi();
}

export function applyMode(mode) {
    const next = MODES.includes(mode) ? mode : "light";
    document.documentElement.setAttribute("data-mode", next);
    safeSet(MODE_KEY, next);
    syncPrefsUi();
}

export function t(key) {
    const { lang } = getPrefs();
    return I18N[lang]?.[key] ?? I18N.en[key] ?? key;
}

export function applyLanguage(lang) {
    const next = LANGS.includes(lang) ? lang : "en";
    document.documentElement.setAttribute("data-lang", next);
    document.documentElement.setAttribute("lang", next === "km" ? "km" : "en");
    safeSet(LANG_KEY, next);
    translateDom();
    syncPrefsUi();
}

function translateDom() {
    document.querySelectorAll("[data-i18n]").forEach((el) => {
        const key = el.getAttribute("data-i18n");
        if (!key) return;
        const value = t(key);
        if (el.hasAttribute("data-i18n-attr")) {
            const attr = el.getAttribute("data-i18n-attr");
            if (attr) el.setAttribute(attr, value);
        } else {
            el.textContent = value;
        }
    });
}

function syncPrefsUi() {
    const { theme, mode, lang } = getPrefs();

    document.querySelectorAll("[data-theme-pick]").forEach((btn) => {
        btn.classList.toggle("active", btn.getAttribute("data-theme-pick") === theme);
        btn.setAttribute("aria-pressed", btn.getAttribute("data-theme-pick") === theme ? "true" : "false");
    });

    document.querySelectorAll("[data-mode-pick]").forEach((btn) => {
        btn.classList.toggle("active", btn.getAttribute("data-mode-pick") === mode);
        btn.setAttribute("aria-pressed", btn.getAttribute("data-mode-pick") === mode ? "true" : "false");
    });

    document.querySelectorAll("[data-lang-pick]").forEach((btn) => {
        btn.classList.toggle("active", btn.getAttribute("data-lang-pick") === lang);
        btn.setAttribute("aria-pressed", btn.getAttribute("data-lang-pick") === lang ? "true" : "false");
    });

    const label = document.getElementById("prefsLangLabel");
    if (label) label.textContent = lang === "km" ? "ខ្មែរ" : "EN";
}

function togglePrefsMenu(force) {
    const menu = document.getElementById("prefsMenu");
    const btn = document.getElementById("prefsDropdownBtn");
    if (!menu || !btn) return;

    const shouldOpen =
        typeof force === "boolean" ? force : menu.classList.contains("hidden");

    menu.classList.toggle("hidden", !shouldOpen);
    btn.setAttribute("aria-expanded", shouldOpen ? "true" : "false");
}

export function initPreferences() {
    const { theme, mode, lang } = getPrefs();
    applyTheme(theme);
    applyMode(mode);
    applyLanguage(lang);

    const btn = document.getElementById("prefsDropdownBtn");
    const menu = document.getElementById("prefsMenu");
    if (!btn || !menu) return;

    btn.addEventListener("click", (e) => {
        e.stopPropagation();
        const accountMenu = document.getElementById("accountMenu");
        if (accountMenu) accountMenu.classList.add("hidden");
        togglePrefsMenu();
    });

    menu.addEventListener("click", (e) => e.stopPropagation());

    menu.querySelectorAll("[data-theme-pick]").forEach((el) => {
        el.addEventListener("click", () => applyTheme(el.getAttribute("data-theme-pick")));
    });

    menu.querySelectorAll("[data-mode-pick]").forEach((el) => {
        el.addEventListener("click", () => applyMode(el.getAttribute("data-mode-pick")));
    });

    menu.querySelectorAll("[data-lang-pick]").forEach((el) => {
        el.addEventListener("click", () => applyLanguage(el.getAttribute("data-lang-pick")));
    });

    document.addEventListener("click", () => togglePrefsMenu(false));
    document.addEventListener("keydown", (e) => {
        if (e.key === "Escape") togglePrefsMenu(false);
    });
}
