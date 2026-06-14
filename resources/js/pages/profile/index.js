import { ensureApiToken } from "../../shared/token.js";
import { fetchJson, fetchWithToken, postJson, isAuthorRole } from "../../shared/api.js";
import { initEcho } from "../../shared/echo.js";

export function initProfilePage() {
    if (!document.getElementById("userProfilePage")) return;

    const loading = document.getElementById("loading");
    const profile = document.getElementById("profile");
    const errorBox = document.getElementById("error");
    const errorText = document.getElementById("errorText");
    const paymentBanner = document.getElementById("paymentBanner");
    const subscribeBtn = document.getElementById("subscribeBtn");
    const subscriptionHint = document.getElementById("subscriptionHint");
    const stripeConfigHint = document.getElementById("stripeConfigHint");
    const booksList = document.getElementById("booksList");
    const purchasesList = document.getElementById("purchasesList");

    let apiToken = null;
    let purchasedBookIds = new Set();
    let userSubscribed = false;
    let subscriptionAmount = 9.99;
    let booksCache = [];
    let userRole = "";
    let khqrEnabled = true;
    let commissionRate = 10;

    function showError(message) {
        if (!errorBox) return;
        if (errorText) errorText.textContent = message;
        errorBox.classList.remove("hidden");
    }

    function showPaymentBanner(type) {
        if (!paymentBanner) return;

        const params = new URLSearchParams(window.location.search);
        const payment = params.get("payment");
        const paymentType = params.get("type");

        if (payment === "success") {
            paymentBanner.className = "mb-4 rounded-xl border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800";
            paymentBanner.textContent = paymentType === "subscription"
                ? "Subscription payment successful! Your access will activate shortly."
                : "Payment successful! Your book purchase will appear below once confirmed.";
            paymentBanner.classList.remove("hidden");
        } else if (payment === "cancelled") {
            paymentBanner.className = "mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800";
            paymentBanner.textContent = "Payment was cancelled. You can try again anytime.";
            paymentBanner.classList.remove("hidden");
        } else if (type) {
            paymentBanner.className = "mb-4 rounded-xl border px-4 py-3 text-sm";
            paymentBanner.textContent = type;
            paymentBanner.classList.remove("hidden");
        }
    }

    function redirectToCheckout(data) {
        const checkoutUrl = data?.data?.checkout_url;
        if (checkoutUrl) {
            window.location.href = checkoutUrl;
            return true;
        }

        if (data?.data?.purchase?.status === "paid" || data?.data?.user_subscribe) {
            showPaymentBanner("Completed successfully.");
            window.location.reload();
            return true;
        }

        return false;
    }

    async function handleSubscribe() {
        if (!subscribeBtn) return;

        subscribeBtn.disabled = true;
        subscribeBtn.textContent = "Redirecting to Stripe…";

        try {
            const data = await postJson(apiToken, "/api/v1/user/subscribe");
            if (!redirectToCheckout(data)) {
                window.location.reload();
            }
        } catch (e) {
            showError(e.message || "Subscription failed.");
            subscribeBtn.disabled = false;
            subscribeBtn.textContent = "Subscribe via Stripe";
        }
    }

    async function handleBuyBook(bookId, button, paymentMethod = "card") {
        if (!button) return;

        button.disabled = true;
        const row = button.closest("[data-book-row]");
        row?.querySelectorAll(".buy-book-btn").forEach((b) => { b.disabled = true; });
        const originalText = button.textContent;
        button.textContent = "Redirecting…";

        try {
            const data = await postJson(apiToken, "/api/v1/books/" + bookId + "/buy", {
                payment_method: paymentMethod,
            });
            if (!redirectToCheckout(data)) {
                window.location.reload();
            }
        } catch (e) {
            showError(e.message || "Purchase failed.");
            row?.querySelectorAll(".buy-book-btn").forEach((b) => { b.disabled = false; });
            button.textContent = originalText;
        }
    }

    function renderBuyActions(book) {
        const price = Number(book.price).toFixed(2);
        const readUrl = book.read_url || ("/dashboard/books/" + book.id + "/read");
        const khqrBtn = khqrEnabled
            ? `<button type="button" data-buy-book="${book.id}" data-payment="khqr" class="buy-book-btn px-3 py-1.5 bg-emerald-600 text-white text-xs font-medium rounded-lg hover:bg-emerald-700 transition">Scan KHQR</button>`
            : "";

        const readBtn = book.has_pdf
            ? `<a href="${readUrl}" class="px-3 py-1.5 border border-gray-300 text-xs font-medium rounded-lg hover:bg-gray-50">${book.has_full_access ? "Read" : "Preview"}</a>`
            : "";

        return `<div class="flex flex-wrap items-center gap-1.5 justify-end">
            ${readBtn}
            <button type="button" data-buy-book="${book.id}" data-payment="card" class="buy-book-btn px-3 py-1.5 bg-black text-white text-xs font-medium rounded-lg hover:bg-gray-800 transition">Card $${price}</button>
            ${khqrBtn}
        </div>`;
    }

    function renderBooks(books) {
        if (!booksList) return;

        const paidBooks = books.filter((b) => b.price && Number(b.price) > 0);

        if (!paidBooks.length) {
            booksList.innerHTML = '<span class="text-sm text-gray-400">No paid books available.</span>';
            return;
        }

        booksList.innerHTML = paidBooks.map((book) => {
            const purchased = purchasedBookIds.has(book.id);
            const hasAccess = userSubscribed || purchased;
            const price = Number(book.price).toFixed(2);

            let action = "";
            if (hasAccess) {
                action = '<span class="inline-flex px-2 py-1 rounded-full text-xs font-medium bg-green-50 text-green-700">Owned</span>';
            } else {
                action = renderBuyActions(book);
            }

            return `<div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3" data-book-row="${book.id}">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">${book.title}</div>
                    <div class="text-xs text-gray-500">$${price}</div>
                </div>
                ${action}
            </div>`;
        }).join("");

        booksList.querySelectorAll(".buy-book-btn").forEach((btn) => {
            btn.addEventListener("click", () => {
                handleBuyBook(
                    btn.getAttribute("data-buy-book"),
                    btn,
                    btn.getAttribute("data-payment") || "card"
                );
            });
        });
    }

    function renderPurchases(purchases) {
        if (!purchasesList) return;

        if (!purchases.length) {
            purchasesList.innerHTML = '<span class="text-sm text-gray-400">No purchases yet.</span>';
            return;
        }

        purchasesList.innerHTML = purchases.map((record) => {
            const book = record.book || {};
            const amount = Number(record.amount || book.price || 0).toFixed(2);
            const date = record.purchased_at
                ? new Date(record.purchased_at).toLocaleString()
                : "-";

            return `<div class="flex items-center justify-between gap-3 rounded-lg border border-gray-100 bg-gray-50 px-4 py-3">
                <div class="min-w-0">
                    <div class="text-sm font-medium text-gray-900 truncate">${book.title || "Book"}</div>
                    <div class="text-xs text-gray-500">${date}</div>
                </div>
                <div class="text-sm font-semibold text-gray-900">$${amount}</div>
            </div>`;
        }).join("");
    }

    function updateSubscriptionUi() {
        if (subscribeBtn) {
            if (userSubscribed) {
                subscribeBtn.classList.add("hidden");
                if (subscriptionHint) {
                    subscriptionHint.textContent = "You have an active subscription with full library access.";
                }
            } else {
                subscribeBtn.classList.remove("hidden");
                subscribeBtn.textContent = `Subscribe via Stripe ($${subscriptionAmount.toFixed(2)})`;
                if (subscriptionHint) {
                    subscriptionHint.textContent = `Subscribe for $${subscriptionAmount.toFixed(2)} to access all paid books, or buy individual titles below.`;
                }
            }
        }

        if (stripeConfigHint) {
            const feeNote = commissionRate > 0
                ? ` Platform fee: ${commissionRate}% on each sale (author receives the rest).`
                : "";
            stripeConfigHint.textContent = khqrEnabled
                ? `Pay by card or scan KHQR via Stripe.${feeNote}`
                : `Payments are processed securely by Stripe.${feeNote}`;
        }
    }

    function renderAuthorEarnings(earnings) {
        const section = document.getElementById("authorEarningsSection");
        if (!section || !earnings?.has_author_profile) return;

        section.classList.remove("hidden");

        const set = (id, val) => {
            const el = document.getElementById(id);
            if (el) el.textContent = val;
        };

        set("authorSalesCount", earnings.sales_count ?? 0);
        set("authorGross", "$" + Number(earnings.gross_revenue || 0).toFixed(2));
        set("authorPlatformFee", "$" + Number(earnings.platform_fee_total || 0).toFixed(2));
        set("authorNetEarnings", "$" + Number(earnings.net_earnings || 0).toFixed(2));

        const list = document.getElementById("authorSalesList");
        if (!list) return;

        const sales = earnings.sales || [];
        list.innerHTML = sales.length
            ? sales.slice(0, 5).map((s) => `<div class="flex justify-between gap-2 rounded-lg bg-gray-50 px-3 py-2">
                <span class="min-w-0 truncate">${s.book_title || "Book"} · ${s.payment_method_label || "Card"} · ${s.buyer_name || "Buyer"}</span>
                <span class="shrink-0 text-green-700 font-medium">$${Number(s.author_earnings || 0).toFixed(2)}</span>
            </div>`).join("")
            : '<span class="text-gray-400">No sales yet on your books.</span>';
    }

    async function loadAuthorEarnings(profileData) {
        if (profileData?.author_earnings) {
            renderAuthorEarnings(profileData.author_earnings);
            return;
        }

        if (!isAuthorRole(userRole)) return;

        try {
            const earningsData = await fetchWithToken(apiToken, "/api/v1/author/earnings");
            if (earningsData?.data) renderAuthorEarnings(earningsData.data);
        } catch {
            /* no author profile linked */
        }
    }

    (async () => {
        try {
            showPaymentBanner();

            apiToken = await ensureApiToken();
            if (!apiToken) {
                window.location.href = "/login";
                return;
            }

            const [meData, stripeConfig] = await Promise.all([
                fetchWithToken(apiToken, "/api/v1/me"),
                fetchJson("/api/v1/stripe/config").then(({ data }) => data).catch(() => null),
            ]);

            const stripePayload = stripeConfig?.data ?? {};
            subscriptionAmount = stripePayload.subscription_amount ?? 9.99;
            khqrEnabled = stripePayload.khqr_enabled !== false;
            commissionRate = stripePayload.admin_commission_rate ?? 10;

            const u = meData.data || {};
            const name = u.name || "-";
            const email = u.email || "-";
            const role = u.role || "N/A";
            userRole = role;
            const status = u.status || "Active";
            userSubscribed = !!u.user_subscribe;
            const userId = u.id || "-";

            const avatarInitial = document.getElementById("avatarInitial");
            const elName = document.getElementById("name");
            const elEmail = document.getElementById("email");
            const roleBadge = document.getElementById("roleBadge");

            if (avatarInitial) avatarInitial.textContent = (name || "?").charAt(0).toUpperCase();
            if (elName) elName.textContent = name;
            if (elEmail) elEmail.textContent = email;
            if (roleBadge) {
                roleBadge.textContent = role;
                const roleLower = role.toLowerCase().replace(/\s+/g, "_");
                if (roleLower === "super_admin") {
                    roleBadge.className = "inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-purple-50 text-purple-700";
                } else if (roleLower === "admin") {
                    roleBadge.className = "inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700";
                } else if (roleLower === "author") {
                    roleBadge.className = "inline-flex px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700";
                }
            }

            const statusValue = document.getElementById("statusValue");
            const subscriptionValue = document.getElementById("subscriptionValue");
            if (statusValue) statusValue.textContent = status;
            if (subscriptionValue) subscriptionValue.textContent = userSubscribed ? "Active" : "None";

            const detailName = document.getElementById("detailName");
            const detailEmail = document.getElementById("detailEmail");
            const detailRole = document.getElementById("detailRole");
            const detailStatus = document.getElementById("detailStatus");
            const detailSubscription = document.getElementById("detailSubscription");
            const detailId = document.getElementById("detailId");

            if (detailName) detailName.textContent = name;
            if (detailEmail) detailEmail.textContent = email;
            if (detailRole) detailRole.textContent = role;
            if (detailStatus) detailStatus.textContent = status;
            if (detailSubscription) detailSubscription.textContent = userSubscribed ? "Subscribed" : "Not subscribed";
            if (detailId) detailId.textContent = userId;

            updateSubscriptionUi();

            if (subscribeBtn) {
                subscribeBtn.addEventListener("click", handleSubscribe);
            }

            const [booksData, permissionsData, purchasesData, profileData] = await Promise.all([
                fetchWithToken(apiToken, "/api/v1/books"),
                fetchWithToken(apiToken, "/api/v1/permissions"),
                fetchWithToken(apiToken, "/api/v1/user/purchases"),
                fetchJson("/api/v1/user/profile", { token: apiToken, silent: true }).then(({ data }) => data).catch(() => null),
            ]);

            const books = booksData?.data?.data || booksData?.data || [];
            const permissionsList = permissionsData?.data?.data || permissionsData?.data || [];
            const purchases = purchasesData?.data || [];

            purchasedBookIds = new Set(
                purchases.map((p) => p.book_id || p.book?.id).filter(Boolean),
            );

            booksCache = Array.isArray(books) ? books : [];

            const bookCount = booksData?.data?.total ?? books.length ?? "-";
            const permissionsCount = permissionsData?.data?.total ?? permissionsList.length ?? "-";

            const elBookCount = document.getElementById("bookCount");
            const elPermissionsCount = document.getElementById("permissionsCount");

            if (elBookCount) elBookCount.textContent = bookCount;
            if (elPermissionsCount) elPermissionsCount.textContent = permissionsCount;

            renderBooks(booksCache);
            renderPurchases(Array.isArray(purchases) ? purchases : []);
            await loadAuthorEarnings(profileData);

            const badges = document.getElementById("permissionBadges");
            if (badges) {
                if (Array.isArray(permissionsList) && permissionsList.length > 0) {
                    badges.innerHTML = permissionsList.map((p) => {
                        const displayName = p.display_name || p.name || "Unknown";
                        return `<span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-medium bg-green-50 border border-green-100 text-green-700">
                            <svg class="w-3 h-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5"/></svg>
                            ${displayName}
                        </span>`;
                    }).join("");
                } else {
                    badges.innerHTML = '<span class="text-sm text-gray-400">No permissions assigned</span>';
                }
            }

            if (loading) loading.classList.add("hidden");
            if (profile) profile.classList.remove("hidden");

            const echo = initEcho(apiToken);
            if (echo && userId && userId !== "-") {
                echo.private("purchases." + userId).listen(".purchase.updated", (e) => {
                    if (e.purchase?.status === "paid") {
                        purchasedBookIds.add(e.purchase.book_id);
                        renderBooks(booksCache);
                        fetchWithToken(apiToken, "/api/v1/user/purchases")
                            .then((data) => renderPurchases(data?.data || []))
                            .catch(() => {});
                        if (subscriptionValue) subscriptionValue.textContent = userSubscribed ? "Active" : "None";
                    }
                });
            }
        } catch (e) {
            showError(e.message || "Network error. Please try again.");
        }
    })();
}
