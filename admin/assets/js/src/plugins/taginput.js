(function ($) {
    $.fn.tagInput = function () {
        this.each(function () {
            var $this = $(this);
            var $target = $('.tag-hidden-input', $this);
            var $input = $('.tag-inner-input', $this);
            var tags = [];

            function _update() {
                $target.val($this.data('tags').join(', '));
                _placeholder();
            }

            function _placeholder() {
                var placeholder = $input.data('placeholder');
                if (placeholder.length > 0) {
                    if ($this.data('tags').length === 0) {
                        $input.attr('placeholder', placeholder);
                        $input.prop('size', placeholder.length);
                    } else {
                        $input.attr('placeholder', '');
                        $input.prop('size', 1);
                    }
                }
            }

            function _createTag(value) {
                $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
            }

            function addTag(value) {
                if ($this.data('tags').indexOf(value) === -1) {
                    $this.data('tags').push(value);
                    _createTag(value);
                    _update();
                }
                $input.val('');
            }

            function removeTag(value) {
                var tags = $input.parent().data('tags');
                var index = tags.indexOf(value);
                if (index > -1) {
                    tags.splice(index, 1);
                    $this.data('tags', tags);
                    _update();
                }
            }

            if ($target.val()) {
                tags = $target.val().split(', ');
                $.each(tags, function (index, value) {
                    value = value.trim();
                    tags[index] = value;
                    _createTag(value);
                });
            }

            $this.data('tags', tags)
                .on('mousedown', '.tag-remove', false)
                .on('click', '.tag-remove', function () {
                    var $tag = $(this).parent();
                    removeTag($tag.text());
                    $tag.remove();
                    return false;
                });

            if ($input.attr('placeholder') !== undefined) {
                $input.data('placeholder', $input.attr('placeholder'));
                _placeholder();
            } else {
                $input.data('placeholder', '');
            }

            $this.on('mousedown', function () {
                $input.trigger('focus');
                return false;
            });

            $input.on('focus', function () {
                $this.addClass('focused');
            }).on('blur', function () {
                var value = $input.val().trim();
                if (value !== '') {
                    addTag(value);
                }
                $this.removeClass('focused');
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
