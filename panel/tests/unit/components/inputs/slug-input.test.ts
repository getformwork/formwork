import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { SlugInput } from "../../../../src/ts/components/inputs/slug-input";

/**
 * Make a slug input with its source in the document, as the panel renders them
 *
 * @param value      Value of the slug
 * @param autoUpdate Whether the slug follows the source, otherwise it is generated with a button
 * @param attributes Other attributes of the slug input
 */
function makeSlug({ value = "", autoUpdate = false, attributes = "" } = {}) {
    document.body.innerHTML = `
        <form>
            <input type="text" id="title" name="title">
            <div class="form-input-wrap">
                <input type="text" class="form-input form-input-slug" id="slug" name="slug" value="${value}" data-source="title" data-auto-update="${autoUpdate}" ${attributes}>
                ${autoUpdate ? "" : '<button type="button" class="form-input-action" data-generate-slug="slug">Generate</button>'}
            </div>
        </form>`;
    const element = document.getElementById("slug") as HTMLInputElement;
    return { input: new SlugInput(element), element, source: document.getElementById("title") as HTMLInputElement };
}

/**
 * Type a text in a field, as the user does
 */
function type(element: HTMLInputElement, text: string) {
    element.value = text;
    element.dispatchEvent(new Event("input", { bubbles: true }));
    element.dispatchEvent(new KeyboardEvent("keyup", { bubbles: true }));
}

const generate = () => (document.querySelector("[data-generate-slug]") as HTMLElement).click();

describe("SlugInput", () => {
    test("cleans the slug while typing, preserving hyphens at the ends", () => {
        const { element } = makeSlug();
        type(element, "Hello World-");
        assert.equal(element.value, "hello-world-");
        type(element, "-foo");
        assert.equal(element.value, "-foo");
    });

    test("makes a complete slug when leaving the field", () => {
        const { element } = makeSlug();
        type(element, "-Hello World-");
        element.dispatchEvent(new FocusEvent("blur"));
        assert.equal(element.value, "hello-world");
    });

    test("makes a complete slug from the set value", () => {
        const { input } = makeSlug();
        input.value = "Hello World!";
        assert.equal(input.value, "hello-world");
    });

    test("generates the slug from the source while typing in it", () => {
        const { element, source } = makeSlug({ autoUpdate: true });
        type(source, "Hello world");
        assert.equal(element.value, "hello-world");
        type(source, "Hello world, again");
        assert.equal(element.value, "hello-world-again");
        type(source, "Ça va?");
        assert.equal(element.value, "ca-va");
    });

    test("generates the slug from the source when loaded", () => {
        document.body.innerHTML = '<input id="title" value="Foo Bar"><input id="slug" name="slug" data-source="title" data-auto-update="true">';
        const element = document.getElementById("slug") as HTMLInputElement;
        new SlugInput(element);
        assert.equal(element.value, "foo-bar");
    });

    test("generates the slug from the source with the button", () => {
        const { element, source } = makeSlug();
        type(source, "Hello world");
        assert.equal(element.value, "");
        generate();
        assert.equal(element.value, "hello-world");
    });

    test("does not generate the slug of read-only and disabled fields", () => {
        for (const attribute of ["readonly", "disabled"]) {
            const { element, source } = makeSlug({ value: "foo", autoUpdate: true, attributes: attribute });
            element.value = "foo";
            type(source, "Hello world");
            assert.equal(element.value, "foo", attribute);
        }
    });

    test("does not generate the slug without a source", () => {
        document.body.innerHTML = '<input id="slug" name="slug" value="foo" data-source="missing" data-auto-update="true">';
        const element = document.getElementById("slug") as HTMLInputElement;
        new SlugInput(element);
        assert.equal(element.value, "foo");
    });
});
