import Utils from "../utils";

export default function DurationInput(input, options) {
    const defaults = {
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

    const TIME_INTERVALS = {
        years: 60 * 60 * 24 * 365,
        months: 60 * 60 * 24 * 30,
        weeks: 60 * 60 * 24 * 7,
        days: 60 * 60 * 24,
        hours: 60 * 60,
        minutes: 60,
        seconds: 1,
    };

    let field, hiddenInput;

    const innerInputs = {};

    const labels = {};

    options = Utils.extendObject({}, defaults, options);

    createField();

    function secondsToIntervals(seconds, intervalNames = options.intervals) {
        const intervals = {};
        seconds = Utils.toSafeInteger(seconds);
        for (const t in TIME_INTERVALS) {
            if (intervalNames.includes(t)) {
                intervals[t] = Math.floor(seconds / TIME_INTERVALS[t]);
                seconds -= intervals[t] * TIME_INTERVALS[t];
            }
        }
        return intervals;
    }

    function intervalsToSeconds(intervals) {
        let seconds = 0;
        for (const interval in intervals) {
            seconds += intervals[interval] * TIME_INTERVALS[interval];
        }
        return Utils.toSafeInteger(seconds);
    }

    function updateHiddenInput() {
        const intervals = {};
        let seconds = 0;
        let step = 0;
        for (const i in innerInputs) {
            intervals[i] = innerInputs[i].value;
        }
        seconds = intervalsToSeconds(intervals);
        if (hiddenInput.step) {
            step = hiddenInput.step * TIME_INTERVALS[options.unit];
            seconds = Math.floor(seconds / step) * step;
        }
        if (hiddenInput.min) {
            seconds = Math.max(seconds, hiddenInput.min);
        }
        if (hiddenInput.max) {
            seconds = Math.min(seconds, hiddenInput.max);
        }
        hiddenInput.value = Math.round(seconds / TIME_INTERVALS[options.unit]);
    }

    function updateInnerInputs() {
        const intervals = secondsToIntervals(hiddenInput.value * TIME_INTERVALS[options.unit]);
        for (const i in innerInputs) {
            innerInputs[i].value = intervals[i];
        }
    }

    function updateInnerInputsLength() {
        for (const i in innerInputs) {
            innerInputs[i].style.width = `${Math.max(3, innerInputs[i].value.length + 2)}ch`;
        }
    }

    function updateLabels() {
        for (const i in innerInputs) {
            labels[i].innerHTML = options.labels[i][parseInt(innerInputs[i].value) === 1 ? 0 : 1];
        }
    }

    function createInnerInputs(intervals, steps) {
        field = document.createElement("div");
        field.className = "input-duration";

        let innerInput;

        for (const name of options.intervals) {
            innerInput = document.createElement("input");
            const wrap = document.createElement("span");
            wrap.className = `duration-${name}`;
            innerInput.type = "number";
            innerInput.value = intervals[name] || 0;
            innerInput.style.width = `${Math.max(3, innerInput.value.length + 2)}ch`;
            if (steps[name] > 1) {
                innerInput.step = steps[name];
            }
            if (input.disabled) {
                innerInput.disabled = true;
            }
            innerInputs[name] = innerInput;
            innerInput.addEventListener("input", function () {
                while (this.value.charAt(0) === "0" && this.value.length > 1 && !this.value.charAt(1).match(/[.,]/)) {
                    this.value = this.value.slice(1);
                }
                while (this.value > Utils.getMaxSafeInteger()) {
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
            innerInput.addEventListener("focus", () => {
                field.classList.add("focused");
            });
            innerInput.addEventListener("blur", () => {
                field.classList.remove("focused");
            });
            wrap.addEventListener("mousedown", function (event) {
                const input = $("input", this);
                if (input && event.target !== input) {
                    input.focus();
                    event.preventDefault();
                }
            });
            const label = document.createElement("label");
            label.innerHTML = options.labels[name][parseInt(innerInput.value) === 1 ? 0 : 1];
            labels[name] = label;
            wrap.appendChild(innerInput);
            wrap.appendChild(label);
            field.appendChild(wrap);
        }

        field.addEventListener("mousedown", function (event) {
            if (event.target === this) {
                innerInput.focus();
                event.preventDefault();
            }
        });

        return field;
    }

    function createField() {
        hiddenInput = document.createElement("input");
        hiddenInput.className = "input-duration-hidden";
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
            options.intervals = input.dataset.intervals.split(", ");
        }
        if ("unit" in input.dataset) {
            options.unit = input.dataset.unit;
        }
        const valueSeconds = input.value * TIME_INTERVALS[options.unit];
        const stepSeconds = input.step * TIME_INTERVALS[options.unit];
        const field = createInnerInputs(secondsToIntervals(valueSeconds || 0), secondsToIntervals(stepSeconds || 1));
        input.parentNode.replaceChild(field, input);
        field.appendChild(hiddenInput);
    }
}
