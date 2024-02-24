import { $, $$ } from "../../utils/selectors";
import Sortable from "sortablejs";

export class ArrayInput {
    constructor(input: HTMLInputElement) {
        const isAssociative = input.classList.contains("form-input-array-associative");
        const inputName = input.dataset.name;

        $$(".form-input-array-row", input).forEach((element) => bindRowEvents(element));

        Sortable.create(input, {
            handle: ".sortable-handle",
            forceFallback: true,
        });

        function addRow(row: HTMLElement) {
            const clone = row.cloneNode(true) as HTMLElement;
            const parent = row.parentNode as ParentNode;
            clearRow(clone);
            bindRowEvents(clone);
            if (row.nextSibling) {
                parent.insertBefore(clone, row.nextSibling);
            } else {
                parent.appendChild(clone);
            }
        }

        function removeRow(row: HTMLElement) {
            const parent = row.parentNode as ParentNode;
            if ($$(".form-input-array-row", parent).length > 1) {
                parent.removeChild(row);
            } else {
                clearRow(row);
            }
        }

        function clearRow(row: HTMLElement) {
            if (isAssociative) {
                const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
                inputKey.value = "";
                inputKey.removeAttribute("value");
            }
            const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
            inputValue.value = "";
            inputValue.removeAttribute("value");
            inputValue.name = `${inputName}[]`;
        }

        function updateAssociativeRow(row: HTMLElement) {
            const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
            const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
            inputValue.name = `${inputName}[${inputKey.value.trim()}]`;
        }

        function bindRowEvents(row: HTMLElement) {
            const inputAdd = $(".form-input-array-add", row) as HTMLButtonElement;
            const inputRemove = $(".form-input-array-remove", row) as HTMLButtonElement;

            inputAdd.addEventListener("click", addRow.bind(inputAdd, row));
            inputRemove.addEventListener("click", removeRow.bind(inputRemove, row));

            if (isAssociative) {
                const inputKey = $(".form-input-array-key", row) as HTMLInputElement;
                const inputValue = $(".form-input-array-value", row) as HTMLInputElement;
                inputKey.addEventListener("keyup", updateAssociativeRow.bind(inputKey, row));
                inputValue.addEventListener("keyup", updateAssociativeRow.bind(inputValue, row));
            }
        }
    }
}
