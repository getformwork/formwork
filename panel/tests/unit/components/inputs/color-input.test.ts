import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, mock, test } from "node:test";
import { ColorInput } from "../../../../src/ts/components/inputs/color-input";

/**
 * Make a color input in the document as the panel renders it
 */
function makeColor(value = "#ff0000") {
    document.body.innerHTML = `<div class="form-input-wrap"><div class="flex"><input class="form-input-color" type="color" id="color" name="color" value="${value}"><output class="form-input-color-value" for="color"></output></div></div>`;
    const element = document.getElementById("color") as HTMLInputElement;
    return { input: new ColorInput(element), element };
}

const output = () => document.querySelector("output") as HTMLOutputElement;

describe("ColorInput", () => {
    test("shows the value", () => {
        makeColor("#ff0000");
        assert.equal(output().textContent, "#ff0000");
    });

    test("updates the value shown when the value changes", () => {
        const { element } = makeColor();
        element.value = "#00ff00";
        element.dispatchEvent(new Event("input"));
        assert.equal(output().textContent, "#00ff00");
    });

    test("opens the picker when the value shown is clicked", () => {
        const { element } = makeColor();
        const click = mock.fn();
        element.addEventListener("click", click);
        output().click();
        assert.equal(click.mock.callCount(), 1);
        assert.ok(document.activeElement === element);
    });
});
