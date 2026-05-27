import { $, $$ } from "../../utils/selectors";
import { check, exclamation } from "../icons";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { triggerDownload } from "../../utils/forms";

export class Backups {
    constructor() {
        const makeBackupCommand = $("[data-view=backups] [data-command=make-backup]");

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener("click", function () {
                const button = this as HTMLButtonElement;

                let spinner = $(".spinner");

                if (!spinner) {
                    spinner = document.createElement("div");
                    button.insertAdjacentElement("afterend", spinner);
                }

                spinner.className = "spinner";
                spinner.innerText = "";

                button.disabled = true;

                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}backup/make/`,
                        data: { "csrf-token": app.config.csrfToken },
                    },
                    (response) => {
                        if (response.status === "success") {
                            button.disabled = false;

                            spinner.classList.add("spinner-success");
                            spinner.innerHTML = check;

                            const template = $("#backups-row") as HTMLTemplateElement;
                            if (template) {
                                const table = $("#backups-table") as HTMLTableElement;

                                const node = template.content.cloneNode(true) as HTMLElement;

                                ($(".backup-uri", node) as HTMLAnchorElement).href = response.data.uri;
                                ($(".backup-uri", node) as HTMLElement).innerText = response.data.filename;

                                ($(".backup-date", node) as HTMLElement).innerText = response.data.date;
                                ($(".backup-size", node) as HTMLElement).innerText = response.data.size;
                                ($(".backup-delete", node) as HTMLElement).dataset.modalAction = response.data.deleteUri;

                                const lastTime = $(".backup-last-time");
                                if (lastTime) {
                                    lastTime.innerText = app.config.Backups?.labels.now ?? "";
                                }

                                $("tbody", table)?.prepend(node);

                                const limit = response.data.maxFiles;

                                $$("tr", table).forEach((row, index) => {
                                    if (index + 1 > limit) {
                                        row.remove();
                                    }
                                });

                                ($("#backups-section") as HTMLDivElement).hidden = false;
                            }
                        }

                        if (response.status === "error") {
                            spinner.classList.add("spinner-danger");
                            spinner.innerHTML = exclamation;
                            button.disabled = false;
                        }

                        const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
                        notification.show();

                        if (response.status === "success") {
                            window.setTimeout(() => {
                                triggerDownload(response.data.uri, app.config.csrfToken);
                            }, 1000);
                        }
                    },
                );
            });
        }
    }
}
