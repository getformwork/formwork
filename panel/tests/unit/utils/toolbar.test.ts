import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { initToolbar } from "../../../src/ts/utils/toolbar";
import { press } from "../../helpers/events";

/**
 * Make a toolbar in the document with the given HTML
 */
function makeToolbar(html: string, labelledBy?: string) {
    const toolbar = document.createElement("div");
    toolbar.innerHTML = html;
    document.body.append(toolbar);
    initToolbar(toolbar, labelledBy);
    return toolbar;
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

describe("Toolbar Utils", () => {
    test("makes the element a horizontal toolbar", () => {
        const toolbar = makeToolbar(buttons);
        assert.equal(toolbar.getAttribute("role"), "toolbar");
        assert.equal(toolbar.getAttribute("aria-orientation"), "horizontal");
        assert.equal(toolbar.hasAttribute("aria-labelledby"), false);
    });

    test("labels the toolbar", () => {
        assert.equal(makeToolbar(buttons, "label").getAttribute("aria-labelledby"), "label");
    });

    test("makes the first button the only tab stop", () => {
        assert.deepEqual(tabStops(makeToolbar(buttons)), ["first"]);
    });

    test("makes the first enabled button the tab stop", () => {
        assert.deepEqual(tabStops(makeToolbar('<button id="first" disabled></button><button id="second"></button>')), ["second"]);
    });

    test("moves between the enabled buttons with the arrows, wrapping around", () => {
        const toolbar = makeToolbar('<button id="first"></button><button id="second" disabled></button><button id="third"></button>');
        (toolbar.querySelector("#first") as HTMLElement).focus();

        assert.equal(press("ArrowRight").defaultPrevented, true);
        assert.equal(document.activeElement?.id, "third");
        press("ArrowRight");
        assert.equal(document.activeElement?.id, "first");
        press("ArrowLeft");
        assert.equal(document.activeElement?.id, "third");
    });

    test("moves to the first and last buttons with Home and End", () => {
        const toolbar = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();

        press("End");
        assert.equal(document.activeElement?.id, "third");
        press("Home");
        assert.equal(document.activeElement?.id, "first");
    });

    test("ignores other keys", () => {
        const toolbar = makeToolbar(buttons);
        (toolbar.querySelector("#first") as HTMLElement).focus();

        assert.equal(press("Enter").defaultPrevented, false);
        assert.equal(document.activeElement?.id, "first");
    });

    test("makes the focused button the tab stop", () => {
        const toolbar = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();
        assert.deepEqual(tabStops(toolbar), ["second"]);
    });

    test("ignores the buttons of dropdown menus", () => {
        const toolbar = makeToolbar('<button id="first"></button><div class="dropdown-menu"><button id="item"></button></div><button id="second"></button>');
        (toolbar.querySelector("#first") as HTMLElement).focus();

        press("ArrowRight");
        assert.equal(document.activeElement?.id, "second");
        assert.equal((toolbar.querySelector("#item") as HTMLElement).tabIndex, 0);
    });

    test("makes the buttons added later reachable", async () => {
        const toolbar = makeToolbar("");
        toolbar.insertAdjacentHTML("beforeend", buttons);
        await mutationsObserved();

        assert.deepEqual(tabStops(toolbar), ["first"]);
    });

    test("moves the tab stop when the focused button is disabled", async () => {
        const toolbar = makeToolbar(buttons);
        const second = toolbar.querySelector("#second") as HTMLButtonElement;
        second.focus();
        second.disabled = true;
        await mutationsObserved();

        assert.deepEqual(tabStops(toolbar), ["first"]);
    });

    test("preserves the focused button as tab stop when another is disabled", async () => {
        const toolbar = makeToolbar(buttons);
        (toolbar.querySelector("#second") as HTMLElement).focus();
        (toolbar.querySelector("#first") as HTMLButtonElement).disabled = true;
        await mutationsObserved();

        assert.deepEqual(tabStops(toolbar), ["second"]);
    });
});
