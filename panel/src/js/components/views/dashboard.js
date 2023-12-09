import { $, $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Chart } from "../chart";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { triggerDownload } from "../../utils/forms";

export class Dashboard {
    constructor() {
        $$("[data-chart-data]").forEach((element) => {
            const data = JSON.parse(element.dataset.chartData);
            new Chart(element, data);
        });

        const clearCacheCommand = $("[data-command=clear-cache]");
        const makeBackupCommand = $("[data-command=make-backup]");

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener("click", () => {
                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}cache/clear/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").content },
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
                const button = this;
                button.disabled = true;
                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}backup/make/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").content },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();
                        setTimeout(() => {
                            if (response.status === "success") {
                                triggerDownload(response.data.uri, $("meta[name=csrf-token]").content);
                            }
                            button.disabled = false;
                        }, 1000);
                    },
                );
            });
        }
    }
}
