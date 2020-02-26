Formwork.Tooltips = {
    init: function () {
        $$('[title]').forEach(function (element) {
            element.setAttribute('data-tooltip', element.getAttribute('title'));
            element.removeAttribute('title');
        });

        $$('[data-tooltip]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip = new Formwork.Tooltip(this.getAttribute('data-tooltip'), {
                    referenceElement: this,
                    position: 'bottom',
                    offset: {
                        x: 0, y: 4
                    }
                });
                tooltip.show();
            });
        });

        $$('[data-overflow-tooltip="true"]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip;
                if (this.offsetWidth < this.scrollWidth) {
                    tooltip = new Formwork.Tooltip(this.textContent.trim(), {
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
