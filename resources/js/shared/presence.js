import { ensureApiToken } from "./token.js";
import { fetchJson, postJson } from "./api.js";
import { initEcho } from "./echo.js";

const HEARTBEAT_MS = 60_000;

function presenceBadgeHtml(online) {
    return online
        ? '<span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs bg-green-50 text-green-700"><span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>Online</span>'
        : '<span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs bg-gray-100 text-gray-500"><span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span>Offline</span>';
}

function updatePresenceCell(userId, online) {
    document.querySelectorAll(`[data-presence-user="${userId}"]`).forEach((el) => {
        el.innerHTML = presenceBadgeHtml(online);
    });
}

async function sendHeartbeat(token) {
    await fetchJson("/api/v1/presence/heartbeat", { token, method: "POST", silent: true });
}

export async function initPresence() {
    const token = await ensureApiToken();
    if (!token) return;

    const runHeartbeat = () => sendHeartbeat(token).catch(() => {});

    window.setTimeout(runHeartbeat, 3000);
    setInterval(runHeartbeat, HEARTBEAT_MS);

    const usersPage = document.getElementById("usersPage");
    const userShowPage = document.getElementById("userShowPage");
    const canViewPresence = usersPage?.dataset.canViewPresence === "1" || userShowPage?.dataset.canViewPresence === "1";
    if (!canViewPresence) return;

    const echo = initEcho(token);
    if (!echo) return;

    echo.private("dashboard.presence").listen(".presence.updated", (e) => {
        if (!e.user_id) return;
        updatePresenceCell(e.user_id, Boolean(e.online));
    });

    setInterval(async () => {
        try {
            const { res, data } = await fetchJson("/api/v1/admin/presence", { token, silent: true });
            if (!res.ok) return;
            (data?.data || []).forEach((row) => updatePresenceCell(row.user_id, Boolean(row.online)));
        } catch {
            /* ignore */
        }
    }, 30_000);
}
