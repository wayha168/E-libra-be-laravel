import { ensureApiToken } from "../../shared/token.js";
import { fetchWithToken } from "../../shared/api.js";

export function initBookReadPage() {
    const page = document.getElementById("bookReadPage");
    if (!page) return;

    const bookId = page.dataset.bookId;
    const canvas = document.getElementById("pdfCanvas");
    const pageInfo = document.getElementById("pdfPageInfo");
    const prevBtn = document.getElementById("pdfPrevBtn");
    const nextBtn = document.getElementById("pdfNextBtn");
    const errorBox = document.getElementById("readError");

    let pdfDoc = null;
    let pageNum = 1;
    let maxPage = Infinity;
    let rendering = false;

    function showError(msg) {
        if (!errorBox) return;
        errorBox.textContent = msg;
        errorBox.classList.remove("hidden");
    }

    async function loadPdfJs() {
        if (window.pdfjsLib) return window.pdfjsLib;
        await new Promise((resolve, reject) => {
            const script = document.createElement("script");
            script.src = "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js";
            script.onload = resolve;
            script.onerror = reject;
            document.head.appendChild(script);
        });
        window.pdfjsLib.GlobalWorkerOptions.workerSrc =
            "https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js";
        return window.pdfjsLib;
    }

    async function fetchPdfBlob(url, token, useAuth) {
        const headers = { Accept: "application/pdf" };
        if (useAuth && token) headers.Authorization = "Bearer " + token;

        const res = await fetch(url, { headers });
        if (!res.ok) {
            const data = await res.json().catch(() => null);
            throw new Error(data?.message || `Could not load PDF (${res.status})`);
        }
        return res.blob();
    }

    async function renderPage(num) {
        if (!pdfDoc || !canvas) return;
        rendering = true;
        prevBtn.disabled = num <= 1;
        nextBtn.disabled = num >= Math.min(pdfDoc.numPages, maxPage);

        const pdfjsLib = await loadPdfJs();
        const pdfPage = await pdfDoc.getPage(num);
        const viewport = pdfPage.getViewport({ scale: 1.35 });
        const ctx = canvas.getContext("2d");
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        await pdfPage.render({ canvasContext: ctx, viewport }).promise;
        pageInfo.textContent = `Page ${num} of ${Math.min(pdfDoc.numPages, maxPage)}`;
        rendering = false;
    }

    prevBtn?.addEventListener("click", async () => {
        if (pageNum <= 1 || rendering) return;
        pageNum -= 1;
        await renderPage(pageNum);
    });

    nextBtn?.addEventListener("click", async () => {
        const limit = Math.min(pdfDoc?.numPages || 1, maxPage);
        if (pageNum >= limit || rendering) return;
        pageNum += 1;
        await renderPage(pageNum);
    });

    (async () => {
        try {
            const token = await ensureApiToken();
            const bookData = await fetchWithToken(token, "/api/v1/books/" + bookId);
            const book = bookData?.data || {};

            const fullAccess = !!book.has_full_access;
            const canPreview = !!book.can_preview;
            maxPage = fullAccess ? Infinity : (book.trial_pages || 15);

            let url;
            let useAuth = true;

            if (fullAccess && book.download_url) {
                url = book.download_url;
            } else if (canPreview && book.preview_url) {
                url = book.preview_url;
                useAuth = false;
            } else if (!fullAccess && (book.price || 0) > 0) {
                showError("Subscribe or buy this book to read beyond the free preview.");
                return;
            } else {
                showError("PDF is not available for this book.");
                return;
            }

            const blob = await fetchPdfBlob(url, token, useAuth);
            const pdfjsLib = await loadPdfJs();
            const buffer = await blob.arrayBuffer();
            pdfDoc = await pdfjsLib.getDocument({ data: buffer }).promise;

            if (!fullAccess) {
                maxPage = Math.min(maxPage, pdfDoc.numPages);
            }

            pageNum = 1;
            prevBtn.disabled = false;
            nextBtn.disabled = pdfDoc.numPages <= 1;
            await renderPage(1);
        } catch (e) {
            showError(e.message || "Failed to load book.");
        }
    })();
}
