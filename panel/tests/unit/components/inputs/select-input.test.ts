import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { SelectInput } from "../../../../src/ts/components/inputs/select-input";
import { setTranslation } from "../../../helpers/panel";

beforeEach(() => {
    setTranslation();
});

const options = '<option value="first">First</option><option value="second" selected>Second</option><option value="third" disabled>Third</option><option value="fourth">Fourth</option>';

/**
 * Make a select input in the document as the panel renders it
 */
function makeSelect(html = options, attributes = "") {
    document.body.innerHTML = `<label for="choice" id="label">Choice</label><div class="form-input-wrap"><select class="form-select" id="choice" name="choice" ${attributes}>${html}</select></div>`;
    const select = document.querySelector("select") as HTMLSelectElement;
    const input = new SelectInput(select);
    const change = mock.fn();
    select.addEventListener("change", change);
    return { input, select, change, field: document.getElementById("choice") as HTMLInputElement };
}

const listbox = () => document.querySelector("[role=listbox]") as HTMLElement;

const item = (value: string) => listbox().querySelector(`[data-value="${value}"]`) as HTMLElement;

const visibleItems = () =>
    Array.from(listbox().querySelectorAll<HTMLElement>(".dropdown-item"))
        .filter((element) => element.style.display !== "none")
        .map((element) => element.dataset.value);

const isOpen = () => listbox().style.display === "block";

const selectedItem = () => listbox().querySelector<HTMLElement>(".dropdown-item.selected")?.dataset.value ?? null;

/**
 * Press a key in the text field, typing it if printable
 */
function press(field: HTMLInputElement, key: string) {
    const keydown = new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true });
    field.dispatchEvent(keydown);
    if (key.length === 1 && !keydown.defaultPrevented) {
        field.value += key;
    }
    const keyup = new KeyboardEvent("keyup", { key, bubbles: true, cancelable: true });
    field.dispatchEvent(keyup);
    return { keydown, keyup };
}

/**
 * Type a text in the text field
 */
function type(field: HTMLInputElement, text: string) {
    for (const key of text) {
        press(field, key);
    }
}

/**
 * Click an item of the list with the mouse
 */
function clickItem(value: string) {
    item(value).dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true }));
    item(value).dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, detail: 1 }));
}

