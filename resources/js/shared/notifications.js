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



function notificationDetailUrl(notification) {
    const data = notification?.data || {};
    const type = String(notification?.type || "");

    if (data.purchase_id && type.startsWith("purchase.")) {
        return `/dashboard/purchases/${data.purchase_id}`;
    }

    if (data.book_id) {
        return `/dashboard/books/${data.book_id}`;
    }

    return null;
}

function notificationIconConfig(type) {
    const t = String(type || "");

    if (t.startsWith("purchase.")) {
        return {
            bg: "bg-emerald-100 text-emerald-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 10.5V6a3.75 3.75 0 1 0-7.5 0v4.5m11.356-1.993 1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 0 1-1.12-1.243l1.264-12A1.125 1.125 0 0 1 5.513 7.5h12.974c.576 0 1.059.435 1.119 1.007ZM8.625 10.5a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm7.5 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />',
        };
    }

    if (t.includes("like")) {
        return {
            bg: "bg-rose-100 text-rose-600",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" />',
        };
    }

    if (t.includes("comment")) {
        return {
            bg: "bg-blue-100 text-blue-600",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />',
        };
    }

    if (t.startsWith("recommendation.")) {
        return {
            bg: "bg-purple-100 text-purple-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />',
        };
    }

    if (t === "book_created") {
        return {
            bg: "bg-amber-100 text-amber-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />',
        };
    }

    if (t === "sale") {
        return {
            bg: "bg-green-100 text-green-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        };
    }

    if (t === "login") {
        return {
            bg: "bg-sky-100 text-sky-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />',
        };
    }

    return {
        bg: "bg-gray-100 text-gray-600",
        svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />',
    };
}

function notificationIconMarkup(type) {
    const { bg, svg } = notificationIconConfig(type);

    return `<span class="inline-flex shrink-0 items-center justify-center w-9 h-9 rounded-full ${bg}">
        <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true">${svg}</svg>
    </span>`;
}

function readDotMarkup(unread) {
    if (unread) {
        return '<span class="shrink-0 w-2 h-2 rounded-full bg-blue-600" title="Unread" aria-label="Unread"></span>';
    }

    return '<span class="shrink-0 w-2 h-2 rounded-full bg-gray-300" title="Read" aria-label="Read"></span>';
}

function showToast(notification) {
    const box = ensureToastContainer();
    const el = document.createElement("button");
    const detailUrl = notificationDetailUrl(notification);

    el.type = "button";
    el.className = "text-left rounded-xl border border-gray-200 bg-white shadow-lg px-4 py-3 text-sm hover:bg-gray-50 transition w-full";

    el.innerHTML = `<div class="flex items-start gap-3">
        ${notificationIconMarkup(notification.type)}
        <div class="min-w-0 flex-1">
            <div class="flex items-center gap-2">
                ${readDotMarkup(!notification.read_at)}
                <div class="font-semibold text-gray-900">${escapeHtml(notification.title || "Notification")}</div>
            </div>
            <div class="text-gray-600 mt-1">${escapeHtml(notification.body || "")}</div>
            ${detailUrl ? '<div class="text-xs text-blue-600 mt-1">Tap to view details</div>' : ""}
        </div>
    </div>`;

    el.addEventListener("click", async () => {
        if (!notification.read_at && notification.id && apiToken) {
            await postJson(apiToken, `/api/v1/notifications/${notification.id}/read`);
            updateBadge(Math.max(0, unreadCount - 1));
        }

        if (detailUrl) {
            window.location.href = detailUrl;
        } else {
            openNotificationSidebar();
        }

        el.remove();
    });

    box.appendChild(el);
    setTimeout(() => el.remove(), 8000);
}

function renderNotificationItem(n) {
    const unread = !n.read_at;
    const isRec = String(n.type || "").startsWith("recommendation.");
    const detailUrl = notificationDetailUrl(n);

    return `<button type="button"
        class="w-full text-left px-4 py-3 transition hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40 ${unread ? "bg-blue-50/40" : "bg-white"}"
        data-notification-item
        data-notification-id="${n.id}"
        data-unread="${unread ? "1" : "0"}"
        data-detail-url="${detailUrl ? escapeHtml(detailUrl) : ""}">
        <div class="flex items-start gap-3">
            ${notificationIconMarkup(n.type)}
            <div class="min-w-0 flex-1">
                <div class="flex items-center gap-2">
                    ${readDotMarkup(unread)}
                    <div class="font-medium text-gray-900 truncate">${escapeHtml(n.title)}</div>
                    ${isRec ? '<span class="inline-flex px-1.5 py-0.5 rounded text-[10px] bg-purple-50 text-purple-700 shrink-0">Recommended</span>' : ""}
                </div>
                <div class="text-sm text-gray-600 mt-0.5 line-clamp-2">${escapeHtml(n.body || "")}</div>
                <div class="flex items-center justify-between gap-2 mt-1">
                    <div class="text-xs text-gray-400">${n.created_at ? new Date(n.created_at).toLocaleString() : ""}</div>
                    ${detailUrl ? '<span class="text-xs text-blue-600 shrink-0">View details →</span>' : ""}
                </div>
            </div>
        </div>
    </button>`;
}

function bindNotificationItems(container, token, reloadFn) {
    container?.querySelectorAll("[data-notification-item]").forEach((el) => {
        if (el.dataset.bound === "1") return;
        el.dataset.bound = "1";

        el.addEventListener("click", async () => {
            const id = el.dataset.notificationId;
            const url = el.dataset.detailUrl || "";
            const isUnread = el.dataset.unread === "1";

            if (isUnread && id) {
                await postJson(token, `/api/v1/notifications/${id}/read`);
                updateBadge(Math.max(0, unreadCount - 1));
            }

            if (url) {
                window.location.href = url;
                return;
            }

            if (isUnread) {
                reloadFn();
            }
        });
    });
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
    bindNotificationItems(container, token, reloadFn);
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

    const bootstrap = async () => {
        if (sidebarList) {
            const paginator = await loadNotifications(token, sidebarList, { page: 1, append: false });
            updateSeeMoreButton(seeMoreSidebarBtn, paginator);
        }
        if (pageList) {
            const paginator = await loadNotifications(token, pageList, { page: 1, append: false });
            updateSeeMoreButton(seeMorePageBtn, paginator);
        }
    };

    const defer = (fn) => {
        if (typeof window.requestIdleCallback === "function") {
            window.requestIdleCallback(() => fn(), { timeout: 2000 });
        } else {
            window.setTimeout(fn, 250);
        }
    };

    defer(() => {
        bootstrap().catch(() => {});
    });



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

            showToast(e.notification);

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


