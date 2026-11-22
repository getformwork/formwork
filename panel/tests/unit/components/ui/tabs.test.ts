import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { Tabs } from "../../../../src/ts/components/ui/tabs";
import { byId, press } from "../../../helpers/events";

/**
 * Add a form with tabs to the document
 */
function addTabs(form = "page") {
    document.body.insertAdjacentHTML(
        "beforeend",
        `<form ${form ? `data-form="${form}"` : ""}>
            <div class="tabs" role="tablist">
                <button type="button" class="tabs-tab active" id="${form}-first" data-tab="${form}-first" tabindex="0">First</button>
                <button type="button" class="tabs-tab" id="${form}-second" data-tab="${form}-second" tabindex="-1">Second</button>
                <button type="button" class="tabs-tab" id="${form}-third" data-tab="${form}-third" tabindex="-1">Third</button>
            </div>
            <div class="tabs-panel visible" data-tab="${form}-first"></div>
            <div class="tabs-panel" data-tab="${form}-second"></div>
            <div class="tabs-panel" data-tab="${form}-third"></div>
        </form>`,
    );
}

/**
 * Get the name of the selected tab and of the visible panels
 */
function selection() {
    const selected = Array.from(document.querySelectorAll<HTMLElement>(".tabs-tab.active"), (tab) => `${tab.dataset.tab}:${tab.ariaSelected}:${tab.tabIndex}`);
    const visible = Array.from(document.querySelectorAll<HTMLElement>(".tabs-panel.visible"), (panel) => panel.dataset.tab);
    return { selected, visible };
}

describe("Tabs", () => {
    test("preserves the tab selected by the markup", () => {
        addTabs();
        new Tabs();
        assert.deepEqual(
            Array.from(document.querySelectorAll<HTMLElement>(".tabs-tab.active"), (tab) => tab.id),
            ["page-first"],
        );
        assert.deepEqual(selection().visible, ["page-first"]);
    });

    test("selects a tab when clicked, showing its panel", () => {
        addTabs();
        new Tabs();
        byId("page-second").click();
        assert.deepEqual(selection(), { selected: ["page-second:true:0"], visible: ["page-second"] });
        assert.equal(byId("page-first").ariaSelected, "false");
        assert.equal(byId("page-first").tabIndex, -1);
    });

    test("focuses a tab when clicked, so the arrows work", () => {
        addTabs();
        new Tabs();
        byId("page-second").click();
        assert.equal(document.activeElement?.id, "page-second");
    });

    test("selects the next and previous tabs with the arrows, wrapping around", () => {
        addTabs();
        new Tabs();
        assert.equal(press("ArrowRight", { target: byId("page-first") }).defaultPrevented, true);
        assert.equal(document.activeElement?.id, "page-second");
        assert.deepEqual(selection().visible, ["page-second"]);
        press("ArrowLeft", { target: byId("page-second") });
        press("ArrowLeft", { target: byId("page-first") });
        assert.equal(document.activeElement?.id, "page-third");
        assert.deepEqual(selection().visible, ["page-third"]);
    });

    test("selects the first and last tabs with Home and End", () => {
        addTabs();
        new Tabs();
        press("End", { target: byId("page-first") });
        assert.deepEqual(selection().visible, ["page-third"]);
        press("Home", { target: byId("page-third") });
        assert.deepEqual(selection().visible, ["page-first"]);
    });

    test("ignores other keys", () => {
        addTabs();
        new Tabs();
        assert.equal(press("Enter", { target: byId("page-first") }).defaultPrevented, false);
    });

    test("restores the tab selected before", () => {
        addTabs();
        new Tabs();
        byId("page-second").click();

        document.body.replaceChildren();
        addTabs();
        new Tabs();
        assert.deepEqual(selection().visible, ["page-second"]);
    });

    test("forgets a saved tab that does not exist anymore", () => {
        window.localStorage.setItem("formwork.tabStatus[page]", "page-missing");
        addTabs();
        new Tabs();
        assert.deepEqual(selection().visible, ["page-first"]);
        assert.equal(window.localStorage.getItem("formwork.tabStatus[page]"), null);
    });

    test("remembers the selected tab of each form", () => {
        addTabs("page");
        addTabs("site");
        new Tabs();
        byId("page-second").click();
        byId("site-third").click();
        assert.equal(window.localStorage.getItem("formwork.tabStatus[page]"), "page-second");
        assert.equal(window.localStorage.getItem("formwork.tabStatus[site]"), "site-third");
    });

    test("does not share the selected tab between forms without a name", () => {
        addTabs("");
        new Tabs();
        byId("-second").click();
        assert.deepEqual(selection().visible, ["-second"]);
        assert.equal(window.localStorage.length, 0);
    });

    test("works without access to the local storage", (t) => {
        t.mock.method(Storage.prototype, "getItem", () => {
            throw new DOMException("The operation is insecure.", "SecurityError");
        });
        t.mock.method(Storage.prototype, "setItem", () => {
            throw new DOMException("The operation is insecure.", "SecurityError");
        });
        addTabs();
        new Tabs();
        byId("page-second").click();
        assert.deepEqual(selection().visible, ["page-second"]);
    });
});
