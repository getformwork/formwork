import { $ } from "../../utils/selectors";

function getSafeInteger(value: number) {
    const max = Number.MAX_SAFE_INTEGER;
    const min = -max;
    if (value > max) {
        return max;
    }
    if (value < min) {
        return min;
    }
    return value;
}

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
    constructor(input: HTMLInputElement, userOptions: Partial<DurationInputOptions>) {
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

        let field: HTMLElement, hiddenInput: HTMLInputElement;

        const innerInputs: Partial<Record<TimeInterval, HTMLInputElement>> = {};

        const labels: Partial<Record<TimeInterval, HTMLLabelElement>> = {};

        const options = Object.assign({}, defaults, userOptions);

        createField();

        function secondsToIntervals(seconds: number, intervalNames: TimeInterval[] = options.intervals) {
            const intervals: Partial<Record<TimeInterval, number>> = {};
            seconds = getSafeInteger(seconds);
            Object.keys(TIME_INTERVALS).forEach((t: TimeInterval) => {
                if (intervalNames.includes(t)) {
                    intervals[t] = Math.floor(seconds / TIME_INTERVALS[t]);
                    seconds -= (intervals[t] as number) * TIME_INTERVALS[t];
                }
            });
            return intervals;
        }

        function intervalsToSeconds(intervals: Partial<Record<TimeInterval, number>>) {
            let seconds = 0;
            Object.entries(intervals).forEach(([interval, value]: [TimeInterval, number]) => {
                seconds += value * TIME_INTERVALS[interval];
            });
            return getSafeInteger(seconds);
        }

        function updateHiddenInput() {
            const intervals: Partial<Record<TimeInterval, number>> = {};
            let seconds = 0;
            let step = 0;
            Object.entries(innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
                intervals[i] = parseInt(input.value);
            });
            seconds = intervalsToSeconds(intervals);
            if (hiddenInput.step) {
                step = parseInt(hiddenInput.step) * TIME_INTERVALS[options.unit];
                seconds = Math.floor(seconds / step) * step;
            }
            if (hiddenInput.min) {
                seconds = Math.max(seconds, parseInt(hiddenInput.min));
            }
            if (hiddenInput.max) {
                seconds = Math.min(seconds, parseInt(hiddenInput.max));
            }
            hiddenInput.value = `${Math.round(seconds / TIME_INTERVALS[options.unit])}`;
            hiddenInput.dispatchEvent(new Event("input", { bubbles: true }));
            hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
        }

        function updateInnerInputs() {
            const intervals = secondsToIntervals(parseInt(hiddenInput.value) * TIME_INTERVALS[options.unit]);
            Object.entries(innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
                input.value = `${intervals[i] || 0}`;
            });
        }

        function updateInnerInputsLength() {
            Object.values(innerInputs).forEach((input) => {
                input.style.width = `${Math.max(3, input.value.length + 2)}ch`;
            });
        }

        function updateLabels() {
            Object.entries(innerInputs).forEach(([i, input]: [TimeInterval, HTMLInputElement]) => {
                (labels[i] as HTMLLabelElement).innerHTML = options.labels[i][parseInt(input.value) === 1 ? 0 : 1];
            });
        }

        function createInnerInputs(intervals: Partial<Record<TimeInterval, number>>, steps: Partial<Record<TimeInterval, number>>) {
            field = document.createElement("div");
            field.className = "form-input-duration";

            let innerInput: HTMLInputElement;

            for (const name of options.intervals) {
                innerInput = document.createElement("input");
                innerInput.className = "form-input";
                const wrap = document.createElement("span");
                wrap.className = `duration-${name}`;
                innerInput.type = "number";
                innerInput.value = `${intervals[name] || 0}`;
                innerInput.style.width = `${Math.max(3, innerInput.value.length + 2)}ch`;
                if ((steps[name] as number) > 1) {
                    innerInput.step = `${steps[name]}`;
                }
                if (input.disabled) {
                    innerInput.disabled = true;
                }
                innerInputs[name] = innerInput;
                innerInput.addEventListener("input", function () {
                    while (this.value.charAt(0) === "0" && this.value.length > 1 && !this.value.charAt(1).match(/[.,]/)) {
                        this.value = this.value.slice(1);
                    }
                    while (parseInt(this.value) > Number.MAX_SAFE_INTEGER) {
                        this.value = this.value.slice(0, -1);
                    }
                    updateInnerInputsLength();
                    updateHiddenInput();
                    updateLabels();
                });
                innerInput.addEventListener("blur", () => {
                    updateHiddenInput();
                    updateInnerInputs();
                    updateInnerInputsLength();
                    updateLabels();
                });

                innerInput.addEventListener("focus", () => field.classList.add("focused"));

                innerInput.addEventListener("blur", () => field.classList.remove("focused"));

                wrap.addEventListener("mousedown", function (event: MouseEvent) {
                    const input = $("input", this);
                    if (input && event.target !== input) {
                        input.focus();
                        event.preventDefault();
                    }
                });
                const label = document.createElement("label");
                label.className = "form-label";
                label.innerHTML = options.labels[name][parseInt(innerInput.value) === 1 ? 0 : 1];
                labels[name] = label;
                wrap.appendChild(innerInput);
                wrap.appendChild(label);
                field.appendChild(wrap);
            }

            field.addEventListener("mousedown", function (event: MouseEvent) {
                if (event.target === this) {
                    innerInput.focus();
                    event.preventDefault();
                }
            });

            return field;
        }

        function createField() {
            hiddenInput = document.createElement("input");
            hiddenInput.className = "form-input-duration-hidden";
            hiddenInput.name = input.name;
            hiddenInput.id = input.id;
            hiddenInput.type = "text";
            hiddenInput.value = input.value;
            hiddenInput.readOnly = true;
            hiddenInput.hidden = true;
            if (input.min) {
                hiddenInput.min = input.min;
            }
            if (input.max) {
                hiddenInput.max = input.max;
            }
            if (input.step) {
                hiddenInput.step = input.step;
            }
            if (input.required) {
                hiddenInput.required = true;
            }
            if (input.disabled) {
                hiddenInput.disabled = true;
            }
            if ("intervals" in input.dataset) {
                options.intervals = (input.dataset.intervals as string).split(", ") as TimeInterval[];
            }
            if ("unit" in input.dataset) {
                options.unit = input.dataset.unit as TimeInterval;
            }
            const valueSeconds = parseInt(input.value) * TIME_INTERVALS[options.unit];
            const stepSeconds = parseInt(input.step) * TIME_INTERVALS[options.unit];
            const field = createInnerInputs(secondsToIntervals(valueSeconds || 0), secondsToIntervals(stepSeconds || 1));
            (input.parentNode as ParentNode).replaceChild(field, input);
            field.appendChild(hiddenInput);
        }
    }
}
