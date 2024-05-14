import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { StatisticsChart } from "../statistics-chart";
import { triggerDownload } from "../../utils/forms";

export class Dashboard {
    constructor() {
        const clearCacheCommand = $("[data-view=dashboard] [data-command=clear-cache]");
        const clearPagesCacheCommand = $("[data-view=dashboard] [data-command=clear-pages-cache]");
        const clearImagesCacheCommand = $("[data-view=dashboard] [data-command=clear-images-cache]");
        const makeBackupCommand = $("[data-view=dashboard] [data-command=make-backup]");
        const chart = $(".dashboard-chart");

        const clearCache = (type?: string) => {
            new Request(
                {
                    method: "POST",
                    url: `${app.config.baseUri}cache/clear/${type ?? ""}/`.replace(/\/+$/, "/"),
                    data: { "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content },
                },
                (response) => {
                    const icon = response.status === "error" ? "exclamation-octagon" : "check-circle";
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
