Formwork.Forms = {
    init: function() {
        $('[data-form]').each(function() {
            new Formwork.Form($(this));
        });

        $('input[data-enable]').change(function() {
            var $this = $(this);
            var checked = $this.is(':checked');
            $.each($this.data('enable').split(','), function(index, value) {
                $('input[name="' + value + '"]').attr('disabled', !checked);
            });
        });

        $('.input-reset').click(function() {
            var $target = $('#' + $(this).data('reset'));
            $target.val('');
            $target.change();
        });

        $('input:file').each(function() {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var labelHTML = $span.html();
            $this.data('originalLabel', labelHTML);
        }).on('change input', function() {
            var $this = $(this);
            var $span = $('label[for="' + $this.attr('id') + '"] span');
            var files = $this.prop('files');
            if (files.length) {
                $span.text(files[0].name);
            } else {
                $span.html($this.data('originalLabel'));
            }
        });

        $('input:file[data-auto-upload]').change(function() {
            $(this).closest('form').submit();
        });

        $('.file-input-label').on('drag dragstart dragend dragover dragenter dragleave drop', function(event) {
            event.preventDefault();
        }).on('drop', function(event) {
            var $target = $('#' + $(this).attr('for'));
            $target.prop('files', event.originalEvent.dataTransfer.files);
            // Firefox won't trigger a change event, so we explicitly do that
            $target.change();
        }).on('dragover dragenter', function() {
            $(this).addClass('drag');
        }).on('dragleave drop', function() {
            $(this).removeClass('drag');
        });

        $('.tag-input').tagInput();

        $('.image-input').click(function() {
            var $this = $(this);
            var value = $this.val();
            Formwork.Modals.show('imagesModal', null, function($modal) {
                $modal.find('.image-picker-confirm').data('target', $this);
                $modal.find('.image-picker-thumbnail').each(function() {
                    var $thumbnail = $(this);
                    if ($thumbnail.data('text') == value) {
                        $thumbnail.addClass('selected');
                        return false;
                    }
                });
            });
        });

        $('.image-picker').each(function() {
            var $this = $(this);
            var options = $this.children('option');
            if (options.length > 0) {
                var container = $('<div>', {class: 'image-picker-thumbnails'});
                for (var i = 0; i < options.length; i++) {
                    $('<div>', {
                        class: 'image-picker-thumbnail',
                        'data-value': options[i].value,
                        'data-text': options[i].text
                    }).css({
                        'background-image': 'url(' + options[i].value + ')'
                    }).appendTo(container);
                }
                $this.before(container);
                $('.image-picker-empty-state').hide();
            }
            $this.hide();
        });

        $('.image-picker-confirm').click(function() {
            var $this = $(this);
            $this.data('target').val($this.parent().find('.image-picker-thumbnail.selected').data('text'));
        });

        $('.image-picker-thumbnail').click(function() {
            var $this = $(this);
            $this.siblings().removeClass('selected');
            $this.addClass('selected');
            $this.parent().siblings('.image-input').val($this.data('value'));
        });

        $('[data-command=upload]').click(function() {
            var $target = $('#' + $(this).data('upload-target'));
            $target.click();
        });

        $('.editor-textarea').each(function() {
            new Formwork.Editor($(this).attr('id'));
        });

        $('input[type=range]').on('change input', function() {
            var $this = $(this);
            $this.next('.range-input-value').text($this.val());
        });
    }
};
