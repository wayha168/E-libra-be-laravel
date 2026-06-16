import { ensureApiToken } from "../shared/token.js";
import { fetchJson } from "../shared/api.js";

window.showError = function (message) {
    const errorBox = document.getElementById("error");
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.remove("hidden");
};

window.doLogout = async function () {
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
    const shouldOpen =
        typeof force === "boolean"
            ? force
            : accountMenu.classList.contains("hidden");
    accountMenu.classList.toggle("hidden", !shouldOpen);
}

async function hydrateHeader() {
    if (document.querySelector('[data-header-hydrated="server"]')) {
        return;
    }

    const token = await ensureApiToken();
    if (!token) return;

    try {
        const { res, data } = await fetchJson("/api/v1/me", { token });
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
        const accountRoleEl = document.getElementById("accountRole");
        const accountPermissionsEl = document.getElementById("accountPermissions");
        const accountInitialEl = document.getElementById("accountInitial");

        if (accountName) accountName.textContent = name;
        if (accountEmail) accountEmail.textContent = email;
        if (accountEmailMenu) accountEmailMenu.textContent = email;
        if (accountRoleEl) accountRoleEl.textContent = u?.role || u?.display_role || "-";
        if (accountInitialEl) {
            accountInitialEl.textContent =
                name && String(name).trim().length
                    ? String(name).trim().slice(0, 1).toUpperCase()
                    : "U";
        }

        if (accountPermissionsEl) {
            accountPermissionsEl.innerHTML =
                '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-black/5 text-gray-700">-</span>';
        }

        try {
            const { res: profileRes, data: profileData } = await fetchJson("/api/v1/user/profile", { token });
            if (profileRes.ok && profileData?.permissions && accountPermissionsEl) {
                accountPermissionsEl.innerHTML = profileData.permissions.length
                    ? profileData.permissions
                          .map(
                              (p) =>
                                  `<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-black/5 text-gray-700">${p.display_name || p.name}</span>`,
                          )
                          .join("")
                    : '<span class="inline-flex items-center px-2 py-1 rounded-full text-xs bg-black/5 text-gray-700">-</span>';
            }
        } catch (e2) {
            console.error("Permissions hydration failed", e2);
        }
    } catch (e) {
        console.error("Header hydration failed", e);
    }
}

export function initLayout() {
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutMenuBtn = document.getElementById("logoutMenuBtn");
    if (logoutBtn) logoutBtn.addEventListener("click", window.doLogout);
    if (logoutMenuBtn) logoutMenuBtn.addEventListener("click", window.doLogout);

    const dropdownBtn = document.getElementById("accountDropdownBtn");
    if (dropdownBtn) {
        dropdownBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            toggleDropdown();
        });
    }

    document.addEventListener("click", function () {
        const accountMenu = document.getElementById("accountMenu");
        if (accountMenu && !accountMenu.classList.contains("hidden")) {
            toggleDropdown(false);
        }
    });

    hydrateHeader();
}
