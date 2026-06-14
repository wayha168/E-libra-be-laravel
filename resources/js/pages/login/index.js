import { fetchJson } from "../../shared/api.js";

async function finishLogin(token, showError) {
    const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
    const sessionRes = await fetch("/auth/session", {
        method: "POST",
        headers: {
            "Content-Type": "application/json",
            Accept: "application/json",
            "X-CSRF-TOKEN": csrf || "",
        },
        body: JSON.stringify({ token }),
        credentials: "same-origin",
    });

    if (!sessionRes.ok) {
        const sessionData = await sessionRes.json().catch(() => null);
        showError(sessionData?.message || "Could not start session");
        return false;
    }

    localStorage.setItem("api_token", token);
    window.location.href = "/home";
    return true;
}

export function initLoginPage() {
    const form = document.getElementById("loginForm");
    if (!form) return;

    const errorBox = document.getElementById("errorBox");
    const submitBtn = document.getElementById("submitBtn");
    const btnSpinner = document.getElementById("btnSpinner");
    const btnText = document.getElementById("btnText");
    const googleBtnHost = document.getElementById("googleSignInHost");

    function showError(message) {
        if (!errorBox) return;
        errorBox.textContent = message;
        errorBox.classList.remove("hidden");
    }

    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (errorBox) errorBox.classList.add("hidden");

        if (submitBtn) submitBtn.disabled = true;
        if (btnSpinner) btnSpinner.classList.remove("hidden");
        if (btnText) btnText.textContent = "Signing in...";

        const body = {
            email: form.querySelector('[name="email"]').value,
            password: form.querySelector('[name="password"]').value,
        };

        try {
            const { res, data } = await fetchJson("/api/v1/login", {
                method: "POST",
                headers: { "Content-Type": "application/json" },
                body: JSON.stringify(body),
            });

            if (!res.ok) {
                showError(data?.message || "Invalid credentials");
                return;
            }

            const token = data?.data?.token;
            if (!token) {
                showError("Token missing from response");
                return;
            }

            await finishLogin(token, showError);
        } catch {
            showError("Network error. Please try again.");
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (btnSpinner) btnSpinner.classList.add("hidden");
            if (btnText) btnText.textContent = "Login";
        }
    });

    if (!googleBtnHost) return;

    (async () => {
        try {
            const { res, data } = await fetchJson("/api/v1/auth/google/config");
            const clientId = res.ok ? data?.data?.client_id : null;
            if (!clientId) return;

            await new Promise((resolve) => {
                if (window.google?.accounts?.id) {
                    resolve();
                    return;
                }

                const script = document.createElement("script");
                script.src = "https://accounts.google.com/gsi/client";
                script.async = true;
                script.defer = true;
                script.onload = () => resolve();
                script.onerror = () => resolve();
                document.head.appendChild(script);
            });

            if (!window.google?.accounts?.id) return;

            window.google.accounts.id.initialize({
                client_id: clientId,
                callback: async (response) => {
                    if (errorBox) errorBox.classList.add("hidden");

                    try {
                        const { res, data } = await fetchJson("/api/v1/auth/google", {
                            method: "POST",
                            headers: { "Content-Type": "application/json" },
                            body: JSON.stringify({ credential: response.credential }),
                        });

                        if (!res.ok) {
                            showError(data?.message || "Google sign-in failed");
                            return;
                        }

                        const token = data?.data?.token;
                        if (!token) {
                            showError("Token missing from Google login response");
                            return;
                        }

                        await finishLogin(token, showError);
                    } catch {
                        showError("Google sign-in failed. Please try again.");
                    }
                },
            });

            window.google.accounts.id.renderButton(googleBtnHost, {
                theme: "outline",
                size: "large",
                width: googleBtnHost.offsetWidth || 320,
                text: "signin_with",
            });
        } catch {
            /* Google sign-in optional */
        }
    })();
}
