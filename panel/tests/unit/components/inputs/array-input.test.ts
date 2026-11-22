import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import type { ArrayInput } from "../../../../src/ts/components/inputs/array-input";
import { app } from "../../../../src/ts/services/app";
import { Form } from "../../../../src/ts/services/form";

beforeEach(() => {
    app.modals = {};
});

/**
 * Get the HTML of a row of an array field as the panel renders it
 */
function row(value: string, key?: string) {
    const name = key === undefined ? "items[]" : `items[${key}]`;
    return `
        <div class="form-input-array-row">
            <span class="sortable-handle"></span>
            <div class="form-input-array-item">
                ${key === undefined ? "" : `<input type="text" class="form-input form-input-array-key" value="${key}">`}
                <div class="form-input-array-value">
                    <div class="form-input-wrap"><input type="text" class="form-input" id="items-${key ?? value}" name="${name}" value="${value}"></div>
                </div>
            </div>
            <button type="button" class="form-input-array-remove"></button>
            <button type="button" class="form-input-array-add"></button>
        </div>`;
}

/**
 * Make a form with an array field with the given rows
 */
async function makeArray(rows: string[], { associative = false, label = false } = {}) {
    document.body.innerHTML = `
        ${label ? '<label for="items" id="label">Items</label>' : ""}
        <form data-form="test">
            <fieldset class="form-input-array${associative ? " form-input-array-associative" : ""}" id="items" name="items">${rows.join("")}</fieldset>
        </form>`;
    const form = new Form(document.querySelector("form") as HTMLFormElement);
    // The rows are made sortable once Sortable is loaded, which must happen in the window of the test
    await new Promise((resolve) => setTimeout(resolve));
    return { form, array: form.inputs.items as unknown as ArrayInput };
}

const rows = () => Array.from(document.querySelectorAll<HTMLElement>(".form-input-array-row"));

const keyInput = (row: HTMLElement) => row.querySelector(".form-input-array-key") as HTMLInputElement;

const valueInput = (row: HTMLElement) => row.querySelector(".form-input-array-value input") as HTMLInputElement;

/**
 * Type a value in an input
 */
function type(input: HTMLInputElement, value: string) {
    input.value = value;
    input.dispatchEvent(new Event("input", { bubbles: true }));
}

const click = (row: HTMLElement, button: "add" | "remove") => (row.querySelector(`.form-input-array-${button}`) as HTMLButtonElement).click();

describe("ArrayInput", () => {
    describe("value", () => {
        test("returns the values of a list as JSON", async () => {
            const { array } = await makeArray([row("first"), row("second")]);
            assert.equal(array.value, '{"0":"first","1":"second"}');
        });

        test("ignores empty values of a list", async () => {
            const { array } = await makeArray([row("first"), row("  "), row("second")]);
            assert.equal(array.value, '{"0":"first","1":"second"}');
        });

        test("returns the values of an associative array by key", async () => {
            const { array } = await makeArray([row("bar", "foo"), row("qux", "baz")], { associative: true });
            assert.equal(array.value, '{"foo":"bar","baz":"qux"}');
        });

        test("trims keys and values", async () => {
            const { array } = await makeArray([row(" bar ", " foo ")], { associative: true });
            assert.equal(array.value, '{"foo":"bar"}');
        });

        test("ignores the rows without a key of an associative array", async () => {
            const { array } = await makeArray([row("bar", "foo"), row("qux", "")], { associative: true });
            assert.equal(array.value, '{"foo":"bar"}');
        });
    });

    describe("rows", () => {
        test("adds an empty row after a row", async () => {
            const { form } = await makeArray([row("first"), row("second")]);
            click(rows()[0], "add");
            assert.deepEqual(
                rows().map((item) => valueInput(item).value),
                ["first", "", "second"],
            );
            assert.equal(valueInput(rows()[1]).name, "items[]");
            assert.equal(form.formInputs.filter((input) => input.name === "items[]").length, 3);
        });

        test("gives the added row a new id and the focus", async () => {
            await makeArray([row("first")]);
            click(rows()[0], "add");
            const added = valueInput(rows()[1]);
            assert.notEqual(added.id, valueInput(rows()[0]).id);
            assert.ok(document.activeElement === added);
        });

        test("adds an empty row with an empty key to an associative array, focusing the key", async () => {
            await makeArray([row("bar", "foo")], { associative: true });
            click(rows()[0], "add");
            const added = rows()[1];
            assert.equal(keyInput(added).value, "");
            assert.equal(valueInput(added).value, "");
            assert.equal(valueInput(added).name, "items[]");
            assert.ok(document.activeElement === keyInput(added));
        });

        test("binds the buttons of the added rows", async () => {
            await makeArray([row("first")]);
            click(rows()[0], "add");
            click(rows()[1], "add");
            assert.equal(rows().length, 3);
            click(rows()[2], "remove");
            assert.equal(rows().length, 2);
        });

        test("removes a row", async () => {
            await makeArray([row("first"), row("second")]);
            click(rows()[0], "remove");
            assert.deepEqual(
                rows().map((item) => valueInput(item).value),
                ["second"],
            );
        });

        test("clears the only row instead of removing it", async () => {
            await makeArray([row("bar", "foo")], { associative: true });
            click(rows()[0], "remove");
            assert.equal(rows().length, 1);
            assert.equal(keyInput(rows()[0]).value, "");
            assert.equal(valueInput(rows()[0]).value, "");
            assert.equal(valueInput(rows()[0]).name, "items[]");
        });

        test("removes the inputs of the removed rows from the form", async () => {
            const { form } = await makeArray([row("bar", "foo"), row("qux", "baz")], { associative: true });
            click(rows()[1], "remove");
            assert.equal("items[baz]" in form.inputs, false);
        });
    });

    describe("keys", () => {
        test("names the value after the key", async () => {
            const { form } = await makeArray([row("bar", "foo")], { associative: true });
            type(keyInput(rows()[0]), " baz ");
            assert.equal(valueInput(rows()[0]).name, "items[baz]");
            assert.ok(form.inputs["items[baz]"].element === valueInput(rows()[0]));
        });

        test("requires the key of a row with a value", async () => {
            await makeArray([row("", "")], { associative: true });
            type(valueInput(rows()[0]), "bar");
            assert.equal(keyInput(rows()[0]).required, true);
            type(valueInput(rows()[0]), " ");
            assert.equal(keyInput(rows()[0]).required, false);
        });
    });

    test("focuses the first input when the label is clicked", async () => {
        await makeArray([row("bar", "foo")], { associative: true, label: true });
        (document.getElementById("label") as HTMLElement).click();
        assert.ok(document.activeElement === keyInput(rows()[0]));
    });
});
