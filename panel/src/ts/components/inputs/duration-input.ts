import { effect, type Signal, signal } from "@preact/signals-core";
import { app } from "../../services/app";
import { intervalsToSeconds, secondsToIntervals, TIME_INTERVALS, type TimeInterval, type TimeIntervals } from "../../utils/duration";
import { $ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Represents the singular and plural name of a time interval
 */
type TimeIntervalLabel = [singular: string, plural: string];

/**
 * Remove the leading zeros of a number and the digits that make it larger than the largest safe
 * integer
 */
function cleanNumber(text: string) {
    let cleaned = text;

    while (cleaned.charAt(0) === "0" && cleaned.length > 1 && !cleaned.charAt(1).match(/[.,]/)) {
        cleaned = cleaned.slice(1);
    }

    while (parseInt(cleaned, 10) > Number.MAX_SAFE_INTEGER) {
        cleaned = cleaned.slice(0, -1);
    }

    return cleaned;
}

/**
 * Holds the options of a duration input
 */
export interface DurationInputOptions {
    unit: TimeInterval;
    intervals: TimeInterval[];
    labels: Record<TimeInterval, TimeIntervalLabel>;
}

/**
 * Provides a duration input with a field for each time interval
 *
 * The native input is hidden and holds the total, constrained by its `step`, `min` and `max`
 */
export class DurationInput extends Input<HTMLInputElement> {
    /**
     * Options of the input
     */
    readonly options: DurationInputOptions;

    /**
     * Element wrapping the fields
     */
    private field!: HTMLElement;

    /**
     * Field of each interval
     */
    private innerInputs: Partial<Record<TimeInterval, HTMLInputElement>> = {};

    // Text of the inner inputs, the width of the inputs and their labels are derived from it
    /**
     * Text of each field, used for its width and label
     */
    private texts: Partial<Record<TimeInterval, Signal<string>>> = {};

    /**
     * Create the duration input
     */
    constructor(element: HTMLInputElement, options: Partial<DurationInputOptions> = {}) {
        const defaults: DurationInputOptions = {
            unit: "seconds",
            intervals: ["years", "months", "weeks", "days", "hours", "minutes", "seconds"],
            labels: {
                years: app.translation.getStrings("date.duration.years", ["year", "years"]) as TimeIntervalLabel,
                months: app.translation.getStrings("date.duration.months", ["month", "months"]) as TimeIntervalLabel,
                weeks: app.translation.getStrings("date.duration.weeks", ["week", "weeks"]) as TimeIntervalLabel,
                days: app.translation.getStrings("date.duration.days", ["day", "days"]) as TimeIntervalLabel,
                hours: app.translation.getStrings("date.duration.hours", ["hour", "hours"]) as TimeIntervalLabel,
                minutes: app.translation.getStrings("date.duration.minutes", ["minute", "minutes"]) as TimeIntervalLabel,
                seconds: app.translation.getStrings("date.duration.seconds", ["second", "seconds"]) as TimeIntervalLabel,
            },
        };

        super(element);

        this.options = { ...defaults, ...options };

        this.createField();
    }

    // The getter is defined again because a class defining only the setter would lose it
    get value() {
        return this.element.value;
    }

    /**
     * Set the total and show it in the fields
     */
    set value(value: string) {
        this.element.value = value;
        this.updateInnerInputs();
    }

    /**
     * Update the native input from the fields
     */
    private updateHiddenInput() {
        const intervals: TimeIntervals = {};
        (Object.entries(this.innerInputs) as [TimeInterval, HTMLInputElement][]).forEach(([i, input]) => {
            intervals[i] = parseInt(input.value, 10);
        });
        let seconds = intervalsToSeconds(intervals);
        if (this.element.step) {
            const step = parseInt(this.element.step, 10) * TIME_INTERVALS[this.options.unit];
            seconds = Math.floor(seconds / step) * step;
        }
        if (this.element.min) {
            seconds = Math.max(seconds, parseInt(this.element.min, 10));
        }
        if (this.element.max) {
            seconds = Math.min(seconds, parseInt(this.element.max, 10));
        }
        this.element.value = `${Math.round(seconds / TIME_INTERVALS[this.options.unit])}`;
        this.dispatchChange();
    }

    /**
     * Update the fields from the native input
     */
    private updateInnerInputs() {
        const intervals = secondsToIntervals(parseInt(this.element.value, 10) * TIME_INTERVALS[this.options.unit], this.options.intervals);
        (Object.entries(this.innerInputs) as [TimeInterval, HTMLInputElement][]).forEach(([i, input]) => {
            input.value = `${intervals[i] || 0}`;
        });
        this.updateTexts();
    }

    /**
     * Update the texts from the fields
     */
    private updateTexts() {
        (Object.entries(this.innerInputs) as [TimeInterval, HTMLInputElement][]).forEach(([i, input]) => {
            (this.texts[i] as Signal<string>).value = input.value;
        });
    }

    /**
     * Create a field and a label for each interval
     *
     * @param intervals Initial value of each field
     * @param steps     Step of each field
     */
    private createInnerInputs(intervals: TimeIntervals, steps: TimeIntervals) {
        this.field = document.createElement("div");
        this.field.className = "form-input-duration-wrap";

        let lastInput: HTMLInputElement | undefined;

        for (const name of this.options.intervals) {
            const wrap = this.createInterval(name, intervals[name] || 0, steps[name] as number);

            lastInput = $("input", wrap) as HTMLInputElement;

            this.field.appendChild(wrap);
        }

        this.field.addEventListener("mousedown", (event: MouseEvent) => {
            if (event.target === this.field) {
                lastInput?.focus();
                event.preventDefault();
            }
        });

        return this.field;
    }

    /**
     * Create the field and the label of an interval
     *
     * @param name  Name of the interval
     * @param value Initial value of the field
     * @param step  Step of the field
     */
    private createInterval(name: TimeInterval, value: number, step: number) {
        const wrap = document.createElement("span");
        wrap.className = `duration-${name}`;

        const input = document.createElement("input");
        input.id = `${this.element.id}.${name}`;
        input.className = "form-input";
        input.type = "number";
        input.value = `${value}`;

        if (step > 1) {
            input.step = `${step}`;
        }

        if (this.element.disabled) {
            input.disabled = true;
        }

        this.innerInputs[name] = input;

        // The width of the field and its label depend on the text
        const text = signal(input.value);
        this.texts[name] = text;

        input.addEventListener("input", () => {
            const cleaned = cleanNumber(input.value);

            if (cleaned !== input.value) {
                input.value = cleaned;
            }

            text.value = input.value;
            this.updateHiddenInput();
        });

        input.addEventListener("focus", () => this.field.classList.add("focused"));

        input.addEventListener("blur", () => {
            this.updateHiddenInput();
            this.updateInnerInputs();
            this.field.classList.remove("focused");
        });

        wrap.addEventListener("mousedown", (event: MouseEvent) => {
            if (event.target !== input) {
                input.focus();
                event.preventDefault();
            }
        });

        const label = document.createElement("label");
        label.className = "form-label";
        label.htmlFor = input.id;

        effect(() => {
            input.style.width = `${Math.max(3, text.value.length + 2)}ch`;
            label.textContent = this.options.labels[name][parseInt(text.value, 10) === 1 ? 0 : 1];
        });

        wrap.appendChild(input);
        wrap.appendChild(label);

        return wrap;
    }

    /**
     * Replace the native input with the fields
     */
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

        const valueSeconds = parseInt(this.element.value, 10) * TIME_INTERVALS[this.options.unit];
        const stepSeconds = parseInt(this.element.step, 10) * TIME_INTERVALS[this.options.unit];
        const field = this.createInnerInputs(secondsToIntervals(valueSeconds || 0, this.options.intervals), secondsToIntervals(stepSeconds || 1, this.options.intervals));
        (this.element.parentNode as ParentNode).replaceChild(field, this.element);
        field.appendChild(this.element);
        $(`label[for="${this.element.id}"]`)?.addEventListener("click", () => $(".form-input", field)?.focus());
    }
}
