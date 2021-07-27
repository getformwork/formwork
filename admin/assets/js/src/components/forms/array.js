import Sortable from 'sortablejs';

export default function ArrayInput(input) {
    var isAssociative = input.classList.contains('input-array-associative');
    var inputName = input.getAttribute('data-name');

    $$('.input-array-row', input).forEach(function (element) {
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
        if ($$('.input-array-row', row.parentNode).length > 1) {
            row.parentNode.removeChild(row);
        } else {
            clearRow(row);
        }
    }

    function clearRow(row) {
        var inputKey, inputValue;
        if (isAssociative) {
            inputKey = $('.input-array-key', row);
            inputKey.value = '';
            inputKey.removeAttribute('value');
        }
        inputValue = $('.input-array-value', row);
        inputValue.value = '';
        inputValue.removeAttribute('value');
        inputValue.name = inputName + '[]';
    }

    function updateAssociativeRow(row) {
        var inputKey = $('.input-array-key', row);
        var inputValue = $('.input-array-value', row);
        inputValue.name = inputName + '[' + inputKey.value.trim() + ']';
    }

    function bindRowEvents(row) {
        var inputAdd = $('.input-array-add', row);
        var inputRemove = $('.input-array-remove', row);
        var inputKey, inputValue;

        inputAdd.addEventListener('click', addRow.bind(inputAdd, row));
        inputRemove.addEventListener('click', removeRow.bind(inputRemove, row));

        if (isAssociative) {
            inputKey = $('.input-array-key', row);
            inputValue = $('.input-array-value', row);
            inputKey.addEventListener('keyup', updateAssociativeRow.bind(inputKey, row));
            inputValue.addEventListener('keyup',updateAssociativeRow.bind(inputValue, row));
        }
    }
}
