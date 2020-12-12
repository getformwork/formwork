import Utils from './utils';

export default function DurationInput(input, options) {
    var defaults = {
        unit: 'seconds',
        display: ['years', 'months', 'weeks', 'days', 'hours', 'minutes', 'seconds'],
        labels: {
            years: ['year', 'years'],
            months: ['month', 'months'],
            weeks: ['week', 'weeks'],
            days: ['day', 'days'],
            hours: ['hour', 'hours'],
            minutes: ['minute', 'minutes'],
            seconds: ['second', 'seconds']
        }
    };

    var TIME_INTERVALS = {
        years: 60 * 60 * 24 * 365,
        months: 60 * 60 * 24 * 30,
        weeks: 60 * 60 * 24 * 7,
        days: 60 * 60 * 24,
        hours: 60 * 60,
        minutes: 60,
        seconds: 1
    };

    var field, hiddenInput;

    var innerInputs = {};

    var labels = {};

    options = Utils.extendObject({}, defaults, options);

    createField();

    function secondsToIntervals(seconds) {
        var intervals = {};
        var t;
        seconds = Utils.toSafeInteger(seconds);
        for (t in TIME_INTERVALS) {
            if (Object.prototype.hasOwnProperty.call(TIME_INTERVALS, t) && options.display.indexOf(t) !== -1) {
                intervals[t] = Math.floor(seconds / TIME_INTERVALS[t]);
                seconds -= intervals[t] * TIME_INTERVALS[t];
            }
        }
        return intervals;
    }

    function intervalsToSeconds(intervals) {
        var seconds = 0;
        var i;
        for (i in intervals) {
            if (Object.prototype.hasOwnProperty.call(intervals, i) && Object.prototype.hasOwnProperty.call(TIME_INTERVALS, i)) {
                seconds += intervals[i] * TIME_INTERVALS[i];
            }
        }
        return Utils.toSafeInteger(seconds);
    }

    function updateHiddenInput() {
        var intervals = {};
        var seconds = 0;
        var step = 0;
        var i = 0;
        for (i in innerInputs) {
            if (Object.prototype.hasOwnProperty.call(innerInputs, i)) {
                intervals[i] = innerInputs[i].value;
            }
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
        var intervals = secondsToIntervals(hiddenInput.value * TIME_INTERVALS[options.unit]);
        var i;
        for (i in innerInputs) {
            if (Object.prototype.hasOwnProperty.call(innerInputs, i)) {
                innerInputs[i].value = intervals[i];
            }
        }
    }

    function updateInnerInputsLength() {
        var i;
        for (i in innerInputs) {
            if (Object.prototype.hasOwnProperty.call(innerInputs, i)) {
                innerInputs[i].style.width = Math.max(3, innerInputs[i].value.length + 2) + 'ch';
            }
        }
    }

    function updateLabels() {
        var i;
        for (i in innerInputs) {
            if (Object.prototype.hasOwnProperty.call(innerInputs, i)) {
                labels[i].innerHTML = options.labels[i][parseInt(innerInputs[i].value) === 1 ? 0 : 1];
            }
        }
    }

    function createInnerInputs(intervals, steps) {
        var wrap, name, innerInput, label, i;

        field = document.createElement('div');
        field.className = 'duration-input';

        for (i = 0; i < options.display.length; i++) {
            name = options.display[i];
            wrap = document.createElement('span');
            wrap.className = 'duration-' + name;
            innerInput = document.createElement('input');
            innerInput.type = 'number';
            innerInput.value = intervals[name] || 0;
            innerInput.style.width = Math.max(3, innerInput.value.length + 2) + 'ch';
            if (steps[name] > 1) {
                innerInput.step = steps[name];
            }
            if (input.disabled) {
                innerInput.disabled = true;
            }
            innerInputs[name] = innerInput;
            innerInput.addEventListener('change', function () {
                updateHiddenInput();
                updateInnerInputs();
                updateInnerInputsLength();
                updateLabels();
            });
            innerInput.addEventListener('input', function () {
                while (this.value > Utils.getMaxSafeInteger()) {
                    this.value = this.value.slice(0, -1);
                }
                updateInnerInputsLength();
                updateHiddenInput();
                updateLabels();
            });
            innerInput.addEventListener('focus', function () {
                field.classList.add('focused');
            });
            innerInput.addEventListener('blur', function () {
                field.classList.remove('focused');
            });
            wrap.addEventListener('mousedown', function (event) {
                var input = $('input', this);
                if (input && event.target !== input) {
                    input.focus();
                    event.preventDefault();
                }
            });
            label = document.createElement('label');
            label.innerHTML = options.labels[name][parseInt(innerInput.value) === 1 ? 0 : 1];
            labels[name] = label;
            wrap.appendChild(innerInput);
            wrap.appendChild(label);
            field.appendChild(wrap);
        }

        field.addEventListener('mousedown', function (event) {
            if (event.target === this) {
                innerInput.focus();
                event.preventDefault();
            }
        });

        return field;
    }

    function createField() {
        var field, valueSeconds, stepSeconds;
        hiddenInput = document.createElement('input');
        hiddenInput.className = 'duration-hidden-input';
        hiddenInput.name = input.name;
        hiddenInput.id = input.id;
        hiddenInput.type = 'text';
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
        if (input.hasAttribute('data-display')) {
            options.display = input.getAttribute('data-display').split(', ');
        }
        if (input.hasAttribute('data-unit')) {
            options.unit = input.getAttribute('data-unit');
        }
        valueSeconds = input.value * TIME_INTERVALS[options.unit];
        stepSeconds = input.step * TIME_INTERVALS[options.unit];
        field = createInnerInputs(secondsToIntervals(valueSeconds || 0), secondsToIntervals(stepSeconds || 1));
        input.parentNode.replaceChild(field, input);
        field.appendChild(hiddenInput);
    }
}
