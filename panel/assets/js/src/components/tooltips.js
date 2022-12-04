import Tooltip from './tooltip';

export default {
    init: function () {
        $$('[title]', document.body).forEach(function (element) {
            element.setAttribute('data-tooltip', element.getAttribute('title'));
            element.removeAttribute('title');
        });

        $$('[data-tooltip]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip = new Tooltip(this.getAttribute('data-tooltip'), {
                    referenceElement: this,
                    position: 'bottom',
                    offset: {
                        x: 0, y: 4
                    }
                });
                tooltip.show();
            });

            // Immediately show tooltip on focused buttons
            if (element.tagName.toLowerCase() === 'button' || element.classList.contains('button')) {
                element.addEventListener('focus', function () {
                    var tooltip = new Tooltip(this.getAttribute('data-tooltip'), {
                        referenceElement: this,
                        position: 'bottom',
                        offset: {
                            x: 0, y: 4
                        },
                        delay: 0
                    });
                    tooltip.show();

                });
            }
        });

        $$('[data-overflow-tooltip="true"]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip;
                if (this.offsetWidth < this.scrollWidth) {
                    tooltip = new Tooltip(this.textContent.trim(), {
                        referenceElement: this,
                        position: 'bottom',
                        offset: {
                            x: 0, y: 4
                        }
                    });
                    tooltip.show();
                }
            });
        });
    }
};
