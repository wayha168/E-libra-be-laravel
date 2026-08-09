import { ensureApiToken } from "./token.js";
import { fetchJson, postJson } from "./api.js";
import { initEcho, isEchoConnected } from "./echo.js";

const POLL_MS_LIVE = 60_000;
const POLL_MS_FALLBACK = 5_000;

let toastContainer = null;
let unreadCount = 0;
let apiToken = null;
const knownIds = new Set();

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

function playAlertSound(kind = "default") {
    try {
        const AudioCtx = window.AudioContext || window.webkitAudioContext;
        if (!AudioCtx) return;

        const ctx = new AudioCtx();
        const now = ctx.currentTime;

        const tones =
            kind === "purchase"
                ? [
                      { f: 880, t: 0, d: 0.12 },
                      { f: 1174.66, t: 0.1, d: 0.16 },
                      { f: 1396.91, t: 0.22, d: 0.22 },
                  ]
                : kind === "chat"
                  ? [
                        { f: 740, t: 0, d: 0.1 },
                        { f: 988, t: 0.12, d: 0.14 },
                    ]
                  : [{ f: 820, t: 0, d: 0.12 }];

        for (const tone of tones) {
            const osc = ctx.createOscillator();
            const gain = ctx.createGain();
            osc.type = "sine";
            osc.frequency.value = tone.f;
            gain.gain.setValueAtTime(0.0001, now + tone.t);
            gain.gain.exponentialRampToValueAtTime(0.18, now + tone.t + 0.02);
            gain.gain.exponentialRampToValueAtTime(0.0001, now + tone.t + tone.d);
            osc.connect(gain);
            gain.connect(ctx.destination);
            osc.start(now + tone.t);
            osc.stop(now + tone.t + tone.d + 0.02);
        }

        window.setTimeout(() => ctx.close().catch(() => {}), 800);
    } catch {
        /* ignore autoplay / AudioContext failures */
    }
}

function soundKindForNotification(type) {
    const t = String(type || "");
    if (t.startsWith("purchase.")) return "purchase";
    if (t === "chat.message") return "chat";
    return null;
}

