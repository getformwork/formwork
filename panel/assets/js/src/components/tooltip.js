import Utils from './utils';

export default function Tooltip(text, options) {
    const defaults = {
        container: document.body,
        referenceElement: document.body,
        position: 'top',
        offset: {
            x: 0,
            y: 0,
        },
        delay: 500,
        timeout: null,
        removeOnMouseout: true,
        removeOnClick: false,
    };

    const referenceElement = options.referenceElement;
    let tooltip, delayTimer, timeoutTimer;

    options = Utils.extendObject({}, defaults, options);

    // IE 10-11 support classList only on HTMLElement
    if (referenceElement instanceof HTMLElement) {
        // Remove tooltip when clicking on buttons
        if (referenceElement.tagName.toLowerCase() === 'button' || referenceElement.classList.contains('button')) {
            referenceElement.addEventListener('click', remove);
            referenceElement.addEventListener('blur', remove);
        }
    }

    if (options.removeOnMouseout) {
        referenceElement.addEventListener('mouseout', remove);
    }
    if (options.removeOnClick) {
        referenceElement.addEventListener('click', remove);
    }

    function show() {
        delayTimer = setTimeout(() => {
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.setAttribute('role', 'tooltip');
            tooltip.style.display = 'block';
            tooltip.innerHTML = text;

            options.container.appendChild(tooltip);

            const position = getTooltipPosition(tooltip);
            tooltip.style.top = `${position.top}px`;
            tooltip.style.left = `${position.left}px`;

            if (options.timeout !== null) {
                timeoutTimer = setTimeout(remove, options.timeout);
            }
        }, options.delay);
    }

    function remove() {
        clearTimeout(delayTimer);
        clearTimeout(timeoutTimer);
        if (tooltip !== undefined && options.container.contains(tooltip)) {
            options.container.removeChild(tooltip);
        }
    }

    function getTooltipPosition(tooltip) {
        const rect = referenceElement.getBoundingClientRect();
        const top = rect.top + window.pageYOffset;
        const left = rect.left + window.pageXOffset;

        const hw = (rect.width - tooltip.offsetWidth) / 2;
        const hh = (rect.height - tooltip.offsetHeight) / 2;

        switch (options.position) {
        case 'top':
            return {
                top: Math.round(top - tooltip.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x),
            };
        case 'right':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left + referenceElement.offsetWidth + options.offset.x),
            };
        case 'bottom':
            return {
                top: Math.round(top + referenceElement.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x),
            };
        case 'left':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left - tooltip.offsetWidth + options.offset.x),
            };
        case 'center':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left + hw + options.offset.x),
            };
        }
    }

    return {
        show: show,
        remove: remove,
    };
}
