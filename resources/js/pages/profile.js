import { ensureApiToken } from "../shared/token.js";

if (document.getElementById("bookCount")) {
    const loading = document.getElementById("loading");
    const profile = document.getElementById("profile");
    const errorBox = document.getElementById("error");

    const elName = document.getElementById("name");
    const elEmail = document.getElementById("email");
    const elRole = document.getElementById("role");

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove("hidden");
    }

    async function fetchWithAuth(path, token) {
        const headers = { Accept: "application/json" };
        if (token) {
            headers.Authorization = "Bearer " + token;
        }

        const res = await fetch(path, { headers });
        if (!res.ok) {
            const data = await res.json().catch(() => null);
            const msg = data?.message || data?.error || "Request failed (" + res.status + ")";
            showError(msg);
            return null;
        }

        return res.json();
    }

    (async () => {
        try {
            const token = await ensureApiToken();
            if (!token) {
                window.location.href = "/login";
                return;
            }

            const meData = await fetchWithAuth("/api/v1/me", token);
            if (!meData) return;

            const u = meData.data || {};
            const name = u.name || "-";
            const email = u.email || "-";
            const role = u.role || "N/A";

            const avatarInitial = document.getElementById("avatarInitial");

            if (avatarInitial) avatarInitial.textContent = (name || "?").charAt(0).toUpperCase();
            if (elName) elName.textContent = name;
            if (elEmail) elEmail.textContent = email;
            if (elRole) elRole.textContent = role;

            const [booksData, permissionsData] = await Promise.all([
                fetchWithAuth("/api/v1/books", token),
                fetchWithAuth("/api/v1/permissions", token),
            ]);

            const bookCount = booksData?.data?.total ?? booksData?.total ?? "-";
            const permissionsCount = permissionsData?.data?.total ?? permissionsData?.total ?? "-";
            const elBookCount = document.getElementById("bookCount");
            const elPermissionsCount = document.getElementById("permissionsCount");

            if (elBookCount) elBookCount.textContent = bookCount;
            if (elPermissionsCount) elPermissionsCount.textContent = permissionsCount;

            if (loading) loading.classList.add("hidden");
            if (profile) profile.classList.remove("hidden");
        } catch (e) {
            showError("Network error. Please try again.");
        }
    })();
}
