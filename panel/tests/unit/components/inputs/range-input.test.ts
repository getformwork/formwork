import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { RangeInput } from "../../../../src/ts/components/inputs/range-input";

/**
 * Make a range input in the document as the panel renders it
 */
function makeRange(attributes = 'min="0" max="10" step="1" value="5"') {
    document.body.innerHTML = `<div class="flex"><div class="form-input-range"><input class="form-input" type="range" id="range" name="range" ${attributes}></div><output class="form-input-range-value" for="range"></output></div>`;
    const element = document.getElementById("range") as HTMLInputElement;
    return { input: new RangeInput(element), element };
}

const output = () => document.querySelector("output")?.textContent;

const progress = (element: HTMLElement) => element.style.getPropertyValue("--progress");

const ticks = () => document.querySelectorAll(".form-input-range-ticks .tick").length;

describe("RangeInput", () => {
    test("shows the value and the progress", () => {
        const { element } = makeRange();
        assert.equal(output(), "5");
        assert.equal(progress(element), "50%");
    });

    test("updates the value and the progress when the value changes", () => {
        const { element } = makeRange();
        element.value = "8";
        element.dispatchEvent(new Event("input"));
        assert.equal(output(), "8");
        assert.equal(progress(element), "80%");
    });

    test("shows the progress of a range not starting from zero", () => {
        const { element } = makeRange('min="10" max="20" value="15"');
        assert.equal(progress(element), "50%");
    });

    test("shows the progress of decimal values", () => {
        const { element } = makeRange('min="0" max="1" step="0.1" value="0.5"');
        assert.equal(progress(element), "50%");
    });

    test("updates the value shown when set", () => {
        const { input, element } = makeRange();
        input.value = "2";
        assert.equal(output(), "2");
        assert.equal(progress(element), "20%");
    });

    describe("ticks", () => {
        test("adds a tick for each step", () => {
            makeRange('min="0" max="10" step="2" value="4" data-ticks="true"');
            assert.equal(ticks(), 6);
            assert.equal((document.querySelector(".form-input-range-ticks") as HTMLElement).dataset.for, "range");
        });

        test("adds the given number of ticks", () => {
            makeRange('min="0" max="10" value="4" data-ticks="4"');
            assert.equal(ticks(), 5);
        });

        test("adds no ticks when their number is zero", () => {
            makeRange('min="0" max="10" value="4" data-ticks="0"');
            assert.equal(document.querySelector(".form-input-range-ticks"), null);
        });

        test("adds a tick for each decimal step", () => {
            makeRange('min="0" max="1" step="0.25" value="0.5" data-ticks="true"');
            assert.equal(ticks(), 5);
        });
    });
});
