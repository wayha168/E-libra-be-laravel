/**
 * Live thumbnails for image file inputs (before submit).
 * Works with single and multi file inputs that accept images.
 */
export function initImageUploadPreviews() {
    document.querySelectorAll('input[type="file"]').forEach((input) => {
        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        const accept = (input.getAttribute("accept") || "").toLowerCase();
        if (accept && !accept.includes("image")) {
            return;
        }

        let preview = input.parentElement?.querySelector("[data-image-preview]");
        if (!preview) {
            preview = document.createElement("div");
            preview.setAttribute("data-image-preview", "");
            preview.className = "mt-2 flex flex-wrap gap-2";
            input.insertAdjacentElement("afterend", preview);
        }

        input.addEventListener("change", () => {
            preview.innerHTML = "";
            const files = Array.from(input.files || []).filter((f) => f.type.startsWith("image/"));
            if (files.length === 0) {
                return;
            }

            files.forEach((file) => {
                const url = URL.createObjectURL(file);
                const img = document.createElement("img");
                img.src = url;
                img.alt = file.name;
                img.className = "w-24 h-24 rounded-lg object-cover border border-gray-200";
                img.onload = () => URL.revokeObjectURL(url);
                preview.appendChild(img);
            });
        });
    });
}
