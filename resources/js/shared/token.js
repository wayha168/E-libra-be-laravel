export function isProbablyToken(value) {
    return typeof value === "string" && value.length > 20;
}

export async function ensureApiToken() {
    let token = sessionStorage.getItem("api_token") || localStorage.getItem("api_token");
    if (token && isProbablyToken(token)) {
        return token;
    }

    try {
        const res = await fetch("/auth/token", {
            headers: { Accept: "application/json" },
            credentials: "same-origin",
        });
        if (!res.ok) return null;

        const data = await res.json();
        token = data?.token;
        if (token && isProbablyToken(token)) {
            localStorage.setItem("api_token", token);
            return token;
        }
    } catch (e) {
        console.error("Token bootstrap failed", e);
    }

    return null;
}
