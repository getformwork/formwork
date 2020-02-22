Formwork.ArrayInput = function (input) {
    var isAssociative = input.classList.contains('array-input-associative');
    var inputName = input.getAttribute('data-name');

    $('.array-input-add', input).addEventListener('click', addRow);
    $('.array-input-remove', input).addEventListener('click', removeRow);

    if (isAssociative) {
        $('.array-input-key', input).addEventListener('keyup', function () {
            $('.array-input-value', this.parentNode).setAttribute('name', inputName + '[' + this.value + ']');
        });
        $('.array-input-value', input).addEventListener('keyup', function () {
            this.setAttribute('name', inputName + '[' + $('.array-input-key', this.parentNode).value + ']');
        });
    }

    /* global Sortable:false */
    Sortable.create(input, {
        handle: '.sort-handle',
        forceFallback: true
    });

    function addRow() {
        var row = this.closest('.array-input-row');
        var clone = row.cloneNode(true);
        $('.array-input-key', clone).value = '';
        $('.array-input-value', clone).value = '';
        $('.array-input-add', clone).addEventListener('click', addRow);
        $('.array-input-remove', clone).addEventListener('click', removeRow);
        if (row.nextSibling) {
            row.parentNode.insertBefore(clone, row.nextSibling);
        } else {
            row.parentNode.appendChild(clone);
        }
    }

    function removeRow() {
        var row = this.closest('.array-input-row');
        if ($$('.array-input-row', row.parentNode).length > 0) {
            row.parentNode.removeChild(row);
        } else {
            $('.array-input-key', row).value = '';
            $('.array-input-value', row).value = '';

            Formwork.Utils.triggerEvent($('.array-input-key', row), 'keyup');
        }
    }
};
