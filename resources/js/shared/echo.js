import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;
Pusher.logToConsole = false;

let echoInstance = null;
let connected = false;

function reverbHost() {
    const configured = import.meta.env.VITE_REVERB_HOST;
    if (configured && configured !== "localhost" && configured !== "127.0.0.1") {
        return configured;
    }

    // Prefer page hostname so local/prod WS matches the browser origin
    return window.location.hostname || configured || "127.0.0.1";
}

/**
 * On HTTPS pages always use WSS (mixed-content blocks ws://).
 * Prefer same-origin ports (443/80) so nginx can proxy /app → Reverb.
 */
function reverbConnection() {
    const pageIsHttps = window.location.protocol === "https:";
    const configuredScheme = import.meta.env.VITE_REVERB_SCHEME;
    const scheme = pageIsHttps ? "https" : configuredScheme || "http";
    const forceTLS = scheme === "https";

    const configuredPort = import.meta.env.VITE_REVERB_PORT;
    let port;
    if (configuredPort !== undefined && configuredPort !== null && String(configuredPort) !== "") {
        port = Number(configuredPort);
        // Built with local :8080 but page is HTTPS → use 443 (nginx proxy)
        if (pageIsHttps && (port === 8080 || port === 80)) {
            port = 443;
        }
    } else {
        port = forceTLS ? 443 : 8080;
    }

    return { scheme, forceTLS, port };
}

export function isEchoConnected() {
    return Boolean(echoInstance) && connected;
}

export function initEcho(token) {
    if (!token) {
        return null;
    }

    if (echoInstance) {
        return echoInstance;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const enabled = import.meta.env.VITE_REVERB_ENABLED !== "false";

    if (!key || !enabled) {
        return null;
    }

    const host = reverbHost();
    const { forceTLS, port } = reverbConnection();

    try {
        echoInstance = new Echo({
            broadcaster: "reverb",
            key,
            wsHost: host,
            wsPort: port,
            wssPort: port,
            forceTLS,
            enabledTransports: ["ws", "wss"],
            disableStats: true,
            activityTimeout: 30_000,
            pongTimeout: 10_000,
            authEndpoint: "/api/v1/broadcasting/auth",
            auth: {
                headers: {
                    Authorization: "Bearer " + token,
                    Accept: "application/json",
                },
            },
        });

        const connection = echoInstance.connector?.pusher?.connection;
        if (connection) {
            connection.bind("connected", () => {
                connected = true;
            });
            connection.bind("disconnected", () => {
                connected = false;
            });
            connection.bind("unavailable", () => {
                connected = false;
            });
            connection.bind("failed", () => {
                connected = false;
            });
            connection.bind("error", () => {
                connected = false;
            });

            if (connection.state === "connected") {
                connected = true;
            }
        }
    } catch {
        connected = false;
        echoInstance = null;
    }

    return echoInstance;
}

export function getEcho() {
    return echoInstance;
}
