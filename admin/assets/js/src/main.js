var Formwork = {
    init: function () {
        Formwork.Modals.init();
        Formwork.Forms.init();
        Formwork.Dropdowns.init();
        Formwork.Tooltips.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();
        Formwork.Updates.init();

        $('.toggle-navigation').click(function () {
            $('.sidebar').toggleClass('show');
        });

        $('[data-chart-data]').each(function () {
            // Use $.data() instead of $.attr() to parse JSON string
            var data = $(this).data('chart-data');
            new Formwork.Chart(this, data);
        });

        $('meta[name=notification]').each(function () {
            var $this = $(this);
            new Formwork.Notification($this.attr('content'), $this.attr('data-type'), $this.attr('data-interval'));
            $this.remove();
        });

        if ($('[data-command=save]').length > 0) {
            $(document).keydown(function () {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which === 83) { // ctrl/cmd + S
                        $('[data-command=save]').click();
                        return false;
                    }
                }
            });
        }

    }
};

$(function () {
    Formwork.init();
});
