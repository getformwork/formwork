import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { dispatchChange, Input } from "../../../../src/ts/components/inputs/input";

describe("Input Component", () => {
    describe("dispatchChange", () => {
        test("dispatches the input and change events, which bubble", () => {
            document.body.innerHTML = '<form><input name="foo"></form>';
            const events: string[] = [];
            const form = document.querySelector("form") as HTMLFormElement;
            form.addEventListener("input", (event) => events.push(`${event.type}:${event.bubbles}`));
            form.addEventListener("change", (event) => events.push(`${event.type}:${event.bubbles}`));
            dispatchChange(document.querySelector("input") as HTMLInputElement);
            assert.deepEqual(events, ["input:true", "change:true"]);
        });
    });

    describe("Input", () => {
        test("gets and sets the name and the value of the element", () => {
            document.body.innerHTML = '<input name="foo" value="bar">';
            const input = new Input(document.querySelector("input") as HTMLInputElement);
            assert.equal(input.name, "foo");
            assert.equal(input.value, "bar");
            input.name = "baz";
            input.value = "qux";
            assert.equal((document.querySelector("input") as HTMLInputElement).name, "baz");
            assert.equal((document.querySelector("input") as HTMLInputElement).value, "qux");
        });
    });
});
