import "../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, describe, mock, test } from "node:test";
import { Modal } from "../../../src/ts/services/modal";
import { byId, press } from "../../helpers/events";
import { resetModals } from "../../helpers/panel";

afterEach(() => {
    resetModals();
});

/**
 * Add a modal to the document and make it
 */
function makeModal(id = "modal", content = '<input id="first"><input id="second"><button type="button" id="last" data-dismiss>Close</button>') {
    document.body.insertAdjacentHTML("beforeend", `<div class="modal" id="${id}"><div class="modal-container">${content}</div></div>`);
    return new Modal(document.getElementById(id) as HTMLElement);
}

const focused = () => document.activeElement?.id;

const hasBackdrop = () => document.querySelector(".modal-backdrop") !== null;

describe("Modal", () => {
    describe("opening", () => {
        test("opens as a modal dialog with a backdrop", () => {
            const modal = makeModal();
            modal.open();
            assert.equal(modal.isOpen, true);
            assert.equal(modal.element.classList.contains("open"), true);
            // happy-dom does not reflect the ARIA properties set by the modal to the attributes
            assert.equal(modal.element.role, "dialog");
            assert.equal(modal.element.ariaModal, "true");
            assert.equal(hasBackdrop(), true);
        });

        test("focuses the first focusable element", () => {
            makeModal().open();
            assert.equal(focused(), "first");
        });

        test("opens when a trigger is clicked, setting the action of its form", () => {
            document.body.innerHTML = '<button type="button" id="trigger" data-modal="modal" data-modal-action="/panel/foo/">Open</button>';
            const modal = makeModal("modal", '<form><input name="foo"></form>');
            const open = mock.fn();
            modal.onOpen(open);
            byId("trigger").click();
            assert.equal(modal.isOpen, true);
            assert.equal(modal.form?.element.getAttribute("action"), "/panel/foo/");
            assert.ok(open.mock.calls[0].arguments[1] === byId("trigger"));
        });

        test("calls the callbacks before and after opening", () => {
            const modal = makeModal();
            const calls: string[] = [];
            modal.onBeforeOpen((openedModal) => calls.push(`before:${openedModal.isOpen}`));
            modal.onOpen((openedModal) => calls.push(`open:${openedModal.isOpen}`));
            modal.open();
            assert.deepEqual(calls, ["before:false", "open:true"]);
        });

        test("removes the tooltips", () => {
            document.body.innerHTML = '<div class="tooltip">Foo</div>';
            makeModal().open();
            assert.equal(document.querySelectorAll(".tooltip").length, 0);
        });

        test("makes a form for the form of the modal", () => {
            const modal = makeModal("modal", '<form><input name="foo" value="bar"></form>');
            assert.equal(modal.form?.inputs.foo.value, "bar");
        });

        test("sets the message of the modal as text", () => {
            const modal = makeModal("modal", '<p class="modal-text"></p>');
            modal.setMessage("<b>Delete?</b>");
            assert.equal(modal.element.querySelector(".modal-text")?.textContent, "<b>Delete?</b>");
            assert.equal(modal.element.querySelector("b"), null);
        });
    });

    describe("closing", () => {
        test("closes removing the backdrop", () => {
            const modal = makeModal();
            modal.open();
            modal.close();
            assert.equal(modal.isClosed, true);
            assert.equal(modal.element.classList.contains("open"), false);
            assert.equal(hasBackdrop(), false);
        });

        test("calls the callbacks before and after closing", () => {
            const modal = makeModal();
            const calls: string[] = [];
            modal.onBeforeClose((closedModal) => calls.push(`before:${closedModal.isOpen}`));
            modal.onClose((closedModal) => calls.push(`close:${closedModal.isOpen}`));
            modal.open();
            modal.close();
            assert.deepEqual(calls, ["before:true", "close:false"]);
        });

        test("closes with the dismiss button", () => {
            const modal = makeModal();
            modal.open();
            byId("last").click();
            assert.equal(modal.isClosed, true);
        });

        test("closes with Escape", () => {
            const modal = makeModal();
            modal.open();
            press("Escape", { type: "keyup" });
            assert.equal(modal.isClosed, true);
        });

        test("closes when the backdrop is clicked", () => {
            const modal = makeModal();
            modal.open();
            modal.element.dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
            modal.element.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
            assert.equal(modal.isClosed, true);
        });

        test("does not close when a press inside the content is released on the backdrop", () => {
            const modal = makeModal();
            modal.open();
            byId("first").dispatchEvent(new MouseEvent("mousedown", { bubbles: true }));
            modal.element.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
            assert.equal(modal.isOpen, true);
        });

        test("gives the focus back to the trigger", () => {
            document.body.innerHTML = '<button type="button" id="trigger" data-modal="modal">Open</button>';
            const modal = makeModal();
            byId("trigger").focus();
            byId("trigger").click();
            modal.close();
            assert.equal(focused(), "trigger");
        });

        test("gives the focus back to the button of the menu of the trigger", () => {
            document.body.innerHTML = '<button type="button" id="menu-button" data-dropdown="menu">Menu</button><div class="dropdown-menu" id="menu"><button type="button" id="trigger" data-modal="modal">Open</button></div>';
            const modal = makeModal();
            byId("trigger").click();
            modal.close();
            assert.equal(focused(), "menu-button");
        });

        test("preserves the element to focus when opened again", () => {
            document.body.innerHTML = '<button type="button" id="trigger">Open</button>';
            const modal = makeModal();
            byId("trigger").focus();
            modal.open();
            modal.open();
            modal.close();
            assert.equal(focused(), "trigger");
        });
    });

    describe("commands", () => {
        test("calls the command of a button with the button", () => {
            const modal = makeModal("modal", '<button type="button" id="confirm" data-command="confirm">Confirm</button>');
            const command = mock.fn();
            modal.onCommand("confirm", command);
            byId("confirm").click();
            assert.equal(command.mock.callCount(), 1);
            assert.ok(command.mock.calls[0].arguments[0] === modal);
            assert.ok(command.mock.calls[0].arguments[1] === byId("confirm"));
        });

        test("triggers commands from scripts", () => {
            const modal = makeModal();
            const command = mock.fn();
            modal.onCommand("confirm", command);
            modal.triggerCommand("confirm");
            assert.equal(command.mock.callCount(), 1);
        });
    });

    describe("focus", () => {
        test("moves the focus from the last element to the first with Tab", () => {
            makeModal().open();
            byId("last").focus();
            assert.equal(press("Tab").defaultPrevented, true);
            assert.equal(focused(), "first");
        });

        test("moves the focus from the first element to the last with Shift+Tab", () => {
            makeModal().open();
            assert.equal(press("Tab", { shiftKey: true }).defaultPrevented, true);
            assert.equal(focused(), "last");
        });

        test("leaves Tab to the browser between the elements", () => {
            makeModal().open();
            assert.equal(press("Tab").defaultPrevented, false);
        });

        test("keeps the focus inside the open modal", () => {
            document.body.innerHTML = '<button type="button" id="outside">Outside</button>';
            makeModal().open();
            byId("outside").focus();
            assert.equal(focused(), "first");
        });

        test("does not keep the focus inside a closed modal", () => {
            document.body.innerHTML = '<button type="button" id="outside">Outside</button>';
            const modal = makeModal();
            modal.open();
            modal.close();
            byId("outside").focus();
            assert.equal(focused(), "outside");
        });
    });

    describe("nested modals", () => {
        test("keeps the focus inside the topmost modal", () => {
            document.body.innerHTML = '<button type="button" id="outside">Outside</button>';
            makeModal("first-modal", '<input id="first-input">').open();
            makeModal("second-modal", '<input id="second-input">').open();
            byId("outside").focus();
            byId("first-input").focus();
            assert.equal(focused(), "second-input");
        });

        test("focuses a modal opened over another", () => {
            makeModal("first-modal", '<input id="first-input">').open();
            makeModal("second-modal", '<input id="second-input">').open();
            assert.equal(focused(), "second-input");
        });

        test("removes the backdrop when the last modal is closed", () => {
            const first = makeModal("first-modal", '<input id="first-input">');
            const second = makeModal("second-modal", '<input id="second-input">');
            first.open();
            second.open();
            second.close();
            first.close();
            assert.equal(hasBackdrop(), false);
        });
    });
});
