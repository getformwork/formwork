import type { Form, HTMLInputLike } from "../../services/form";
import { $, $$ } from "../../utils/selectors";
import { InputBase } from "./input";

/**
 * Provides an input with a list of rows that can be added, removed and reordered
 *
 * Each row has a value input and, if the array is associative, a key input. The value is a JSON
 * object
 */
export class ArrayInput extends InputBase<HTMLFieldSetElement> {
    /**
     * Form containing the input
     */
    readonly form: Form;
    /**
     * Whether the rows have a key
     */
    readonly isAssociative: boolean;

    /**
     * Key and value inputs of each row
     */
    private itemCache = new WeakMap<HTMLElement, { key?: HTMLInputLike; value: HTMLInputLike }>();

    /**
     * Create the array input
     */
    constructor(element: HTMLFieldSetElement, form: Form) {
        super(element);

        this.form = form;

        this.isAssociative = element.classList.contains("form-input-array-associative");

        void this.init(element);
    }

    /**
     * Bind the rows and make them sortable
     */
    private async init(element: HTMLFieldSetElement) {
        $$(".form-input-array-row", element).forEach((element) => {
            this.bindItemEvents(element);
        });

        $(`label[for="${element.id}"]`)?.addEventListener("click", () => $(".form-input", element)?.focus());

        if (this.isAssociative) {
            this.form.element.addEventListener("submit", () => this.handleSubmit());
        }

        const { default: Sortable } = await import("sortablejs");

        Sortable.create(element, {
            handle: ".sortable-handle",
            forceFallback: true,
            invertSwap: true,
            swapThreshold: 0.75,
            animation: 150,
        });
    }

    /**
     * Get the value of the array as JSON
     */
    get value(): string {
        const values: { [key: string]: string } = {};

        let i = 0;

        $$(".form-input-array-row", this.element).forEach((item) => {
            const { key: keyInput, value: valueInput } = this.getItemInputs(item);

            const key = keyInput?.value.trim() ?? "";
            const value = valueInput.value.trim();

            if (this.isAssociative && key) {
                values[key] = value;
            } else if (value) {
                values[i++] = value;
            }
        });

        return JSON.stringify(values);
    }

    /**
     * Rename the inputs without a key before the form is submitted, so that they are not sent
     */
    private handleSubmit() {
        const emptyKeyName = `${this.name}[]`;
        for (const input of this.form.formInputs) {
            if (input.element.name === emptyKeyName) {
                // Prevent items with empty keys from being submitted
                input.name = "";
            }
        }
    }

    /**
     * Get the key and value inputs of a row
     */
    private getItemInputs(item: HTMLElement): { key?: HTMLInputLike; value: HTMLInputLike } {
        const cached = this.itemCache.get(item);
        if (cached) {
            return cached;
        }

        const key = this.isAssociative ? ($(".form-input-array-key", item) as HTMLInputLike) : undefined;
        const value = $(`.form-input-array-value [name^="${this.name}"]`, item) as HTMLInputLike;
        const entry = { key, value };
        this.itemCache.set(item, entry);
        return entry;
    }

    /**
     * Add an empty row after a row
     */
    private addItem(item: HTMLElement) {
        const { value: valueInput } = this.getItemInputs(item);
        const newItem = item.cloneNode(true) as HTMLElement;

        $(".form-input-array-value", newItem)?.replaceChildren();

        this.form.duplicateInput(valueInput, $(".form-input-array-value", newItem) as HTMLElement);

        const parent = item.parentNode as ParentNode;

        this.clearItem(newItem);
        this.bindItemEvents(newItem);

        // Update name attribute based on cloned key input
        if (this.isAssociative) {
            const { key: newKeyInput, value: newValueInput } = this.getItemInputs(newItem);
            this.onKeyInput(newKeyInput as HTMLInputLike, newValueInput);
        }

        if (item.nextSibling) {
            parent.insertBefore(newItem, item.nextSibling);
        } else {
            parent.appendChild(newItem);
        }

        // Focus the new input item
        this.focusItem(newItem);
    }

    /**
     * Remove a row, or clear it if it is the only one
     */
    private removeItem(item: HTMLElement) {
        const parent = item.parentNode as ParentNode;
        if ($$(".form-input-array-row", parent).length > 1) {
            parent.removeChild(item);
            this.itemCache.delete(item);
        } else {
            this.clearItem(item);
        }
    }

    /**
     * Clear a row
     */
    private clearItem(item: HTMLElement) {
        const { key: keyInput, value: valueInput } = this.getItemInputs(item);

        this.updateFormInputValue(valueInput, "");

        if (this.isAssociative && keyInput) {
            keyInput.value = "";
            keyInput.required = false;
            this.onKeyInput(keyInput, valueInput);
        }
    }

    /**
     * Bind the buttons of a row and keep the name of its value input in sync with the key
     */
    private bindItemEvents(item: HTMLElement) {
        const inputAdd = $(".form-input-array-add", item) as HTMLButtonElement;
        const inputRemove = $(".form-input-array-remove", item) as HTMLButtonElement;

        inputAdd.addEventListener("click", () => this.addItem(item));
        inputRemove.addEventListener("click", () => this.removeItem(item));

        if (this.isAssociative) {
            const { key: keyInput, value: valueInput } = this.getItemInputs(item) as { key: HTMLInputLike; value: HTMLInputLike };
            keyInput.addEventListener("input", () => this.onKeyInput(keyInput, valueInput));
            valueInput.addEventListener("input", () => this.onValueInput(keyInput, valueInput));
        }
    }

    /**
     * Focus the key input of a row, or the value input if the array is not associative
     */
    private focusItem(item: HTMLElement) {
        const { key: keyInput, value: valueInput } = this.getItemInputs(item);
        const inputToFocus = this.isAssociative && keyInput ? keyInput : valueInput;
        inputToFocus.focus();
    }

    /**
     * Name the value input of a row after its key
     */
    private onKeyInput(keyInput: HTMLInputLike, valueInput: HTMLInputLike) {
        const newName = `${this.name}[${keyInput.value.trim()}]`;

        if (valueInput.name === newName) {
            return;
        }

        this.updateFormInputName(valueInput, newName);
        this.onValueInput(keyInput, valueInput);
    }

    /**
     * Require the key of a row if it has a value
     */
    private onValueInput(keyInput: HTMLInputLike, valueInput: HTMLInputLike) {
        keyInput.required = valueInput.value.trim() !== "";
    }

    /**
     * Change the name of the form input wrapping an element
     */
    private updateFormInputName(element: HTMLInputLike, newName: string) {
        for (const input of this.form.formInputs) {
            if (input.element === element) {
                input.name = newName;
                break;
            }
        }
    }

    /**
     * Change the value of the form input wrapping an element
     */
    private updateFormInputValue(element: HTMLInputLike, newValue: string) {
        for (const input of this.form.formInputs) {
            if (input.element === element) {
                input.value = newValue;
                break;
            }
        }
    }
}
