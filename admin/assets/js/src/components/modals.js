Formwork.Modals = {
    init: function () {
        $$('[data-modal]').forEach(function (element) {
            element.addEventListener('click', function () {
                var modal = this.getAttribute('data-modal');
                var action = this.getAttribute('data-modal-action');
                if (action) {
                    Formwork.Modals.show(modal, action);
                } else {
                    Formwork.Modals.show(modal);
                }
            });
        });

        $$('.modal [data-dismiss]').forEach(function (element) {
            element.addEventListener('click', function () {
                var valid;
                if (this.hasAttribute('data-validate')) {
                    valid = Formwork.Modals.validate(this.getAttribute('data-dismiss'));
                    if (!valid) {
                        return;
                    }
                }
                Formwork.Modals.hide(this.getAttribute('data-dismiss'));
            });
        });

        $$('.modal').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (event.target === this) {
                    Formwork.Modals.hide();
                }
            });
        });

        document.addEventListener('keyup', function (event) {
            // ESC key
            if (event.which === 27) {
                Formwork.Modals.hide();
            }
        });
    },

    show: function (id, action, callback) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.add('show');
        if (action) {
            $('form', modal).setAttribute('action', action);
        }
        if ($('[autofocus]', modal)) {
            Formwork.Utils.triggerEvent($('[autofocus]', modal), 'focus'); // Firefox bug
        }
        if (typeof callback === 'function') {
            callback(modal);
        }
        $$('.tooltip').forEach(function (element) {
            element.parentNode.removeChild(element);
        });
        this.createBackdrop();
    },

    hide: function (id) {
        if (typeof id !== 'undefined') {
            document.getElementById(id).classList.remove('show');
        } else {
            $$('.modal').forEach(function (element) {
                element.classList.remove('show');
            });
        }
        this.removeBackdrop();
    },

    createBackdrop: function () {
        var backdrop;
        if (!$('.modal-backdrop')) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    },

    removeBackdrop: function () {
        var backdrop = $('.modal-backdrop');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
    },

    validate: function (id) {
        var valid = false;
        var modal = document.getElementById(id);
        $$('[required]', id).forEach(function (element) {
            if (element.value === '') {
                element.classList('input-invalid');
                Formwork.Utils.triggerEvent(element, 'focus');
                $('.modal-error', modal).style.display = 'block';
                valid = false;
                return false;
            }
            valid = true;
        });
        return valid;
    }
};
