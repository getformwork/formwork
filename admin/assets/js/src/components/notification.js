var Notification = function(text, type, interval) {
    var top = false;

    function hasNotifications() {
        return $('.notification').length > 0;
    }

    if (hasNotifications()) {
        var $last = $('.notification:last');
        top = $last.offset().top + $last.outerHeight(true);
    }

    var $notification = $('<div>', {
        class: 'notification'
    }).text(text).appendTo('body');

    if (top) $notification.css('top', top);

    if (type) $notification.addClass('notification-' + type);

    setTimeout(function() {
        offset = $notification.outerHeight(true);

        $('.notification').each(function() {
            var $this = $(this);
            if ($this.is($notification)) {
                $this.addClass('fadeout');
            } else {
                $this.css('top', '-=' + offset);
            }
        });

        setTimeout(function() {
            $notification.remove();
        }, 400);
        
    }, interval);
};
