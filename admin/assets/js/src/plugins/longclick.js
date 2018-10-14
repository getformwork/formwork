(function ($) {
    $.fn.longclick = function (callback, timeout, interval) {
        var timer;
        function clear() {
            clearTimeout(timer);
        }
        $(window).mouseup(clear);
        $(this).mousedown(function (event) {
            if (event.which !== 1) {
                clear();
            } else {
                callback();
                timer = window.setTimeout(function () {
                    timer = window.setInterval(callback, interval ? interval : 250);
                }, timeout ? timeout : 500);
            }
        }).mouseout(clear);
    };
}(jQuery));
