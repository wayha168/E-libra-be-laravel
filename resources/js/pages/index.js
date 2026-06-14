import { initLayout } from "../bootstrap/layout.js";
import { initSidebar } from "../bootstrap/sidebar.js";
import { initAutoSearch } from "../shared/autoSearch.js";
import { initNotifications, initActivityLive } from "../shared/notifications.js";
import { initPresence } from "../shared/presence.js";
import { initDashboardPage } from "./dashboard/index.js";
import { initProfilePage } from "./profile/index.js";
import { initEarningsPage } from "./earnings/index.js";
import { initLoginPage } from "./login/index.js";
import { initHomePage } from "./home/index.js";
import { initBookFeedbackPage } from "./books/feedback.js";
import { initBookReadPage } from "./books/read.js";

document.addEventListener("DOMContentLoaded", () => {
    initLayout();
    initSidebar();
    initAutoSearch();
    initNotifications();
    initActivityLive();
    initPresence();
    initDashboardPage();
    initProfilePage();
    initEarningsPage();
    initLoginPage();
    initHomePage();
    initBookFeedbackPage();
    initBookReadPage();
});
