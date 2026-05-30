import * as icons from "./icons";
import { $ } from "../utils/selectors";

type NotificationType = "info" | "success" | "warning" | "error";

type NotificationOptions = {
    interval: number;
    icon?: keyof typeof icons;
    newestOnTop: boolean;
    fadeOutDelay: number;
    mouseleaveDelay: number;
    typeClass: Record<NotificationType, string>;
    defaultIcons: Record<NotificationType, keyof typeof icons>;
};

export class Notification {
    text: string;
    type: NotificationType;
    options: NotificationOptions;
    containerElement: HTMLElement | null;
    notificationElement!: HTMLElement;

    constructor(text: string, type: NotificationType, options: Partial<NotificationOptions> = {}) {
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
            defaultIcons: {
                info: "infoCircle",
                success: "checkCircle",
                warning: "exclamationTriangle",
                error: "exclamationOctagon",
            },
        };

        this.text = text;
        this.type = type;

        this.options = Object.assign({}, defaults, options);

        this.containerElement = $(".notification-container");
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

            let timer = window.setTimeout(() => this.remove(), interval);

            notification.addEventListener("click", () => this.remove());

            notification.addEventListener("mouseenter", () => clearTimeout(timer));

            notification.addEventListener("mouseleave", () => ((timer = window.setTimeout(() => this.remove())), this.options.mouseleaveDelay));

            return notification;
        };

        if (!this.options.icon) {
            this.options.icon = this.options.defaultIcons[this.type];
        }

        if (this.options.icon) {
            this.notificationElement = create(this.text, this.type, this.options.interval);
            this.notificationElement.insertAdjacentHTML("afterbegin", icons[this.options.icon] || "");
        } else {
            this.notificationElement = create(this.text, this.type, this.options.interval);
        }
    }

    remove() {
        this.notificationElement.classList.add("fadeout");

        window.setTimeout(() => {
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
