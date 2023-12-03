import Icons from "./icons";
import Utils from "./utils";

export default function Notification(text, type, options) {
    const defaults = {
        interval: 5000,
        icon: null,
        newestOnTop: true,
        fadeOutDelay: 300,
        mouseleaveDelay: 1000,
    };

    let container = $(".notification-container");

    let notification;

    options = Utils.extendObject({}, defaults, options);

    function create(text, type, interval) {
        if (!container) {
            container = document.createElement("div");
            container.className = "notification-container";
            document.body.appendChild(container);
        }

        notification = document.createElement("div");
        notification.className = `notification notification-${type}`;
        notification.innerHTML = text;

        if (options.newestOnTop && container.childNodes.length > 0) {
            container.insertBefore(notification, container.childNodes[0]);
        } else {
            container.appendChild(notification);
        }

        let timer = setTimeout(remove, interval);

        notification.addEventListener("click", remove);

        notification.addEventListener("mouseenter", () => {
            clearTimeout(timer);
        });

        notification.addEventListener("mouseleave", () => {
            timer = setTimeout(remove, options.mouseleaveDelay);
        });
    }

    function show() {
        if (options.icon !== null) {
            Icons.pass(options.icon, (icon) => {
                create(text, type, options.interval);
                notification.insertAdjacentHTML("afterBegin", icon);
            });
        } else {
            create(text, type, options.interval);
        }
    }

    function remove() {
        notification.classList.add("fadeout");

        setTimeout(() => {
            if (notification && notification.parentNode) {
                container.removeChild(notification);
            }
            if (container && container.childNodes.length < 1) {
                if (container.parentNode) {
                    document.body.removeChild(container);
                }
                container = null;
            }
        }, options.fadeOutDelay);
    }

    return {
        show: show,
        remove: remove,
    };
}
