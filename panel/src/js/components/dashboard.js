import Notification from "./notification";
import Request from "./request";
import Utils from "./utils";

export default {
    init: function () {
        const clearCacheCommand = $("[data-command=clear-cache]");
        const makeBackupCommand = $("[data-command=make-backup]");

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener("click", () => {
                Request(
                    {
                        method: "POST",
                        url: `${Formwork.config.baseUri}cache/clear/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").getAttribute("content") },
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
                button.setAttribute("disabled", "");
                Request(
                    {
                        method: "POST",
                        url: `${Formwork.config.baseUri}backup/make/`,
                        data: { "csrf-token": $("meta[name=csrf-token]").getAttribute("content") },
                    },
                    (response) => {
                        const notification = new Notification(response.message, response.status, { icon: "check-circle" });
                        notification.show();
                        setTimeout(() => {
                            if (response.status === "success") {
                                Utils.triggerDownload(response.data.uri, $("meta[name=csrf-token]").getAttribute("content"));
                            }
                            button.removeAttribute("disabled");
                        }, 1000);
                    },
                );
            });
        }
    },
};
