import { $ } from "../utils/selectors";
import * as icons from "./icons";

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
    static getContainer(): HTMLElement {
        let container = $(".notification-container");

        if (!container) {
            // Layouts render this container with its live region attributes, this is only a fallback
            container = document.createElement("div");
            container.className = "notification-container";
            document.body.appendChild(container);
        }

        return container;
    }

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

        this.containerElement = null;
    }

    show() {
        const create = (text: string, type: NotificationType, interval: number) => {
            this.containerElement = Notification.getContainer();

            const notification = document.createElement("div");
            notification.className = `notification notification-${this.options.typeClass[type]}`;
            notification.tabIndex = 0;

            if (type === "error" || type === "warning") {
                notification.setAttribute("role", "alert");
            }

            notification.innerHTML = text;

            if (this.options.newestOnTop && this.containerElement.childNodes.length > 0) {
                this.containerElement.insertBefore(notification, this.containerElement.childNodes[0]);
            } else {
                this.containerElement.appendChild(notification);
            }

            let timer = window.setTimeout(() => this.remove(), interval);

            const pause = () => window.clearTimeout(timer);

            const resume = () => {
                window.clearTimeout(timer);
                timer = window.setTimeout(() => this.remove(), this.options.mouseleaveDelay);
            };

            notification.addEventListener("click", () => this.remove());

            // Pause the countdown while the pointer or keyboard focus is on the notification
            notification.addEventListener("mouseenter", pause);
            notification.addEventListener("mouseleave", () => {
                if (!notification.matches(":focus-within")) {
                    resume();
                }
            });
            notification.addEventListener("focusin", pause);
            notification.addEventListener("focusout", resume);

            notification.addEventListener("keydown", (event) => {
                if (event.key === "Escape" || event.key === "Enter" || event.key === " ") {
                    event.preventDefault();
                    this.remove();
                }
            });

            return notification;
        };

        if (!this.options.icon) {
            this.options.icon = this.options.defaultIcons[this.type];
        }

        if (this.options.icon) {
            this.notificationElement = create(this.text, this.type, this.options.interval);

            // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
            this.notificationElement.insertAdjacentHTML("afterbegin", icons[this.options.icon] || "");
        } else {
            this.notificationElement = create(this.text, this.type, this.options.interval);
        }
    }

    remove() {
        this.notificationElement.classList.add("fadeout");

        window.setTimeout(() => {
            if (this.containerElement && this.notificationElement?.parentNode) {
                this.containerElement.removeChild(this.notificationElement);
            }
        }, this.options.fadeOutDelay);
    }
}
