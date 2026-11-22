import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, type TestContext, test } from "node:test";
import { TagsInput } from "../../../../src/ts/components/inputs/tags-input";
import { setTranslation } from "../../../helpers/panel";

describe("TagsInput", () => {
    beforeEach(() => {
        setTranslation();
    });

    /**
     * Make a tags input in the document as the panel renders it
     */
    function makeTags(value = "foo, bar", attributes = "") {
        document.body.innerHTML = `<div class="form-input-wrap"><input class="form-input form-input-tags" type="text" id="tags" name="tags" value="${value}" placeholder="Add tags" ${attributes}></div>`;
        const element = document.querySelector("input[name=tags]") as HTMLInputElement;
        const input = new TagsInput(element);
        const change = mock.fn();
        element.addEventListener("change", change);
        return { input, element, change, field: document.getElementById("tags") as HTMLInputElement };
    }

    const tags = () => Array.from(document.querySelectorAll(".tag"), (tag) => tag.textContent);

    /**
     * Press a key in the text field
     */
    function press(field: HTMLInputElement, key: string) {
        const event = new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true });
        field.dispatchEvent(event);
        field.dispatchEvent(new KeyboardEvent("keyup", { key, bubbles: true }));
        return event;
    }

    /**
     * Type a text in the text field
     */
    function type(field: HTMLInputElement, text: string) {
        field.value = text;
        field.dispatchEvent(new Event("input", { bubbles: true }));
    }

    const options = (suggestions: unknown) => `data-options='${JSON.stringify(suggestions)}'`;

    const suggestions = () => Array.from(document.querySelectorAll<HTMLElement>(".dropdown-list .dropdown-item"));

    describe("tags", () => {
        test("shows the tags of the value in a text field", () => {
            const { element, field } = makeTags();
            assert.deepEqual(tags(), ["foo", "bar"]);
            assert.equal(element.hidden, true);
            assert.equal(element.hasAttribute("id"), false);
            assert.equal(field.tagName, "INPUT");
            assert.notEqual(field, element);
        });

        test("trims the tags of the value, ignoring the empty ones and preserving the spaces inside them", () => {
            makeTags("  Hello world ,  , foo,  ");
            assert.deepEqual(tags(), ["Hello world", "foo"]);
        });

        test("shows no tags for an empty value", () => {
            makeTags("");
            assert.deepEqual(tags(), []);
        });

        test("renames a tag, keeping its position", () => {
            const { input, element, change } = makeTags("foo, bar, baz");
            input.renameTag("bar", "qux");
            assert.deepEqual(tags(), ["foo", "qux", "baz"]);
            assert.equal(element.value, "foo, qux, baz");
            assert.equal(change.mock.callCount(), 1);
        });

        test("does not rename a missing tag", () => {
            const { input, element, change } = makeTags("foo, bar");
            input.renameTag("baz", "qux");
            assert.equal(element.value, "foo, bar");
            assert.equal(change.mock.callCount(), 0);
        });

        test("adds a typed tag with Enter or comma", () => {
            const { element, field, change } = makeTags();
            type(field, "baz");
            assert.equal(press(field, "Enter").defaultPrevented, true);
            type(field, "qux");
            assert.equal(press(field, ",").defaultPrevented, true);
            assert.deepEqual(tags(), ["foo", "bar", "baz", "qux"]);
            assert.equal(element.value, "foo, bar, baz, qux");
            assert.equal(field.value, "");
            assert.equal(change.mock.callCount(), 2);
        });

        test("trims typed tags", () => {
            const { element, field } = makeTags("");
            type(field, "  baz  ");
            press(field, "Enter");
            assert.equal(element.value, "baz");
        });

        test("adds the typed tag when leaving the field", () => {
            const { element, field } = makeTags();
            field.focus();
            type(field, "baz");
            field.blur();
            assert.equal(element.value, "foo, bar, baz");
        });

        test("ignores Enter without a typed tag", () => {
            const { field, change } = makeTags();
            assert.equal(press(field, "Enter").defaultPrevented, false);
            assert.equal(change.mock.callCount(), 0);
        });

        test("rejects duplicate tags, keeping the typed text", () => {
            const { field } = makeTags();
            type(field, "foo");
            press(field, "Enter");
            assert.deepEqual(tags(), ["foo", "bar"]);
            assert.equal(field.value, "foo");
            assert.equal(field.classList.contains("form-input-invalid"), true);
            type(field, "fooo");
            press(field, "o");
            assert.equal(field.classList.contains("form-input-invalid"), false);
        });

        test("rejects tags beyond the limit", () => {
            const { field } = makeTags("foo, bar", 'data-limit="2"');
            type(field, "baz");
            press(field, "Enter");
            assert.deepEqual(tags(), ["foo", "bar"]);
            assert.equal(field.classList.contains("form-input-invalid"), true);
        });

        test("removes the last tag with Backspace in the empty field", () => {
            const { element, field } = makeTags();
            assert.equal(press(field, "Backspace").defaultPrevented, true);
            assert.equal(element.value, "foo");
        });

        test("does not remove tags with Backspace while typing", () => {
            const { element, field } = makeTags();
            type(field, "ba");
            assert.equal(press(field, "Backspace").defaultPrevented, false);
            assert.equal(element.value, "foo, bar");
        });

        test("removes a tag with its button", () => {
            const { element } = makeTags();
            const remove = document.querySelectorAll<HTMLElement>(".tag-remove")[0];
            assert.equal(remove.getAttribute("aria-label") ?? remove.ariaLabel, "Remove: foo");
            remove.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true }));
            remove.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, detail: 1 }));
            assert.equal(element.value, "bar");
        });

        test("removes a tag with its button from the keyboard, keeping the focus in the field", () => {
            const { element, field } = makeTags();
            const remove = document.querySelectorAll<HTMLElement>(".tag-remove")[1];
            remove.focus();
            remove.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, detail: 0 }));
            assert.equal(element.value, "foo");
            assert.ok(document.activeElement === field);
        });

        test("clears the typed text with Escape", () => {
            const { field } = makeTags();
            type(field, "baz");
            press(field, "Escape");
            assert.equal(field.value, "");
            assert.deepEqual(tags(), ["foo", "bar"]);
        });

        test("sets the value, replacing the tags", () => {
            const { input } = makeTags();
            input.value = "baz, qux";
            assert.deepEqual(tags(), ["baz", "qux"]);
            assert.equal(input.value, "baz, qux");
        });

        test("shows the placeholder only without tags", () => {
            const { element, field } = makeTags("");
            assert.equal(field.placeholder, "Add tags");
            type(field, "foo");
            press(field, "Enter");
            assert.equal(field.placeholder, "");
            press(field, "Backspace");
            assert.equal(element.value, "");
            assert.equal(field.placeholder, "Add tags");
        });
    });

    describe("suggestions", () => {
        test("suggests the options", () => {
            makeTags("", options(["first", "second"]));
            assert.deepEqual(
                suggestions().map((item) => item.dataset.value),
                ["first", "second"],
            );
        });

        test("uses the keys of associative options as values", () => {
            makeTags("", options({ en: "English", it: "Italian" }));
            assert.deepEqual(
                suggestions().map((item) => `${item.dataset.value}:${item.textContent}`),
                ["en:English", "it:Italian"],
            );
        });

        test("shows the thumbnails and the icons of the suggestions", () => {
            makeTags("", options({ "image.jpg": { value: "image.jpg", thumb: "/site/image.jpg/thumbnail" }, "document.pdf": { value: "document.pdf", icon: "file-pdf" } }));
            const [image, pdf] = suggestions();
            assert.equal(image.querySelector("img.dropdown-thumb")?.getAttribute("src"), "/site/image.jpg/thumbnail");
            assert.notEqual(pdf.querySelector("svg"), null);
        });

        test("adds a suggestion when clicked", () => {
            const { element } = makeTags("", options(["first", "second"]));
            suggestions()[1].click();
            assert.equal(element.value, "second");
        });

        test("filters the suggestions while typing, adding the first one with Enter", (t) => {
            t.mock.timers.enable({ apis: ["setTimeout"] });
            const { element, field } = makeTags("", options(["first", "second", "third"]));
            field.focus();
            type(field, "sec");
            field.dispatchEvent(new KeyboardEvent("keyup", { key: "c", bubbles: true }));
            t.mock.timers.tick(100);
            assert.deepEqual(
                suggestions()
                    .filter((item) => item.style.display !== "none")
                    .map((item) => item.dataset.value),
                ["second"],
            );
            press(field, "Enter");
            assert.equal(element.value, "second");
        });

        describe("open list", () => {
            beforeEach((t) => {
                // The list is filtered shortly after typing
                (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
            });

            const list = () => document.querySelector(".dropdown-list") as HTMLElement;

            const selected = () => document.querySelector<HTMLElement>(".dropdown-list .dropdown-item.selected")?.dataset.value;

            test("opens when the text field gets the focus", () => {
                const { field } = makeTags("", options(["first", "second"]));
                field.focus();
                assert.equal(list().style.display, "block");
            });

            test("moves through the suggestions with the arrows, adding the selected one with Enter", () => {
                const { element, field } = makeTags("", options(["first", "second", "third"]));
                field.focus();
                assert.equal(press(field, "ArrowDown").defaultPrevented, true);
                assert.equal(selected(), "first");
                press(field, "ArrowDown");
                assert.equal(selected(), "second");
                assert.equal(press(field, "ArrowUp").defaultPrevented, true);
                assert.equal(selected(), "first");
                press(field, "Enter");
                assert.equal(element.value, "first");
            });

            test("leaves the arrows to the browser when closed", () => {
                const { field } = makeTags("", options(["first"]));
                assert.equal(press(field, "ArrowDown").defaultPrevented, false);
            });

            test("closes with Escape", (t) => {
                const { field } = makeTags("", options(["first"]));
                field.focus();
                press(field, "Escape");
                t.mock.timers.tick(100);
                assert.equal(list().style.display, "none");
            });

            test("closes when the text field loses the focus", () => {
                const { field } = makeTags("", options(["first"]));
                field.focus();
                field.blur();
                assert.equal(list().style.display, "none");
            });
        });

        test("accepts only the options when required", () => {
            const { element, field } = makeTags("", `${options(["first"])} data-accept="options"`);
            type(field, "first");
            press(field, "Enter");
            type(field, "other");
            press(field, "Enter");
            assert.equal(element.value, "first");
        });

        test("accepts any tag when allowed", () => {
            const { element, field } = makeTags("", `${options(["first"])} data-accept="any"`);
            type(field, "other");
            press(field, "Enter");
            assert.equal(element.value, "other");
        });
    });
});
