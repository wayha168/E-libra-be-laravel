/**
 * Auto-submit GET search/filter forms after typing (debounced) or on select change.
 */
export function initAutoSearch() {
    document.querySelectorAll("[data-auto-search]").forEach((form) => {
        if (form.dataset.autoSearchBound === "1") return;
        form.dataset.autoSearchBound = "1";

        const input = form.querySelector("[data-auto-search-input]");
        const selects = form.querySelectorAll("[data-auto-search-select]");
        let timer = null;
        const delay = 450;

        function submitForm() {
            if (typeof form.requestSubmit === "function") {
                form.requestSubmit();
            } else {
                form.submit();
            }
        }

        if (input) {
            input.addEventListener("input", () => {
                clearTimeout(timer);
                timer = setTimeout(submitForm, delay);
            });

            input.addEventListener("keydown", (e) => {
                if (e.key === "Enter") {
                    e.preventDefault();
                    clearTimeout(timer);
                    submitForm();
                }
            });
        }

        selects.forEach((select) => {
            select.addEventListener("change", submitForm);
        });
    });
}
