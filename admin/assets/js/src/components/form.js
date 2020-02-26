Formwork.Form = function (form) {
    var originalData = Formwork.Utils.serializeForm(form);

    form.addEventListener('submit', function () {
        window.removeEventListener('beforeunload', handleBeforeunload);
    });

    window.addEventListener('beforeunload', handleBeforeunload);

    $$('input[type=file][data-auto-upload]', form).forEach(function (element) {
        element.addEventListener('change', function () {
            if (!hasChanged(false)) {
                form.submit();
            }
        });
    });

    $('#changesModal [data-command=continue]').addEventListener('click', function () {
        window.removeEventListener('beforeunload', handleBeforeunload);
        window.location.href = this.getAttribute('data-href');
    });

    $$('a[href]:not([href^="#"]):not([target="_blank"])').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (hasChanged()) {
                event.preventDefault();
                Formwork.Modals.show('changesModal', null, function (modal) {
                    $('[data-command=continue]', modal).setAttribute('data-href', element.href);
                });
            }
        });
    });

    function handleBeforeunload(event) {
        if (hasChanged()) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    function hasChanged(checkFileInputs) {
        var fileInputs, i;
        fileInputs = $$('input[file]', form);
        if (typeof checkFileInputs === 'undefined') {
            checkFileInputs = true;
        }
        if (checkFileInputs === true && fileInputs.length > 0) {
            for (i = 0; i < fileInputs.length; i++) {
                if (fileInputs[i].files.length > 0) {
                    return true;
                }
            }
        }
        return Formwork.Utils.serializeForm(form) !== originalData;
    }
};
