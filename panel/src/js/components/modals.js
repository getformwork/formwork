import Utils from './utils';

let Modals;

export default Modals = {
    init: function () {
        $$('[data-modal]').forEach((element) => {
            element.addEventListener('click', function () {
                const modal = this.getAttribute('data-modal');
                const action = this.getAttribute('data-modal-action');
                if (action) {
                    Modals.show(modal, action);
                } else {
                    Modals.show(modal);
                }
            });
        });

        $$('.modal [data-dismiss]').forEach((element) => {
            element.addEventListener('click', function () {
                if (this.hasAttribute('data-validate')) {
                    const valid = Modals.validate(this.getAttribute('data-dismiss'));
                    if (!valid) {
                        return;
                    }
                }
                Modals.hide(this.getAttribute('data-dismiss'));
            });
        });

        $$('.modal').forEach((element) => {
            let mousedownTriggered = false;
            element.addEventListener('mousedown', () => mousedownTriggered = true);
            element.addEventListener('click', function (event) {
                if (mousedownTriggered && event.target === this) {
                    Modals.hide();
                }
                mousedownTriggered = false;
            });
        });

        document.addEventListener('keyup', (event) => {
            // ESC key
            if (event.which === 27) {
                Modals.hide();
            }
        });

        window.addEventListener('focus', () => {
            const modal = $('.modal.show');
            if (modal) {
                Utils.firstFocusableElement(modal).focus();
            }
        });
    },

    show: function (id, action, callback) {
        const modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.add('show');
        if (action) {
            $('form', modal).setAttribute('action', action);
        }
        document.activeElement.blur(); // Don't retain focus on any element
        if ($('[autofocus]', modal)) {
            $('[autofocus]', modal).focus(); // Firefox bug
        } else {
            Utils.firstFocusableElement(modal).focus();
        }
        if (typeof callback === 'function') {
            callback(modal);
        }
        $$('.tooltip').forEach((element) => {
            element.parentNode.removeChild(element);
        });
        this.createBackdrop();
    },

    hide: function (id) {
        if (typeof id !== 'undefined') {
            document.getElementById(id).classList.remove('show');
        } else {
            $$('.modal').forEach((element) => {
                element.classList.remove('show');
            });
        }
        this.removeBackdrop();
    },

    createBackdrop: function () {
        if (!$('.modal-backdrop')) {
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    },

    removeBackdrop: function () {
        const backdrop = $('.modal-backdrop');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
    },

    validate: function (id) {
        let valid = false;

        const modal = document.getElementById(id);

        $$('[required]', id).forEach((element) => {
            if (element.value === '') {
                element.classList('input-invalid');
                element.focus();
                $('.modal-error', modal).style.display = 'block';
                valid = false;
                return false;
            }

            valid = true;
        });

        return valid;
    },
};
