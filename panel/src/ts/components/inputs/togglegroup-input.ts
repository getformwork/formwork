import { $, $$ } from "../../utils/selectors";
import { InputBase } from "./input";

/**
 * Provides a group of radio buttons shown as a toggle
 */
export class TogglegroupInput extends InputBase<HTMLFieldSetElement> {
    /**
     * Create the toggle group
     */
    constructor(fieldset: HTMLFieldSetElement) {
        super(fieldset);

        $(`label[for="${this.name}"]`)?.addEventListener("click", () => {
            $("input:checked", this.element)?.focus();
        });
    }

    /**
     * Get the name of the group
     */
    get name() {
        return this.element.name;
    }

    /**
     * Set the name of the group and of its radio buttons
     */
    set name(value: string) {
        this.element.name = value;
        $$<HTMLInputElement>("input", this.element)?.forEach((input) => {
            input.name = value;
        });
    }

    /**
     * Get the value of the checked radio button
     */
    get value() {
        return ($(`input:checked`, this.element) as HTMLInputElement).value;
    }

    /**
     * Check the radio button with a value
     */
    set value(value: string) {
        const input = $(`input[value="${value}"]`, this.element) as HTMLInputElement;
        if (input) {
            input.checked = true;
        }
    }
}
