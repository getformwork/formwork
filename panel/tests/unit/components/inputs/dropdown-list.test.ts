import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { DropdownList } from "../../../../src/ts/components/inputs/dropdown-list";

/**
 * Make a list with the given items, by value, in the document
 */
function makeList(html: string, open: boolean | null = null) {
    document.body.innerHTML = `<div class="dropdown-list">${html}</div>`;
    return new DropdownList(document.querySelector(".dropdown-list") as HTMLElement, open);
}

const items = '<div class="dropdown-item" data-value="first">First</div><div class="dropdown-item disabled" data-value="second">Second</div><div class="dropdown-item" data-value="third">Third</div><div class="dropdown-item" data-value="fourth" style="display: none">Fourth</div>';

const selected = (list: DropdownList) => list.selected.value?.dataset.value ?? null;

describe("DropdownList", () => {
    test("shows and hides the list", () => {
        const list = makeList(items, false);
        assert.equal(list.element.style.display, "none");
        list.open.value = true;
        assert.equal(list.element.style.display, "block");
    });

    test("leaves the display of the list unchanged until opened or closed", () => {
        const list = makeList(items);
        assert.equal(list.element.style.display, "");
    });

    test("marks the selected item", () => {
        const list = makeList(items);
        list.select(list.find("third"));
        assert.deepEqual(
            Array.from(list.element.querySelectorAll<HTMLElement>(".selected"), (item) => item.dataset.value),
            ["third"],
        );
    });

    test("does not select disabled items", () => {
        const list = makeList(items);
        list.select(list.find("second"));
        assert.equal(selected(list), null);
    });

    test("finds items by value, also with special characters", () => {
        // happy-dom cannot parse escaped double quotes in attribute selectors, unlike browsers
        const list = makeList('<div class="dropdown-item" data-value="foo bar].txt">Foo</div>');
        assert.equal(list.find("foo bar].txt")?.textContent, "Foo");
        assert.equal(list.find("missing"), null);
    });

    test("selects the first and last selectable items", () => {
        const list = makeList(items);
        list.selectFirst();
        assert.equal(selected(list), "first");
        list.selectLast();
        assert.equal(selected(list), "third");
    });

    test("selects the next and previous selectable items, wrapping around", () => {
        const list = makeList(items);
        list.selectNext();
        assert.equal(selected(list), "first");
        list.selectNext();
        assert.equal(selected(list), "third");
        list.selectNext();
        assert.equal(selected(list), "first");
        list.selectPrevious();
        assert.equal(selected(list), "third");
        list.selectPrevious();
        assert.equal(selected(list), "first");
    });

    test("selects the last item when moving back without a selection", () => {
        const list = makeList(items);
        list.selectPrevious();
        assert.equal(selected(list), "third");
    });

    test("filters the items, returning how many are visible", () => {
        const list = makeList(items);
        assert.equal(
            list.filter((item) => item.dataset.value !== "first"),
            3,
        );
        assert.equal(list.countDisplayed(), 3);
        assert.equal(list.find("first")?.style.display, "none");
        assert.equal(list.find("fourth")?.style.display, "block");
    });

    test("sorts the items by value", () => {
        const list = makeList(items);
        list.sort();
        assert.deepEqual(
            list.items.map((item) => item.dataset.value),
            ["first", "fourth", "second", "third"],
        );
    });
});
