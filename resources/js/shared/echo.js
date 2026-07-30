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

    // Prefer page hostname so local WS matches the browser origin
    return window.location.hostname || configured || "127.0.0.1";
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
    const scheme = import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(":", "") || "http";
    const port = Number(import.meta.env.VITE_REVERB_PORT || 8080);
    const forceTLS = scheme === "https";

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
            // Snappier dead-connection detection / reconnect
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

            // If already connected by the time we bind
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
