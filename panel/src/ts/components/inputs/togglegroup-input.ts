import { $, $$ } from "../../utils/selectors";

export class TogglegroupInput {
    readonly element: HTMLFieldSetElement;

    constructor(fieldset: HTMLFieldSetElement) {
        this.element = fieldset;

        $(`label[for="${this.name}"]`)?.addEventListener("click", () => {
            $("input:checked", this.element)?.focus();
        });
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
        $$<HTMLInputElement>("input", this.element)?.forEach((input) => {
            input.name = value;
        });
    }

    get value() {
        return ($(`input:checked`, this.element) as HTMLInputElement).value;
    }

    set value(value: string) {
        const input = $(`input[value="${value}"]`, this.element) as HTMLInputElement;
        if (input) {
            input.checked = true;
        }
    }
}
