import { $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { throttle } from "../../utils/events";
import { TogglegroupInput } from "../inputs/togglegroup-input";

export class Plugins {
    constructor() {
        $$<HTMLInputElement>(".plugin-status-toggle").forEach((toggle) => {
            const togglegroup = new TogglegroupInput(toggle.closest(".form-togglegroup") as HTMLFieldSetElement);
            const action = toggle.dataset.action;

            toggle.addEventListener("change", () => {
                if (!action) {
                    return;
                }

                togglegroup.element.disabled = true;

                throttle(() => {
                    new Request(
                        {
                            method: "POST",
                            url: action,
                            data: { "csrf-token": app.config.csrfToken as string },
                        },
                        (response) => {
                            if (!app.forms["plugin-form"]?.hasChanged()) {
                                window.location.reload();
                            } else {
                                const notification = new Notification(response.message, response.status);
                                notification.show();
                                if (response.status === "error") {
                                    togglegroup.value = toggle.value === "1" ? "0" : "1";
                                }
                                togglegroup.element.disabled = false;
                            }
                        },
                    );
                }, 500)();
            });
        });
    }
}
