import "../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { Modal } from "../../../src/ts/services/modal";
import { initFileForm } from "../../../src/ts/views/files";
import { resetModals, setTranslation } from "../../helpers/panel";

beforeEach(() => {
    app.config.csrfToken = "token";
    setTranslation();
    app.modals = {};
});

afterEach(() => {
    resetModals();
});

/**
 * Add the commands and the modal of the form of a file, as the panel renders them, and initialize
 * the form
 */
function makeFileForm() {
    document.body.innerHTML = `
        <form data-form="file-form">
            <button type="button" id="rename" data-modal="renameFileModal" data-modal-action="/panel/pages/blog/files/image.jpg/rename/" data-filename="image.jpg">Rename</button>
            <button type="button" id="replace" data-command="replaceFile" data-action="/panel/pages/blog/files/image.jpg/replace/" data-extension=".jpg">Replace</button>
        </form>
        <div class="modal" id="renameFileModal"><div class="modal-container"><form method="post">
            <input type="text" id="renameFileModal.filename" name="renameFileModal[filename]" required>
            <button type="submit">Rename</button>
        </form></div></div>`;
    app.modals.renameFileModal = new Modal(document.getElementById("renameFileModal") as HTMLElement);
    initFileForm();
}

const filename = () => document.getElementById("renameFileModal.filename") as HTMLInputElement;

describe("Files View", () => {
    describe("rename", () => {
        test("fills the name of the file, selecting it without the extension", () => {
            makeFileForm();
            (document.getElementById("rename") as HTMLElement).click();
            assert.equal(filename().value, "image.jpg");
            assert.equal(filename().selectionStart, 0);
            assert.equal(filename().selectionEnd, 5);
            assert.equal(app.modals.renameFileModal.form?.element.getAttribute("action"), "/panel/pages/blog/files/image.jpg/rename/");
        });

        test("renames the file with Enter", (t) => {
            const submit = t.mock.method(HTMLFormElement.prototype, "submit", () => undefined);
            const requestSubmit = t.mock.method(HTMLFormElement.prototype, "requestSubmit", () => undefined);
            makeFileForm();
            (document.getElementById("rename") as HTMLElement).click();
            const enter = new KeyboardEvent("keydown", { key: "Enter", bubbles: true, cancelable: true });
            filename().dispatchEvent(enter);
            assert.equal(enter.defaultPrevented, true);
            assert.equal(submit.mock.callCount() + requestSubmit.mock.callCount(), 1);
        });

        test("does not rename the file with an empty name", (t) => {
            const submit = t.mock.method(HTMLFormElement.prototype, "submit", () => undefined);
            makeFileForm();
            (document.getElementById("rename") as HTMLElement).click();
            filename().value = "";
            filename().dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true, cancelable: true }));
            assert.equal(submit.mock.callCount(), 0);
        });
    });

    describe("replace", () => {
        test("asks for a file with the same extension and uploads it", (t) => {
            const submit = t.mock.method(HTMLFormElement.prototype, "submit", () => undefined);
            const click = t.mock.method(HTMLInputElement.prototype, "click", () => undefined);
            makeFileForm();
            (document.getElementById("replace") as HTMLElement).click();
            assert.equal(click.mock.callCount(), 1);
            const input = click.mock.calls[0].this as HTMLInputElement;
            assert.equal(input.type, "file");
            assert.equal(input.accept, ".jpg");
            input.dispatchEvent(new Event("change"));
            assert.equal(submit.mock.callCount(), 1);
            const form = submit.mock.calls[0].this as HTMLFormElement;
            assert.equal(form.getAttribute("action"), "/panel/pages/blog/files/image.jpg/replace/");
            assert.equal(form.method, "post");
            assert.equal(form.enctype, "multipart/form-data");
            assert.equal((form.querySelector("[name=csrf-token]") as HTMLInputElement).value, "token");
        });
    });
});
