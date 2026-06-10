const form = document.getElementById("loginForm");
const errorBox = document.getElementById("errorBox");
const submitBtn = document.getElementById("submitBtn");
const btnSpinner = document.getElementById("btnSpinner");
const btnText = document.getElementById("btnText");

function showError(message) {
    if (!errorBox) return;
    errorBox.textContent = message;
    errorBox.classList.remove("hidden");
}

if (form) {
    form.addEventListener("submit", async (e) => {
        e.preventDefault();
        if (errorBox) errorBox.classList.add("hidden");

        if (submitBtn) submitBtn.disabled = true;
        if (btnSpinner) btnSpinner.classList.remove("hidden");
        if (btnText) btnText.textContent = "Signing in...";

        const body = {
            email: form.email.value,
            password: form.password.value,
        };

        try {
            const res = await fetch("/api/v1/login", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                },
                body: JSON.stringify(body),
            });

            const data = await res.json().catch(() => null);

            if (!res.ok) {
                showError(data?.message || "Invalid credentials");
                return;
            }

            const token = data?.data?.token;
            if (!token) {
                showError("Token missing from response");
                return;
            }

            localStorage.setItem("api_token", token);
            window.location.href = "/home";
        } catch (err) {
            showError("Network error. Please try again.");
        } finally {
            if (submitBtn) submitBtn.disabled = false;
            if (btnSpinner) btnSpinner.classList.add("hidden");
            if (btnText) btnText.textContent = "Login";
        }
    });
}
