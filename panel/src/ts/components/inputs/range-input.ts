import { $ } from "../../utils/selectors";

export class RangeInput {
    readonly element: HTMLInputElement;

    constructor(element: HTMLInputElement) {
        this.element = element;

        this.initInput();
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value() {
        return this.element.value;
    }

    set value(value: string) {
        this.element.value = value;
    }

    private initInput() {
        const updateValueLabel = (element: HTMLInputElement) => {
            element.style.setProperty("--progress", `${Math.round((parseInt(element.value) / (parseInt(element.max) - parseInt(element.min))) * 100)}%`);
            const outputElement = $(`output[for="${element.id}"]`);
            if (outputElement) {
                outputElement.innerText = element.value;
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
            const count = this.element.dataset.ticks ?? "";

            switch (count) {
                case "0":
                    break;

                case "true":
                case "":
                    addTicks((parseInt(this.element.max) - parseInt(this.element.min)) / (parseInt(this.element.step) || 1) + 1);
                    break;

                default:
                    addTicks(parseInt(count) + 1);
                    break;
            }
        }
    }
}
