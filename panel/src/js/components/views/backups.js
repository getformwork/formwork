import { $, $$ } from "../../utils/selectors";
import { app } from "../../app";
import { insertIcon } from "../icons";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { triggerDownload } from "../../utils/forms";

export class Backups {
    constructor() {
        const makeBackupCommand = $("[data-view=backups] [data-command=make-backup]");

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener("click", function () {
                const button = this;

                const getSpinner = () => {
                    let spinner = $(".spinner");

                    if (!spinner) {
                        spinner = document.createElement("div");
                        button.insertAdjacentElement("afterend", spinner);
                    }

                    spinner.className = "spinner";
                    spinner.innerHTML = "";

                    return spinner;
                };

                const spinner = getSpinner();

                button.disabled = true;

                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}backup/make/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").content },
                    },
                    (response) => {
                        if (response.status === "success") {
                            button.disabled = false;

                            spinner.classList.add("spinner-success");
                            insertIcon("check", spinner);

                            const template = $("#backups-row");
                            if (template) {
                                const table = $("#backups-table");

                                const node = template.content.cloneNode(true);

                                $(".backup-uri", node).href = response.data.uri;
                                $(".backup-uri", node).innerHTML = response.data.filename;

                                $(".backup-date", node).innerHTML = response.data.date;
                                $(".backup-size", node).innerHTML = response.data.size;
                                $(".backup-delete", node).dataset.modalAction = response.data.deleteUri;

                                $(".backup-last-time").innerHTML = app.config.Backups.labels.now;

                                $("tbody", table).prepend(node);

                                const limit = response.data.maxFiles;

                                $$("tr", table).forEach((row, index) => {
                                    if (index + 1 > limit) {
                                        row.remove();
                                    }
                                });
                            }
                        }

                        if (response.status === "error") {
                            spinner.classList.add("spinner-error");
                            insertIcon("exclamation", spinner);
                            button.disabled = false;
                        }

                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();

                        if (response.status === "success") {
                            setTimeout(() => {
                                triggerDownload(response.data.uri, $("meta[name=csrf-token]").content);
                            }, 1000);
                        }
                    },
                );
            });
        }
    }
}
