/**
 * App toast — success/error with cancel button, auto-hide after 5s.
 */
const TOAST_MS = 5000;

let container = null;

function ensureContainer() {
    if (container && document.body.contains(container)) return container;

    container = document.createElement("div");
    container.id = "appToastStack";
    container.className = "fixed top-4 right-4 z-[80] flex flex-col gap-2 w-[min(100%-2rem,22rem)] pointer-events-none";
    container.setAttribute("aria-live", "polite");
    document.body.appendChild(container);
    return container;
}

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

/**
 * @param {{ type?: 'success'|'error'|'info', title?: string, message: string, durationMs?: number }} options
 */
export function showAppToast(options = {}) {
    const type = options.type || "info";
    const title =
        options.title ||
        (type === "success" ? "Success" : type === "error" ? "Error" : "Notice");
    const message = options.message || "";
    const durationMs = typeof options.durationMs === "number" ? options.durationMs : TOAST_MS;

    if (!message) return null;

    const box = ensureContainer();
    const el = document.createElement("div");

    const tone =
        type === "success"
            ? {
                  border: "border-emerald-200",
                  bg: "bg-white",
                  bar: "bg-emerald-500",
                  iconBg: "bg-emerald-100 text-emerald-700",
                  title: "text-emerald-900",
              }
            : type === "error"
              ? {
                    border: "border-red-200",
                    bg: "bg-white",
                    bar: "bg-red-500",
                    iconBg: "bg-red-100 text-red-700",
                    title: "text-red-900",
                }
              : {
                    border: "border-gray-200",
                    bg: "bg-white",
                    bar: "bg-gray-400",
                    iconBg: "bg-gray-100 text-gray-700",
                    title: "text-gray-900",
                };

    const icon =
        type === "success"
            ? '<path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />'
            : type === "error"
              ? '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m0 3.75h.008v.008H12V16.5Zm9-4.5a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'
              : '<path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />';

    el.className = `pointer-events-auto relative overflow-hidden rounded-xl border ${tone.border} ${tone.bg} shadow-lg px-3 py-3 text-sm`;
    el.setAttribute("role", "status");
    el.innerHTML = `
        <div class="flex items-start gap-3">
            <span class="inline-flex shrink-0 items-center justify-center w-8 h-8 rounded-full ${tone.iconBg}">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">${icon}</svg>
            </span>
            <div class="min-w-0 flex-1 pt-0.5">
                <div class="font-semibold ${tone.title}">${escapeHtml(title)}</div>
                <div class="text-gray-600 mt-0.5 break-words">${escapeHtml(message)}</div>
            </div>
            <button type="button" data-toast-dismiss class="shrink-0 inline-flex items-center justify-center w-8 h-8 rounded-lg text-gray-400 hover:text-gray-700 hover:bg-gray-100 transition" aria-label="Dismiss">
                <svg class="w-4 h-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
            </button>
        </div>
        <div class="absolute left-0 right-0 bottom-0 h-0.5 ${tone.bar} origin-left" data-toast-progress style="transform: scaleX(1); transition: transform ${durationMs}ms linear;"></div>
    `;

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

    box.prepend(el);

    // Kick progress bar next frame
    requestAnimationFrame(() => {
        const bar = el.querySelector("[data-toast-progress]");
        if (bar) bar.style.transform = "scaleX(0)";
    });

    window.setTimeout(dismiss, durationMs);
    return el;
}

export function initFlashToasts() {
    const success = document.body?.dataset?.flashSuccess;
    const error = document.body?.dataset?.flashError;
    const uploadOk = document.body?.dataset?.flashUploadSuccess;
    const uploadErr = document.body?.dataset?.flashUploadError;

    if (uploadOk) {
        showAppToast({ type: "success", title: "Upload successful", message: uploadOk });
    } else if (success) {
        showAppToast({ type: "success", title: "Success", message: success });
    }

    if (uploadErr) {
        showAppToast({ type: "error", title: "Upload failed", message: uploadErr });
    } else if (error) {
        showAppToast({ type: "error", title: "Error", message: error });
    }

    // Validation errors (first few)
    const validation = document.body?.dataset?.flashValidation;
    if (validation) {
        showAppToast({ type: "error", title: "Could not save", message: validation });
    }
}

export function initUploadFormToasts() {
    document.querySelectorAll("form[enctype='multipart/form-data']").forEach((form) => {
        form.addEventListener("submit", () => {
            const hasFile = Array.from(form.querySelectorAll('input[type="file"]')).some(
                (input) => input.files && input.files.length > 0,
            );
            if (!hasFile) return;

            showAppToast({
                type: "info",
                title: "Uploading…",
                message: "Please wait while your file is uploaded.",
                durationMs: 5000,
            });
        });
    });
}

if (typeof window !== "undefined") {
    window.showAppToast = showAppToast;
}
