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
     * Update progress and output when the value changes and add the ticks
     */
    private initInput() {
        const updateValueLabel = (element: HTMLInputElement) => {
            element.style.setProperty("--progress", `${Math.round((parseInt(element.value, 10) / (parseInt(element.max, 10) - parseInt(element.min, 10))) * 100)}%`);
            const outputElement = $(`output[for="${element.id}"]`);
            if (outputElement) {
                outputElement.textContent = element.value;
            }
        };

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

        this.element.addEventListener("change", () => updateValueLabel(this.element));
        this.element.addEventListener("input", () => updateValueLabel(this.element));

        updateValueLabel(this.element);

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
}
