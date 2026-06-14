import { ensureApiToken } from "../../shared/token.js";
import { fetchWithToken, fetchJson } from "../../shared/api.js";
import { initEcho } from "../../shared/echo.js";
import { DashboardChartManager, renderChartsForTab } from "../../shared/dashboardCharts.js";

export function initDashboardPage() {
    if (!document.getElementById("overviewContent")) return;

    const loading = document.getElementById("overviewLoading");
    const content = document.getElementById("overviewContent");
    const errorBox = document.getElementById("overviewError");
    const liveIndicator = document.getElementById("liveIndicator");
    const chartPeriod = document.getElementById("chartPeriod");

    const chartManager = new DashboardChartManager();
    let currentCharts = null;
    let activeChartTab = "income";
    let apiToken = null;
    let overviewScope = content?.dataset.defaultScope || "admin";

    function showError(message) {
        if (errorBox) {
            errorBox.textContent = message;
            errorBox.classList.remove("hidden");
        }
    }

    function setText(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function setLabel(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    }

    function toggleHidden(id, hidden) {
        const el = document.getElementById(id);
        if (el) el.classList.toggle("hidden", hidden);
    }

    function applyAuthorLayout(totals) {
        setLabel("labelBooks", "My Books");
        setLabel("labelCategories", "Categories");
        setLabel("labelUsers", "Paid Sales");
        setLabel("labelRevenue", "Net Earnings");
        setLabel("labelPaid", "Gross Revenue");
        setLabel("labelPending", "Platform Fee");
        setLabel("labelComments", "Book Comments");
        setLabel("labelCommission", "Total Sales");

        toggleHidden("cardPending", false);
        toggleHidden("cardCommission", false);
        toggleHidden("sectionRecentUsers", true);
        document.querySelectorAll("[data-admin-only]").forEach((el) => el.classList.add("hidden"));

        setText("bookCount", totals.books ?? 0);
        setText("categoryCount", totals.categories ?? 0);
        setText("userCount", totals.sales_count ?? 0);
        setText("revenueCount", "$" + Number(totals.net_earnings || 0).toFixed(2));
        setText("purchasesPaidCount", "$" + Number(totals.gross_revenue || 0).toFixed(2));
        setText("purchasesPendingCount", "$" + Number(totals.platform_fee || 0).toFixed(2));
        setText("commentsCount", totals.comments ?? 0);
        setText("adminCommissionCount", totals.sales_count ?? 0);

        setText("recentSalesTitle", "My Recent Sales");
        setText("recentCommentsTitle", "Comments on My Books");
        setText("chartsSubtitle", "Your sales income over time");
    }

    function applyAdminLayout() {
        setLabel("labelBooks", "Books");
        setLabel("labelCategories", "Categories");
        setLabel("labelUsers", "Registered Users");
        setLabel("labelRevenue", "Revenue");
        setLabel("labelPaid", "Paid Purchases");
        setLabel("labelPending", "Pending Purchases");
        setLabel("labelComments", "Book Comments");
        setLabel("labelCommission", "Admin Commission (10%)");

        toggleHidden("cardPending", false);
        toggleHidden("cardCommission", false);
        toggleHidden("sectionRecentUsers", false);
        document.querySelectorAll("[data-admin-only]").forEach((el) => el.classList.remove("hidden"));

        setText("recentSalesTitle", "Recent Purchases");
        setText("recentCommentsTitle", "Recent Book Feedback");
        setText("chartsSubtitle", "Income, registrations, purchases, and library stats");
    }

    function statusBadge(status) {
        const colors = {
            paid: "bg-green-50 text-green-700",
            pending: "bg-amber-50 text-amber-700",
            canceled: "bg-gray-100 text-gray-600",
        };
        const cls = colors[status] || colors.canceled;
        return `<span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-medium ${cls}">${status}</span>`;
    }

    function renderCharts(charts, scope) {
        currentCharts = charts;
        chartManager.showPanel(activeChartTab);

        if (scope === "author" && charts?.author_income) {
            chartManager.renderAuthorIncome(document.getElementById("chartIncome"), charts.author_income);
            return;
        }

        renderChartsForTab(chartManager, activeChartTab, charts);
    }

    async function loadOverview(period) {
        const payload = await fetchWithToken(
            apiToken,
            `/api/v1/dashboard/overview?period=${encodeURIComponent(period || "6m")}`,
        );
        overviewScope = payload.data?.scope || overviewScope;
        renderOverview(payload.data || {});
        renderCharts(payload.data?.charts || null, overviewScope);
        return payload;
    }

    function renderOverview(data) {
        const totals = data.totals || {};
        overviewScope = data.scope || overviewScope;

        if (overviewScope === "author") {
            applyAuthorLayout(totals);
        } else {
            applyAdminLayout();
            setText("bookCount", totals.books ?? 0);
            setText("categoryCount", totals.categories ?? 0);
            setText("userCount", totals.users ?? 0);
            setText("revenueCount", "$" + Number(totals.revenue || 0).toFixed(2));
            setText("purchasesPaidCount", totals.purchases_paid ?? 0);
            setText("purchasesPendingCount", totals.purchases_pending ?? 0);
            setText("commentsCount", totals.comments ?? 0);
            setText("adminCommissionCount", "$" + Number(totals.admin_commission || 0).toFixed(2));
        }

        const recentUsers = document.getElementById("recentUsers");
        if (recentUsers && overviewScope === "admin") {
            const users = data.recent_users || [];
            recentUsers.innerHTML = users.length
                ? users.map((u) => `<div class="flex items-center justify-between rounded-lg bg-gray-50 px-3 py-2">
                    <div><div class="font-medium text-gray-900">${u.name}</div><div class="text-xs text-gray-500">${u.email}</div></div>
                    <div class="text-xs text-gray-400">${new Date(u.created_at).toLocaleDateString()}</div>
                </div>`).join("")
                : '<span class="text-gray-400">No users yet.</span>';
        }

        const sales = overviewScope === "author"
            ? (data.recent_sales || [])
            : (data.recent_purchases || []);
        renderPurchases(sales, overviewScope);

        const recentComments = document.getElementById("recentComments");
        if (recentComments) {
            const comments = data.recent_comments || [];
            recentComments.innerHTML = comments.length
                ? comments.map((c) => `<div class="rounded-lg bg-gray-50 px-3 py-2">
                    <div class="text-xs text-gray-500">${c.user?.name || "User"} on <strong>${c.book?.title || "Book"}</strong></div>
                    <div class="text-sm text-gray-800 mt-1">${c.body}</div>
                </div>`).join("")
                : '<span class="text-gray-400">No comments yet.</span>';
        }
    }

    function renderPurchases(purchases, scope) {
        const recentPurchases = document.getElementById("recentPurchases");
        if (!recentPurchases) return;

        if (!purchases.length) {
            recentPurchases.innerHTML = scope === "author"
                ? '<span class="text-gray-400">No sales on your books yet.</span>'
                : '<span class="text-gray-400">No purchases yet.</span>';
            return;
        }

        recentPurchases.innerHTML = purchases.map((p) => {
            const amount = scope === "author"
                ? Number(p.author_earnings ?? p.amount ?? 0).toFixed(2)
                : Number(p.amount || 0).toFixed(2);
            const subtitle = scope === "author"
                ? `${p.book?.title || "Book"} · you receive $${amount}`
                : `${p.book?.title || "Book"} · $${amount}`;

            return `<div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2" data-purchase-id="${p.id}">
                <div class="min-w-0">
                    <div class="font-medium text-gray-900 truncate">${p.user?.name || "Buyer"}</div>
                    <div class="text-xs text-gray-500 truncate">${subtitle}</div>
                </div>
                ${scope === "admin" ? statusBadge(p.status) : `<span class="text-xs font-semibold text-green-700">$${amount}</span>`}
            </div>`;
        }).join("");
    }

    function renderRecommendations(items) {
        const el = document.getElementById("recommendationsList");
        if (!el) return;

        if (!items?.length) {
            el.innerHTML =
                '<div class="col-span-full text-center text-gray-400 py-6">Like, review, or buy books to get personalized recommendations.</div>';
            return;
        }

        el.innerHTML = items
            .map(
                (book) =>
                    `<a href="${book.show_url || "/dashboard/books/" + book.id}" class="block rounded-xl border border-gray-200 p-4 hover:border-gray-300 hover:bg-gray-50 transition">
                <div class="font-medium text-gray-900 line-clamp-2">${escapeHtml(book.title || "Book")}</div>
                <div class="text-xs text-purple-700 mt-1">${escapeHtml(book.reason || "Recommended")}</div>
                <div class="text-xs text-gray-500 mt-2">${escapeHtml(book.category?.name || book.category || "Uncategorized")}${book.author?.name || book.author ? " · " + escapeHtml(book.author?.name || book.author) : ""}${book.purchase_count > 0 ? " · " + book.purchase_count + " bought" : ""}</div>
                <div class="text-sm font-semibold mt-2">${book.price > 0 ? "$" + Number(book.price).toFixed(2) : "Free"}</div>
            </a>`,
            )
            .join("");
    }

    function escapeHtml(value) {
        return String(value ?? "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;");
    }

    async function loadRecommendations() {
        try {
            const payload = await fetchWithToken(apiToken, "/api/v1/recommendations?limit=6");
            renderRecommendations(payload?.data || []);
        } catch {
            /* ignore */
        }
    }

    function upsertPurchase(purchase) {
        if (overviewScope !== "admin") return;
        const recentPurchases = document.getElementById("recentPurchases");
        if (!recentPurchases || !purchase?.id) return;

        const existing = recentPurchases.querySelector(`[data-purchase-id="${purchase.id}"]`);
        const html = `<div class="flex items-center justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2" data-purchase-id="${purchase.id}">
            <div class="min-w-0">
                <div class="font-medium text-gray-900 truncate">${purchase.user?.name || "User"}</div>
                <div class="text-xs text-gray-500 truncate">${purchase.book?.title || "Book"} · $${Number(purchase.amount || 0).toFixed(2)}</div>
            </div>
            ${statusBadge(purchase.status)}
        </div>`;

        if (existing) {
            existing.outerHTML = html;
        } else {
            const empty = recentPurchases.querySelector(".text-gray-400");
            if (empty) empty.remove();
            recentPurchases.insertAdjacentHTML("afterbegin", html);
        }
    }

    document.querySelectorAll("[data-chart-tab]").forEach((btn) => {
        btn.addEventListener("click", () => {
            activeChartTab = btn.dataset.chartTab;
            chartManager.showPanel(activeChartTab);
            renderCharts(currentCharts, overviewScope);
        });
    });

    if (chartPeriod) {
        chartPeriod.addEventListener("change", async () => {
            try {
                await loadOverview(chartPeriod.value);
            } catch (e) {
                showError(e.message || "Failed to reload charts.");
            }
        });
    }

    (async () => {
        try {
            apiToken = await ensureApiToken();
            if (!apiToken) {
                window.location.href = "/login";
                return;
            }

            await loadOverview(chartPeriod?.value || "6m");
            await loadRecommendations();
            if (loading) loading.classList.add("hidden");
            if (content) content.classList.remove("hidden");

            if (overviewScope !== "author") {
                const echo = initEcho(apiToken);
                if (echo) {
                    if (liveIndicator) liveIndicator.classList.remove("hidden");

                    echo.private("dashboard.overview")
                        .listen(".dashboard.stats", (e) => {
                            if (!e.stats) return;
                            setText("bookCount", e.stats.books ?? 0);
                            setText("categoryCount", e.stats.categories ?? 0);
                            setText("userCount", e.stats.users ?? 0);
                            setText("revenueCount", "$" + Number(e.stats.revenue || 0).toFixed(2));
                            setText("purchasesPaidCount", e.stats.purchases_paid ?? 0);
                            setText("purchasesPendingCount", e.stats.purchases_pending ?? 0);
                            setText("commentsCount", e.stats.comments ?? 0);
                            setText("adminCommissionCount", "$" + Number(e.stats.admin_commission || 0).toFixed(2));
                            loadOverview(chartPeriod?.value || "6m").catch(() => {});
                        })
                        .listen(".purchase.updated", (e) => {
                            if (e.purchase) upsertPurchase(e.purchase);
                        });
                }
            }
        } catch (e) {
            showError(e.message || "Network error loading overview.");
        }
    })();
}
