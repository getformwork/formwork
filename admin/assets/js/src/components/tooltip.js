var Tooltip = function(referenceElement, text, options) {
    var defaults = {
        container: document.body,
        position: 'top',
        offset: {x: 0, y: 0},
        delay: 500
    };

    options = $.extend({}, defaults, options);

    var $referenceElement = $(referenceElement);
    var timer;

    function _position($tooltip) {
        var offset = Utils.offset($referenceElement[0]);

        var top = offset.top;
        var left = offset.left;

        var hw = ($referenceElement.outerWidth() - $tooltip.outerWidth()) / 2;
        var hh = ($referenceElement.outerHeight() - $tooltip.outerHeight()) / 2;
        switch (options.position) {
            case 'top':
                return {
                    top: Math.round(top - $tooltip.outerHeight() + options.offset.y),
                    left: Math.round(left + hw + options.offset.x)
                };
            case 'right':
                return {
                    top: Math.round(top + hh + options.offset.y),
                    left: Math.round(left+ $referenceElement.outerWidth() + options.offset.x)
                };
            case 'bottom':
                return {
                    top: Math.round(top + $referenceElement.outerHeight() + options.offset.y),
                    left: Math.round(left + hw + options.offset.x)
                };
            case 'left':
                return {
                    top: Math.round(top + hh + options.offset.y),
                    left: Math.round(left - $tooltip.outerWidth() + options.offset.x)
                };
        }
    }

    function _show() {
        timer = setTimeout(function() {
            var $tooltip = $('<div class="tooltip" role="tooltip">');
            $tooltip.appendTo(options.container);
            $tooltip.text(text);
            $tooltip.css(_position($tooltip)).fadeIn(200);
        }, options.delay);
    }

    function _remove() {
        clearTimeout(timer);
        $('.tooltip').fadeOut(100, function() {
            $(this).remove();
        });
    }

    $referenceElement.on('mouseout', _remove);

    _show();

    return {show: _show};

};
