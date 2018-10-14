Formwork.Form = function(form) {
    var $window = $(window);
    var $form = $(form);

    $form.data('originalData', $form.serialize());

    $window.on('beforeunload', function() {
        if (hasChanged()) {
            return true;
        }
    });

    $form.submit(function() {
        $window.off('beforeunload');
    });

    $('a[href]:not([href^="#"]):not([target="_blank"])').click(function(event) {
        if (hasChanged()) {
            var link = this;
            event.preventDefault();
            Formwork.Modals.show('changesModal', null, function($modal) {
                $('[data-command=continue]', $modal).click(function() {
                    $window.off('beforeunload');
                    window.location.href = $(this).attr('data-href');
                }).attr('data-href', link.href);
            });
        }
    });

    function hasChanged() {
        var $fileInputs = $(':file', $form);
        if ($fileInputs.length > 0) {
            for (var i = 0; i < $fileInputs.length; i++) {
                if ($fileInputs[i].files.length > 0) {
                    return true;
                }
            }
        }
        return $form.serialize() != $form.data('originalData');
    }
};
