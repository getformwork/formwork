import { $ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Provides a color input that shows its value in the linked `output` element
 */
export class ColorInput extends Input<HTMLInputElement> {
    constructor(element: HTMLInputElement) {
        super(element);

        this.initInput();
    }

    /**
     * Show the value in the output and open the picker when the output is clicked
     */
    private initInput() {
        const outputElement = $(`output[for="${this.element.id}"]`);

        if (outputElement) {
            const updateValueLabel = (element: HTMLInputElement) => {
                outputElement.textContent = element.value;
            };

            this.element.addEventListener("change", () => updateValueLabel(this.element));
            this.element.addEventListener("input", () => updateValueLabel(this.element));

            updateValueLabel(this.element);

            outputElement.addEventListener("click", () => {
                this.element.focus();
                this.element.click();
            });
        }
    }
}
