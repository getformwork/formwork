import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { StatisticsChart } from "../statistics-chart";

export class Dashboard {
    constructor() {
        const clearCacheCommand = $("[data-view=dashboard] [data-command=clear-cache]");
        const clearPagesCacheCommand = $("[data-view=dashboard] [data-command=clear-pages-cache]");
        const clearImagesCacheCommand = $("[data-view=dashboard] [data-command=clear-images-cache]");
        const chart = $(".dashboard-chart");

        const clearCache = (type?: string) => {
            new Request(
                {
                    method: "POST",
                    url: `${app.config.baseUri}cache/clear/${type ?? ""}/`.replace(/\/+$/, "/"),
                    data: { "csrf-token": app.config.csrfToken },
                },
                (response) => {
                    const icon = response.status === "error" ? "exclamationOctagon" : "checkCircle";
                    const notification = new Notification(response.message, response.status, { icon });
                    notification.show();
                },
            );
        };

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener("click", () => clearCache());
        }

        if (clearPagesCacheCommand) {
            clearPagesCacheCommand.addEventListener("click", () => clearCache("pages"));
        }

        if (clearImagesCacheCommand) {
            clearImagesCacheCommand.addEventListener("click", () => clearCache("images"));
        }

        if (chart) {
            const chartData = chart.dataset.chartData;
            if (chartData) {
                new StatisticsChart(chart, JSON.parse(chartData));
            }
        }
    }
}
