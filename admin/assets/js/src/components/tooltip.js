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

    var referenceElement = options.referenceElement;
    var tooltip, timer;

    options = Formwork.Utils.extendObject({}, defaults, options);

    // Remove tooltip when clicking on buttons
    if (referenceElement.tagName.toLowerCase() === 'button' || referenceElement.classList.contains('button')) {
        referenceElement.addEventListener('click', remove);
    }

    referenceElement.addEventListener('mouseout', remove);

    function show() {
        timer = setTimeout(function () {
            var position;
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.setAttribute('role', 'tooltip');
            tooltip.style.display = 'block';
            tooltip.innerHTML = text;

            options.container.appendChild(tooltip);

            position = getTooltipPosition(tooltip);
            tooltip.style.top = position.top + 'px';
            tooltip.style.left = position.left + 'px';
        }, options.delay);
    }

    function remove() {
        clearTimeout(timer);
        if (tooltip !== undefined && options.container.contains(tooltip)) {
            options.container.removeChild(tooltip);
        }
    }

    function getTooltipPosition(tooltip) {
        var rect = referenceElement.getBoundingClientRect();
        var top = rect.top + window.pageYOffset;
        var left = rect.left + window.pageXOffset;

        var hw = (rect.width - tooltip.offsetWidth) / 2;
        var hh = (rect.height - tooltip.offsetHeight) / 2;

        switch (options.position) {
        case 'top':
            return {
                top: Math.round(top - tooltip.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'right':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left + referenceElement.offsetWidth + options.offset.x)
            };
        case 'bottom':
            return {
                top: Math.round(top + referenceElement.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'left':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left - tooltip.offsetWidth + options.offset.x)
            };
        }
    }

    return {
        show: show,
        remove: remove
    };
};
