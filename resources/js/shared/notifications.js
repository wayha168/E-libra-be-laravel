import { ensureApiToken } from "./token.js";

import { fetchJson, postJson } from "./api.js";

import { initEcho } from "./echo.js";



let toastContainer = null;

let unreadCount = 0;

let apiToken = null;



function escapeHtml(value) {

    return String(value ?? "")

        .replace(/&/g, "&amp;")

        .replace(/</g, "&lt;")

        .replace(/>/g, "&gt;")

        .replace(/"/g, "&quot;");

}



function ensureToastContainer() {

    if (toastContainer) return toastContainer;

    toastContainer = document.createElement("div");

    toastContainer.id = "notificationToasts";

    toastContainer.className = "fixed top-4 right-4 z-[60] flex flex-col gap-2 max-w-sm";

    document.body.appendChild(toastContainer);

    return toastContainer;

}



function showToast(notification, onOpen) {

    const box = ensureToastContainer();

    const el = document.createElement("button");

    el.type = "button";

    el.className = "text-left rounded-xl border border-gray-200 bg-white shadow-lg px-4 py-3 text-sm hover:bg-gray-50 transition w-full";

    el.innerHTML = `<div class="font-semibold text-gray-900">${escapeHtml(notification.title || "Notification")}</div>

        <div class="text-gray-600 mt-1">${escapeHtml(notification.body || "")}</div>`;

    el.addEventListener("click", () => {

        if (onOpen) onOpen();

        el.remove();

    });

    box.appendChild(el);

    setTimeout(() => el.remove(), 8000);

}



function bookLink(notification) {

    const bookId = notification?.data?.book_id;

    if (!bookId) return "";

    return `<a href="/dashboard/books/${bookId}" class="inline-block mt-2 text-xs text-blue-600 hover:underline">View book →</a>`;

}



function renderNotificationItem(n) {

    const unread = !n.read_at;

    const isRec = String(n.type || "").startsWith("recommendation.");

    return `<div class="px-4 py-3 ${unread ? "bg-blue-50/40" : ""}" data-notification-id="${n.id}">

        <div class="flex items-start justify-between gap-2">

            <div class="min-w-0">

                <div class="flex items-center gap-2">

                    <div class="font-medium text-gray-900">${escapeHtml(n.title)}</div>

                    ${isRec ? '<span class="inline-flex px-1.5 py-0.5 rounded text-[10px] bg-purple-50 text-purple-700">Recommended</span>' : ""}

                </div>

                <div class="text-sm text-gray-600 mt-0.5">${escapeHtml(n.body || "")}</div>

                ${bookLink(n)}

                <div class="text-xs text-gray-400 mt-1">${n.created_at ? new Date(n.created_at).toLocaleString() : ""}</div>

            </div>

            ${unread ? `<button type="button" data-mark-read="${n.id}" class="text-xs text-blue-600 hover:underline shrink-0">Mark read</button>` : ""}

        </div>

    </div>`;

}



function updateBadge(count) {

    unreadCount = count;

    const badge = document.getElementById("notificationBadge");

    if (!badge) return;

    if (count > 0) {

        badge.textContent = count > 99 ? "99+" : String(count);

        badge.classList.remove("hidden");

    } else {

        badge.classList.add("hidden");

    }

}



function bindMarkReadButtons(container, token, reloadFn) {

    container?.querySelectorAll("[data-mark-read]").forEach((btn) => {

        btn.addEventListener("click", async (e) => {

            e.stopPropagation();

            await postJson(token, `/api/v1/notifications/${btn.dataset.markRead}/read`);

            reloadFn();

        });

    });

}



async function loadNotifications(token, listEl, { page = 1, append = false } = {}) {

    const { res, data } = await fetchJson(`/api/v1/notifications?page=${page}`, { token, silent: true });

    if (!res.ok || !listEl) return null;



    const paginator = data?.data;

    const items = paginator?.data || [];

    updateBadge(data?.unread_count ?? 0);



    const html = items.map(renderNotificationItem).join("");

    if (append && items.length) {

        listEl.insertAdjacentHTML("beforeend", html);

    } else {

        listEl.innerHTML = items.length

            ? html

            : '<div class="px-4 py-8 text-center text-gray-400 text-sm">No notifications yet.</div>';

    }



    bindMarkReadButtons(listEl, token, () => loadNotifications(token, listEl, { page: 1, append: false }));



    return paginator;

}



function updateSeeMoreButton(btn, paginator) {

    if (!btn || !paginator) return;

    const hasMore = paginator.current_page < paginator.last_page;

    btn.classList.toggle("hidden", !hasMore);

    btn.dataset.nextPage = hasMore ? String(paginator.current_page + 1) : "";

}



function openNotificationSidebar() {

    const sidebar = document.getElementById("notificationSidebar");

    const backdrop = document.getElementById("notificationSidebarBackdrop");

    if (!sidebar || !backdrop) return;

    sidebar.classList.add("open");

    backdrop.classList.remove("hidden");

    backdrop.classList.add("open");

    document.body.classList.add("overflow-hidden");

}



function closeNotificationSidebar() {

    const sidebar = document.getElementById("notificationSidebar");

    const backdrop = document.getElementById("notificationSidebarBackdrop");

    if (!sidebar || !backdrop) return;

    sidebar.classList.remove("open");

    backdrop.classList.remove("open");

    document.body.classList.remove("overflow-hidden");

    window.setTimeout(() => {
        if (!backdrop.classList.contains("open")) {
            backdrop.classList.add("hidden");
        }
    }, 250);

}



function prependNotification(listEl, notification) {

    if (!listEl) return;

    const empty = listEl.querySelector(".text-gray-400");

    if (empty) empty.remove();

    listEl.insertAdjacentHTML("afterbegin", renderNotificationItem(notification));

    bindMarkReadButtons(listEl, apiToken, () => loadNotifications(apiToken, listEl, { page: 1, append: false }));

}



export async function initNotifications() {

    const bell = document.getElementById("notificationBell");

    const sidebarList = document.getElementById("notificationSidebarList");

    const pageList = document.getElementById("notificationsList");

    const markAllBtn = document.getElementById("markAllReadBtn");

    const markAllSidebarBtn = document.getElementById("markAllReadSidebarBtn");

    const seeMoreSidebarBtn = document.getElementById("notificationSeeMoreBtn");

    const seeMorePageBtn = document.getElementById("notificationPageSeeMoreBtn");

    const closeBtn = document.getElementById("notificationSidebarClose");

    const backdrop = document.getElementById("notificationSidebarBackdrop");



    if (!bell && !pageList && !sidebarList) return;



    const token = await ensureApiToken();

    if (!token) return;

    apiToken = token;



    const reloadAll = async () => {

        if (sidebarList) {

            const paginator = await loadNotifications(token, sidebarList, { page: 1, append: false });

            updateSeeMoreButton(seeMoreSidebarBtn, paginator);

        }

        if (pageList) {

            const paginator = await loadNotifications(token, pageList, { page: 1, append: false });

            updateSeeMoreButton(seeMorePageBtn, paginator);

        }

    };



    if (sidebarList) {

        const paginator = await loadNotifications(token, sidebarList, { page: 1, append: false });

        updateSeeMoreButton(seeMoreSidebarBtn, paginator);

    }

    if (pageList) {

        const paginator = await loadNotifications(token, pageList, { page: 1, append: false });

        updateSeeMoreButton(seeMorePageBtn, paginator);

    }



    seeMoreSidebarBtn?.addEventListener("click", async () => {

        const nextPage = Number(seeMoreSidebarBtn.dataset.nextPage || 0);

        if (!nextPage || !sidebarList) return;

        seeMoreSidebarBtn.disabled = true;

        const paginator = await loadNotifications(token, sidebarList, { page: nextPage, append: true });

        updateSeeMoreButton(seeMoreSidebarBtn, paginator);

        seeMoreSidebarBtn.disabled = false;

    });



    seeMorePageBtn?.addEventListener("click", async () => {

        const nextPage = Number(seeMorePageBtn.dataset.nextPage || 0);

        if (!nextPage || !pageList) return;

        seeMorePageBtn.disabled = true;

        const paginator = await loadNotifications(token, pageList, { page: nextPage, append: true });

        updateSeeMoreButton(seeMorePageBtn, paginator);

        seeMorePageBtn.disabled = false;

    });



    const markAll = async () => {

        await postJson(token, "/api/v1/notifications/read-all");

        await reloadAll();

    };



    if (markAllBtn) markAllBtn.addEventListener("click", markAll);

    if (markAllSidebarBtn) markAllSidebarBtn.addEventListener("click", markAll);



    if (bell) {

        bell.addEventListener("click", async (e) => {

            e.stopPropagation();

            openNotificationSidebar();

            if (sidebarList) {

                const paginator = await loadNotifications(token, sidebarList, { page: 1, append: false });

                updateSeeMoreButton(seeMoreSidebarBtn, paginator);

            }

        });

    }



    closeBtn?.addEventListener("click", closeNotificationSidebar);

    backdrop?.addEventListener("click", closeNotificationSidebar);

    document.addEventListener("keydown", (e) => {

        if (e.key === "Escape") closeNotificationSidebar();

    });



    const echo = initEcho(token);

    if (!echo) return;



    try {

        const me = await fetchJson("/api/v1/me", { token });

        const userId = me.data?.data?.id;

        if (!userId) return;



        echo.private("notifications." + userId).listen(".notification.created", (e) => {

            if (!e.notification) return;

            showToast(e.notification, openNotificationSidebar);

            updateBadge(unreadCount + 1);



            if (sidebarList) {

                prependNotification(sidebarList, e.notification);

            }

            if (pageList) {

                prependNotification(pageList, e.notification);

            }

        });

    } catch {

        /* ignore */

    }

}



export async function initActivityLive() {

    if (!document.getElementById("activityPage")) return;



    const token = await ensureApiToken();

    if (!token) return;



    const list = document.getElementById("activityList");

    const badge = document.getElementById("activityLiveBadge");

    const echo = initEcho(token);

    if (!echo || !list) return;



    if (badge) badge.classList.remove("hidden");



    echo.private("dashboard.activities").listen(".activity.recorded", (e) => {

        const a = e.activity;

        if (!a?.id || list.querySelector(`[data-activity-id="${a.id}"]`)) return;



        const empty = list.querySelector(".text-gray-400");

        if (empty) empty.remove();



        list.insertAdjacentHTML("afterbegin", `<div class="px-4 py-3 flex items-start justify-between gap-3 bg-green-50/30" data-activity-id="${a.id}">

            <div>

                <div class="font-medium text-gray-900">${escapeHtml(a.title)}</div>

                <div class="text-sm text-gray-600 mt-0.5">${escapeHtml(a.description || "")}</div>

                <div class="text-xs text-gray-400 mt-1">${escapeHtml(a.actor?.name || a.user?.name || "System")} · just now</div>

            </div>

            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">${escapeHtml(a.type)}</span>

        </div>`);

    });

}


