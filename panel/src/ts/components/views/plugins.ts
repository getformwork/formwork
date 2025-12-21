import { $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { throttle } from "../../utils/events";

export class Plugins {
    constructor() {
        $$(".plugin-status-toggle").forEach((toggle: HTMLInputElement) => {
            toggle.addEventListener(
                "change",
                throttle((event: Event) => {
                    const toggle = event.target as HTMLInputElement;
                    const action = toggle.dataset.action;

                    if (action) {
                        $$(".plugin-status-toggle").forEach((t: HTMLInputElement) => (t.disabled = true));

                        new Request(
                            {
                                method: "POST",
                                url: action,
                                data: { "csrf-token": app.config.csrfToken as string },
                            },
                            (response) => {
                                if (response.status === "success") {
                                    window.location.reload();
                                } else {
                                    const notification = new Notification(response.message, response.status);
                                    notification.show();
                                    $$(".plugin-status-toggle").forEach((t: HTMLInputElement) => (t.disabled = false));
                                }
                            },
                        );
                    }
                }, 500),
            );
        });
    }
}
