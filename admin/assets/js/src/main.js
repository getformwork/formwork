var Formwork = {
    init: function() {
        Formwork.Modals.init();
        Formwork.Forms.init();
        Formwork.Tooltips.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();
        Formwork.Updates.init();

        $('.toggle-navigation').click(function() {
            $('.sidebar').toggleClass('show');
        });

        $('[data-chart-data]').each(function() {
            new Formwork.Chart(this, $(this).data('chart-data'));
        });

        $('meta[name=notification]').each(function() {
            var $this = $(this);
            new Formwork.Notification($this.attr('content'), $this.data('type'), $this.data('interval'));
            $this.remove();
        });

        if ($('[data-command=save]').length > 0) {
            $(document).keydown(function() {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which == 83) { // ctrl/cmd + S
                        $('[data-command=save]').click();
                        return false;
                    }
                }
            });
        }

    }
};

$(function() {
    Formwork.init();
});
