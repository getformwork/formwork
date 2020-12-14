import Modals from './modals';
import Utils from './utils';

export default function Form(form) {
    var originalData = Utils.serializeForm(form);

    window.addEventListener('beforeunload', handleBeforeunload);

    form.addEventListener('submit', removeBeforeUnload);

    $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (hasChanged()) {
                event.preventDefault();
                Modals.show('changesModal', null, function (modal) {
                    $('[data-command=continue]', modal).setAttribute('data-href', element.href);
                });
            }
        });
    });

    $$('input[type=file][data-auto-upload]', form).forEach(function (element) {
        element.addEventListener('change', function () {
            if (!hasChanged(false)) {
                form.requestSubmit($('[type=submit]', form));
            }
        });
    });

    registerModalExceptions();

    function handleBeforeunload(event) {
        if (hasChanged()) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    function removeBeforeUnload() {
        window.removeEventListener('beforeunload', handleBeforeunload);
    }

    function registerModalExceptions() {
        var changesModal = document.getElementById('changesModal');
        var deletePageModal = document.getElementById('deletePageModal');
        var deleteUserModal = document.getElementById('deleteUserModal');

        if (changesModal) {
            $('[data-command=continue]', changesModal).addEventListener('click', function () {
                removeBeforeUnload();
                window.location.href = this.getAttribute('data-href');
            });
        }

        if (deletePageModal) {
            $('[data-command=delete]', deletePageModal).addEventListener('click', removeBeforeUnload);
        }

        if (deleteUserModal) {
            $('[data-command=delete]', deleteUserModal).addEventListener('click', removeBeforeUnload);
        }
    }

    function hasChanged(checkFileInputs) {
        var fileInputs, i;
        if (typeof checkFileInputs === 'undefined') {
            checkFileInputs = true;
        }
        fileInputs = $$('input[type=file]', form);
        if (checkFileInputs === true && fileInputs.length > 0) {
            for (i = 0; i < fileInputs.length; i++) {
                if (fileInputs[i].files.length > 0) {
                    return true;
                }
            }
        }
        return Utils.serializeForm(form) !== originalData;
    }
}
