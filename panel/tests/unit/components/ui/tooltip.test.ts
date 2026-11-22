import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { Tooltip } from "../../../../src/ts/components/ui/tooltip";

beforeEach((t) => {
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
});

/**
 * Add an element to the document with the given size and position in the window
 */
function makeReference(tagName = "span", { top = 100, left = 200, width = 40, height = 20 } = {}) {
    const element = document.createElement(tagName);
    element.textContent = "Foo";
    document.body.append(element);
    // happy-dom has no layout, unlike browsers
    element.getBoundingClientRect = () => ({ top, left, width, height, right: left + width, bottom: top + height, x: left, y: top }) as DOMRect;
    Object.defineProperty(element, "offsetWidth", { value: width });
    Object.defineProperty(element, "offsetHeight", { value: height });
    return element;
}

/**
 * Give the elements of the tooltips a size
 */
function sizeTooltips(width: number, height: number) {
    Object.defineProperty(HTMLElement.prototype, "offsetWidth", { configurable: true, get: () => width });
    Object.defineProperty(HTMLElement.prototype, "offsetHeight", { configurable: true, get: () => height });
}

const shown = () => document.querySelector(".tooltip") as HTMLElement | null;

describe("Tooltip", () => {
    describe("showing", () => {
        test("shows the text after the delay", (t) => {
            const tooltip = new Tooltip("Foo <b>bar</b>", { referenceElement: makeReference() });
            tooltip.show();
            assert.equal(shown(), null);
            assert.equal(tooltip.removed, true);
            t.mock.timers.tick(500);
            assert.equal(shown()?.textContent, "Foo <b>bar</b>");
            assert.equal(shown()?.role, "tooltip");
            assert.equal(tooltip.removed, false);
        });

        test("shows HTML when allowed", (t) => {
            new Tooltip("Foo <b>bar</b>", { referenceElement: makeReference(), html: true, delay: 0 }).show();
            t.mock.timers.tick(0);
            assert.equal(shown()?.querySelector("b")?.textContent, "bar");
        });

        test("does not show the tooltip of a removed element", (t) => {
            const reference = makeReference();
            new Tooltip("Foo", { referenceElement: reference }).show();
            reference.remove();
            t.mock.timers.tick(500);
            assert.equal(shown(), null);
        });

        test("does not cover the open menu of an element", (t) => {
            const reference = makeReference("button");
            reference.setAttribute("aria-expanded", "true");
            new Tooltip("Foo", { referenceElement: reference }).show();
            t.mock.timers.tick(500);
            assert.equal(shown(), null);
        });

        test("sets the stack order", (t) => {
            new Tooltip("Foo", { referenceElement: makeReference(), zIndex: 18, delay: 0 }).show();
            t.mock.timers.tick(0);
            assert.equal(shown()?.style.zIndex, "18");
        });
    });

    describe("position", () => {
        beforeEach(() => {
            sizeTooltips(30, 10);
        });

        test("places the tooltip on the sides of the element", (t) => {
            const reference = makeReference("span", { top: 100, left: 200, width: 40, height: 20 });
            const positions: Record<string, string> = {};
            for (const position of ["top", "right", "bottom", "left", "center"] as const) {
                const tooltip = new Tooltip("Foo", { referenceElement: reference, position, delay: 0 });
                tooltip.show();
                t.mock.timers.tick(0);
                positions[position] = `${tooltip.element.style.top},${tooltip.element.style.left}`;
                tooltip.remove();
            }
            assert.deepEqual(positions, {
                top: "90px,205px",
                right: "105px,240px",
                bottom: "120px,205px",
                left: "105px,170px",
                center: "105px,205px",
            });
        });

        test("places the tooltip at a point with the offset", (t) => {
            const tooltip = new Tooltip("Foo", { referenceElement: makeReference(), position: { x: 300, y: 200 }, offset: { x: 0, y: 16 }, delay: 0 });
            tooltip.show();
            t.mock.timers.tick(0);
            assert.equal(tooltip.element.style.top, "216px");
            assert.equal(tooltip.element.style.left, "300px");
        });

        test("keeps the tooltip inside the window", (t) => {
            const tooltip = new Tooltip("Foo", { referenceElement: makeReference("span", { top: 0, left: 0 }), position: "top", delay: 0 });
            tooltip.show();
            t.mock.timers.tick(0);
            assert.equal(tooltip.element.style.top, "4px");
            assert.equal(tooltip.element.style.left, "5px");

            const right = new Tooltip("Foo", { referenceElement: makeReference(), position: { x: window.innerWidth, y: 100 }, delay: 0 });
            right.show();
            t.mock.timers.tick(0);
            assert.equal(right.element.style.left, `${window.innerWidth - 30 - 4}px`);
        });
    });

    describe("removal", () => {
        test("removes the tooltip after the timeout", (t) => {
            new Tooltip("Foo", { referenceElement: makeReference(), delay: 0, timeout: 1000 }).show();
            t.mock.timers.tick(0);
            t.mock.timers.tick(1000);
            assert.equal(shown(), null);
        });

        test("removes the tooltip when the pointer leaves the element or the tooltip", (t) => {
            const reference = makeReference();
            const tooltip = new Tooltip("Foo", { referenceElement: reference, delay: 0 });
            tooltip.show();
            t.mock.timers.tick(0);
            reference.dispatchEvent(new MouseEvent("mouseout", { relatedTarget: tooltip.element }));
            assert.notEqual(shown(), null);
            tooltip.element.dispatchEvent(new MouseEvent("mouseout"));
            assert.equal(shown(), null);
            assert.equal(tooltip.removed, true);
        });

        test("keeps the tooltip when the pointer leaves, if asked", (t) => {
            const reference = makeReference();
            new Tooltip("Foo", { referenceElement: reference, delay: 0, removeOnMouseout: false }).show();
            t.mock.timers.tick(0);
            reference.dispatchEvent(new MouseEvent("mouseout"));
            assert.notEqual(shown(), null);
        });

        test("removes the tooltip when the element is pressed", (t) => {
            const reference = makeReference();
            new Tooltip("Foo", { referenceElement: reference, delay: 0 }).show();
            t.mock.timers.tick(0);
            reference.dispatchEvent(new MouseEvent("mousedown"));
            assert.equal(shown(), null);
        });

        test("removes the tooltip of a button when it loses the focus", (t) => {
            const reference = makeReference("button");
            new Tooltip("Foo", { referenceElement: reference, delay: 0, removeOnMouseout: false, removeOnMousedown: false }).show();
            t.mock.timers.tick(0);
            reference.dispatchEvent(new FocusEvent("blur"));
            assert.equal(shown(), null);
        });

        test("does not show a tooltip removed before its delay", (t) => {
            const tooltip = new Tooltip("Foo", { referenceElement: makeReference() });
            tooltip.show();
            tooltip.remove();
            t.mock.timers.tick(500);
            assert.equal(shown(), null);
        });
    });
});
