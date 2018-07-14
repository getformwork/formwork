Formwork.Notification = function(text, type, interval) {
    var $notification = $('<div>', {
        class: 'notification'
    }).text(text);

    if ($('.notification').length > 0) {
        var $last = $('.notification:last');
        var top = $last.offset().top + $last.outerHeight(true);
        $notification.css('top', top);
    }

    if (type) {
        $notification.addClass('notification-' + type);
    }

    $notification.appendTo('body');

    setTimeout(function() {
        var offset = $notification.outerHeight(true);

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
