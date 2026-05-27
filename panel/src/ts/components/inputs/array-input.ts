import { $, $$ } from "../../utils/selectors";
import type { Form, HTMLInputLike } from "../form";

export class ArrayInput {
    readonly element: HTMLFieldSetElement;
    readonly form: Form;
    readonly isAssociative: boolean;

    private itemCache = new WeakMap<HTMLElement, { key?: HTMLInputLike; value: HTMLInputLike }>();

    constructor(element: HTMLFieldSetElement, form: Form) {
        this.element = element;

        this.form = form;

        this.isAssociative = element.classList.contains("form-input-array-associative");

        this.init(element);
    }

    private async init(element: HTMLFieldSetElement) {
        $$(".form-input-array-row", element).forEach((element) => this.bindItemEvents(element));

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

    get name(): string {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

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

    private handleSubmit() {
        const emptyKeyName = `${this.name}[]`;
        for (const input of this.form.formInputs) {
            if (input.element.name === emptyKeyName) {
                // Prevent items with empty keys from being submitted
                input.name = "";
            }
        }
    }

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

    private addItem(item: HTMLElement) {
        const { value: valueInput } = this.getItemInputs(item);
        const newItem = item.cloneNode(true) as HTMLElement;

        $(".form-input-array-value", newItem)?.replaceChildren();

        this.form.duplicateInput(valueInput, $(".form-input-array-value", newItem) as HTMLElement);

        const parent = item.parentNode;

        this.clearItem(newItem);
        this.bindItemEvents(newItem);

        // Update name attribute based on cloned key input
        if (this.isAssociative) {
            const { key: newKeyInput, value: newValueInput } = this.getItemInputs(newItem);
            this.onKeyInput(newKeyInput as HTMLInputLike, newValueInput);
        }

        if (item.nextSibling) {
            parent?.insertBefore(newItem, item.nextSibling);
        } else {
            parent?.appendChild(newItem);
        }

        // Focus the new input item
        this.focusItem(newItem);
    }

    private removeItem(item: HTMLElement) {
        const parent = item.parentNode;
        if (parent && $$(".form-input-array-row", parent).length > 1) {
            item.remove();
            this.itemCache.delete(item);
        } else {
            this.clearItem(item);
        }
    }

    private clearItem(item: HTMLElement) {
        const { key: keyInput, value: valueInput } = this.getItemInputs(item);

        this.updateFormInputValue(valueInput, "");

        if (this.isAssociative && keyInput) {
            keyInput.value = "";
            keyInput.required = false;
            this.onKeyInput(keyInput, valueInput);
        }
    }

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

    private focusItem(item: HTMLElement) {
        const { key: keyInput, value: valueInput } = this.getItemInputs(item);
        const inputToFocus = this.isAssociative && keyInput ? keyInput : valueInput;
        inputToFocus.focus();
    }

    private onKeyInput(keyInput: HTMLInputLike, valueInput: HTMLInputLike) {
        const newName = `${this.name}[${keyInput.value.trim()}]`;

        if (valueInput.name === newName) {
            return;
        }

        this.updateFormInputName(valueInput, newName);
        this.onValueInput(keyInput, valueInput);
    }

    private onValueInput(keyInput: HTMLInputLike, valueInput: HTMLInputLike) {
        keyInput.required = valueInput.value.trim() !== "";
    }

    private updateFormInputName(element: HTMLInputLike, newName: string) {
        for (const input of this.form.formInputs) {
            if (input.element === element) {
                input.name = newName;
                break;
            }
        }
    }

    private updateFormInputValue(element: HTMLInputLike, newValue: string) {
        for (const input of this.form.formInputs) {
            if (input.element === element) {
                input.value = newValue;
                break;
            }
        }
    }
}
