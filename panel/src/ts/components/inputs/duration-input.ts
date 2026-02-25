import { $ } from "../../utils/selectors";
import { getSafeInteger } from "../../utils/numbers";

const TIME_INTERVALS = {
    years: 60 * 60 * 24 * 365,
    months: 60 * 60 * 24 * 30,
    weeks: 60 * 60 * 24 * 7,
    days: 60 * 60 * 24,
    hours: 60 * 60,
    minutes: 60,
    seconds: 1,
};

type TimeInterval = keyof typeof TIME_INTERVALS;
type TimeIntervalLabel = [singular: string, plural: string];

interface DurationInputOptions {
    unit: TimeInterval;
    intervals: TimeInterval[];
    labels: Record<TimeInterval, TimeIntervalLabel>;
}

export class DurationInput {
    readonly element: HTMLInputElement;

    readonly options: DurationInputOptions;

    private field: HTMLElement;

    private innerInputs: Partial<Record<TimeInterval, HTMLInputElement>> = {};
    private labels: Partial<Record<TimeInterval, HTMLLabelElement>> = {};

    constructor(element: HTMLInputElement, options: Partial<DurationInputOptions>) {
        const defaults: DurationInputOptions = {
            unit: "seconds",
            intervals: ["years", "months", "weeks", "days", "hours", "minutes", "seconds"],
            labels: {
                years: ["year", "years"],
                months: ["month", "months"],
                weeks: ["week", "weeks"],
                days: ["day", "days"],
                hours: ["hour", "hours"],
                minutes: ["minute", "minutes"],
                seconds: ["second", "seconds"],
            },
        };

        this.element = element;

        this.options = { ...defaults, ...options };

        this.createField();
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
        this.updateInnerInputs();
        this.updateInnerInputsLength();
        this.updateLabels();
    }

    private secondsToIntervals(seconds: number, intervalNames: TimeInterval[] = this.options.intervals) {
        const intervals: Partial<Record<TimeInterval, number>> = {};
        seconds = getSafeInteger(seconds);
        Object.keys(TIME_INTERVALS).forEach((t: TimeInterval) => {
            if (intervalNames.includes(t)) {
                intervals[t] = Math.floor(seconds / TIME_INTERVALS[t]);
                seconds -= intervals[t] * TIME_INTERVALS[t];
            }
        });
        return intervals;
    }

    private intervalsToSeconds(intervals: Partial<Record<TimeInterval, number>>) {
        let seconds = 0;
        Object.entries(intervals).forEach(([interval, value]: [TimeInterval, number]) => {
            seconds += value * TIME_INTERVALS[interval];
        });
        return getSafeInteger(seconds);
    }

