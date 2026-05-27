import { $$ } from "../utils/selectors";
import { Notification } from "./notification";

export class Notifications {
    constructor() {
        let delay = 0;

        $$<HTMLMetaElement>("meta[name=notification]").forEach((element) => {
            window.setTimeout(() => {
                const data = JSON.parse(element.content);
                const notification = new Notification(data.text, data.type, {
                    interval: data.interval,
                    icon: data.icon,
                });
                notification.show();
            }, delay);
            delay += 500;
            element.remove();
        });
    }
}
