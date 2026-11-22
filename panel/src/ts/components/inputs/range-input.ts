import { $ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Provides a range input that shows its progress, its value in the linked `output` element and,
 * optionally, ticks
 */
export class RangeInput extends Input<HTMLInputElement> {
    constructor(element: HTMLInputElement) {
        super(element);

        this.initInput();
    }

    /**
     * Get the value of the range
     */
    get value(): string {
        return this.element.value;
    }

    /**
     * Set the value of the range, updating progress and output
     */
    set value(value: string) {
        this.element.value = value;
        this.updateValueLabel();
    }

    /**
     * Update progress and output when the value changes and add the ticks
     */
    private initInput() {
        const addTicks = (count: number) => {
            const ticks = document.createElement("div");
            ticks.className = "form-input-range-ticks";
            ticks.dataset.for = this.element.id;
            (this.element.parentElement as ParentNode).insertBefore(ticks, this.element.nextSibling);

            for (let i = 0; i < count; i++) {
                const tick = document.createElement("div");
                tick.className = "tick";
                ticks.appendChild(tick);
            }
        };

        this.element.addEventListener("change", () => this.updateValueLabel());
        this.element.addEventListener("input", () => this.updateValueLabel());

        this.updateValueLabel();

        if ("ticks" in this.element.dataset) {
            const count = this.element.dataset.ticks as string;

            switch (count) {
                case "0":
                    break;

                case "true":
                case "":
                    addTicks((parseInt(this.element.max, 10) - parseInt(this.element.min, 10)) / (parseInt(this.element.step, 10) || 1) + 1);
                    break;

                default:
                    addTicks(parseInt(count, 10) + 1);
                    break;
            }
        }
    }

    /**
     * Show the value in the output and the progress
     */
    private updateValueLabel() {
        this.element.style.setProperty("--progress", `${Math.round((parseInt(this.element.value, 10) / (parseInt(this.element.max, 10) - parseInt(this.element.min, 10))) * 100)}%`);

        const outputElement = $(`output[for="${this.element.id}"]`);
        if (outputElement) {
            outputElement.textContent = this.element.value;
        }
    }
}
