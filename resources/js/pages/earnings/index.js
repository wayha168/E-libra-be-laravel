import { ensureApiToken } from "../../shared/token.js";
import { fetchJson } from "../../shared/api.js";
import { DashboardChartManager } from "../../shared/dashboardCharts.js";

export function initEarningsPage() {
    if (!document.getElementById("authorEarningsPage")) return;

    const periodSelect = document.getElementById("authorChartPeriod");
    const chartManager = new DashboardChartManager();

    async function loadChart(period) {
        const token = await ensureApiToken();
        if (!token) return;

        const { res, data: payload } = await fetchJson(
            `/api/v1/author/earnings?period=${encodeURIComponent(period)}`,
            { token, silent: true },
        );
        if (!res.ok) return;

        chartManager.renderAuthorIncome(
            document.getElementById("authorIncomeChart"),
            payload.data?.charts,
        );
    }

    loadChart(periodSelect?.value || "6m");

    periodSelect?.addEventListener("change", () => {
        loadChart(periodSelect.value);
    });
}
