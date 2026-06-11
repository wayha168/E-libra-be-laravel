import { ensureApiToken } from "./shared/token.js";

window.ensureApiToken = ensureApiToken;

window.showError = function(message) {
    const errorBox = document.getElementById("error");
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.remove("hidden");
};

window.doLogout = async function() {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;

    try {
        await fetch("/auth/logout", {
            method: "POST",
            headers: {
                Accept: "application/json",
                "X-CSRF-TOKEN": csrf || "",
            },
            credentials: "same-origin",
        });
    } catch (e) {
        console.error("Logout request failed", e);
    }

    sessionStorage.removeItem("api_token");
    localStorage.removeItem("api_token");
    window.location.href = "/login";
};

function toggleDropdown(force) {
    const accountMenu = document.getElementById("accountMenu");
    if (!accountMenu) return;
    const shouldOpen = typeof force === "boolean" ? force : accountMenu.classList.contains("hidden");
    accountMenu.classList.toggle("hidden", !shouldOpen);
}

document.addEventListener("DOMContentLoaded", function() {
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutMenuBtn = document.getElementById("logoutMenuBtn");
    if (logoutBtn) logoutBtn.addEventListener("click", window.doLogout);
    if (logoutMenuBtn) logoutMenuBtn.addEventListener("click", window.doLogout);

    const dropdownBtn = document.getElementById("accountDropdownBtn");
    if (dropdownBtn) {
        dropdownBtn.addEventListener("click", function(e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }

    document.addEventListener("click", function() {
        const accountMenu = document.getElementById("accountMenu");
        if (accountMenu && !accountMenu.classList.contains("hidden")) {
            toggleDropdown(false);
        }
    });

    (async function hydrateHeader() {
        const token = await ensureApiToken();
        if (!token) return;

        try {
            const res = await fetch("/api/v1/me", {
                headers: {
                    Accept: "application/json",
                    Authorization: "Bearer " + token,
                },
            });
            const data = await res.json().catch(() => null);
            if (!res.ok) {
                if ([401, 403].includes(res.status)) {
                    sessionStorage.removeItem("api_token");
                    localStorage.removeItem("api_token");
                    window.location.href = "/login";
                }
                return;
            }

            const u = data?.data || {};
            const name = u?.name || "-";
            const email = u?.email || "-";

            const accountName = document.getElementById("accountName");
            const accountEmail = document.getElementById("accountEmail");
            const accountEmailMenu = document.getElementById("accountEmailMenu");

            if (accountName) accountName.textContent = name;
            if (accountEmail) accountEmail.textContent = email;
            if (accountEmailMenu) accountEmailMenu.textContent = email;
        } catch (e) {
            console.error("Header hydration failed", e);
        }
    })();
});

import.meta.glob("./pages/**/*.js", { eager: true });
import.meta.glob("../css/pages/**/*.css", { eager: true });
