import { $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { throttle } from "../../utils/events";

export class Plugins {
    constructor() {
        $$(".plugin-status-toggle").forEach((toggle: HTMLInputElement) => {
            const fieldset = toggle.closest(".form-togglegroup") as HTMLFieldSetElement;
            const action = toggle.dataset.action;

            toggle.addEventListener("change", () => {
                if (!action) {
                    return;
                }

                fieldset.disabled = true;

                throttle(() => {
                    new Request(
                        {
                            method: "POST",
                            url: action,
                            data: { "csrf-token": app.config.csrfToken as string },
                        },
                        (response) => {
                            if (response.status === "success" && !app.forms["plugin-form"]?.hasChanged()) {
                                window.location.reload();
                            } else {
                                const notification = new Notification(response.message, response.status);
                                notification.show();
                                fieldset.disabled = false;
                            }
                        },
                    );
                }, 500)();
            });
        });
    }
}
