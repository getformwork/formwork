import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { StatisticsChart } from "../statistics-chart";
import { triggerDownload } from "../../utils/forms";

export class Dashboard {
    constructor() {
        const clearCacheCommand = $("[data-view=dashboard] [data-command=clear-cache]");
        const makeBackupCommand = $("[data-view=dashboard] [data-command=make-backup]");
        const chart = $(".dashboard-chart");

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener("click", () => {
                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}cache/clear/`,
                        data: { "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();
                    },
                );
            });
        }

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener("click", function () {
                const button = this as HTMLButtonElement;
                button.disabled = true;

                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}backup/make/`,
                        data: { "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();

                        if (response.status === "success") {
                            setTimeout(() => {
                                button.disabled = false;
                                triggerDownload(response.data.uri, ($("meta[name=csrf-token]") as HTMLMetaElement).content);
                            }, 1000);
                        }

                        if (response.status === "error") {
                            button.disabled = false;
                        }
                    },
                );
            });
        }

        if (chart) {
            const chartData = chart.dataset.chartData;
            if (chartData) {
                new StatisticsChart(chart, JSON.parse(chartData));
            }
        }
    }
}
