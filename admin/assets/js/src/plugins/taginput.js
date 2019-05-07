(function ($) {
    $.fn.tagInput = function () {

        function _update($input) {
            var $parent = $input.parent();
            $('.tag-hidden-input', $parent).val($parent.data('tags').join(', '));
            _placeholder($input);
        }

        function _placeholder($input) {
            var $parent = $input.parent();
            var placeholder = $input.data('placeholder');
            if (placeholder.length > 0) {
                if ($parent.data('tags').length === 0) {
                    $input.attr('placeholder', placeholder);
                    $input.prop('size', placeholder.length);
                } else {
                    $input.attr('placeholder', '');
                    $input.prop('size', 1);
                }
            }
        }

        function _createTag($input, value) {
            $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
        }

        function addTag($input, value) {
            if ($input.parent().data('tags').indexOf(value) === -1) {
                $input.parent().data('tags').push(value);
                _createTag($input, value);
                _update($input);
            }
            $input.val('');
        }

        function removeTag($input, value) {
            var tags = $input.parent().data('tags');
            var index = tags.indexOf(value);
            if (index > -1) {
                tags.splice(index, 1);
                $input.parent().data('tags', tags);
                _update($input);
            }
        }

        this.each(function () {
            var $this = $(this);
            var $target = $('.tag-hidden-input', $this);
            var $input = $('.tag-inner-input', $this);
            var tags = [];

            if ($target.val()) {
                tags = $target.val().split(', ');
                $.each(tags, function (index, value) {
                    value = value.trim();
                    tags[index] = value;
                    _createTag($input, value);
                });
            }

            $this.data('tags', tags)
                .on('mousedown', '.tag-remove', false)
                .on('click', '.tag-remove', function () {
                    var $tag = $(this).parent();
                    removeTag($input, $tag.text());
                    $tag.remove();
                    return false;
                });

            if ($input.attr('placeholder') !== undefined) {
                $input.data('placeholder', $input.attr('placeholder'));
                _placeholder($input);
            } else {
                $input.data('placeholder', '');
            }
        });

        this.on('mousedown', function () {
            $('.tag-inner-input', this).trigger('focus');
            return false;
        });

        $('.tag-inner-input', this).on('focus', function () {
            $(this).parent().addClass('focused');
        }).on('blur', function () {
            var $this = $(this);
            var value = $this.val().trim();
            if (value !== '') {
                addTag($this, value);
            }
            $this.parent().removeClass('focused');
        }).on('keydown', function (event) {
            var options = {addKeyCodes: [32]};
            var $this = $(this);
            var value = $this.val().trim();

            switch (event.which) {
            case 8: // backspace
                if (value === '') {
                    removeTag($this, $this.prev().text());
                    $this.prev().remove();
                    return false;
                }
                $this.prop('size', Math.max($this.val().length, $this.attr('placeholder').length, 1));
                return true;
            case 13: // enter
            case 188: // comma
                if (value !== '') {
                    addTag($this, value);
                }
                return false;
            default:
                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                    addTag($this, value);
                    return false;
                }
                if (value.length > 0) {
                    $this.prop('size', $this.val().length + 2);
                }
                break;
            }

        });
    };
}(jQuery));
