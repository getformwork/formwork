Formwork.Modals = {
    init: function() {
        $('[data-modal]').click(function() {
            var $this = $(this);
            var modal = $this.data('modal');
            var action = $this.data('modal-action');
            if (action) {
                Formwork.Modals.show(modal, action);
            } else {
                Formwork.Modals.show(modal);
            }
        });

        $('.modal [data-dismiss]').click(function() {
            if ($(this).is('[data-validate]')) {
                var valid = Formwork.Modals.validate($(this).data('dismiss'));
                if (!valid) {
                    return;
                }
            }
            Formwork.Modals.hide($(this).data('dismiss'));
        });

        $('.modal').click(function(event) {
            if (event.target === this) {
                Formwork.Modals.hide();
            }
        });

        $(document).keyup(function(event) {
            // ESC key
            if (event.which == 27) {
                Formwork.Modals.hide();
            }
        });
    },

    show: function (id, action, callback) {
        var $modal = $('#' + id);
        $modal.addClass('show');
        if (action !== null) {
            $modal.find('form').attr('action', action);
        }
        $modal.find('[autofocus]').first().focus(); // Firefox bug
        if (typeof callback === 'function') {
            callback($modal);
        }
        this.createBackdrop();
    },

    hide: function(id) {
        var $modal = id === undefined ? $('.modal') : $('#' + id);
        $modal.removeClass('show');
        this.removeBackdrop();
    },

    createBackdrop: function() {
        if (!$('.modal-backdrop').length) {
            $('<div>', {
                class: 'modal-backdrop'
            }).appendTo('body');
        }
    },

    removeBackdrop: function() {
        $('.modal-backdrop').remove();
    },

    validate: function(id) {
        var valid = false;
        var $modal = $('#' + id);
        $modal.find('[required]').each(function() {
            if ($(this).val() === '') {
                $(this).addClass('animated shake');
                $(this).focus();
                $modal.find('.modal-error').show();
                valid = false;
                return false;
            }
            valid = true;
        });
        return valid;
    }
};
