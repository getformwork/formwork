Formwork.Notification = function (text, type, interval, options) {
    var defaults = {
        top: 20,
        right: 20,
        fadeOutDelay: 300,
        mouseleaveDelay: 1000
    };

    var notification, timer;

    options = Formwork.Utils.extendObject({}, defaults, options);

    function show() {
        var position;
        notification = document.createElement('div');
        notification.className = 'notification';
        notification.classList.add('notification-' + type);
        notification.innerHTML = text;

        position = getNotificationPosition();
        notification.style.top = position.top + 'px';
        notification.style.right = position.right + 'px';

        document.body.append(notification);

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
        var found = false;
        var offset = Formwork.Utils.outerHeight(notification);
        $$('.notification').forEach(function (element) {
            if (element === notification) {
                found = true;
                element.classList.add('fadeout');
            } else if (found) {
                element.style.top = (parseInt(element.style.top) - offset) + 'px';
            }
        });
        setTimeout(function () {
            if (notification && notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, options.fadeOutDelay);
    }

    function getNotificationPosition() {
        var top = options.top;
        var right = options.right;
        var lastNotification = getLastNotification();
        var rect;
        if (lastNotification !== undefined) {
            rect = lastNotification.getBoundingClientRect();
            top = rect.top + Formwork.Utils.outerHeight(lastNotification) - window.pageYOffset;
        }
        return {
            top: top,
            right: right
        };
    }

    function getLastNotification() {
        var visible;
        if ($$('.notification').length > 0) {
            visible = $$('.notification:not(.fadeout)');
            return visible[visible.length - 1];
        }
    }

    return {
        show: show,
        remove: remove
    };
};