    private updateHiddenInput() {
        const intervals: Partial<Record<TimeInterval, number>> = {};
        Object.entries(this.innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
            intervals[i] = parseInt(input.value);
        });
        let seconds = this.intervalsToSeconds(intervals);
        if (this.element.step) {
            const step = parseInt(this.element.step) * TIME_INTERVALS[this.options.unit];
            seconds = Math.floor(seconds / step) * step;
        }
        if (this.element.min) {
            seconds = Math.max(seconds, parseInt(this.element.min));
        }
        if (this.element.max) {
            seconds = Math.min(seconds, parseInt(this.element.max));
        }
        this.element.value = `${Math.round(seconds / TIME_INTERVALS[this.options.unit])}`;
        this.element.dispatchEvent(new Event("input", { bubbles: true }));
        this.element.dispatchEvent(new Event("change", { bubbles: true }));
    }

    private updateInnerInputs() {
        const intervals = this.secondsToIntervals(parseInt(this.element.value) * TIME_INTERVALS[this.options.unit]);
        Object.entries(this.innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
            input.value = `${intervals[i] || 0}`;
        });
    }

    private updateInnerInputsLength() {
        Object.values(this.innerInputs).forEach((input) => {
            input.style.width = `${Math.max(3, input.value.length + 2)}ch`;
        });
    }

    private updateLabels() {
        Object.entries(this.innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
            (this.labels[i] as HTMLLabelElement).innerText = this.options.labels[i][parseInt(input.value) === 1 ? 0 : 1];
        });
    }

    private createInnerInputs(intervals: Partial<Record<TimeInterval, number>>, steps: Partial<Record<TimeInterval, number>>) {
        this.field = document.createElement("div");
        this.field.className = "form-input-duration-wrap";

        let innerInput: HTMLInputElement;

        for (const name of this.options.intervals) {
            innerInput = document.createElement("input");
            innerInput.id = `${this.element.id}.${name}`;
            innerInput.className = "form-input";

            const wrap = document.createElement("span");
            wrap.className = `duration-${name}`;
            innerInput.type = "number";
            innerInput.value = `${intervals[name] || 0}`;
            innerInput.style.width = `${Math.max(3, innerInput.value.length + 2)}ch`;
            if ((steps[name] as number) > 1) {
                innerInput.step = `${steps[name]}`;
            }
            if (this.element.disabled) {
                innerInput.disabled = true;
            }
            this.innerInputs[name] = innerInput;
            innerInput.addEventListener("input", () => {
                while (innerInput.value.charAt(0) === "0" && innerInput.value.length > 1 && !innerInput.value.charAt(1).match(/[.,]/)) {
                    innerInput.value = innerInput.value.slice(1);
                }
                while (parseInt(innerInput.value) > Number.MAX_SAFE_INTEGER) {
                    innerInput.value = innerInput.value.slice(0, -1);
                }
                this.updateInnerInputsLength();
                this.updateHiddenInput();
                this.updateLabels();
            });
            innerInput.addEventListener("blur", () => {
                this.updateHiddenInput();
                this.updateInnerInputs();
                this.updateInnerInputsLength();
                this.updateLabels();
            });

            innerInput.addEventListener("focus", () => this.field.classList.add("focused"));

            innerInput.addEventListener("blur", () => this.field.classList.remove("focused"));

            wrap.addEventListener("mousedown", function (event: MouseEvent) {
                const input = $("input", this);
                if (input && event.target !== input) {
                    input.focus();
                    event.preventDefault();
                }
            });

            const label = document.createElement("label");
            const labelText = this.options.labels[name][parseInt(innerInput.value) === 1 ? 0 : 1];

            label.className = "form-label";
            label.innerText = labelText;
            label.htmlFor = innerInput.id;

            this.labels[name] = label;

            wrap.appendChild(innerInput);
            wrap.appendChild(label);
            this.field.appendChild(wrap);
        }

        this.field.addEventListener("mousedown", function (event: MouseEvent) {
            if (event.target === this) {
                innerInput.focus();
                event.preventDefault();
            }
        });

        return this.field;
    }

    private createField() {
        this.element.className = "";
        this.element.readOnly = true;
        this.element.hidden = true;
        this.element.tabIndex = -1;
        this.element.ariaHidden = "true";

        if ("intervals" in this.element.dataset) {
            this.options.intervals = (this.element.dataset.intervals as string).split(", ") as TimeInterval[];
        }

        if ("unit" in this.element.dataset) {
            this.options.unit = this.element.dataset.unit as TimeInterval;
        }

        const valueSeconds = parseInt(this.element.value) * TIME_INTERVALS[this.options.unit];
        const stepSeconds = parseInt(this.element.step) * TIME_INTERVALS[this.options.unit];
        const field = this.createInnerInputs(this.secondsToIntervals(valueSeconds || 0), this.secondsToIntervals(stepSeconds || 1));
        (this.element.parentNode as ParentNode).replaceChild(field, this.element);
        field.appendChild(this.element);
        $(`label[for="${this.element.id}"]`)?.addEventListener("click", () => $(".form-input", field)?.focus());
    }
}
