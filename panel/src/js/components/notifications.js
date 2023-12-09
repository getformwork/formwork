import { $$ } from "../utils/selectors";
import { Notification } from "./notification";

export class Notifications {
    constructor() {
        $$("meta[name=notification]").forEach((element) => {
            const data = JSON.parse(element.content)[0];
            const notification = new Notification(data.text, data.type, {
                interval: data.interval,
                icon: data.icon,
            });
            notification.show();
            element.parentNode.removeChild(element);
        });
    }
}
