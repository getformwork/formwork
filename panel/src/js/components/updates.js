import Icons from "./icons";
import Notification from "./notification";
import Request from "./request";

export default {
    init: function () {
        const updaterComponent = document.getElementById("updater-component");

        if (updaterComponent) {
            const updateStatus = $(".update-status");
            const spinner = $(".spinner");
            const currentVersion = $(".current-version");
            const currentVersionName = $(".current-version-name");
            const newVersion = $(".new-version");
            const newVersionName = $(".new-version-name");

            const showNewVersion = (name) => {
                spinner.classList.add("spinner-info");
                Icons.inject("info", spinner);
                newVersionName.innerHTML = name;
                newVersion.style.display = "block";
            };

            const showCurrentVersion = () => {
                spinner.classList.add("spinner-success");
                Icons.inject("check", spinner);
                currentVersion.style.display = "block";
            };

            const showInstalledVersion = () => {
                spinner.classList.add("spinner-success");
                Icons.inject("check", spinner);
                currentVersionName.innerHTML = newVersionName.innerHTML;
                currentVersion.style.display = "block";
            };

            setTimeout(() => {
                const data = { "csrf-token": $("meta[name=csrf-token]").getAttribute("content") };

                Request(
                    {
                        method: "POST",
                        url: `${Formwork.config.baseUri}updates/check/`,
                        data: data,
                    },
                    (response) => {
                        updateStatus.innerHTML = response.message;

                        if (response.status === "success") {
                            if (response.data.uptodate === false) {
                                showNewVersion(response.data.release.name);
                            } else {
                                showCurrentVersion();
                            }
                        } else {
                            spinner.classList.add("spinner-error");
                            Icons.inject("exclamation", spinner);
                        }
                    },
                );
            }, 1000);

            $("[data-command=install-updates]").addEventListener("click", () => {
                newVersion.style.display = "none";
                spinner.classList.remove("spinner-info");
                updateStatus.innerHTML = updateStatus.dataset.installingText;

                Request(
                    {
                        method: "POST",
                        url: `${Formwork.config.baseUri}updates/update/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").getAttribute("content") },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();

                        updateStatus.innerHTML = response.data.status;

                        if (response.status === "success") {
                            showInstalledVersion();
                        } else {
                            spinner.classList.add("spinner-error");
                            Icons.inject("exclamation", spinner);
                        }
                    },
                );
            });
        }
    },
};
