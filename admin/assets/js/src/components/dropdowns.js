Formwork.Dropdowns = {
    init: function () {
        if ($('.dropdown').length > 0) {
            $(document).on('click', function (event) {
                var $button = $(event.target).closest('.dropdown-button');
                if ($button.length > 0) {
                    var $dropdown = $('#' + $button.attr('data-dropdown'), '.dropdown');
                    var isVisible = $dropdown.is(':visible');
                    event.preventDefault();
                }
                $('.dropdown-menu').hide();
                if ($dropdown !== undefined && !isVisible) {
                    $dropdown.show();
                }
            });
        }
    }
};
