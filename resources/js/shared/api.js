/**
 * Shared JSON fetch helpers for authenticated API calls.
 */
export async function fetchJson(path, options = {}) {
    const { token, silent = false, ...fetchOptions } = options;
    const headers = {
        Accept: "application/json",
        ...(fetchOptions.headers || {}),
    };

    if (token) {
        headers.Authorization = "Bearer " + token;
    }

    const res = await fetch(path, { ...fetchOptions, headers });
    const data = await res.json().catch(() => null);

    if (!res.ok && !silent) {
        const msg = data?.message || data?.error || `Request failed (${res.status})`;
        throw new Error(msg);
    }

    return { res, data };
}

export async function fetchWithToken(token, path, options = {}) {
    if (!path || typeof path !== "string" || !path.startsWith("/")) {
        throw new Error("fetchWithToken: path must start with /");
    }

    const { data } = await fetchJson(path, { ...options, token });
    return data;
}

export async function postJson(token, path, body = null) {
    const headers = {};
    if (body !== null) {
        headers["Content-Type"] = "application/json";
    }

    return fetchWithToken(token, path, {
        method: "POST",
        headers,
        body: body !== null ? JSON.stringify(body) : undefined,
    });
}

export function isAuthorRole(role) {
    if (!role) return false;
    const normalized = String(role).toLowerCase().replace(/\s+/g, "_");
    return normalized === "author" || normalized === "super_admin" || normalized === "admin";
}
