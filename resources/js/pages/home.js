import { ensureApiToken, isProbablyToken } from "../shared/token.js";

if (document.getElementById("id") && !document.getElementById("bookCount")) {
    const loading = document.getElementById("loading");
    const profile = document.getElementById("profile");
    const errorBox = document.getElementById("error");

    const elName = document.getElementById("name");
    const elEmail = document.getElementById("email");
    const elRole = document.getElementById("role");
    const elId = document.getElementById("id");

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove("hidden");
    }

    (async () => {
        try {
            const token = await ensureApiToken();
            if (!token || !isProbablyToken(token)) {
                window.location.href = "/login";
                return;
            }

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
            const name = u.name || "-";
            const email = u.email || "-";
            const role = u.role || "N/A";
            const id = u.id ?? "-";

            if (elName) elName.textContent = name;
            if (elEmail) elEmail.textContent = email;
            if (elRole) elRole.textContent = role;
            if (elId) elId.textContent = id;

            if (loading) loading.classList.add("hidden");
            if (profile) profile.classList.remove("hidden");
        } catch (e) {
            showError("Network error. Please try again.");
        }
    })();
}
