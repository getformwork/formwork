import { $ } from "../../utils/selectors";
import * as icons from "./icons";

/**
 * Represents the kinds of notifications
 */
export type NotificationType = "info" | "success" | "warning" | "error";

/**
 * Holds the options of a notification
 */
type NotificationOptions = {
    interval: number;
    icon?: keyof typeof icons;
    newestOnTop: boolean;
    fadeOutDelay: number;
    mouseleaveDelay: number;
    typeClass: Record<NotificationType, string>;
    defaultIcons: Record<NotificationType, keyof typeof icons>;
};

/**
 * Shows a message in the corner of the page for a few seconds
 *
 * It stays while the pointer or the focus is on it and can be closed with a click, Escape or Enter
 */
export class Notification {
    /**
     * Get the element containing the notifications
     */
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

    /**
     * Text of the notification
     */
    text: string;
    /**
     * Kind of the notification
     */
    type: NotificationType;
    /**
     * Options of the notification
     */
    options: NotificationOptions;
    /**
     * Element containing the notification
     */
    containerElement: HTMLElement | null;
    /**
     * Element of the notification
     */
    notificationElement!: HTMLElement;

    /**
     * Create a notification, which is shown with `show()`
     */
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

    /**
     * Show the notification
     */
    show() {
        if (!this.options.icon) {
            this.options.icon = this.options.defaultIcons[this.type];
        }

        this.notificationElement = this.create();

        if (this.options.icon) {
            // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
            this.notificationElement.insertAdjacentHTML("afterbegin", icons[this.options.icon] || "");
        }
    }

    /**
     * Add the element of the notification to the container
     */
    private create() {
        this.containerElement = Notification.getContainer();

        const notification = document.createElement("div");
        notification.className = `notification notification-${this.options.typeClass[this.type]}`;
        notification.tabIndex = 0;

        if (this.type === "error" || this.type === "warning") {
            notification.setAttribute("role", "alert");
        }

        notification.innerHTML = this.text;

        if (this.options.newestOnTop && this.containerElement.childNodes.length > 0) {
            this.containerElement.insertBefore(notification, this.containerElement.childNodes[0]);
        } else {
            this.containerElement.appendChild(notification);
        }

        this.bindEvents(notification);

        return notification;
    }

    /**
     * Remove the notification after the interval, unless the pointer or the focus is on it, and
     * when it is clicked or dismissed with the keyboard
     */
    private bindEvents(notification: HTMLElement) {
        let timer = window.setTimeout(() => this.remove(), this.options.interval);

        const pause = () => window.clearTimeout(timer);

        const resume = () => {
            window.clearTimeout(timer);
            timer = window.setTimeout(() => this.remove(), this.options.mouseleaveDelay);
        };

        notification.addEventListener("click", () => this.remove());

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
    }

    /**
     * Fade out and remove the notification
     */
    remove() {
        this.notificationElement.classList.add("fadeout");

        window.setTimeout(() => {
            if (this.containerElement && this.notificationElement?.parentNode) {
                this.containerElement.removeChild(this.notificationElement);
            }
        }, this.options.fadeOutDelay);
    }
}
