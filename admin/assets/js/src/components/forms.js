import ArrayInput from './arrayinput';
import DatePicker from './datepicker';
import DurationInput from './durationinput';
import Editor from './editor';
import FileInput from './fileinput';
import Form from './form';
import ImagePicker from './imagepicker';
import Modals from './modals';
import RangeInput from './rangeinput';
import TagInput from './taginput';
import Utils from './utils';

export default {
    init: function () {

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
                Utils.triggerEvent(target, 'change');
            });
        });

        $$('.input-date').forEach(function (element) {
            DatePicker(element, Formwork.config.DatePicker);
        });

        $$('.input-image').forEach(function (element) {
            element.addEventListener('click', function () {
                Modals.show('imagesModal', null, function (modal) {
                    var selected = $('.image-picker-thumbnail.selected', modal);
                    var thumbnail;
                    if (selected) {
                        selected.classList.remove('selected');
                    }
                    if (element.value) {
                        thumbnail = $('.image-picker-thumbnail[data-filename="' + element.value + '"]', modal);
                        if (thumbnail) {
                            thumbnail.classList.add('selected');
                        }
                    }
                    $('.image-picker-confirm', modal).setAttribute('data-target', element.id);
                });
            });
        });

        $$('.image-picker').forEach(function (element) {
            ImagePicker(element);
        });

        $$('.editor-textarea').forEach(function (element) {
            Editor(element);
        });

        $$('input[type=file]').forEach(function (element) {
            FileInput(element);
        });

        $$('input[data-field=tags]').forEach(function (element) {
            TagInput(element);
        });

        $$('input[data-field=duration]').forEach(function (element) {
            DurationInput(element, Formwork.config.DurationInput);
        });

        $$('input[type=range]').forEach(function (element) {
            RangeInput(element);
        });

        $$('.input-array').forEach(function (element) {
            ArrayInput(element);
        });

        // Load the Form component at the end, after initialization of elements
        $$('[data-form]').forEach(function (element) {
            Form(element);
        });
    }
};
