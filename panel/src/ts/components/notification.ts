import { $ } from "../utils/selectors";
import { passIcon } from "./icons";

type NotificationType = "info" | "success" | "warning" | "error";

type NotificationOptions = {
    interval: number;
    icon?: string;
    newestOnTop: boolean;
    fadeOutDelay: number;
    mouseleaveDelay: number;
    typeClass: Record<NotificationType, string>;
};

export class Notification {
    text: string;
    type: NotificationType;
    options: NotificationOptions;
    containerElement: HTMLElement | null;
    notificationElement: HTMLElement;

    constructor(text: string, type: NotificationType, options: Partial<NotificationOptions>) {
        const defaults: NotificationOptions = {
            interval: 5000,
            icon: undefined,
            newestOnTop: true,
            fadeOutDelay: 300,
            mouseleaveDelay: 1000,
            typeClass: {
                info: "info",
                success: "success",
                warning: "warning",
                error: "danger",
            },
        };

        this.text = text;
        this.type = type;

        this.options = Object.assign({}, defaults, options);

        this.containerElement = $(".notification-container") as HTMLElement;
    }

    show() {
        const create = (text: string, type: NotificationType, interval: number) => {
            if (!this.containerElement) {
                this.containerElement = document.createElement("div");
                this.containerElement.className = "notification-container";
                document.body.appendChild(this.containerElement);
            }

            const notification = document.createElement("div");
            notification.className = `notification notification-${this.options.typeClass[type]}`;
            notification.innerHTML = text;

            if (this.options.newestOnTop && this.containerElement.childNodes.length > 0) {
                this.containerElement.insertBefore(notification, this.containerElement.childNodes[0]);
            } else {
                this.containerElement.appendChild(notification);
            }

            let timer = setTimeout(() => this.remove(), interval);

            notification.addEventListener("click", () => this.remove());

            notification.addEventListener("mouseenter", () => clearTimeout(timer));

            notification.addEventListener("mouseleave", () => ((timer = setTimeout(() => this.remove())), this.options.mouseleaveDelay));

            return notification;
        };

        if (this.options.icon) {
            passIcon(this.options.icon, (icon) => {
                this.notificationElement = create(this.text, this.type, this.options.interval);
                this.notificationElement.insertAdjacentHTML("afterbegin", icon);
            });
        } else {
            this.notificationElement = create(this.text, this.type, this.options.interval);
        }
    }

    remove() {
        this.notificationElement.classList.add("fadeout");

        setTimeout(() => {
            if (this.containerElement && this.notificationElement && this.notificationElement.parentNode) {
                this.containerElement.removeChild(this.notificationElement);
            }
            if (this.containerElement && this.containerElement.childNodes.length < 1) {
                if (this.containerElement.parentNode) {
                    document.body.removeChild(this.containerElement);
                }
                this.containerElement = null;
            }
        }, this.options.fadeOutDelay);
    }
}
