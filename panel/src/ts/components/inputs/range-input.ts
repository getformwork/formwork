import { $ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Read a numeric attribute of a range, which may be decimal, or its default
 */
const toNumber = (value: string, defaultValue: number) => {
    const number = parseFloat(value);
    return Number.isNaN(number) ? defaultValue : number;
};

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
                case "": {
                    const { min, max } = this.getBounds();
                    // The steps may be decimal, the count is rounded against floating point errors
                    addTicks(Math.round((max - min) / (toNumber(this.element.step, 1) || 1)) + 1);
                    break;
                }

                default:
                    addTicks(parseInt(count, 10) + 1);
                    break;
            }
        }
    }

    /**
     * Get the minimum and the maximum of the range, with the defaults of the browsers
     */
    private getBounds() {
        return {
            min: toNumber(this.element.min, 0),
            max: toNumber(this.element.max, 100),
        };
    }

    /**
     * Show the value in the output and the progress from the minimum
     */
    private updateValueLabel() {
        const { min, max } = this.getBounds();
        const progress = max > min ? ((toNumber(this.element.value, min) - min) / (max - min)) * 100 : 0;

        this.element.style.setProperty("--progress", `${Math.round(progress)}%`);

        const outputElement = $(`output[for="${this.element.id}"]`);
        if (outputElement) {
            outputElement.textContent = this.element.value;
        }
    }
}
