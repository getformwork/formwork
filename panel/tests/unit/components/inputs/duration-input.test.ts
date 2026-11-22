import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { DurationInput, type DurationInputOptions } from "../../../../src/ts/components/inputs/duration-input";
import { setTranslation } from "../../../helpers/panel";

describe("DurationInput", () => {
    beforeEach(() => {
        setTranslation();
    });

    /**
     * Make a duration input in the document as the panel renders it
     */
    function makeDuration(attributes = 'value="93784"', options: Partial<DurationInputOptions> = {}) {
        document.body.innerHTML = `<label for="duration" id="duration-label">Duration:</label><div class="form-input-wrap"><input class="form-input form-input-duration" type="number" id="duration" name="duration" ${attributes.includes("data-unit") ? "" : 'data-unit="seconds"'} ${attributes}></div>`;
        const element = document.getElementById("duration") as HTMLInputElement;
        const input = new DurationInput(element, options);
        const change = mock.fn();
        element.addEventListener("change", change);
        return { input, element, change };
    }

    const field = (interval: string) => document.getElementById(`duration.${interval}`) as HTMLInputElement;

    const fields = () => Array.from(document.querySelectorAll<HTMLInputElement>(".form-input-duration-wrap input[type=number]:not(#duration)"), (input) => `${input.id.replace("duration.", "")}:${input.value}`);

    const fieldLabel = (interval: string) => document.querySelector(`label[for="duration.${interval}"]`)?.textContent;

    /**
     * Type a value in the field of an interval
     */
    function type(interval: string, value: string) {
        field(interval).value = value;
        field(interval).dispatchEvent(new Event("input", { bubbles: true }));
    }

    test("shows a field for each interval with the value split into them", () => {
        const { element } = makeDuration('value="93784" data-intervals="days, hours, minutes, seconds"');
        assert.deepEqual(fields(), ["days:1", "hours:2", "minutes:3", "seconds:4"]);
        assert.equal(element.hidden, true);
        assert.equal(element.readOnly, true);
    });

    test("shows all the intervals by default", () => {
        makeDuration('value="0"');
        assert.deepEqual(fields(), ["years:0", "months:0", "weeks:0", "days:0", "hours:0", "minutes:0", "seconds:0"]);
    });

    test("carries over the larger values to the largest interval shown", () => {
        makeDuration('value="90000" data-intervals="hours, minutes"');
        assert.deepEqual(fields(), ["hours:25", "minutes:0"]);
    });

    test("reads the value in its unit", () => {
        makeDuration('value="90" data-unit="minutes" data-intervals="hours, minutes"');
        assert.deepEqual(fields(), ["hours:1", "minutes:30"]);
    });

    test("labels the fields in the singular or plural", () => {
        makeDuration('value="3660" data-intervals="hours, minutes"');
        assert.equal(fieldLabel("hours"), "hour");
        type("minutes", "2");
        assert.equal(fieldLabel("minutes"), "minutes");
    });

    test("translates the labels", () => {
        setTranslation({ "date.duration.hours": ["ora", "ore"] }, "it");
        makeDuration('value="7200" data-intervals="hours"');
        assert.equal(fieldLabel("hours"), "ore");
    });

    test("updates the value from the fields, in its unit", () => {
        const { element, change } = makeDuration('value="0" data-unit="minutes" data-intervals="hours, minutes"');
        type("hours", "2");
        type("minutes", "15");
        assert.equal(element.value, "135");
        assert.equal(change.mock.callCount(), 2);
    });

    /**
     * Get the values of a field after typing each of the given ones
     */
    function typed(...values: string[]) {
        makeDuration('value="0" data-intervals="minutes"');
        return values.map((value) => {
            type("minutes", value);
            return field("minutes").value;
        });
    }

    test("removes the leading zeros while typing", () => {
        assert.deepEqual(typed("007", "000"), ["7", "0"]);
    });

    test("leaves the numbers without leading zeros and the empty fields unchanged while typing", () => {
        assert.deepEqual(typed("42", "100", "0", ""), ["42", "100", "0", ""]);
    });

    test("preserves the zero before the decimal separator while typing", () => {
        assert.deepEqual(typed("0.5", "0,5", "00.5"), ["0.5", "0,5", "0.5"]);
    });

    test("removes the digits making the number larger than the largest safe integer while typing", () => {
        assert.deepEqual(typed("9007199254740991", "9007199254740992", "99999999999999999999"), ["9007199254740991", "900719925474099", "999999999999999"]);
    });

    test("splits the value again when leaving a field", () => {
        const { element } = makeDuration('value="0" data-intervals="hours, minutes"');
        type("minutes", "90");
        field("minutes").dispatchEvent(new FocusEvent("blur"));
        assert.deepEqual(fields(), ["hours:1", "minutes:30"]);
        assert.equal(element.value, "5400");
    });

    test("rounds the value down to the step", () => {
        const { element } = makeDuration('value="0" step="15" data-unit="minutes" data-intervals="minutes"');
        assert.equal(field("minutes").step, "15");
        type("minutes", "40");
        assert.equal(element.value, "30");
    });

    test("reads an empty field as zero", () => {
        const { element } = makeDuration('value="3660" data-intervals="hours, minutes"');
        type("minutes", "");
        assert.equal(element.value, "3600");
    });

    test("sets the value, showing it in the fields", () => {
        const { input } = makeDuration('value="0" data-intervals="hours, minutes"');
        input.value = "7260";
        assert.equal(input.value, "7260");
        assert.deepEqual(fields(), ["hours:2", "minutes:1"]);
        assert.equal(fieldLabel("minutes"), "minute");
    });

    test("disables the fields of a disabled input", () => {
        makeDuration('value="0" data-intervals="hours, minutes" disabled');
        assert.equal(field("hours").disabled, true);
        assert.equal(field("minutes").disabled, true);
    });

    test("focuses the first field when the label is clicked", () => {
        makeDuration('value="0" data-intervals="hours, minutes"');
        (document.getElementById("duration-label") as HTMLElement).click();
        assert.ok(document.activeElement === field("hours"));
    });

    test("marks the input while a field is focused", () => {
        makeDuration('value="0" data-intervals="hours, minutes"');
        const wrap = document.querySelector(".form-input-duration-wrap") as HTMLElement;
        field("hours").dispatchEvent(new FocusEvent("focus"));
        assert.equal(wrap.classList.contains("focused"), true);
        field("hours").dispatchEvent(new FocusEvent("blur"));
        assert.equal(wrap.classList.contains("focused"), false);
    });

    test("fits the width of the fields to their text", () => {
        makeDuration('value="0" data-intervals="seconds"');
        assert.equal(field("seconds").style.width, "3ch");
        type("seconds", "12345");
        assert.equal(field("seconds").style.width, "7ch");
    });
});
