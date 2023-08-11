import ArrayInput from './forms/array';
import DateInput from './forms/date';
import DurationInput from './forms/duration';
import Editor from './forms/editor';
import FileInput from './forms/file';
import Form from './form';
import ImagePicker from './forms/image';
import Modals from './modals';
import RangeInput from './forms/range';
import TagInput from './forms/tag';
import Utils from './utils';
import SelectInput from './forms/select';

export default {
    init: function () {

        $$('input[data-enable]').forEach((element) => {
            element.addEventListener('change', function () {
                const inputs = this.getAttribute('data-enable').split(',');
                for (const name of inputs) {
                    const input = $(`input[name="${name}"]`);
                    if (!this.checked) {
                        input.setAttribute('disabled', '');
                    } else {
                        input.removeAttribute('disabled');
                    }
                }
            });
        });

        $$('.input-reset').forEach((element) => {
            element.addEventListener('click', function () {
                const target = document.getElementById(this.getAttribute('data-reset'));
                target.value = '';
                Utils.triggerEvent(target, 'change');
            });
        });

        $$('.input-date').forEach((element) => {
            DateInput(element, Formwork.config.DateInput);
        });

        $$('.input-image').forEach((element) => {
            element.addEventListener('click', () => {
                Modals.show('imagesModal', null, (modal) => {
                    const selected = $('.image-picker-thumbnail.selected', modal);
                    if (selected) {
                        selected.classList.remove('selected');
                    }
                    if (element.value) {
                        const thumbnail = $(`.image-picker-thumbnail[data-filename="${element.value}"]`, modal);
                        if (thumbnail) {
                            thumbnail.classList.add('selected');
                        }
                    }
                    $('.image-picker-confirm', modal).setAttribute('data-target', element.id);
                });
            });
        });

        $$('.image-picker').forEach((element) => {
            ImagePicker(element);
        });

        $$('.editor-textarea').forEach((element) => {
            Editor(element);
        });

        $$('input[type=file]').forEach((element) => {
            FileInput(element);
        });

        $$('input[data-field=tags]').forEach((element) => {
            TagInput(element);
        });

        $$('input[data-field=duration]').forEach((element) => {
            DurationInput(element, Formwork.config.DurationInput);
        });

        $$('input[type=range]').forEach((element) => {
            RangeInput(element);
        });

        $$('.input-array').forEach((element) => {
            ArrayInput(element);
        });

        $$('select:not([hidden])').forEach((element) => {
            SelectInput(element, Formwork.config.SelectInput);
        });

        $$('.files-list').forEach((filesList) => {
            const toggle = $('.input-togglegroup', filesList);

            const viewAs = window.localStorage.getItem('formwork.filesListViewAs');

            if (viewAs) {
                $$('input', toggle).forEach((input) => input.checked = false);
                $(`input[value=${viewAs}]`, filesList).checked = true;
                filesList.classList.toggle('is-thumbnails', viewAs === 'thumbnails');
            }

            $$('input', toggle).forEach((input) => {
                input.addEventListener('input', () => {
                    filesList.classList.toggle('is-thumbnails', input.value === 'thumbnails');
                    window.localStorage.setItem('formwork.filesListViewAs', input.value);
                });
            });
        });

        // Load the Form component at the end, after initialization of elements
        $$('[data-form]').forEach((element) => {
            Form(element);
        });
    },
};
