import Sortable from 'sortablejs';

export default function ArrayInput(input) {
    var isAssociative = input.classList.contains('array-input-associative');
    var inputName = input.getAttribute('data-name');

    $$('.array-input-row', input).forEach(function (element) {
        bindRowEvents(element);
    });

    Sortable.create(input, {
        handle: '.sort-handle',
        forceFallback: true
    });

    function addRow(row) {
        var clone = row.cloneNode(true);
        clearRow(clone);
        bindRowEvents(clone);
        if (row.nextSibling) {
            row.parentNode.insertBefore(clone, row.nextSibling);
        } else {
            row.parentNode.appendChild(clone);
        }
    }

    function removeRow(row) {
        if ($$('.array-input-row', row.parentNode).length > 1) {
            row.parentNode.removeChild(row);
        } else {
            clearRow(row);
        }
    }

    function clearRow(row) {
        var inputKey, inputValue;
        if (isAssociative) {
            inputKey = $('.array-input-key', row);
            inputKey.value = '';
            inputKey.removeAttribute('value');
        }
        inputValue = $('.array-input-value', row);
        inputValue.value = '';
        inputValue.removeAttribute('value');
        inputValue.name = inputName + '[]';
    }

    function updateAssociativeRow(row) {
        var inputKey = $('.array-input-key', row);
        var inputValue = $('.array-input-value', row);
        inputValue.name = inputName + '[' + inputKey.value.trim() + ']';
    }

    function bindRowEvents(row) {
        var inputAdd = $('.array-input-add', row);
        var inputRemove = $('.array-input-remove', row);
        var inputKey, inputValue;

        inputAdd.addEventListener('click', addRow.bind(inputAdd, row));
        inputRemove.addEventListener('click', removeRow.bind(inputRemove, row));

        if (isAssociative) {
            inputKey = $('.array-input-key', row);
            inputValue = $('.array-input-value', row);
            inputKey.addEventListener('keyup', updateAssociativeRow.bind(inputKey, row));
            inputValue.addEventListener('keyup',updateAssociativeRow.bind(inputValue, row));
        }
    }
}
