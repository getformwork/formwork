import "../../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, mock, test } from "node:test";
import { Dropdowns } from "../../../../src/ts/components/ui/dropdowns";
import { byId, press } from "../../../helpers/events";

// The text typed in the menus is forgotten after half a second, also by the next tests
beforeEach(() => {
    mock.timers.enable({ apis: ["setTimeout"] });
});

afterEach(() => {
    mock.timers.tick(500);
    mock.timers.reset();
});

const isOpen = (id: string) => getComputedStyle(byId(id)).display !== "none";

/**
 * Click an element with the mouse (`detail` 1) or with the keyboard (`detail` 0)
 */
function click(id: string, { keyboard = false } = {}) {
    const event = new MouseEvent("click", { detail: keyboard ? 0 : 1, bubbles: true, cancelable: true });
    byId(id).dispatchEvent(event);
    return event;
}

const focused = () => document.activeElement?.id;

describe("Dropdowns", () => {
    beforeEach(() => {
        // The menus are hidden by the stylesheet of the panel
        document.head.innerHTML = "<style>.dropdown-menu { display: none; }</style>";
        document.body.innerHTML = `
            <div class="dropdown">
                <button class="dropdown-button" id="button" data-dropdown="menu">Menu</button>
                <div class="dropdown-menu" id="menu">
                    <a class="dropdown-item" id="first" href="#first">First</a>
                    <button class="dropdown-item" id="disabled" disabled>Disabled</button>
                    <button class="dropdown-item" id="second">Second</button>
                    <button class="dropdown-item" id="aria-disabled" aria-disabled="true">Disabled</button>
                    <button class="dropdown-item" id="third">Third</button>
                </div>
            </div>
            <div class="dropdown">
                <button class="dropdown-button" id="other-button" data-dropdown="other-menu">Other</button>
                <div class="dropdown-menu" id="other-menu"><button class="dropdown-item" id="other-item">Other</button></div>
            </div>
            <button id="outside">Outside</button>
        `;
        new Dropdowns();
    });

    test("links the buttons to their menus", () => {
        const button = byId("button");
        assert.equal(button.getAttribute("aria-haspopup"), "menu");
        assert.equal(button.getAttribute("aria-controls"), "menu");
        assert.equal(button.getAttribute("aria-expanded"), "false");
    });

    test("opens the menu when the button is clicked", () => {
        const event = click("button");
        assert.equal(isOpen("menu"), true);
        assert.equal(event.defaultPrevented, true);
        assert.equal(byId("button").getAttribute("aria-expanded"), "true");
        assert.equal(focused(), "button");
    });

    test("makes the menu an ARIA menu reachable with the arrows", () => {
        click("button");
        assert.equal(byId("menu").getAttribute("role"), "menu");
        for (const item of byId("menu").querySelectorAll<HTMLElement>(".dropdown-item")) {
            assert.equal(item.getAttribute("role"), "menuitem");
            assert.equal(item.tabIndex, -1);
        }
    });

    test("focuses the first item when the button is clicked with the keyboard", () => {
        click("button", { keyboard: true });
        assert.equal(focused(), "first");
    });

    test("closes the menu when the button is clicked again", () => {
        click("button");
        click("button");
        assert.equal(isOpen("menu"), false);
        assert.equal(byId("button").getAttribute("aria-expanded"), "false");
    });

    test("closes the menu when something else is clicked", () => {
        click("button");
        click("outside");
        assert.equal(isOpen("menu"), false);
    });

    test("closes the other menus when a menu is opened", () => {
        click("button");
        click("other-button");
        assert.equal(isOpen("menu"), false);
        assert.equal(isOpen("other-menu"), true);
        assert.equal(byId("button").getAttribute("aria-expanded"), "false");
    });

    test("closes the menu when the focus moves out of it", () => {
        click("button");
        byId("outside").focus();
        assert.equal(isOpen("menu"), false);
    });

    test("removes the tooltips covering the menu when it is opened", () => {
        document.body.insertAdjacentHTML("beforeend", '<div class="tooltip">Menu</div>');
        click("button");
        assert.equal(document.querySelectorAll(".tooltip").length, 0);
    });

    test("gives the focus back to the button when an item is chosen with the keyboard", () => {
        click("button", { keyboard: true });
        click("second", { keyboard: true });
        assert.equal(isOpen("menu"), false);
        assert.equal(focused(), "button");
    });

    test("sets up the buttons created after the page is loaded when they get the focus", () => {
        document.body.insertAdjacentHTML("beforeend", '<div class="dropdown"><button class="dropdown-button" id="new-button" data-dropdown="other-menu">New</button></div>');
        byId("new-button").focus();
        assert.equal(byId("new-button").getAttribute("aria-controls"), "other-menu");
    });

    describe("button keyboard", () => {
        test("opens the menu focusing the first item with ArrowDown", () => {
            byId("button").focus();
            assert.equal(press("ArrowDown").defaultPrevented, true);
            assert.equal(isOpen("menu"), true);
            assert.equal(focused(), "first");
        });

        test("opens the menu focusing the last item with ArrowUp", () => {
            byId("button").focus();
            press("ArrowUp");
            assert.equal(focused(), "third");
        });

        test("closes the menu with Escape", () => {
            click("button");
            press("Escape");
            assert.equal(isOpen("menu"), false);
        });

        test("moves to the item starting with a typed character in a menu opened with the mouse", () => {
            click("button");
            assert.equal(focused(), "button");
            assert.equal(press("s").defaultPrevented, true);
            assert.equal(focused(), "second");
        });

        test("ignores typed characters while the menu is closed", () => {
            byId("button").focus();
            assert.equal(press("s").defaultPrevented, false);
            assert.equal(focused(), "button");
        });

        test("leaves Space to the button", () => {
            click("button");
            assert.equal(press(" ").defaultPrevented, false);
        });
    });

    describe("menu keyboard", () => {
        beforeEach(() => {
            click("button", { keyboard: true });
        });

        test("moves between the enabled items with the arrows, wrapping around", () => {
            assert.equal(press("ArrowDown").defaultPrevented, true);
            assert.equal(focused(), "second");
            press("ArrowDown");
            assert.equal(focused(), "third");
            press("ArrowDown");
            assert.equal(focused(), "first");
            press("ArrowUp");
            assert.equal(focused(), "third");
        });

        test("moves to the first and last items with Home and End", () => {
            press("End");
            assert.equal(focused(), "third");
            press("Home");
            assert.equal(focused(), "first");
        });

        test("closes the menu with Escape, giving the focus back to the button", () => {
            assert.equal(press("Escape").defaultPrevented, true);
            assert.equal(isOpen("menu"), false);
            assert.equal(focused(), "button");
        });

        test("closes the menu with Tab, moving on from the button", () => {
            assert.equal(press("Tab").defaultPrevented, false);
            assert.equal(isOpen("menu"), false);
            assert.equal(focused(), "button");
        });

        test("chooses links with Space", (t) => {
            const linkClick = t.mock.method(byId("first"), "click");
            assert.equal(press(" ").defaultPrevented, true);
            assert.equal(linkClick.mock.callCount(), 1);
        });

        test("leaves Space to the buttons", () => {
            press("ArrowDown");
            assert.equal(press(" ").defaultPrevented, false);
        });
    });

    describe("typing", () => {
        beforeEach(() => {
            byId("menu").innerHTML = `
                <button class="dropdown-item" id="foo">Foo</button>
                <button class="dropdown-item" id="bar">
                    Bar
                </button>
                <button class="dropdown-item" id="disabled" aria-disabled="true">Disabled</button>
                <button class="dropdown-item" id="baz">Baz</button>
                <button class="dropdown-item" id="qux">Qux</button>`;
            click("button", { keyboard: true });
        });

        test("moves to the next item starting with a typed character", () => {
            assert.equal(press("b").defaultPrevented, true);
            assert.equal(focused(), "bar");
        });

        test("ignores case and surrounding whitespace", () => {
            press("B");
            assert.equal(focused(), "bar");
        });

        test("cycles through the items starting with a repeated character", () => {
            press("b");
            assert.equal(focused(), "bar");
            press("b");
            assert.equal(focused(), "baz");
            press("b");
            assert.equal(focused(), "bar");
        });

        test("moves to the item starting with the typed text", () => {
            press("b");
            press("a");
            assert.equal(focused(), "bar");
            press("z");
            assert.equal(focused(), "baz");
        });

        test("wraps around looking for the typed character", () => {
            press("End");
            press("b");
            assert.equal(focused(), "bar");
        });

        test("looks from the first item when the focused one is disabled", () => {
            byId("disabled").focus();
            press("q");
            assert.equal(focused(), "qux");
        });

        test("keeps the focus without matches", () => {
            assert.equal(press("x").defaultPrevented, true);
            assert.equal(focused(), "foo");
        });

        test("ignores typed characters with modifiers", () => {
            assert.equal(press("b", { ctrlKey: true }).defaultPrevented, false);
            assert.equal(focused(), "foo");
        });

        test("forgets the typed text after half a second", () => {
            press("b");
            mock.timers.tick(500);
            press("f");
            assert.equal(focused(), "foo");
        });

        test("keeps the typed text while typing within half a second", () => {
            press("b");
            mock.timers.tick(400);
            press("a");
            mock.timers.tick(400);
            press("z");
            assert.equal(focused(), "baz");
        });
    });

    test("skips the items that are not displayed", (t) => {
        // happy-dom has no layout, so the items always have client rects
        t.mock.method(Element.prototype, "getClientRects", function (this: Element) {
            return getComputedStyle(this).display === "none" ? [] : [{}];
        });
        byId("second").style.display = "none";
        click("button", { keyboard: true });
        press("ArrowDown");
        assert.equal(focused(), "third");
    });

    test("ignores the keyboard in menus without a button", () => {
        document.body.insertAdjacentHTML("beforeend", '<div class="dropdown-menu" id="lonely" style="display: block"><button class="dropdown-item" id="lonely-first">First</button><button class="dropdown-item">Second</button></div>');
        byId("lonely-first").focus();
        assert.equal(press("ArrowDown").defaultPrevented, false);
        assert.equal(focused(), "lonely-first");
    });
});
