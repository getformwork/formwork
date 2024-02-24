import { $ } from "../../utils/selectors";

export class RangeInput {
    constructor(input: HTMLInputElement) {
        input.addEventListener("change", updateValueLabel);
        input.addEventListener("input", updateValueLabel);

        updateValueLabel.call(input);

        if ("ticks" in input.dataset) {
            const count = input.dataset.ticks as string;

            switch (count) {
                case "0":
                    break;

                case "true":
                case "":
                    addTicks((parseInt(input.max) - parseInt(input.min)) / (parseInt(input.step) || 1) + 1);
                    break;

                default:
                    addTicks(parseInt(count) + 1);
                    break;
            }
        }

        function updateValueLabel(this: HTMLInputElement) {
            this.style.setProperty("--progress", `${Math.round((parseInt(this.value) / (parseInt(this.max) - parseInt(this.min))) * 100)}%`);
            const outputElement = $(`output[for="${this.id}"]`);
            if (outputElement) {
                outputElement.innerHTML = this.value;
            }
        }

        function addTicks(count: number) {
            const ticks = document.createElement("div");
            ticks.className = "form-input-range-ticks";
            ticks.dataset.for = input.id;
            (input.parentElement as ParentNode).insertBefore(ticks, input.nextSibling);

            for (let i = 0; i < count; i++) {
                const tick = document.createElement("div");
                tick.className = "tick";
                ticks.appendChild(tick);
            }
        }
    }
}