function notificationDetailUrl(notification) {
    const data = notification?.data || {};
    const type = String(notification?.type || "");

    if (data.purchase_id && type.startsWith("purchase.")) {
        return `/dashboard/purchases/${data.purchase_id}`;
    }

    if (type === "chat.message") {
        return null;
    }

    if (type === "promotion.new" && data.book_id) {
        return `/dashboard/books/${data.book_id}`;
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

    if (t.startsWith("recommendation.") || t === "book.released") {
        return {
            bg: "bg-purple-100 text-purple-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.455 2.456L21.75 6l-1.036.259a3.375 3.375 0 0 0-2.455 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />',
        };
    }

    if (t === "promotion.new") {
        return {
            bg: "bg-orange-100 text-orange-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" />',
        };
    }

    if (t === "chat.message") {
        return {
            bg: "bg-indigo-100 text-indigo-700",
            svg: '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.087.16 2.185.283 3.293.369V21l4.076-4.076a1.526 1.526 0 0 1 1.037-.443 48.282 48.282 0 0 0 5.68-.494c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />',
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

function showBrowserAlert(notification) {
    if (typeof window.Notification === "undefined") return;
    if (window.Notification.permission !== "granted") return;
    if (document.visibilityState === "visible") return;

    try {
        const n = new window.Notification(notification.title || "e-Libra", {
            body: notification.body || "",
            tag: notification.id ? `elibra-notif-${notification.id}` : undefined,
        });
        n.onclick = () => {
            window.focus();
            n.close();
        };
    } catch {
        /* ignore */
    }
}

function ensureBrowserAlertPermission() {
    if (typeof window.Notification === "undefined") return;
    if (window.Notification.permission !== "default") return;
    // Non-blocking; user gesture may be required in some browsers
    window.Notification.requestPermission().catch(() => {});
}

function showToast(notification) {
    showBrowserAlert(notification);
    const box = ensureToastContainer();
    const el = document.createElement("div");
    const detailUrl = notificationDetailUrl(notification);

    el.className =
        "relative overflow-hidden text-left rounded-xl border border-gray-200 bg-white shadow-lg px-4 py-3 text-sm w-full";
    el.setAttribute("role", "status");

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
        <button type="button" data-toast-dismiss class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" aria-label="Dismiss">
            <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
        </button>
    </div>
    <div class="absolute left-0 right-0 bottom-0 h-0.5 bg-blue-500 origin-left" data-toast-progress style="transform: scaleX(1); transition: transform 5000ms linear;"></div>`;

    const dismiss = () => {
        el.style.opacity = "0";
        el.style.transform = "translateY(-6px)";
        el.style.transition = "opacity 0.18s ease, transform 0.18s ease";
        window.setTimeout(() => el.remove(), 180);
    };

    el.querySelector("[data-toast-dismiss]")?.addEventListener("click", (e) => {
        e.preventDefault();
        e.stopPropagation();
        dismiss();
    });

    el.addEventListener("click", async (e) => {
        if (e.target.closest("[data-toast-dismiss]")) return;

        if (!notification.read_at && notification.id && apiToken) {
            await postJson(apiToken, `/api/v1/notifications/${notification.id}/read`);
            updateBadge(Math.max(0, unreadCount - 1));
        }

        if (detailUrl) {
            window.location.href = detailUrl;
        } else {
            openNotificationSidebar();
        }

        dismiss();
    });

    box.prepend(el);
    requestAnimationFrame(() => {
        const bar = el.querySelector("[data-toast-progress]");
        if (bar) bar.style.transform = "scaleX(0)";
    });
    setTimeout(dismiss, 5000);
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

function rememberIds(items) {
    for (const item of items || []) {
        if (item?.id) knownIds.add(String(item.id));
    }
}

function isKnown(id) {
    return Boolean(id && knownIds.has(String(id)));
}

async function loadNotifications(token, listEl, { page = 1, append = false } = {}) {
    const { res, data } = await fetchJson(`/api/v1/notifications?page=${page}`, { token, silent: true });
    if (!res.ok || !listEl) return null;

    const paginator = data?.data;
    const items = paginator?.data || [];
    updateBadge(data?.unread_count ?? 0);
    rememberIds(items);

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
    if (!listEl || !notification?.id) return;
    if (listEl.querySelector(`[data-notification-id="${CSS.escape(String(notification.id))}"]`)) return;

    const empty = listEl.querySelector(".text-gray-400");
    if (empty) empty.remove();

    listEl.insertAdjacentHTML("afterbegin", renderNotificationItem(notification));
    bindMarkReadButtons(listEl, apiToken, () => loadNotifications(apiToken, listEl, { page: 1, append: false }));
}

function applyIncomingNotification(notification, { toast = true, bumpBadge = true } = {}) {
    if (!notification?.id || isKnown(notification.id)) return false;

    knownIds.add(String(notification.id));

    const sound = soundKindForNotification(notification.type);
    if (sound) playAlertSound(sound);

    if (toast) showToast(notification);
    if (bumpBadge) updateBadge(unreadCount + 1);

    const sidebarList = document.getElementById("notificationSidebarList");
    const pageList = document.getElementById("notificationsList");

    if (sidebarList) prependNotification(sidebarList, notification);
    if (pageList) prependNotification(pageList, notification);

    return true;
}

async function resolveUserId(token) {
    const fromDom =
        document.getElementById("notificationBell")?.dataset.userId ||
        document.querySelector("[data-auth-user-id]")?.dataset.authUserId;

    if (fromDom) return fromDom;

    try {
        const me = await fetchJson("/api/v1/me", { token, silent: true });
        return me.data?.data?.id || null;
    } catch {
        return null;
    }
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
    ensureBrowserAlertPermission();

    let seeded = false;

    const reloadAll = async () => {
        if (sidebarList) {
            const paginator = await loadNotifications(token, sidebarList, { page: 1, append: false });
            updateSeeMoreButton(seeMoreSidebarBtn, paginator);
        }
        if (pageList) {
            const paginator = await loadNotifications(token, pageList, { page: 1, append: false });
            updateSeeMoreButton(seeMorePageBtn, paginator);
        }
        seeded = true;
    };

    const syncNewNotifications = async ({ toast = true } = {}) => {
        try {
            const { res, data } = await fetchJson("/api/v1/notifications?page=1", { token, silent: true });
            if (!res.ok) return;

            const items = data?.data?.data || [];
            updateBadge(data?.unread_count ?? unreadCount);

            // First sync only seeds known IDs so we don't toast existing items
            if (!seeded) {
                rememberIds(items);
                seeded = true;
                return;
            }

            // Newest first so toasts appear in natural order (latest on top)
            const fresh = items.filter((n) => n?.id && !isKnown(n.id));
            for (const notification of fresh) {
                applyIncomingNotification(notification, { toast, bumpBadge: false });
            }
        } catch {
            /* ignore */
        }
    };

    const defer = (fn) => {
        if (typeof window.requestIdleCallback === "function") {
            window.requestIdleCallback(() => fn(), { timeout: 800 });
        } else {
            window.setTimeout(fn, 50);
        }
    };

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

    // Backup poll: light when WebSocket is live, faster when it is not
    const pollOnce = () => {
        if (document.visibilityState === "hidden") return;
        syncNewNotifications({ toast: true }).catch(() => {});
    };

    let pollTimer = null;
    const schedulePoll = () => {
        if (pollTimer) window.clearTimeout(pollTimer);
        const delay = isEchoConnected() ? POLL_MS_LIVE : POLL_MS_FALLBACK;
        pollTimer = window.setTimeout(() => {
            pollOnce();
            schedulePoll();
        }, delay);
    };
    schedulePoll();

    defer(() => {
        reloadAll()
            .then(() => {
                // Catch anything that arrived during the seed window
                window.setTimeout(() => pollOnce(), 1500);
            })
            .catch(() => {});
    });

    document.addEventListener("visibilitychange", () => {
        if (document.visibilityState === "visible") {
            pollOnce();
            schedulePoll();
        }
    });

    window.addEventListener("focus", () => {
        pollOnce();
    });

    const echo = initEcho(token);
    if (!echo) return;

    try {
        const userId = await resolveUserId(token);
        if (!userId) return;

        echo.private("notifications." + userId).listen(".notification.created", (e) => {
            if (!e.notification) return;
            applyIncomingNotification(e.notification, { toast: true, bumpBadge: true });
        });
    } catch {
        /* polling still covers updates */
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

        list.insertAdjacentHTML(
            "afterbegin",
            `<div class="px-4 py-3 flex items-start justify-between gap-3 bg-green-50/30" data-activity-id="${a.id}">
            <div>
                <div class="font-medium text-gray-900">${escapeHtml(a.title)}</div>
                <div class="text-sm text-gray-600 mt-0.5">${escapeHtml(a.description || "")}</div>
                <div class="text-xs text-gray-400 mt-1">${escapeHtml(a.actor?.name || a.user?.name || "System")} · just now</div>
            </div>
            <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium bg-gray-100 text-gray-600">${escapeHtml(a.type)}</span>
        </div>`,
        );
    });
}
