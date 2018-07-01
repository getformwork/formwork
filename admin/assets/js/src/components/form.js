Formwork.Form = function(form) {
    var $window = $(window);
    var $form = $(form);

    $form.data('original-data', $form.serialize());

    $window.on('beforeunload', function() {
        if (hasChanged()) return true;
    });

    $form.submit(function() {
        $window.off('beforeunload');
    });

    $('a[href]:not([href^="#"]):not([target="_blank"])').click(function(event) {
        if (hasChanged()) {
            var link = this;
            event.preventDefault();
            Formwork.Modals.show('changesModal', null, function($modal) {
                $modal.find('.button-continue').click(function() {
                    $window.off('beforeunload');
                    window.location.href = $(this).data('href');
                }).attr('data-href', link.href);
            });
        }
    });

    function hasChanged() {
        return $form.serialize() != $form.data('original-data');
    }
};
