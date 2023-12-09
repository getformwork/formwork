import { $ } from "../utils/selectors";
import { passIcon } from "./icons";

export class Notification {
    constructor(text, type, options) {
        const defaults = {
            interval: 5000,
            icon: null,
            newestOnTop: true,
            fadeOutDelay: 300,
            mouseleaveDelay: 1000,
        };

        this.text = text;
        this.type = type;

        this.options = Object.assign({}, defaults, options);

        this.containerElement = $(".notification-container");
    }

    show() {
        const create = (text, type, interval) => {
            if (!this.containerElement) {
                this.containerElement = document.createElement("div");
                this.containerElement.className = "notification-container";
                document.body.appendChild(this.containerElement);
            }

            const notification = document.createElement("div");
            notification.className = `notification notification-${type}`;
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

        if (this.options.icon !== null) {
            passIcon(this.options.icon, (icon) => {
                this.notificationElement = create(this.text, this.type, this.options.interval);
                this.notificationElement.insertAdjacentHTML("afterBegin", icon);
            });
        } else {
            this.notificationElement = create(this.text, this.type, this.options.interval);
        }
    }

    remove() {
        this.notificationElement.classList.add("fadeout");

        setTimeout(() => {
            if (this.notificationElement && this.notificationElement.parentNode) {
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
