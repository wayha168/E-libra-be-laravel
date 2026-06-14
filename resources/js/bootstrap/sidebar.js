const STORAGE_KEY = "elibra_aside_collapsed";

const SVG_COLLAPSE = `<svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v12a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18V6ZM13.5 6.75A2.25 2.25 0 0 1 15.75 4.5h2.25A2.25 2.25 0 0 1 20.25 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-2.25a2.25 2.25 0 0 1-2.25-2.25V6.75Z" /></svg>`;

/* Panel with sidebar bar — open menu when collapsed */
const SVG_EXPAND = `<svg class="w-5 h-5 text-gray-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true"><rect x="3.75" y="4.5" width="16.5" height="15" rx="1.5" stroke="currentColor" fill="none"/><path stroke-linecap="round" d="M9 4.5v15"/></svg>`;

export function initSidebar() {
    const aside = document.getElementById("dashboardAside");
    const toggleBtn = document.getElementById("asideToggleBtn");
    const layout = document.getElementById("dashboardLayout");

    if (!aside || !toggleBtn) return;

    function setIcon(collapsed) {
        toggleBtn.innerHTML = collapsed ? SVG_EXPAND : SVG_COLLAPSE;
    }

    function setCollapsed(collapsed) {
        layout?.classList.toggle("aside-collapsed", collapsed);
        aside.classList.toggle("aside-is-collapsed", collapsed);
        toggleBtn.setAttribute("aria-expanded", collapsed ? "false" : "true");
        toggleBtn.title = collapsed ? "Open sidebar" : "Close sidebar";
        toggleBtn.setAttribute("aria-label", collapsed ? "Open sidebar" : "Close sidebar");
        setIcon(collapsed);
        localStorage.setItem(STORAGE_KEY, collapsed ? "1" : "0");
    }

    const saved = localStorage.getItem(STORAGE_KEY) === "1";
    setCollapsed(saved);

    toggleBtn.addEventListener("click", () => {
        const collapsed = aside.classList.contains("aside-is-collapsed");
        setCollapsed(!collapsed);
    });
}
