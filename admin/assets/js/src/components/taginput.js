import Utils from './utils';

export default function TagInput(input) {
    var options = {addKeyCodes: [32]};
    var tags = [];
    var field, innerInput, hiddenInput, placeholder, dropdown;

    createField();
    createDropdown();

    registerInputEvents();

    function createField() {
        var isRequired = input.hasAttribute('required');
        var isDisabled = input.hasAttribute('disabled');

        field = document.createElement('div');
        field.className = 'tag-input';

        innerInput = document.createElement('input');
        innerInput.className = 'tag-inner-input';
        innerInput.id = input.id;
        innerInput.type = 'text';
        innerInput.placeholder = input.placeholder;

        innerInput.setAttribute('size', '');

        hiddenInput = document.createElement('input');
        hiddenInput.className = 'tag-hidden-input';
        hiddenInput.name = input.name;
        hiddenInput.id = input.id;
        hiddenInput.type = 'text';
        hiddenInput.value = input.value;
        hiddenInput.readOnly = true;
        hiddenInput.hidden = true;

        if (isRequired) {
            hiddenInput.required = true;
        }

        if (isDisabled) {
            field.disabled = true;
            innerInput.disabled = true;
            hiddenInput.disabled = true;
        }

        input.parentNode.replaceChild(field, input);
        field.appendChild(innerInput);
        field.appendChild(hiddenInput);

        if (hiddenInput.value) {
            tags = hiddenInput.value.split(', ');
            tags.forEach(function (value, index) {
                value = value.trim();
                tags[index] = value;
                insertTag(value);
            });
        }

        if (innerInput.placeholder) {
            placeholder = innerInput.placeholder;
            updatePlaceholder();
        } else {
            placeholder = '';
        }

        field.addEventListener('mousedown', function (event) {
            innerInput.focus();
            event.preventDefault();
        });
    }

    function createDropdown() {
        var list, key, item;

        if (input.hasAttribute('data-options')) {

            list = JSON.parse(input.getAttribute('data-options'));

            dropdown = document.createElement('div');
            dropdown.className = 'dropdown-list';

            for (key in list) {
                item = document.createElement('div');
                item.className = 'dropdown-item';
                item.innerHTML = list[key];
                item.setAttribute('data-value', key);
                item.addEventListener('click', function () {
                    addTag(this.getAttribute('data-value'));
                });
                dropdown.appendChild(item);
            }

            field.appendChild(dropdown);

            innerInput.addEventListener('focus', function () {
                if (getComputedStyle(dropdown).display === 'none') {
                    updateDropdown();
                    dropdown.scrollTop = 0;
                    dropdown.style.display = 'block';
                }
            });

            innerInput.addEventListener('blur', function () {
                if (getComputedStyle(dropdown).display !== 'none') {
                    updateDropdown();
                    dropdown.style.display = 'none';
                }
            });

            innerInput.addEventListener('keydown', function (event) {
                switch (event.which) {
                case 8: // backspace
                    updateDropdown();
                    break;
                case 13: // enter
                    if (getComputedStyle(dropdown).display !== 'none') {
                        addTagFromSelectedDropdownItem();
                        event.preventDefault();
                    }
                    break;
                case 38: // up arrow
                    if (getComputedStyle(dropdown).display !== 'none') {
                        selectPrevDropdownItem();
                        event.preventDefault();
                    }
                    break;
                case 40: // down arrow
                    if (getComputedStyle(dropdown).display !== 'none') {
                        selectNextDropdownItem();
                        event.preventDefault();
                    }
                    break;
                default:
                    if (options.addKeyCodes.indexOf(event.which) > -1) {
                        addTagFromSelectedDropdownItem();
                        event.preventDefault();
                    }
                }
            });

            innerInput.addEventListener('keyup', Utils.debounce(function (event) {
                var value = innerInput.value.trim();
                switch (event.which) {
                case 27: // escape
                    dropdown.style.display = 'none';
                    break;
                case 38: // up arrow
                case 40: // down arrow
                    return true;
                default:
                    dropdown.style.display = 'block';
                    filterDropdown(value);
                    if (value.length > 0) {
                        selectFirstDropdownItem();
                    }
                }
            }, 100));
        }
    }

    function registerInputEvents() {
        innerInput.addEventListener('focus', function () {
            field.classList.add('focused');
        });

        innerInput.addEventListener('blur', function () {
            var value = innerInput.value.trim();
            if (value !== '') {
                addTag(value);
            }
            field.classList.remove('focused');
        });

        innerInput.addEventListener('keydown', function () {
            var value = innerInput.value.trim();
            switch (event.which) {
            case 8: // backspace
                if (value === '') {
                    removeTag(tags[tags.length - 1]);
                    if (innerInput.previousSibling){
                        innerInput.parentNode.removeChild(innerInput.previousSibling);
                    }
                    event.preventDefault();
                } else {
                    innerInput.size = Math.max(innerInput.value.length, innerInput.placeholder.length, 1);
                }
                break;
            case 13: // enter
            case 188: // comma
                if (value !== '') {
                    addTag(value);
                }
                event.preventDefault();
                break;
            case 27: // escape
                clearInput();
                innerInput.blur();
                event.preventDefault();
                break;
            default:
                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                    addTag(value);
                    event.preventDefault();
                    break;
                }
                if (value.length > 0) {
                    innerInput.size = innerInput.value.length + 2;
                }
                break;
            }
        });
    }

    function updateTags() {
        hiddenInput.value = tags.join(', ');
        updatePlaceholder();
    }

    function updatePlaceholder() {
        if (placeholder.length > 0) {
            if (tags.length === 0) {
                innerInput.placeholder = placeholder;
                innerInput.size = placeholder.length;
            } else {
                innerInput.placeholder = '';
                innerInput.size = 1;
            }
        }
    }

    function validateTag(value) {
        if (tags.indexOf(value) === -1) {
            if (dropdown) {
                return $('[data-value="' + value + '"]', dropdown) !== null;
            }
            return true;
        }
        return false;
    }

    function insertTag(value) {
        var tag = document.createElement('span');
        var tagRemove = document.createElement('i');
        tag.className = 'tag';
        tag.innerHTML = value;
        tag.style.marginRight = '.25rem';
        innerInput.parentNode.insertBefore(tag, innerInput);

        tagRemove.className = 'tag-remove';
        tagRemove.addEventListener('mousedown', function (event) {
            removeTag(value);
            tag.parentNode.removeChild(tag);
            event.preventDefault();
        });
        tag.appendChild(tagRemove);
    }

    function addTag(value) {
        if (validateTag(value)) {
            tags.push(value);
            insertTag(value);
            updateTags();
        } else {
            updatePlaceholder();
        }
        innerInput.value = '';
        if (dropdown) {
            updateDropdown();
        }
    }

    function removeTag(value) {
        var index = tags.indexOf(value);
        if (index > -1) {
            tags.splice(index, 1);
            updateTags();
        }
        if (dropdown) {
            updateDropdown();
        }
    }

    function clearInput() {
        innerInput.value = '';
        updatePlaceholder();
    }

    function updateDropdown() {
        var visibleItems = 0;
        $$('.dropdown-item', dropdown).forEach(function (element) {
            if (getComputedStyle(element).display !== 'none') {
                visibleItems++;
            }
            if (tags.indexOf(element.getAttribute('data-value')) === -1) {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
            element.classList.remove('selected');
        });
        if (visibleItems > 0) {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }

    function filterDropdown(value) {
        var visibleItems = 0;
        dropdown.style.display = 'block';
        $$('.dropdown-item', dropdown).forEach(function (element) {
            var text = element.textContent;
            var regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), 'i');
            if (text.match(regexp) !== null && element.style.display !== 'none') {
                element.style.display = 'block';
                visibleItems++;
            } else {
                element.style.display = 'none';
            }
        });
        if (visibleItems > 0) {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }

    function scrollToDropdownItem(item) {
        var dropdownScrollTop = dropdown.scrollTop;
        var dropdownHeight = dropdown.clientHeight;
        var dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
        var dropdownStyle = getComputedStyle(dropdown);
        var dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
        var dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
        var itemTop = item.offsetTop;
        var itemHeight = item.clientHeight;
        var itemBottom = itemTop + itemHeight;
        if (itemTop < dropdownScrollTop) {
            dropdown.scrollTop = itemTop - dropdownPaddingTop;
        } else if (itemBottom > dropdownScrollBottom) {
            dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
        }
    }

    function addTagFromSelectedDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        if (getComputedStyle(selectedItem).display !== 'none') {
            innerInput.value = selectedItem.getAttribute('data-value');
        }
    }

    function selectDropdownItem(item) {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        if (selectedItem) {
            selectedItem.classList.remove('selected');
        }
        if (item) {
            item.classList.add('selected');
            scrollToDropdownItem(item);
        }
    }

    function selectFirstDropdownItem() {
        var items = $$('.dropdown-item', dropdown);
        var i;
        for (i = 0; i < items.length; i++) {
            if (getComputedStyle(items[i]).display !== 'none') {
                selectDropdownItem(items[i]);
                return;
            }
        }
    }

    function selectLastDropdownItem() {
        var items = $$('.dropdown-item', dropdown);
        var i;
        for (i = items.length - 1; i >= 0; i--) {
            if (getComputedStyle(items[i]).display !== 'none') {
                selectDropdownItem(items[i]);
                return;
            }
        }
    }

    function selectPrevDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        var previousItem;
        if (selectedItem) {
            previousItem = selectedItem.previousSibling;
            while (previousItem && previousItem.style.display === 'none') {
                previousItem = previousItem.previousSibling;
            }
            if (previousItem) {
                return selectDropdownItem(previousItem);
            }
            selectDropdownItem(selectedItem.previousSibling);
        }
        selectLastDropdownItem();
    }

    function selectNextDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        var nextItem;
        if (selectedItem) {
            nextItem = selectedItem.nextSibling;
            while (nextItem && nextItem.style.display === 'none') {
                nextItem = nextItem.nextSibling;
            }
            if (nextItem) {
                return selectDropdownItem(nextItem);
            }
        }
        selectFirstDropdownItem();
    }
}
