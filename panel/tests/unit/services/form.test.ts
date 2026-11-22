import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { Form } from "../../../src/ts/services/form";
import type { Modal } from "../../../src/ts/services/modal";
import { leavePage, setTranslation } from "../../helpers/panel";

/**
 * Stub of the modal asking to confirm leaving the page, recording its callbacks
 */
function makeChangesModal() {
    const element = document.createElement("div");
    element.innerHTML = '<button data-command="continue"></button>';
    const commands: Record<string, (modal: Modal, button?: HTMLElement) => void> = {};
    const openCallbacks: ((modal: Modal) => void)[] = [];
    const modal = {
        element,
        commands,
        onCommand: (command: string, callback: (modal: Modal, button?: HTMLElement) => void) => {
            commands[command] = callback;
        },
        onOpen: (callback: (modal: Modal) => void) => {
            openCallbacks.push(callback);
        },
        open: mock.fn(() => {
            for (const callback of openCallbacks) {
                callback(modal as unknown as Modal);
            }
        }),
    };
    return modal;
}

let changesModal: ReturnType<typeof makeChangesModal>;

beforeEach(() => {
    changesModal = makeChangesModal();
    app.modals = { changesModal: changesModal as unknown as Modal };
});

/**
 * Make a form in the document with the given HTML
 */
function makeForm(html: string, options = {}) {
    document.body.insertAdjacentHTML("beforeend", `<form data-form="test">${html}</form>`);
    return new Form(document.querySelector("form") as HTMLFormElement, options);
}

const input = (id: string) => document.getElementById(id) as HTMLInputElement;

/**
 * Click a link with the given modifiers and return whether following it was prevented
 */
function clickLink(id: string, init: MouseEventInit = {}) {
    const event = new MouseEvent("click", { bubbles: true, cancelable: true, ...init });
    document.getElementById(id)?.dispatchEvent(event);
    return event.defaultPrevented;
}

