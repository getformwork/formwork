Formwork.Modals = {
    init: function () {
        $('[data-modal]').click(function () {
            var $this = $(this);
            var modal = $this.attr('data-modal');
            var action = $this.attr('data-modal-action');
            if (action) {
                Formwork.Modals.show(modal, action);
            } else {
                Formwork.Modals.show(modal);
            }
        });

        $('.modal [data-dismiss]').click(function () {
            var $this = $(this);
            if ($this.is('[data-validate]')) {
                var valid = Formwork.Modals.validate($this.attr('data-dismiss'));
                if (!valid) {
                    return;
                }
            }
            Formwork.Modals.hide($this.attr('data-dismiss'));
        });

        $('.modal').click(function (event) {
            if (event.target === this) {
                Formwork.Modals.hide();
            }
        });

        $(document).keyup(function (event) {
            // ESC key
            if (event.which === 27) {
                Formwork.Modals.hide();
            }
        });
    },

    show: function (id, action, callback) {
        var $modal = $('#' + id);
        $modal.addClass('show');
        if (action !== null) {
            $('form', $modal).attr('action', action);
        }
        $('[autofocus]', $modal).first().focus(); // Firefox bug
        if (typeof callback === 'function') {
            callback($modal);
        }
        this.createBackdrop();
    },

    hide: function (id) {
        var $modal = id === undefined ? $('.modal') : $('#' + id);
        $modal.removeClass('show');
        this.removeBackdrop();
    },

    createBackdrop: function () {
        if (!$('.modal-backdrop').length) {
            $('<div>', {class: 'modal-backdrop'}).appendTo('body');
        }
    },

    removeBackdrop: function () {
        $('.modal-backdrop').remove();
    },

    validate: function (id) {
        var valid = false;
        var $modal = $('#' + id);
        $('[required]', $modal).each(function () {
            var $this = $(this);
            if ($this.val() === '') {
                $this.addClass('animated shake').focus();
                $('.modal-error', $modal).show();
                valid = false;
                return false;
            }
            valid = true;
        });
        return valid;
    }
};
