import { check, exclamation, info } from "../icons";
import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";

export class Updates {
    constructor() {
        const updaterComponent = document.getElementById("updater-component");

        if (updaterComponent) {
            const updateStatus = $(".update-status") as HTMLElement;
            const spinner = $(".spinner") as HTMLElement;
            const currentVersion = $(".current-version") as HTMLElement;
            const currentVersionName = $(".current-version-name") as HTMLElement;
            const newVersion = $(".new-version") as HTMLElement;
            const newVersionName = $(".new-version-name") as HTMLElement;
            const installCommand = $("[data-command=install-updates]") as HTMLElement;

            const showNewVersion = (name: string) => {
                spinner.classList.add("spinner-info");
                spinner.innerHTML = info;
                newVersionName.innerText = name;
                newVersion.style.display = "block";
            };

            const showCurrentVersion = () => {
                spinner.classList.add("spinner-success");
                spinner.innerHTML = check;
                currentVersion.style.display = "block";
            };

            const showInstalledVersion = () => {
                spinner.classList.add("spinner-success");
                spinner.innerHTML = check;
                currentVersionName.innerText = newVersionName.innerText;
                currentVersion.style.display = "block";
            };

            window.setTimeout(() => {
                const data = { "csrf-token": app.config.csrfToken };

                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}updates/check/`,
                        data: data,
                    },
                    (response) => {
                        updateStatus.innerText = response.message;

                        if (response.status === "success") {
                            if (response.data.uptodate === false) {
                                showNewVersion(response.data.release.name);
                            } else {
                                showCurrentVersion();
                            }
                        } else {
                            spinner.classList.add("spinner-danger");
                            spinner.innerHTML = exclamation;
                        }
                    },
                );
            }, 1000);

            installCommand.addEventListener("click", () => {
                newVersion.style.display = "none";
                spinner.classList.remove("spinner-info");
                $(".icon", spinner)?.remove();
                updateStatus.innerText = updateStatus.dataset.installingText ?? "";

                new Request(
                    {
                        method: "POST",
                        url: `${app.config.baseUri}updates/update/`,
                        data: { "csrf-token": app.config.csrfToken },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
                        notification.show();

                        updateStatus.innerText = response.data.status;

                        if (response.status === "success") {
                            showInstalledVersion();
                        } else {
                            spinner.classList.add("spinner-danger");
                            spinner.innerHTML = exclamation;
                        }
                    },
                );
            });
        }
    }
}
