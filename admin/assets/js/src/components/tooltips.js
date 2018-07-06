Formwork.Tooltips = {
    init: function() {
        $('[title]').each(function() {
            var $this = $(this);
            $this.attr('data-tooltip', $this.attr('title'))
            .removeAttr('title');
        });

        $('[data-tooltip]').mouseover(function() {
            var $this = $(this);
            var tooltip = new Formwork.Tooltip($this.data('tooltip'), {
                referenceElement: $this,
                position: 'bottom',
                offset: {x: 0, y: 4}
            });
            tooltip.show();
        });

        $('[data-overflow-tooltip="true"]').mouseover(function() {
            var $this = $(this);
            if ($this.prop('offsetWidth') < $this.prop('scrollWidth')) {
                var tooltip = new Formwork.Tooltip($this.text().trim(), {
                    referenceElement: $this,
                    position: 'bottom',
                    offset: {x: 0, y: 4}
                });
                tooltip.show();
            }
        });
    }
};
