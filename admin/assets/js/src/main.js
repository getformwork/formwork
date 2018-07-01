var Formwork = {
    init: function() {
        Formwork.Modals.init();
        Formwork.Forms.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();

        $('.toggle-navigation').click(function() {
            $('.sidebar').toggleClass('show');
        });

        $('.overflow-title').mouseover(function() {
            var $this = $(this);
            if ($this.prop('offsetWidth') < $this.prop('scrollWidth')) {
                $this.attr('title', $this.text().trim());
            } else {
                $this.removeAttr('title');
            }
        });

        $('[data-chart-data]').each(function() {
            new Formwork.Chart(this, $(this).data('chart-data'));
        });

        $('meta[name=notification]').each(function() {
            var $this = $(this);
            new Formwork.Notification($this.attr('content'), $this.data('type'), $this.data('interval'));
            $this.remove();
        });
    }
};

$(function() {
    Formwork.init();
});
