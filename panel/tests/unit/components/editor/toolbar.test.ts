import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { Toolbar } from "../../../../src/ts/components/editor/toolbar";
import type * as icons from "../../../../src/ts/components/ui/icons";
import { press } from "../../../helpers/events";

/**
 * Make a toolbar in the document with the given HTML
 */
function makeToolbar(html: string, labelledBy?: string) {
    const element = document.createElement("div");
    element.innerHTML = html;
    document.body.append(element);
    return new Toolbar(element, labelledBy);
}

/**
 * Get the ids of the buttons of a toolbar that are tab stops
 */
function tabStops(toolbar: HTMLElement) {
    return Array.from(toolbar.querySelectorAll("button"))
        .filter((button) => button.tabIndex === 0)
        .map((button) => button.id);
}

/**
 * Wait for the mutation observers to be notified
 */
const mutationsObserved = () => new Promise((resolve) => setTimeout(resolve));

const buttons = '<button id="first"></button><button id="second"></button><button id="third"></button>';

describe("Toolbar", () => {
    test("makes the element a horizontal toolbar", () => {
        const { element: toolbar } = makeToolbar(buttons);
        assert.equal(toolbar.getAttribute("role"), "toolbar");
        assert.equal(toolbar.getAttribute("aria-orientation"), "horizontal");
        assert.equal(toolbar.hasAttribute("aria-labelledby"), false);
    });

    test("labels the toolbar", () => {
        assert.equal(makeToolbar(buttons, "label").element.getAttribute("aria-labelledby"), "label");
    });

    test("makes the first button the only tab stop", () => {
        assert.deepEqual(tabStops(makeToolbar(buttons).element), ["first"]);
    });

    test("makes the first enabled button the tab stop", () => {
        assert.deepEqual(tabStops(makeToolbar('<button id="first" disabled></button><button id="second"></button>').element), ["second"]);
    });

    test("moves between the enabled buttons with the arrows, wrapping around", () => {
        const { element: toolbar } = makeToolbar('<button id="first"></button><button id="second" disabled></button><button id="third"></button>');
        (toolbar.querySelector("#first") as HTMLElement).focus();

        assert.equal(press("ArrowRight").defaultPrevented, true);
        assert.equal(document.activeElement?.id, "third");
        press("ArrowRight");
        assert.equal(document.activeElement?.id, "first");
        press("ArrowLeft");
        assert.equal(document.activeElement?.id, "third");
    });

    test("moves to the first and last buttons with Home and End", () => {
        const { element: toolbar } = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();

        press("End");
        assert.equal(document.activeElement?.id, "third");
        press("Home");
        assert.equal(document.activeElement?.id, "first");
    });

    test("ignores other keys", () => {
        const { element: toolbar } = makeToolbar(buttons);
        (toolbar.querySelector("#first") as HTMLElement).focus();

        assert.equal(press("Enter").defaultPrevented, false);
        assert.equal(document.activeElement?.id, "first");
    });

    test("makes the focused button the tab stop", () => {
        const { element: toolbar } = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();
        assert.deepEqual(tabStops(toolbar), ["second"]);
    });

    test("ignores the buttons of dropdown menus", () => {
        const { element: toolbar } = makeToolbar('<button id="first"></button><div class="dropdown-menu"><button id="item"></button></div><button id="second"></button>');
        (toolbar.querySelector("#first") as HTMLElement).focus();

        press("ArrowRight");
        assert.equal(document.activeElement?.id, "second");
        assert.equal((toolbar.querySelector("#item") as HTMLElement).tabIndex, 0);
    });

    test("moves the tab stop when the focused button is disabled", async () => {
        const { element: toolbar } = makeToolbar(buttons);
        const second = toolbar.querySelector("#second") as HTMLButtonElement;
        second.focus();
        second.disabled = true;
        await mutationsObserved();

        assert.deepEqual(tabStops(toolbar), ["first"]);
    });

    test("preserves the focused button as tab stop when another is disabled", async () => {
        const { element: toolbar } = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();
        (toolbar.querySelector("#first") as HTMLButtonElement).disabled = true;
        await mutationsObserved();

        assert.deepEqual(tabStops(toolbar), ["second"]);
    });

    describe("add", () => {
        test("makes the buttons added reachable", () => {
            const toolbar = makeToolbar("");
            const group = document.createElement("div");
            group.innerHTML = buttons;
            toolbar.add(group);
            assert.deepEqual(tabStops(toolbar.element), ["first"]);
        });

        test("adds the buttons at the end or at the start", () => {
            const toolbar = makeToolbar('<button id="second"></button>');
            toolbar.add(Object.assign(document.createElement("button"), { id: "third" }));
            toolbar.add(Object.assign(document.createElement("button"), { id: "first" }), "afterbegin");
            assert.deepEqual(
                Array.from(toolbar.element.children, (child) => child.id),
                ["first", "second", "third"],
            );
        });
    });

    describe("remove", () => {
        test("moves the tab stop when the focused button is removed", () => {
            const toolbar = makeToolbar(buttons);
            const second = toolbar.element.querySelector("#second") as HTMLButtonElement;
            second.focus();
            toolbar.remove(second);
            assert.deepEqual(tabStops(toolbar.element), ["first"]);
        });
    });

    describe("createButton", () => {
        test("creates a button with an icon, a tooltip and a label", () => {
            const button = makeToolbar("").createButton("bold", "Bold");
            assert.equal(button.tagName, "BUTTON");
            assert.equal(button.type, "button");
            assert.equal(button.className, "button toolbar-button");
            assert.equal(button.dataset.tooltip, "Bold");
            assert.equal(button.ariaLabel, "Bold");
            assert.notEqual(button.querySelector("svg.icon"), null);
        });

        test("creates a button without an icon for unknown icons", () => {
            const button = makeToolbar("").createButton("missing" as keyof typeof icons, "Foo");
            assert.equal(button.innerHTML, "");
        });
    });
});
