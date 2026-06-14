import Echo from "laravel-echo";
import Pusher from "pusher-js";

window.Pusher = Pusher;
Pusher.logToConsole = false;

let echoInstance = null;
let connectionFailed = false;

function reverbHost() {
    const configured = import.meta.env.VITE_REVERB_HOST;
    if (configured && configured !== "localhost") {
        return configured;
    }

    return window.location.hostname || "127.0.0.1";
}

export function initEcho(token) {
    if (!token || echoInstance || connectionFailed) {
        return echoInstance;
    }

    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const enabled = import.meta.env.VITE_REVERB_ENABLED !== "false";

    if (!key || !enabled) {
        return null;
    }

    const host = reverbHost();
    const scheme = import.meta.env.VITE_REVERB_SCHEME || window.location.protocol.replace(":", "");
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
            connection.bind("error", () => {
                connectionFailed = true;
            });
            connection.bind("failed", () => {
                connectionFailed = true;
            });
            connection.bind("unavailable", () => {
                connectionFailed = true;
            });
        }
    } catch {
        connectionFailed = true;
        echoInstance = null;
    }

    return echoInstance;
}

export function getEcho() {
    return echoInstance;
}
