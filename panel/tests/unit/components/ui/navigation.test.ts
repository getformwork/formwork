import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { Navigation } from "../../../../src/ts/components/ui/navigation";
import { byId, press } from "../../../helpers/events";

/**
 * Add the layout of the panel to the document and handle its navigation
 */
function makeNavigation({ save = true } = {}) {
    document.body.innerHTML = `
        <header class="panel-header"><button type="button" class="button sidebar-toggle" id="toggle">Menu</button></header>
        <div class="sidebar show-from-md"><a href="/panel/pages/" id="pages">Pages</a></div>
        <main class="panel-main">${save ? '<button type="submit" data-command="save" id="save">Save</button>' : ""}</main>`;
    new Navigation();
}

const sidebar = () => document.querySelector(".sidebar") as HTMLElement;

const backdrop = () => document.querySelector(".sidebar-backdrop") as HTMLElement | null;

const isOpen = () => sidebar().classList.contains("show");

describe("Navigation", () => {
    describe("sidebar", () => {
        beforeEach(() => {
            makeNavigation();
        });

        test("links the toggle to the sidebar", () => {
            assert.equal(sidebar().id, "panel-sidebar");
            assert.equal(byId("toggle").getAttribute("aria-controls"), "panel-sidebar");
            assert.equal(byId("toggle").getAttribute("aria-expanded"), "false");
        });

        test("opens the sidebar with a backdrop, making the rest of the page inert", () => {
            byId("toggle").click();
            assert.equal(isOpen(), true);
            assert.equal(byId("toggle").getAttribute("aria-expanded"), "true");
            assert.notEqual(backdrop(), null);
            assert.equal((document.querySelector(".panel-main") as HTMLElement).inert, true);
            assert.equal((document.querySelector(".panel-header") as HTMLElement).inert, true);
        });

        test("closes the sidebar with the toggle", () => {
            byId("toggle").click();
            byId("toggle").click();
            assert.equal(isOpen(), false);
            assert.equal(backdrop(), null);
            assert.equal((document.querySelector(".panel-main") as HTMLElement).inert, false);
        });

        test("closes the sidebar when the backdrop is clicked", () => {
            byId("toggle").click();
            backdrop()?.click();
            assert.equal(isOpen(), false);
            assert.equal(backdrop(), null);
        });

        test("closes the sidebar with Escape, giving the focus back to the toggle", () => {
            byId("toggle").click();
            byId("pages").focus();
            press("Escape");
            assert.equal(isOpen(), false);
            assert.ok(document.activeElement === byId("toggle"));
        });

        test("closes the sidebar when the window shows it always", (t) => {
            byId("toggle").click();
            window.dispatchEvent(new Event("resize"));
            assert.equal(isOpen(), true);
            // The toggle is hidden on large windows
            t.mock.method(byId("toggle"), "getClientRects", () => []);
            window.dispatchEvent(new Event("resize"));
            assert.equal(isOpen(), false);
        });
    });

    describe("save shortcut", () => {
        test("saves with Ctrl+S and Cmd+S", () => {
            makeNavigation();
            const save = mock.fn((event: Event) => event.preventDefault());
            byId("save").addEventListener("click", save);
            assert.equal(press("s", { ctrlKey: true }).defaultPrevented, true);
            press("s", { metaKey: true });
            assert.equal(save.mock.callCount(), 2);
        });

        test("ignores the shortcut with Alt", () => {
            makeNavigation();
            const save = mock.fn((event: Event) => event.preventDefault());
            byId("save").addEventListener("click", save);
            assert.equal(press("s", { ctrlKey: true, altKey: true }).defaultPrevented, false);
            assert.equal(save.mock.callCount(), 0);
        });

        test("leaves the shortcut to the browser without a save command", () => {
            makeNavigation({ save: false });
            assert.equal(press("s", { ctrlKey: true }).defaultPrevented, false);
        });
    });
});
