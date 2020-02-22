Formwork.Forms = {
    init: function () {

        $$('[data-form]').forEach(function (element) {
            Formwork.Form(element);
        });

        $$('input[data-enable]').forEach(function (element) {
            element.addEventListener('change', function () {
                var i, input;
                var inputs = this.getAttribute('data-enable').split(',');
                for (i = 0; i < inputs.length; i++) {
                    input = $('input[name="' + inputs[i] + '"]');
                    if (!this.checked) {
                        input.setAttribute('disabled', '');
                    } else {
                        input.removeAttribute('disabled');
                    }
                }
            });
        });

        $$('.input-reset').forEach(function (element) {
            element.addEventListener('click', function () {
                var target = document.getElementById(this.getAttribute('data-reset'));
                target.value = '';
                Formwork.Utils.triggerEvent(target, 'change');
            });
        });

        $$('.image-input').forEach(function (element) {
            element.addEventListener('click', function () {
                Formwork.Modals.show('imagesModal', null, function (modal) {
                    var selected = $('.image-picker-thumbnail.selected', modal);
                    if (selected) {
                        selected.classList.remove('selected');
                    }
                    if (this.value) {
                        $('.image-picker-thumbnail[data-filename="' + this.value + '"]', modal).classList.add('selected');
                    }
                    $('.image-picker-confirm', modal).setAttribute('data-target', element.id);
                });
            });
        });

        $$('.image-picker').forEach(function (element) {
            Formwork.ImagePicker(element);
        });

        $$('.editor-textarea').forEach(function (element) {
            Formwork.Editor(element);
        });

        $$('input[type=file]').forEach(function (element) {
            Formwork.FileInput(element);
        });

        $$('input[data-field=tags]').forEach(function (element) {
            Formwork.TagInput(element);
        });

        $$('input[type=range]').forEach(function (element) {
            Formwork.RangeInput(element);
        });

        $$('.array-input').forEach(function (element) {
            Formwork.ArrayInput(element);
        });
    }
};
