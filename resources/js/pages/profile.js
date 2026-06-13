import { ensureApiToken } from "../shared/token.js";

if (document.getElementById("bookCount")) {
    const loading = document.getElementById("loading");
    const profile = document.getElementById("profile");
    const errorBox = document.getElementById("error");
    const errorText = document.getElementById("errorText");

    function showError(message) {
        if (!errorBox) return;
        if (errorText) errorText.textContent = message;
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
            const status = u.status || "Active";
            const userSubscribe = u.user_subscribe;
            const userId = u.id || "-";

            // Header card
            const avatarInitial = document.getElementById("avatarInitial");
            const elName = document.getElementById("name");
            const elEmail = document.getElementById("email");
            const roleBadge = document.getElementById("roleBadge");

            if (avatarInitial) avatarInitial.textContent = (name || "?").charAt(0).toUpperCase();
            if (elName) elName.textContent = name;
            if (elEmail) elEmail.textContent = email;
            if (roleBadge) {
                roleBadge.textContent = role;
                // Color the role badge
                const roleLower = role.toLowerCase().replace(/\s+/g, '_');
                if (roleLower === 'super_admin') {
                    roleBadge.className = 'inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700';
                } else if (roleLower === 'admin') {
                    roleBadge.className = 'inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700';
                } else if (roleLower === 'author') {
                    roleBadge.className = 'inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700';
                }
            }

            // Stats
            const statusValue = document.getElementById("statusValue");
            const subscriptionValue = document.getElementById("subscriptionValue");
            if (statusValue) statusValue.textContent = status;
            if (subscriptionValue) subscriptionValue.textContent = userSubscribe ? "Active" : "None";

            // Detail section
            const detailName = document.getElementById("detailName");
            const detailEmail = document.getElementById("detailEmail");
            const detailRole = document.getElementById("detailRole");
            const detailStatus = document.getElementById("detailStatus");
            const detailSubscription = document.getElementById("detailSubscription");
            const detailId = document.getElementById("detailId");

            if (detailName) detailName.textContent = name;
            if (detailEmail) detailEmail.textContent = email;
            if (detailRole) detailRole.textContent = role;
            if (detailStatus) detailStatus.textContent = status;
            if (detailSubscription) detailSubscription.textContent = userSubscribe ? "Subscribed" : "Not subscribed";
            if (detailId) detailId.textContent = userId;

            // Fetch books and permissions in parallel
            const [booksData, permissionsData] = await Promise.all([
                fetchWithAuth("/api/v1/books", token),
                fetchWithAuth("/api/v1/permissions", token),
            ]);

            const bookCount = booksData?.data?.total ?? booksData?.total ?? "-";
            const permissionsList = permissionsData?.data?.data || permissionsData?.data || [];
            const permissionsCount = permissionsData?.data?.total ?? permissionsList.length ?? "-";

            const elBookCount = document.getElementById("bookCount");
            const elPermissionsCount = document.getElementById("permissionsCount");

            if (elBookCount) elBookCount.textContent = bookCount;
            if (elPermissionsCount) elPermissionsCount.textContent = permissionsCount;

            // Permission badges
            const badges = document.getElementById("permissionBadges");
            if (badges) {
                if (Array.isArray(permissionsList) && permissionsList.length > 0) {
                    badges.innerHTML = permissionsList.map(p => {
                        const displayName = p.display_name || p.name || "Unknown";
                        return `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 border border-green-100 text-green-700">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            ${displayName}
                        </span>`;
                    }).join("");
                } else {
                    badges.innerHTML = '<span class="text-sm text-gray-400">No permissions assigned</span>';
                }
            }

            if (loading) loading.classList.add("hidden");
            if (profile) profile.classList.remove("hidden");
        } catch (e) {
            showError("Network error. Please try again.");
        }
    })();
}
