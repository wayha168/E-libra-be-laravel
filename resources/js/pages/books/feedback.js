import { ensureApiToken } from "../../shared/token.js";
import { fetchJson } from "../../shared/api.js";

function escapeHtml(value) {
    return String(value ?? "")
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;");
}

function formatDate(value) {
    if (!value) return "—";
    try {
        return new Date(value).toLocaleString();
    } catch {
        return "—";
    }
}

function paginatedItems(payload) {
    const block = payload?.data;
    if (Array.isArray(block)) return block;
    if (Array.isArray(block?.data)) return block.data;
    return [];
}

function paginatedNext(payload) {
    const block = payload?.data;
    if (!block || Array.isArray(block)) return null;
    return block.current_page < block.last_page ? (block.current_page + 1) : null;
}

export function initBookFeedbackPage() {
    const root = document.getElementById("bookFeedbackRoot");
    if (!root) return;

    const bookId = root.dataset.bookId;
    const likeCountEl = document.getElementById("bookLikeCount");
    const commentCountEl = document.getElementById("bookCommentCount");
    const likesList = document.getElementById("bookLikesList");
    const commentsList = document.getElementById("bookCommentsList");
    const likesMoreBtn = document.getElementById("bookLikesMore");
    const commentsMoreBtn = document.getElementById("bookCommentsMore");

    let apiToken = null;
    let likesPage = 1;
    let commentsPage = 1;
    let likesHasMore = false;
    let commentsHasMore = false;

    async function api(path) {
        const { res, data } = await fetchJson(path, { token: apiToken });
        if (!res.ok) throw new Error(data?.message || "Request failed");
        return data;
    }

    function updateMeta(meta) {
        if (!meta) return;
        if (likeCountEl) likeCountEl.textContent = meta.likes_count ?? 0;
        if (commentCountEl) commentCountEl.textContent = meta.comments_count ?? 0;
    }

    function renderLikeRows(items, append = false) {
        if (!likesList) return;

        if (!append) {
            likesList.innerHTML = "";
        }

        if (!items.length && !append) {
            likesList.innerHTML = '<tr><td colspan="3" class="px-4 py-6 text-center text-gray-400">No likes yet.</td></tr>';
            return;
        }

        const html = items.map((like) => {
            const user = like.user || {};
            return `<tr class="hover:bg-gray-50/50">
                <td class="px-4 py-2 font-medium text-gray-900">${escapeHtml(user.name || "User")}</td>
                <td class="px-4 py-2 text-gray-600">${escapeHtml(user.email || "—")}</td>
                <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">${escapeHtml(formatDate(like.created_at))}</td>
            </tr>`;
        }).join("");

        likesList.insertAdjacentHTML("beforeend", html);
    }

    function renderCommentRows(items, append = false) {
        if (!commentsList) return;

        if (!append) {
            commentsList.innerHTML = "";
        }

        if (!items.length && !append) {
            commentsList.innerHTML = '<tr><td colspan="4" class="px-4 py-6 text-center text-gray-400">No comments yet.</td></tr>';
            return;
        }

        const html = items.map((comment) => {
            const user = comment.user || {};
            return `<tr class="hover:bg-gray-50/50 align-top">
                <td class="px-4 py-2 font-medium text-gray-900">${escapeHtml(user.name || "User")}</td>
                <td class="px-4 py-2 text-gray-600">${escapeHtml(user.email || "—")}</td>
                <td class="px-4 py-2 text-gray-800 whitespace-pre-wrap max-w-md">${escapeHtml(comment.body)}</td>
                <td class="px-4 py-2 text-gray-500 text-xs whitespace-nowrap">${escapeHtml(formatDate(comment.created_at))}</td>
            </tr>`;
        }).join("");

        commentsList.insertAdjacentHTML("beforeend", html);
    }

    async function loadLikes(page = 1, append = false) {
        const payload = await api(`/api/v1/books/${bookId}/likes?page=${page}`);
        const items = paginatedItems(payload);
        likesHasMore = paginatedNext(payload) !== null;
        renderLikeRows(items, append);
        updateMeta(payload.meta);
        likesMoreBtn?.classList.toggle("hidden", !likesHasMore);
    }

    async function loadComments(page = 1, append = false) {
        const payload = await api(`/api/v1/books/${bookId}/comments?page=${page}`);
        const items = paginatedItems(payload);
        commentsHasMore = paginatedNext(payload) !== null;
        renderCommentRows(items, append);
        updateMeta(payload.meta);
        commentsMoreBtn?.classList.toggle("hidden", !commentsHasMore);
    }

    likesMoreBtn?.addEventListener("click", async () => {
        if (!likesHasMore) return;
        likesPage += 1;
        try {
            await loadLikes(likesPage, true);
        } catch (e) {
            alert(e.message);
            likesPage -= 1;
        }
    });

    commentsMoreBtn?.addEventListener("click", async () => {
        if (!commentsHasMore) return;
        commentsPage += 1;
        try {
            await loadComments(commentsPage, true);
        } catch (e) {
            alert(e.message);
            commentsPage -= 1;
        }
    });

    (async () => {
        apiToken = await ensureApiToken();
        if (!apiToken) return;

        try {
            await Promise.all([loadLikes(1), loadComments(1)]);
        } catch (e) {
            const msg = `<tr><td colspan="3" class="px-4 py-6 text-center text-red-600">${escapeHtml(e.message)}</td></tr>`;
            if (likesList) likesList.innerHTML = msg;
            if (commentsList) {
                commentsList.innerHTML = `<tr><td colspan="4" class="px-4 py-6 text-center text-red-600">${escapeHtml(e.message)}</td></tr>`;
            }
        }
    })();
}
