// Main app script loaded across the authenticated layout (kept intentionally small).
// Dashboard UI wiring lives here; page-specific data fetching lives in resources/js/home.js.

function initAppShell() {
    // No-op placeholder: currently the layout is hydrated by resources/js/home.js.
    // This file exists because the build expects it and you requested fixes here only.
    // If you later add more dashboard pages, route-specific logic can be added here.
}

if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", initAppShell);
} else {
    initAppShell();
}
