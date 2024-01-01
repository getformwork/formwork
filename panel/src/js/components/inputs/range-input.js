import { $ } from "../../utils/selectors";

export class RangeInput {
    constructor(input) {
        input.addEventListener("change", updateValueLabel);
        input.addEventListener("input", updateValueLabel);

        updateValueLabel.call(input);

        if ("ticks" in input.dataset) {
            const count = input.dataset.ticks;

            switch (count) {
                case 0:
                    break;

                case "true":
                case "":
                    addTicks((input.max - input.min) / (input.step || 1) + 1);
                    break;

                default:
                    addTicks(parseInt(count) + 1);
                    break;
            }
        }

        function updateValueLabel() {
            this.style.setProperty("--progress", `${Math.round((this.value / (this.max - this.min)) * 100)}%`);
            $(`output[for="${this.id}"]`).innerHTML = this.value;
        }

        function addTicks(count) {
            const ticks = document.createElement("div");
            ticks.className = "input-range-ticks";
            ticks.dataset.for = input.id;
            input.parentElement.insertBefore(ticks, input.nextSibling);

            for (let i = 0; i < count; i++) {
                const tick = document.createElement("div");
                tick.className = "tick";
                ticks.appendChild(tick);
            }
        }
    }
}