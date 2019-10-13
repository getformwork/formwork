(function ($) {
    $.fn.tagInput = function () {
        this.each(function () {
            var options = {addKeyCodes: [32]};
            var $this = $(this);
            var $field = createField();
            var $target = $('.tag-hidden-input', $field);
            var $input = $('.tag-inner-input', $field);
            var $dropdown = createDropdown();
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

            function createDropdown() {
                if ($this.is('[data-options]')) {
                    var list = $.parseJSON($this.attr('data-options'));
                    var dropdown = $('<div class="dropdown-list"></div>');
                    $field.append(dropdown);
                    for (var key in list) {
                        dropdown.append(
                            '<div class="dropdown-item" data-value="' + key + '">' + list[key] + '</div>'
                        );
                    }
                    return dropdown;
                }
                return null;
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

            function validateTag(value) {
                if ($input.data('tags').indexOf(value) === -1) {
                    if ($dropdown !== null) {
                        return $('[data-value="' + value + '"]', $dropdown).length > 0;
                    }
                    return true;
                }
                return false;
            }

            function insertTag(value) {
                $input.before('\n<span class="tag">' + value + '<i class="tag-remove"></i></span>');
            }

            function addTag(value) {
                if (validateTag(value)) {
                    $input.data('tags').push(value);
                    insertTag(value);
                    updateTags();
                } else {
                    updatePlaceholder();
                }
                $input.val('');
                if ($dropdown !== null) {
                    updateDropdown();
                }
            }

            function removeTag(value) {
                var tags = $input.data('tags');
                var index = tags.indexOf(value);
                if (index > -1) {
                    tags.splice(index, 1);
                    $input.data('tags', tags);
                    updateTags();
                }
                if ($dropdown !== null) {
                    updateDropdown();
                }
            }

            function clearInput() {
                $input.val('');
                updatePlaceholder();
            }

            function updateDropdown() {
                var tags = $input.data('tags');
                $('.dropdown-item', $dropdown).each(function () {
                    var visible = tags.indexOf($(this).attr('data-value')) === -1;
                    $(this).toggle(visible);
                });
                $('.dropdown-item', $dropdown).removeClass('selected');
                $dropdown.toggle($('.dropdown-item', $dropdown).filter(':visible').length > 0);
            }

            function filterDropdown(value) {
                $dropdown.show();
                $('.dropdown-item', $dropdown).each(function () {
                    var $this = $(this);
                    var regexp = new RegExp(Formwork.Utils.escapeRegExp(value), 'i');
                    var matched = !!$(this).text().match(regexp);
                    if ($this.is(':visible')) {
                        $this.toggle(matched);
                    }
                });
                $dropdown.toggle($('.dropdown-item', $dropdown).filter(':visible').length > 0);
            }

            function scrollToDropdownItem($item) {
                var dropdownScrollTop = $dropdown.scrollTop();
                var dropdownTop = $dropdown.position().top;
                var dropdownHeight = $dropdown.outerHeight();
                var dropdownBottom = dropdownTop + dropdownHeight;
                var itemTop = $item.position().top;
                var itemHeight = $item.outerHeight();
                var itemBottom = itemTop + itemHeight;
                if (dropdownBottom - itemBottom > 0 && dropdownBottom - itemBottom < itemHeight) {
                    $dropdown.scrollTop(dropdownScrollTop + itemHeight);
                } else if (itemBottom > dropdownHeight || itemBottom < dropdownTop) {
                    $dropdown.scrollTop(dropdownScrollTop + itemTop);
                }
            }

            function addTagFromSelectedDropdownItem() {
                var $selectedItem = $('.dropdown-item', $dropdown).filter('.selected:visible');
                if ($selectedItem.length > 0) {
                    $input.val($selectedItem.attr('data-value'));
                }
            }

            function getDropdownItems() {
                return $('.dropdown-item', $dropdown).filter(':visible');
            }

            function selectDropdownItem($item) {
                $('.dropdown-item', $dropdown).removeClass('selected');
                if ($item.length > 0) {
                    $item.addClass('selected');
                    scrollToDropdownItem($item);
                }
            }

            function selectFirstDropdownItem() {
                selectDropdownItem(getDropdownItems().first());
            }

            function selectLastDropdownItem() {
                selectDropdownItem(getDropdownItems().last());
            }

            function selectPrevDropdownItem() {
                var $prev = getDropdownItems().filter('.selected').prevAll(':visible').first();
                if ($prev.length > 0) {
                    selectDropdownItem($prev);
                } else {
                    selectLastDropdownItem();
                }
            }

            function selectNextDropdownItem() {
                var $next = getDropdownItems().filter('.selected').nextAll(':visible').first();
                if ($next.length > 0) {
                    selectDropdownItem($next);
                } else {
                    selectFirstDropdownItem();
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

            if ($dropdown !== null) {
                $input.on('focus', function () {
                    if (!$dropdown.is(':visible')) {
                        updateDropdown();
                        $dropdown.show();
                    }
                }).on('blur', function () {
                    if ($dropdown.is(':visible')) {
                        $dropdown.hide();
                    }
                }).on('keydown', function (event) {
                    switch (event.which) {
                    case 8: // backspace
                        updateDropdown();
                        break;
                    case 13: // enter
                        if ($dropdown.is(':visible')) {
                            addTagFromSelectedDropdownItem();
                            return false;
                        }
                        break;
                    case 38: // up arrow
                        if ($dropdown.is(':visible')) {
                            selectPrevDropdownItem();
                            return false;
                        }
                        break;
                    case 40: // down arrow
                        if ($dropdown.is(':visible')) {
                            selectNextDropdownItem();
                            return false;
                        }
                        break;
                    default:
                        if (options.addKeyCodes.indexOf(event.which) > -1) {
                            addTagFromSelectedDropdownItem();
                            return false;
                        }
                    }
                }).on('keyup', Formwork.Utils.debounce(function (event) {
                    var value = $input.val().trim();
                    switch (event.which) {
                    case 27: // escape
                        $dropdown.hide();
                        break;
                    case 38: // up arrow
                    case 40: // down arrow
                        return true;
                    default:
                        $dropdown.show();
                        filterDropdown(value);
                        if (value.length > 0) {
                            selectFirstDropdownItem();
                        }
                    }
                }, 100));

                $dropdown.on('click', '.dropdown-item', function () {
                    addTag($(this).attr('data-value'));
                });
            }

            $input.on('focus', function () {
                $field.addClass('focused');
            }).on('blur', function () {
                var value = $input.val().trim();
                if (value !== '') {
                    addTag(value);
                }
                $field.removeClass('focused');
            }).on('keydown', function (event) {
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
                case 27: // escape
                    clearInput();
                    $input.trigger('blur');
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
