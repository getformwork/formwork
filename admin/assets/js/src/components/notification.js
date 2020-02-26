Formwork.Notification = function (text, type, interval, options) {
    var defaults = {
        newestOnTop: true,
        fadeOutDelay: 300,
        mouseleaveDelay: 1000
    };

    var container = $('.notification-container');

    var notification, timer;

    options = Formwork.Utils.extendObject({}, defaults, options);

    function show() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }

        notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.innerHTML = text;

        if (options.newestOnTop && container.childNodes.length > 0) {
            container.insertBefore(notification, container.childNodes[0]);
        } else {
            container.appendChild(notification);
        }

        timer = setTimeout(remove, interval);

        notification.addEventListener('click', remove);

        notification.addEventListener('mouseenter', function () {
            clearTimeout(timer);
        });

        notification.addEventListener('mouseleave', function () {
            timer = setTimeout(remove, options.mouseleaveDelay);
        });
    }

    function remove() {
        notification.classList.add('fadeout');

        setTimeout(function () {
            if (notification.parentNode) {
                container.removeChild(notification);
            }
            if (container.childNodes.length < 1) {
                if (container.parentNode) {
                    document.body.removeChild(container);
                }
                container = null;
            }
        }, options.fadeOutDelay);
    }

    return {
        show: show,
        remove: remove
    };
};
