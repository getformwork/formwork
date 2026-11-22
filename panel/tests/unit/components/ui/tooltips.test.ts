import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { Tooltips } from "../../../../src/ts/components/ui/tooltips";
import { byId } from "../../../helpers/events";

beforeEach((t) => {
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
});

/**
 * Add the given HTML to the document and handle its tooltips
 */
function makeTooltips(html: string) {
    document.body.innerHTML = html;
    return new Tooltips();
}

const shown = () => Array.from(document.querySelectorAll(".tooltip"), (tooltip) => tooltip.textContent);

/**
 * Move the pointer over an element, waiting for the tooltip
 */
function hover(t: { mock: { timers: { tick(ms: number): void } } }, target: HTMLElement, x = 100, y = 50) {
    const event = new MouseEvent("mousemove", { bubbles: true, clientX: x, clientY: y });
    // happy-dom does not set the position in the page from the one in the window, unlike browsers
    Object.defineProperties(event, { pageX: { value: x }, pageY: { value: y } });
    target.dispatchEvent(event);
    t.mock.timers.tick(500);
    t.mock.timers.tick(0);
}

/**
 * Move the focus to an element, waiting for the tooltip
 */
function focus(t: { mock: { timers: { tick(ms: number): void } } }, target: HTMLElement) {
    target.focus();
    t.mock.timers.tick(0);
}

describe("Tooltips", () => {
    test("turns the titles into tooltips", () => {
        makeTooltips('<button type="button" id="foo" title="Foo">Foo</button>');
        assert.equal(byId("foo").hasAttribute("title"), false);
        assert.equal(byId("foo").dataset.tooltip, "Foo");
    });

    test("shows the tooltip of the element under the pointer, at the pointer", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip"><b id="inner">Foo</b></span>');
        hover(t, byId("inner"), 100, 50);
        assert.deepEqual(shown(), ["Foo tooltip"]);
        const tooltip = document.querySelector(".tooltip") as HTMLElement;
        assert.equal(tooltip.style.top, "66px");
        assert.equal(tooltip.style.left, "100px");
    });

    test("waits for the pointer to stop", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip">Foo</span>');
        byId("foo").dispatchEvent(new MouseEvent("mousemove", { bubbles: true }));
        t.mock.timers.tick(400);
        assert.deepEqual(shown(), []);
    });

    test("shows one tooltip at a time", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip">Foo</span><span id="bar" data-tooltip="Bar tooltip">Bar</span>');
        hover(t, byId("foo"));
        hover(t, byId("bar"));
        assert.deepEqual(shown(), ["Bar tooltip"]);
    });

    test("keeps the tooltip while the pointer moves on its element", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip">Foo</span>');
        hover(t, byId("foo"), 100, 50);
        hover(t, byId("foo"), 110, 50);
        assert.deepEqual(shown(), ["Foo tooltip"]);
        assert.equal((document.querySelector(".tooltip") as HTMLElement).style.left, "100px");
    });

    test("does not cover the open menu of a button", (t) => {
        makeTooltips('<button type="button" id="foo" data-tooltip="Foo tooltip" aria-expanded="true">Foo</button>');
        hover(t, byId("foo"));
        assert.deepEqual(shown(), []);
    });

    test("does not show the tooltips of the page behind an open modal", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip">Foo</span><div class="modal open"><span id="bar" data-tooltip="Bar tooltip">Bar</span></div>');
        hover(t, byId("foo"));
        assert.deepEqual(shown(), []);
        hover(t, byId("bar"));
        assert.deepEqual(shown(), ["Bar tooltip"]);
    });

    test("shows the whole text of a truncated element", (t) => {
        makeTooltips('<div class="truncate" id="foo"> Foo bar baz </div><div class="truncate" id="bar">Bar</div>');
        // happy-dom has no layout, unlike browsers
        Object.defineProperty(byId("foo"), "scrollWidth", { value: 200 });
        Object.defineProperty(byId("foo"), "offsetWidth", { value: 100 });
        hover(t, byId("foo"));
        assert.deepEqual(shown(), ["Foo bar baz"]);
        hover(t, byId("bar"));
        assert.deepEqual(shown(), ["Foo bar baz"]);
    });

    test("ignores the pointer on elements removed meanwhile", (t) => {
        makeTooltips('<span id="foo" data-tooltip="Foo tooltip">Foo</span>');
        byId("foo").dispatchEvent(new MouseEvent("mousemove", { bubbles: true }));
        byId("foo").remove();
        t.mock.timers.tick(500);
        assert.deepEqual(shown(), []);
    });

    describe("focus", () => {
        test("shows the tooltip of a focused button below it", (t) => {
            makeTooltips('<button type="button" id="foo" data-tooltip="Foo tooltip">Foo</button><span id="bar" data-tooltip="Bar tooltip" tabindex="0">Bar</span>');
            focus(t, byId("bar"));
            assert.deepEqual(shown(), []);
            focus(t, byId("foo"));
            assert.deepEqual(shown(), ["Foo tooltip"]);
        });

        test("removes the tooltip of a button when it loses the focus", (t) => {
            makeTooltips('<button type="button" id="foo" data-tooltip="Foo tooltip">Foo</button>');
            focus(t, byId("foo"));
            byId("foo").blur();
            assert.deepEqual(shown(), []);
        });

        test("shows the tooltip of a button that gets the focus again", (t) => {
            makeTooltips('<button type="button" id="foo" data-tooltip="Foo tooltip">Foo</button><button type="button" id="bar">Bar</button>');
            focus(t, byId("foo"));
            focus(t, byId("bar"));
            focus(t, byId("foo"));
            assert.deepEqual(shown(), ["Foo tooltip"]);
        });
    });
});
