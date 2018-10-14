Formwork.Tooltip = function (text, options) {
    var defaults = {
        container: document.body,
        referenceElement: document.body,
        position: 'top',
        offset: {
            x: 0, y: 0
        },
        delay: 500
    };

    var $referenceElement = $(options.referenceElement);
    var $tooltip;
    var timer;

    options = $.extend({}, defaults, options);

    $referenceElement.on('mouseout', _remove);

    // Remove tooltip when clicking on buttons
    if ($referenceElement.is('button, .button')) {
        $referenceElement.on('click', _remove);
    }

    function _tooltipPosition($tooltip) {
        var offset = $referenceElement.offset();

        if (offset.top === 0 && offset.left === 0) {
            var rect = $referenceElement[0].getBoundingClientRect();
            offset.top = rect.top + window.pageYOffset;
            offset.left = rect.left + window.pageXOffset;
        }

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
                left: Math.round(left + $referenceElement.outerWidth() + options.offset.x)
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
        timer = setTimeout(function () {
            $tooltip = $('<div class="tooltip" role="tooltip">')
                .appendTo(options.container);

            $tooltip.text(text)
                .css(_tooltipPosition($tooltip))
                .fadeIn(200);
        }, options.delay);
    }

    function _remove() {
        clearTimeout(timer);
        if ($tooltip !== undefined) {
            $tooltip.fadeOut(100, function () {
                $tooltip.remove();
            });
        }
    }

    return {
        show: _show,
        remove: _remove
    };
};
