(function ($) {
    $.fn.tagInput = function () {
        this.each(function () {
            var $this = $(this);
            var $field = createField();
            var $target = $('.tag-hidden-input', $field);
            var $input = $('.tag-inner-input', $field);
            var tags = [];

            function createField() {
                var isRequired = $this.is('[required]');
                var isDisabled = $this.is('[disabled]');
                var field = $(
                    '<div class="tag-input"' + (isDisabled ? ' disabled' : '') + '>\n' +
                        '<input class="tag-inner-input" id="' + $this.attr('id') + '" type="text" size="" placeholder="' + $this.attr('placeholder') + '"' + (isDisabled ? ' disabled' : '') + '>\n' +
                        '<input class="tag-hidden-input" name="' + $this.attr('name') + '" hidden readonly value="' + $this.attr('value') + '"' + (isRequired ? ' required' : '') + (isDisabled ? ' disabled' : '') + '>\n' +
                    '</div>'
                );
                $this.replaceWith(field);
                return field;
            }

            function updateTags() {
                $target.val($input.data('tags').join(', '));
                updatePlaceholder();
            }

            function updatePlaceholder() {
                var placeholder = $input.data('placeholder');
                if (placeholder.length > 0) {
                    if ($input.data('tags').length === 0) {
                        $input.attr('placeholder', placeholder);
                        $input.prop('size', placeholder.length);
                    } else {
                        $input.attr('placeholder', '');
                        $input.prop('size', 1);
                    }
                }
            }

            function insertTag(value) {
                $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
            }

            function addTag(value) {
                if ($input.data('tags').indexOf(value) === -1) {
                    $input.data('tags').push(value);
                    insertTag(value);
                    updateTags();
                }
                $input.val('');
            }

            function removeTag(value) {
                var tags = $input.data('tags');
                var index = tags.indexOf(value);
                if (index > -1) {
                    tags.splice(index, 1);
                    $input.data('tags', tags);
                    updateTags();
                }
            }

            if ($target.val()) {
                tags = $target.val().split(', ');
                $.each(tags, function (index, value) {
                    value = value.trim();
                    tags[index] = value;
                    insertTag(value);
                });
            }

            $input.data('tags', tags)
                .on('mousedown', '.tag-remove', false)
                .on('click', '.tag-remove', function () {
                    var $tag = $(this).parent();
                    removeTag($tag.text());
                    $tag.remove();
                    return false;
                });

            if ($input.attr('placeholder') !== undefined) {
                $input.data('placeholder', $input.attr('placeholder'));
                updatePlaceholder();
            } else {
                $input.data('placeholder', '');
            }

            $field.on('mousedown', function () {
                $input.trigger('focus');
                return false;
            });

            $input.on('focus', function () {
                $field.addClass('focused');
            }).on('blur', function () {
                var value = $input.val().trim();
                if (value !== '') {
                    addTag(value);
                }
                $field.removeClass('focused');
            }).on('keydown', function (event) {
                var options = {addKeyCodes: [32]};
                var value = $input.val().trim();

                switch (event.which) {
                case 8: // backspace
                    if (value === '') {
                        removeTag($input.prev().text());
                        $input.prev().remove();
                        return false;
                    }
                    $input.prop('size', Math.max($input.val().length, $input.attr('placeholder').length, 1));
                    return true;
                case 13: // enter
                case 188: // comma
                    if (value !== '') {
                        addTag(value);
                    }
                    return false;
                default:
                    if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                        addTag(value);
                        return false;
                    }
                    if (value.length > 0) {
                        $input.prop('size', $input.val().length + 2);
                    }
                    break;
                }
            });
        });
    };
}(jQuery));