describe("SelectInput", () => {
    describe("field", () => {
        test("replaces the select with a combobox showing the selected option", () => {
            const { select, field } = makeSelect();
            assert.equal(field.tagName, "INPUT");
            assert.equal(field.value, "Second");
            assert.equal(field.getAttribute("role"), "combobox");
            assert.equal(field.getAttribute("aria-expanded"), "false");
            assert.equal(field.getAttribute("aria-controls"), listbox().id);
            assert.equal(select.hidden, true);
            assert.equal(select.hasAttribute("id"), false);
            assert.equal(select.name, "choice");
        });

        test("lists the options with their state", () => {
            makeSelect();
            assert.deepEqual(
                Array.from(listbox().querySelectorAll<HTMLElement>("[role=option]"), (element) => `${element.dataset.value}:${element.getAttribute("aria-selected")}:${element.getAttribute("aria-disabled") ?? "false"}`),
                ["first:false:false", "second:true:false", "third:false:true", "fourth:false:false"],
            );
            assert.equal(listbox().getAttribute("aria-labelledby"), "label");
        });

        test("disables the field of a disabled select", () => {
            const { field } = makeSelect(options, "disabled");
            assert.equal(field.disabled, true);
        });

        test("shows an empty label without a selected option", () => {
            const { field } = makeSelect('<option value="">None</option>'.replace("None", ""));
            assert.equal(field.value, "");
        });
    });

    describe("list", () => {
        test("opens the list with the current option when focused", () => {
            const { field } = makeSelect();
            field.focus();
            assert.equal(isOpen(), true);
            assert.equal(field.getAttribute("aria-expanded"), "true");
            assert.equal(selectedItem(), "second");
        });

        test("closes the list when the field loses the focus", () => {
            const { field } = makeSelect();
            field.focus();
            field.blur();
            assert.equal(isOpen(), false);
            assert.equal(field.getAttribute("aria-expanded"), "false");
        });

        test("moves between the enabled options with the arrows", () => {
            const { field } = makeSelect();
            field.focus();
            press(field, "ArrowDown");
            assert.equal(selectedItem(), "fourth");
            assert.equal(field.getAttribute("aria-activedescendant"), item("fourth").id);
            press(field, "ArrowDown");
            assert.equal(selectedItem(), "first");
            press(field, "ArrowUp");
            assert.equal(selectedItem(), "fourth");
        });

        test("opens the closed list with the arrows", () => {
            const { field } = makeSelect();
            field.focus();
            press(field, "Escape");
            press(field, "ArrowDown");
            assert.equal(isOpen(), true);
            assert.equal(selectedItem(), "second");
        });

        test("chooses the selected option with Enter", () => {
            const { field, select, change } = makeSelect();
            field.focus();
            press(field, "ArrowDown");
            assert.equal(press(field, "Enter").keydown.defaultPrevented, true);
            assert.equal(select.value, "fourth");
            assert.equal(field.value, "Fourth");
            assert.equal(change.mock.callCount(), 1);
            assert.equal(isOpen(), false);
        });

        test("chooses an option when clicked", () => {
            const { field, select } = makeSelect();
            field.focus();
            clickItem("first");
            assert.equal(select.value, "first");
            assert.equal(field.value, "First");
            assert.equal(isOpen(), false);
        });

        test("does not choose disabled options", () => {
            const { field, select } = makeSelect();
            field.focus();
            clickItem("third");
            assert.equal(select.value, "second");
        });
    });

    describe("typing", () => {
        test("replaces the label with the typed text, filtering the options", () => {
            const { field } = makeSelect();
            field.focus();
            type(field, "fi");
            assert.equal(field.value, "fi");
            assert.deepEqual(visibleItems(), ["first"]);
            assert.equal(selectedItem(), "first");
        });

        test("filters ignoring case and diacritics, at the beginning of words", () => {
            const { field } = makeSelect('<option value="cafe">Café latte</option><option value="tea">Iced tea</option>');
            field.focus();
            type(field, "LAT");
            assert.deepEqual(visibleItems(), ["cafe"]);
        });

        test("shows the empty state without matching options", () => {
            const { field } = makeSelect();
            field.focus();
            type(field, "xyz");
            assert.deepEqual(visibleItems(), []);
            assert.equal((listbox().querySelector(".dropdown-empty") as HTMLElement).style.display, "block");
            assert.equal(listbox().querySelector(".dropdown-empty")?.textContent, "No matching options");
        });

        test("chooses the first matching option with Enter", () => {
            const { field, select } = makeSelect();
            field.focus();
            type(field, "fou");
            press(field, "Enter");
            assert.equal(select.value, "fourth");
            assert.equal(field.value, "Fourth");
        });

        test("chooses the option with the typed label when leaving the field", () => {
            const { field, select } = makeSelect();
            field.focus();
            type(field, "First");
            field.blur();
            assert.equal(select.value, "first");
        });

        test("restores the label when the typed text matches no option", () => {
            const { field, select, change } = makeSelect();
            field.focus();
            type(field, "xyz");
            field.blur();
            assert.equal(field.value, "Second");
            assert.equal(select.value, "second");
            assert.equal(change.mock.callCount(), 0);
        });

        test("restores the label and closes the list with Escape", () => {
            const { field } = makeSelect();
            field.focus();
            type(field, "fi");
            const { keydown, keyup } = press(field, "Escape");
            assert.equal(field.value, "Second");
            assert.equal(isOpen(), false);
            assert.equal(keydown.defaultPrevented, true);
            // Escape does not reach the page, e.g. closing a modal
            const pageEscape = mock.fn();
            document.addEventListener("keyup", pageEscape);
            field.dispatchEvent(keyup);
            assert.equal(pageEscape.mock.callCount(), 0);
        });

        test("replaces the label with the pasted text", () => {
            const { field } = makeSelect();
            field.focus();
            field.dispatchEvent(new KeyboardEvent("keydown", { key: "v", ctrlKey: true, bubbles: true, cancelable: true }));
            assert.equal(field.value, "");
        });

        test("leaves Escape to the page when the list is closed", () => {
            const { field } = makeSelect();
            field.focus();
            press(field, "Escape");
            const pageEscape = mock.fn();
            document.addEventListener("keyup", pageEscape);
            const { keydown } = press(field, "Escape");
            assert.equal(keydown.defaultPrevented, false);
            assert.equal(pageEscape.mock.callCount(), 1);
        });
    });

    describe("options", () => {
        test("shows the thumbnails and the icons of the options", () => {
            makeSelect('<option value="image.jpg" data-thumb="/site/image.jpg/thumbnail">image.jpg</option><option value="page" data-icon="page">Page</option>');
            assert.equal(item("image.jpg").querySelector("img.dropdown-thumb")?.getAttribute("src"), "/site/image.jpg/thumbnail");
            assert.notEqual(item("page").querySelector("svg"), null);
            assert.equal(item("page").textContent?.trim(), "Page");
        });

        test("copies the other data of the options to the items", () => {
            makeSelect('<option value="blog" data-allowed-templates="post">Blog</option>');
            assert.equal(item("blog").dataset.allowedTemplates, "post");
        });
    });

    describe("value", () => {
        test("sets the value, updating the label", () => {
            const { input, field, change } = makeSelect();
            input.value = "fourth";
            assert.equal(input.value, "fourth");
            assert.equal(field.value, "Fourth");
            assert.equal(change.mock.callCount(), 1);
        });

        test("empties the label for values without an option", () => {
            const { input, field } = makeSelect();
            input.value = "missing";
            assert.equal(field.value, "");
        });

        test("adds an option", () => {
            const { input, field } = makeSelect();
            input.addOption({ label: "Fifth", value: "fifth" });
            field.focus();
            clickItem("fifth");
            assert.equal(input.value, "fifth");
            assert.equal((document.querySelector('option[value="fifth"]') as HTMLOptionElement).textContent, "Fifth");
        });

        test("removes an option", () => {
            const { input } = makeSelect();
            input.removeOption("first");
            assert.equal(document.querySelector('option[value="first"]'), null);
            assert.equal(listbox().querySelector('[data-value="first"]'), null);
            assert.equal(input.value, "second");
        });

        test("chooses the first option when the selected one is removed", () => {
            const { input, field } = makeSelect();
            input.removeOption("second");
            assert.equal(input.value, "first");
            assert.equal(field.value, "First");
        });
    });
});
