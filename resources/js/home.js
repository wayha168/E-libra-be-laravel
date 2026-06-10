const token =
    sessionStorage.getItem("api_token") || localStorage.getItem("api_token");

const loading = document.getElementById("loading");
const profile = document.getElementById("profile");
const errorBox = document.getElementById("error");

const elName = document.getElementById("name");
const elEmail = document.getElementById("email");
const elRole = document.getElementById("role");
const elId = document.getElementById("id");

const logoutBtn = document.getElementById("logoutBtn");

// Header dropdown
const accountName = document.getElementById("accountName");
const accountNameShort = document.getElementById("accountNameShort");
const accountEmail = document.getElementById("accountEmail");
const dropdownBtn = document.getElementById("accountDropdownBtn");
const accountMenu = document.getElementById("accountMenu");
const logoutMenuBtn = document.getElementById("logoutMenuBtn");
const accountEmailMenu = document.getElementById("accountEmailMenu");

function showError(message) {
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.remove("hidden");
}

function doLogout() {
    sessionStorage.removeItem("api_token");
    localStorage.removeItem("api_token");
    window.location.href = "/login";
}

if (logoutBtn) logoutBtn.addEventListener("click", doLogout);
if (logoutMenuBtn) logoutMenuBtn.addEventListener("click", doLogout);

function toggleDropdown(force) {
    if (!accountMenu) return;
    const shouldOpen =
        typeof force === "boolean"
            ? force
            : accountMenu.classList.contains("hidden");
    accountMenu.classList.toggle("hidden", !shouldOpen);
}

if (dropdownBtn) {
    dropdownBtn.addEventListener("click", (e) => {
        e.stopPropagation();
        toggleDropdown();
    });
}

document.addEventListener("click", () => {
    if (accountMenu && !accountMenu.classList.contains("hidden")) {
        toggleDropdown(false);
    }
});

if (!token) {
    window.location.href = "/login";
}

(async () => {
    try {
        if (!token) return;

        const res = await fetch("/api/me", {
            headers: {
                Accept: "application/json",
                Authorization: "Bearer " + token,
            },
        });

        const data = await res.json().catch(() => null);

        if (!res.ok) {
            const status = res.status;
            const msg = data?.message || data?.error || "Unauthorized";
            // Helpful debug for why token auth fails
            console.error("/api/me failed", { status, msg, hasToken: !!token });
            showError(`Unauthorized (${status}): ${msg}`);
            sessionStorage.removeItem("api_token");
            window.location.href = "/login";
            return;
        }

        const u = data?.data;
        const name = u?.name || "-";
        const email = u?.email || "-";
        const role = u?.role || "N/A";
        const id = u?.id ?? "-";

        if (elName) elName.textContent = name;
        if (elEmail) elEmail.textContent = email;
        if (elRole) elRole.textContent = role;
        if (elId) elId.textContent = id;

        if (accountName) accountName.textContent = name;
        if (accountNameShort) accountNameShort.textContent = name;
        if (accountEmail) accountEmail.textContent = email;
        if (accountEmailMenu) accountEmailMenu.textContent = email;

        if (loading) loading.classList.add("hidden");
        if (profile) profile.classList.remove("hidden");
    } catch (e) {
        showError("Network error. Please try again.");
    }
})();
