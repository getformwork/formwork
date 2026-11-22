import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { TogglegroupInput } from "../../../../src/ts/components/inputs/togglegroup-input";

/**
 * Make a toggle group in the document as the panel renders it
 *
 * @param checked Value of the checked option, none if null
 * @param id      Name of the field, the id of the group
 * @param name    Name of the group in the form
 */
function makeTogglegroup(checked: string | null = "second", { id = "status", name = "status" } = {}) {
    const options = ["first", "second", "third"].map((value) => `<label class="form-label"><input class="form-input" type="radio" name="${name}" value="${value}" ${value === checked ? "checked" : ""}><span>${value}</span></label>`).join("");
    document.body.innerHTML = `<label class="form-label" id="${id}-label" for="${id}">Status:</label><div class="form-input-wrap"><fieldset class="form-togglegroup" id="${id}" name="${name}">${options}</fieldset></div>`;
    return new TogglegroupInput(document.querySelector("fieldset") as HTMLFieldSetElement);
}

const radio = (value: string) => document.querySelector(`input[value="${value}"]`) as HTMLInputElement;

describe("TogglegroupInput", () => {
    test("returns the value of the checked option", () => {
        assert.equal(makeTogglegroup("second").value, "second");
    });

    test("checks the option with a value", () => {
        const input = makeTogglegroup("second");
        input.value = "third";
        assert.equal(radio("third").checked, true);
        assert.equal(radio("second").checked, false);
        assert.equal(input.value, "third");
    });

    test("ignores values without an option", () => {
        const input = makeTogglegroup("second");
        input.value = "fourth";
        assert.equal(input.value, "second");
    });

    test("renames the group and its options", () => {
        const input = makeTogglegroup("second");
        input.name = "foo";
        assert.equal(input.name, "foo");
        assert.deepEqual(
            Array.from(document.querySelectorAll("input"), (element) => element.name),
            ["foo", "foo", "foo"],
        );
    });

    test("focuses the checked option when the label is clicked", () => {
        makeTogglegroup("second");
        (document.getElementById("status-label") as HTMLElement).click();
        assert.ok(document.activeElement === radio("second"));
    });

    test("returns an empty value without a checked option", () => {
        assert.equal(makeTogglegroup(null).value, "");
    });

    test("focuses the checked option when the label of a nested field is clicked", () => {
        makeTogglegroup("second", { id: "newUserModal.role", name: "newUserModal[role]" });
        (document.getElementById("newUserModal.role-label") as HTMLElement).click();
        assert.ok(document.activeElement === radio("second"));
    });
});
