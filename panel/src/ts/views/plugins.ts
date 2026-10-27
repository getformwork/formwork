import { TogglegroupInput } from "../components/inputs/togglegroup-input";
import { Notification } from "../components/ui/notification";
import { app } from "../services/app";
import { post } from "../services/request";
import { throttle } from "../utils/events";

/**
 * Enable or disable a plugin when its toggle changes
 */
export function initPluginToggle(toggle: HTMLInputElement) {
    const togglegroup = new TogglegroupInput(toggle.closest(".form-togglegroup") as HTMLFieldSetElement);
    const action = toggle.dataset.action;

    const form = app.forms["plugin-form"];

    toggle.addEventListener("change", () => {
        if (!action) {
            return;
        }

        const switchToggle = () => {
            togglegroup.value = toggle.value === "1" ? "0" : "1";
        };

        if (toggle.value === "1" && form && !form.element.noValidate && !form.element.reportValidity()) {
            switchToggle();
            return;
        }

        togglegroup.element.disabled = true;

        throttle(() => {
            post(action, {}, (response) => {
                if (response.status === "success" && !form?.hasChanged()) {
                    window.location.reload();
                } else {
                    const notification = new Notification(response.message, response.status);
                    notification.show();
                    if (response.status === "error") {
                        switchToggle();
                    }
                    togglegroup.element.disabled = false;
                }
            });
        }, 500)();
    });
}
