Formwork.Notification = function(text, type, interval) {
    var $notification = $('<div>', {
        class: 'notification'
    }).text(text);

    if ($('.notification').length > 0) {
        var $last = $('.notification:not(.fadeout):last');
        var top = $last.offset().top + $last.outerHeight(true) - $(window).scrollTop();
        $notification.css('top', top);
    }

    if (type) {
        $notification.addClass('notification-' + type);
    }

    $notification.appendTo('body');

    var timer = setTimeout(remove, interval);

    $notification.click(remove);

    $notification.mouseenter(function() {
        clearTimeout(timer);
    });

    $notification.mouseleave(function() {
        timer = setTimeout(remove, 1000);
    });

    function remove() {
        var found = false;
        var offset = $notification.outerHeight(true);

        $('.notification').each(function() {
            var $this = $(this);
            if ($this.is($notification)) {
                found = true;
                $this.addClass('fadeout');
            } else if (found) {
                $this.css('top', '-=' + offset);
            }
        });

        setTimeout(function() {
            $notification.remove();
        }, 400);

    }

};
