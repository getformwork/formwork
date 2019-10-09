Formwork.Forms = {
    init: function () {
        $('[data-form]').each(function () {
            new Formwork.Form($(this));
        });

        $('input[data-enable]').on('change', function () {
            var $this = $(this);
            var checked = $this.is(':checked');
            $.each($this.attr('data-enable').split(','), function (index, value) {
                $('input[name="' + value + '"]').attr('disabled', !checked);
            });
        });

        $('.input-reset').on('click', function () {
            var $target = $('#' + $(this).attr('data-reset'));
            $target.val('');
            $target.trigger('change');
        });

        $('input:file').each(function () {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var labelHTML = $span.html();
            $this.data('originalLabel', labelHTML);
        }).on('change input', function () {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var files = $this.prop('files');
            if (files.length) {
                $span.text(files[0].name);
            } else {
                $span.html($this.data('originalLabel'));
            }
        });

        $('.file-input-label').on('drag dragstart dragend dragover dragenter dragleave drop', function (event) {
            event.preventDefault();
        }).on('drop', function (event) {
            var $target = $('#' + $(this).attr('for'));
            $target.prop('files', event.originalEvent.dataTransfer.files);
            // Firefox won't trigger a change event, so we explicitly do that
            $target.trigger('change');
        }).on('dragover dragenter', function () {
            $(this).addClass('drag');
        }).on('dragleave drop', function () {
            $(this).removeClass('drag');
        });

        $('.tag-input').tagInput();

        $('.image-input').on('click', function () {
            var $this = $(this);
            var value = $this.val();
            Formwork.Modals.show('imagesModal', null, function ($modal) {
                $('.image-picker-confirm', $modal).data('target', $this);
                $('.image-picker-thumbnail.selected', $modal).removeClass('selected');
                if (value) {
                    $('.image-picker-thumbnail[data-filename="' + value + '"]', $modal).addClass('selected');
                }
            });
        });

        $('.image-picker').each(function () {
            var $this = $(this);
            var options = $this.children('option');
            if (options.length > 0) {
                var container = $('<div>', {class: 'image-picker-thumbnails'});
                for (var i = 0; i < options.length; i++) {
                    $('<div>', {
                        class: 'image-picker-thumbnail',
                        'data-uri': options[i].value,
                        'data-filename': options[i].text
                    }).css({'background-image': 'url(' + options[i].value + ')'}).appendTo(container);
                }
                $this.before(container);
                $('.image-picker-empty-state').hide();
            }
            $this.hide();
        });

        $('.image-picker-confirm').on('click', function () {
            var $this = $(this);
            var target = $this.data('target');
            var filename = $('.image-picker-thumbnail.selected', $this.parent()).attr('data-filename');
            if (typeof target === 'function') {
                target(filename);
            } else {
                $this.data('target').val(filename);
            }
        });

        $('.image-picker-thumbnail').on('click', function () {
            var $this = $(this);
            $this.siblings().removeClass('selected');
            $this.addClass('selected');
            $this.parent().siblings('.image-input').val($this.attr('data-uri'));
        });

        $('.image-picker-thumbnail').on('dblclick', function () {
            $(this).trigger('click');
            $('.image-picker-confirm').trigger('click');
        });

        $('[data-command=upload]').on('click', function () {
            var $target = $('#' + $(this).attr('data-upload-target'));
            $target.trigger('click');
        });

        $('.editor-textarea').each(function () {
            new Formwork.Editor($(this).attr('id'));
        });

        $('input[type=range]').on('change input', function () {
            var $this = $(this);
            $this.next('.range-input-value').text($this.val());
        });

        $('.array-input').each(function () {
            $('.array-input-add', this).on('click', function () {
                var $row = $(this).closest('.array-input-row');
                var $clone = $row.clone(true);
                $clone.find('.array-input-key, .array-input-value').val('');
                $row.after($clone);
            });

            $('.array-input-remove', this).on('click', function () {
                var $row = $(this).closest('.array-input-row');
                if ($row.siblings('.array-input-row').length > 0) {
                    $row.remove();
                } else {
                    $row.find('.array-input-key, .array-input-value').val('');
                    $(this).siblings('.array-input-key').trigger('keyup');
                }
            });

            if ($(this).hasClass('array-input-associative')) {
                var inputName = $(this).attr('data-name');
                $('.array-input-key', this).on('keyup', function () {
                    $(this).siblings('.array-input-value').attr('name', inputName + '[' + $(this).val() + ']');
                });
                $('.array-input-value', this).on('keyup', function () {
                    $(this).attr('name', inputName + '[' + $(this).siblings('.array-input-key').val() + ']');
                });
            }

            /* global Sortable:false */
            Sortable.create(this, {
                handle: '.array-input-handle',
                forceFallback: true
            });
        });
    }
};
