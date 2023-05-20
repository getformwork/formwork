import Tooltip from './tooltip';

export default {
    init: function () {
        $$('[title]', document.body).forEach((element) => {
            element.setAttribute('data-tooltip', element.getAttribute('title'));
            element.removeAttribute('title');
        });

        $$('[data-tooltip]').forEach((element) => {
            element.addEventListener('mouseover', function () {
                const tooltip = new Tooltip(this.getAttribute('data-tooltip'), {
                    referenceElement: this,
                    position: 'bottom',
                    offset: {
                        x: 0,
                        y: 4,
                    },
                });
                tooltip.show();
            });

            // Immediately show tooltip on focused buttons
            if (element.tagName.toLowerCase() === 'button' || element.classList.contains('button')) {
                element.addEventListener('focus', function () {
                    const tooltip = new Tooltip(this.getAttribute('data-tooltip'), {
                        referenceElement: this,
                        position: 'bottom',
                        offset: {
                            x: 0,
                            y: 4,
                        },
                        delay: 0,
                    });
                    tooltip.show();

                });
            }
        });

        $$('[data-overflow-tooltip="true"]').forEach((element) => {
            element.addEventListener('mouseover', function () {
                if (this.offsetWidth < this.scrollWidth) {
                    const tooltip = new Tooltip(this.textContent.trim(), {
                        referenceElement: this,
                        position: 'bottom',
                        offset: {
                            x: 0,
                            y: 4,
                        },
                    });
                    tooltip.show();
                }
            });
        });
    },
};
