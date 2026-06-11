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

            const [booksData, categoriesData, imagesData] = await Promise.all([
                fetchWithAuth("/api/v1/books", token),
                fetchWithAuth("/api/v1/categories", token),
                fetchWithAuth("/api/v1/images", token),
            ]);

            const bookCount = booksData?.data?.total ?? booksData?.total ?? "-";
            const categoryCount = categoriesData?.data?.total ?? categoriesData?.total ?? "-";
            const imageCount = imagesData?.data?.total ?? imagesData?.total ?? "-";

            const elBookCount = document.getElementById("bookCount");
            const elCategoryCount = document.getElementById("categoryCount");
            const elImageCount = document.getElementById("imageCount");

            if (elBookCount) elBookCount.textContent = bookCount;
            if (elCategoryCount) elCategoryCount.textContent = categoryCount;
            if (elImageCount) elImageCount.textContent = imageCount;

            if (loading) loading.classList.add("hidden");
            if (profile) profile.classList.remove("hidden");
        } catch (e) {
            showError("Network error. Please try again.");
        }
    })();
}
