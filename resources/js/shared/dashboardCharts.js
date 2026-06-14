import {
    Chart,
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    BarElement,
    ArcElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Legend,
    Tooltip,
    Filler,
} from "chart.js";

Chart.register(
    LineController,
    BarController,
    DoughnutController,
    LineElement,
    BarElement,
    ArcElement,
    PointElement,
    CategoryScale,
    LinearScale,
    Legend,
    Tooltip,
    Filler,
);

const palette = {
    revenue: { border: "#059669", bg: "rgba(5, 150, 105, 0.12)" },
    commission: { border: "#7c3aed", bg: "rgba(124, 58, 237, 0.12)" },
    users: { border: "#2563eb", bg: "rgba(37, 99, 235, 0.5)" },
    paid: { border: "#16a34a", bg: "rgba(22, 163, 74, 0.55)" },
    pending: { border: "#d97706", bg: "rgba(217, 119, 6, 0.55)" },
    doughnut: ["#111827", "#059669", "#2563eb", "#7c3aed", "#d97706", "#dc2626"],
};

const defaultOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: { position: "bottom", labels: { boxWidth: 12, font: { size: 11 } } },
    },
    scales: {
        x: { grid: { display: false }, ticks: { maxRotation: 45, font: { size: 10 } } },
        y: { beginAtZero: true, ticks: { font: { size: 10 } } },
    },
};

export class DashboardChartManager {
    constructor() {
        this.instances = {};
    }

    destroy(key) {
        if (this.instances[key]) {
            this.instances[key].destroy();
            delete this.instances[key];
        }
    }

    destroyAll() {
        Object.keys(this.instances).forEach((k) => this.destroy(k));
    }

    renderIncome(canvas, charts) {
        if (!canvas || !charts?.income) return;
        this.destroy("income");
        const { labels, income } = charts;
        this.instances.income = new Chart(canvas, {
            type: "line",
            data: {
                labels,
                datasets: [
                    {
                        label: "Revenue ($)",
                        data: income.revenue,
                        borderColor: palette.revenue.border,
                        backgroundColor: palette.revenue.bg,
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: "Admin commission ($)",
                        data: income.admin_commission,
                        borderColor: palette.commission.border,
                        backgroundColor: palette.commission.bg,
                        fill: true,
                        tension: 0.35,
                    },
                ],
            },
            options: defaultOptions,
        });
    }

    renderUsers(canvas, charts) {
        if (!canvas || !charts?.user_registrations) return;
        this.destroy("users");
        this.instances.users = new Chart(canvas, {
            type: "bar",
            data: {
                labels: charts.labels,
                datasets: [
                    {
                        label: "New registrations",
                        data: charts.user_registrations.registrations,
                        backgroundColor: palette.users.bg,
                        borderColor: palette.users.border,
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                ...defaultOptions,
                scales: {
                    ...defaultOptions.scales,
                    y: { ...defaultOptions.scales.y, ticks: { ...defaultOptions.scales.y.ticks, stepSize: 1 } },
                },
            },
        });
    }

    renderPurchases(canvas, charts) {
        if (!canvas || !charts?.purchases) return;
        this.destroy("purchases");
        this.instances.purchases = new Chart(canvas, {
            type: "bar",
            data: {
                labels: charts.labels,
                datasets: [
                    {
                        label: "Paid",
                        data: charts.purchases.paid,
                        backgroundColor: palette.paid.bg,
                        borderColor: palette.paid.border,
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                    {
                        label: "Pending",
                        data: charts.purchases.pending,
                        backgroundColor: palette.pending.bg,
                        borderColor: palette.pending.border,
                        borderWidth: 1,
                        borderRadius: 4,
                    },
                ],
            },
            options: {
                ...defaultOptions,
                scales: {
                    x: { ...defaultOptions.scales.x, stacked: false },
                    y: { ...defaultOptions.scales.y, stacked: false, ticks: { stepSize: 1 } },
                },
            },
        });
    }

    renderLibrary(canvas, charts) {
        if (!canvas || !charts?.library) return;
        this.destroy("library");
        this.instances.library = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: charts.library.labels,
                datasets: [{
                    data: charts.library.values,
                    backgroundColor: palette.doughnut.slice(0, charts.library.labels.length),
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: "bottom" } },
            },
        });
    }

    renderPurchaseStatus(canvas, charts) {
        if (!canvas || !charts?.purchase_status) return;
        this.destroy("status");
        this.instances.status = new Chart(canvas, {
            type: "doughnut",
            data: {
                labels: charts.purchase_status.labels,
                datasets: [{
                    data: charts.purchase_status.values,
                    backgroundColor: ["#16a34a", "#d97706", "#9ca3af"],
                    borderWidth: 0,
                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: "bottom" } },
            },
        });
    }

    renderAuthorIncome(canvas, chartData) {
        if (!canvas || !chartData?.labels) return;
        this.destroy("authorIncome");
        this.instances.authorIncome = new Chart(canvas, {
            type: "line",
            data: {
                labels: chartData.labels,
                datasets: [
                    {
                        label: "Gross ($)",
                        data: chartData.gross,
                        borderColor: palette.revenue.border,
                        backgroundColor: palette.revenue.bg,
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: "Platform fee ($)",
                        data: chartData.platform_fee,
                        borderColor: palette.commission.border,
                        backgroundColor: palette.commission.bg,
                        fill: true,
                        tension: 0.35,
                    },
                    {
                        label: "Your net ($)",
                        data: chartData.net,
                        borderColor: palette.users.border,
                        backgroundColor: "rgba(37, 99, 235, 0.12)",
                        fill: true,
                        tension: 0.35,
                    },
                ],
            },
            options: defaultOptions,
        });
    }

    showPanel(panelKey) {
        document.querySelectorAll("[data-chart-panel]").forEach((el) => {
            el.classList.toggle("hidden", el.dataset.chartPanel !== panelKey);
        });
        document.querySelectorAll("[data-chart-tab]").forEach((btn) => {
            const active = btn.dataset.chartTab === panelKey;
            btn.classList.toggle("bg-black", active);
            btn.classList.toggle("text-white", active);
            btn.classList.toggle("text-gray-700", !active);
            btn.classList.toggle("border-gray-300", !active);
        });
    }
}

export function renderChartsForTab(manager, tab, charts) {
    if (!manager || !charts) return;

    if (tab === "income") {
        manager.renderIncome(document.getElementById("chartIncome"), charts);
    } else if (tab === "users") {
        manager.renderUsers(document.getElementById("chartUsers"), charts);
    } else if (tab === "purchases") {
        manager.renderPurchases(document.getElementById("chartPurchases"), charts);
    } else if (tab === "library") {
        manager.renderLibrary(document.getElementById("chartLibrary"), charts);
        manager.renderPurchaseStatus(document.getElementById("chartPurchaseStatus"), charts);
    }
}
