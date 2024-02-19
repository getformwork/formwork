import { $, $$ } from "../../utils/selectors";
import Sortable from "sortablejs";

export class ArrayInput {
    constructor(input) {
        const isAssociative = input.classList.contains("form-input-array-associative");
        const inputName = input.dataset.name;

        $$(".form-input-array-row", input).forEach((element) => bindRowEvents(element));

        Sortable.create(input, {
            handle: ".sortable-handle",
            forceFallback: true,
        });

        function addRow(row) {
            const clone = row.cloneNode(true);
            clearRow(clone);
            bindRowEvents(clone);
            if (row.nextSibling) {
                row.parentNode.insertBefore(clone, row.nextSibling);
            } else {
                row.parentNode.appendChild(clone);
            }
        }

        function removeRow(row) {
            if ($$(".form-input-array-row", row.parentNode).length > 1) {
                row.parentNode.removeChild(row);
            } else {
                clearRow(row);
            }
        }

        function clearRow(row) {
            if (isAssociative) {
                const inputKey = $(".form-input-array-key", row);
                inputKey.value = "";
                inputKey.removeAttribute("value");
            }
            const inputValue = $(".form-input-array-value", row);
            inputValue.value = "";
            inputValue.removeAttribute("value");
            inputValue.name = `${inputName}[]`;
        }

        function updateAssociativeRow(row) {
            const inputKey = $(".form-input-array-key", row);
            const inputValue = $(".form-input-array-value", row);
            inputValue.name = `${inputName}[${inputKey.value.trim()}]`;
        }

        function bindRowEvents(row) {
            const inputAdd = $(".form-input-array-add", row);
            const inputRemove = $(".form-input-array-remove", row);

            inputAdd.addEventListener("click", addRow.bind(inputAdd, row));
            inputRemove.addEventListener("click", removeRow.bind(inputRemove, row));

            if (isAssociative) {
                const inputKey = $(".form-input-array-key", row);
                const inputValue = $(".form-input-array-value", row);
                inputKey.addEventListener("keyup", updateAssociativeRow.bind(inputKey, row));
                inputValue.addEventListener("keyup", updateAssociativeRow.bind(inputValue, row));
            }
        }
    }
}