describe("Form", () => {
    describe("inputs", () => {
        test("loads the named native fields", () => {
            const form = makeForm('<input name="foo" value="bar"><select name="baz"><option value="qux"></option></select><textarea name="text"></textarea><input value="unnamed">');
            assert.deepEqual(Object.keys(form.inputs), ["foo", "baz", "text"]);
            assert.equal(form.inputs.foo.value, "bar");
        });

        test("returns the last input of fields with the same name", () => {
            const form = makeForm('<input id="first" name="foo"><input id="second" name="foo">');
            assert.equal(form.inputs.foo.element.id, "second");
            assert.equal(form.formInputs.length, 2);
        });
    });

    describe("changes", () => {
        test("is unchanged when loaded", () => {
            assert.equal(makeForm('<input name="foo" value="bar">').hasChanged(), false);
        });

        test("detects changed values", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            assert.equal(form.hasChanged(), true);
        });

        test("is unchanged when the values are restored", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            input("foo").value = "bar";
            assert.equal(form.hasChanged(), false);
        });

        test("detects selected files, unless asked to ignore them", () => {
            const form = makeForm('<input type="text" name="foo">');
            const fileInput = document.createElement("input");
            fileInput.type = "file";
            Object.defineProperty(fileInput, "files", { value: [new File(["Hello"], "file.txt")] });
            form.element.append(fileInput);
            assert.equal(form.hasChanged(), true);
            assert.equal(form.hasChanged(false), false);
        });

        test("updates the changed state shortly after the last input", (t) => {
            t.mock.timers.enable({ apis: ["setTimeout"] });
            const form = makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            input("foo").dispatchEvent(new Event("input", { bubbles: true }));
            t.mock.timers.tick(499);
            assert.equal(form.changed.value, false);
            t.mock.timers.tick(1);
            assert.equal(form.changed.value, true);
        });
    });

    describe("leaving the page", () => {
        test("asks for confirmation with unsaved changes", () => {
            makeForm('<input id="foo" name="foo" value="bar">');
            assert.equal(leavePage(), false);
            input("foo").value = "baz";
            assert.equal(leavePage(), true);
        });

        test("does not ask for confirmation once submitted", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            form.element.dispatchEvent(new Event("submit"));
            assert.equal(leavePage(), false);
        });

        test("does not ask for confirmation when disabled", () => {
            makeForm('<input id="foo" name="foo" value="bar">', { preventUnloadOnChanges: false });
            input("foo").value = "baz";
            assert.equal(leavePage(), false);
        });

        test("confirms following links with unsaved changes with the modal", () => {
            document.body.innerHTML = '<a id="link" href="/panel/pages/">Pages</a>';
            makeForm('<input id="foo" name="foo" value="bar">');

            assert.equal(clickLink("link"), false);
            assert.equal(changesModal.open.mock.callCount(), 0);

            input("foo").value = "baz";
            assert.equal(clickLink("link"), true);
            assert.equal(changesModal.open.mock.callCount(), 1);
            assert.equal(changesModal.element.querySelector<HTMLElement>("[data-command=continue]")?.dataset.href, "http://example.test/panel/pages/");
        });

        test("allows leaving once confirmed", () => {
            document.body.innerHTML = '<a id="link" href="/panel/pages/">Pages</a>';
            makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            changesModal.commands.continue(changesModal as unknown as Modal);
            assert.equal(leavePage(), false);
        });

        test("does not confirm links not leaving the page", () => {
            document.body.innerHTML = `
                <a id="fragment" href="#foo">Fragment</a>
                <a id="blank" href="/panel/" target="_blank">Blank</a>
                <a id="frame" href="/panel/" target="formwork-preview">Preview</a>
                <div class="editor-wrap"><a id="editor" href="/panel/">Editor</a></div>
            `;
            makeForm('<input id="foo" name="foo" value="bar">');
            input("foo").value = "baz";
            for (const id of ["fragment", "blank", "frame", "editor"]) {
                assert.equal(clickLink(id), false, id);
            }
            assert.equal(changesModal.open.mock.callCount(), 0);
        });
    });

    describe("removeInputs", () => {
        test("removes the inputs of the elements inside a parent", () => {
            const form = makeForm('<input name="foo"><div id="parent"><input name="bar"><div><input name="baz"></div></div>');
            form.removeInputs(document.getElementById("parent") as HTMLElement);
            assert.deepEqual(Object.keys(form.inputs), ["foo"]);
        });

        test("removes the input of the parent itself", () => {
            const form = makeForm('<input id="foo" name="foo"><input name="bar">');
            form.removeInputs(input("foo"));
            assert.deepEqual(Object.keys(form.inputs), ["bar"]);
        });
    });

    describe("behaviors", () => {
        test("clears a date with its reset button", () => {
            setTranslation();
            const form = makeForm('<div class="form-input-wrap"><input type="text" class="form-input form-input-date" id="date" name="date" value="2024-01-15T10:30" data-time="false"><button type="button" id="reset" class="form-input-action" data-reset="date"></button></div>');
            assert.equal(form.inputs.date.value, "2024-01-15T10:30:00");
            input("reset").click();
            assert.equal(input("date").value, "");
            assert.equal(form.inputs.date.value, "");
        });

        test("clears a field with its reset button", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar"><button type="button" id="reset" class="form-input-action" data-reset="foo"></button>');
            const change = mock.fn();
            form.element.addEventListener("change", change);
            input("reset").click();
            assert.equal(input("foo").value, "");
            assert.equal(change.mock.callCount(), 1);
        });

        test("enables the fields of a checkbox while it is checked", () => {
            makeForm('<input type="checkbox" id="toggle" data-enable="foo,bar"><input name="foo" disabled><input name="bar" disabled><input name="baz" disabled>');
            input("toggle").click();
            assert.deepEqual(
                Array.from(document.querySelectorAll("input[name]"), (element) => (element as HTMLInputElement).disabled),
                [false, false, true],
            );
            input("toggle").click();
            assert.deepEqual(
                Array.from(document.querySelectorAll("input[name]"), (element) => (element as HTMLInputElement).disabled),
                [true, true, true],
            );
        });
    });

    describe("duplicateInput", () => {
        test("appends an empty copy of the input with a new id", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar"><div id="target"></div>');
            form.duplicateInput(input("foo"), document.getElementById("target") as HTMLElement);
            const copy = document.querySelector("#target input") as HTMLInputElement;
            assert.equal(copy.name, "foo");
            assert.equal(copy.value, "");
            assert.notEqual(copy.id, "foo");
            assert.equal(input("foo").value, "bar");
        });

        test("unchecks copies of checkboxes", () => {
            const form = makeForm('<input type="checkbox" id="foo" name="foo" checked><div id="target"></div>');
            form.duplicateInput(input("foo"), document.getElementById("target") as HTMLElement);
            assert.equal((document.querySelector("#target input") as HTMLInputElement).checked, false);
        });

        test("loads the copy as an input of the form", () => {
            const form = makeForm('<input id="foo" name="foo" value="bar"><div id="target"></div>');
            form.duplicateInput(input("foo"), document.getElementById("target") as HTMLElement);
            assert.equal(form.formInputs.length, 2);
            assert.ok(form.inputs.foo.element === document.querySelector("#target input"));
        });

        test("copies the wrap of the input with its actions, description and icon", () => {
            const form = makeForm(`
                <div class="form-input-wrap" id="wrap">
                    <span class="form-input-icon"></span>
                    <input id="foo" name="foo" value="bar">
                    <button type="button" class="form-input-action"></button>
                    <div class="form-input-description"></div>
                    <div class="other"></div>
                </div>
                <div id="target"></div>
            `);
            form.duplicateInput(input("foo"), document.getElementById("target") as HTMLElement);
            const copy = document.querySelector("#target .form-input-wrap") as HTMLElement;
            assert.deepEqual(
                Array.from(copy.children, (child) => child.className || child.tagName.toLowerCase()),
                ["form-input-icon", "input", "form-input-action", "form-input-description"],
            );
            assert.notEqual(copy.id, "");
        });
    });
});
