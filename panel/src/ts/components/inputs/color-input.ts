import { $ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Provides a color input that shows its value in the linked `output` element
 */
export class ColorInput extends Input<HTMLInputElement> {
    /**
     * Element showing the value
     */
    private readonly output: HTMLElement | null;

    constructor(element: HTMLInputElement) {
        super(element);

        this.output = $(`output[for="${this.element.id}"]`);

        this.initInput();
    }

    /**
     * Get the value of the color
     */
    get value(): string {
        return this.element.value;
    }

    /**
     * Set the value of the color, updating the output
     */
    set value(value: string) {
        this.element.value = value;
        this.updateValueLabel();
    }

    /**
     * Show the value in the output and open the picker when the output is clicked
     */
    private initInput() {
        if (this.output) {
            this.element.addEventListener("change", () => this.updateValueLabel());
            this.element.addEventListener("input", () => this.updateValueLabel());

            this.updateValueLabel();

            this.output.addEventListener("click", () => {
                this.element.focus();
                this.element.click();
            });
        }
    }

    /**
     * Show the value in the output
     */
    private updateValueLabel() {
        if (this.output) {
            this.output.textContent = this.element.value;
        }
    }
}
