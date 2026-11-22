import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import type { UploadInput } from "../../../../src/ts/components/inputs/upload-input";
import { app } from "../../../../src/ts/services/app";
import { Form } from "../../../../src/ts/services/form";
import { setTranslation } from "../../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../../stubs/xhr";

/**
 * Get the template of the items of the files, as the panel renders it
 *
 * @param commands Commands the user is allowed to use
 */
function itemTemplate(commands = ["renameFile", "replaceFile", "deleteFile"]) {
    const buttons = commands.map((command) => `<button type="button" class="dropdown-item" data-command="${command}" data-action="">${command}</button>`).join("");
    return `<template id="files-item">
        <div class="files-item" role="row" data-filename="">
            <div class="file-thumbnail" aria-hidden="true"></div>
            <div class="files-item-cell file-icon" role="cell"></div>
            <div class="files-item-cell file-name truncate" role="cell"><a></a></div>
            <div class="files-item-cell file-date" role="cell"></div>
            <div class="files-item-cell file-size" role="cell"></div>
            <div class="files-item-cell file-actions" role="cell">
                <div class="dropdown">
                    <button type="button" class="button dropdown-button" data-dropdown=""></button>
                    <div class="dropdown-menu" id="">
                        <a class="dropdown-item" data-command="infoFile" href="">Info</a>
                        <a class="dropdown-item" data-command="previewFile" href="" target="">Preview</a>
                        ${buttons}
                    </div>
                </div>
            </div>
        </div>
    </template>`;
}

/**
 * Make a form with an upload input as the panel renders it
 *
 * @param autoUpload Whether the files are uploaded as soon as they are chosen
 * @param list       Whether the files of the page are listed
 * @param multiple   Whether the input accepts more than one file
 * @param commands   Commands the user is allowed to use on the uploaded files
 * @param fields     HTML of the other fields of the form
 */
function makeUpload({ autoUpload = false, list = false, multiple = false, commands = undefined as string[] | undefined, fields = "" } = {}) {
    document.body.innerHTML = `
        <form data-form="page-editor-form" method="post">
            ${fields}
            ${list ? '<div class="files-list" data-for="files" hidden><div class="files-items"></div></div>' : ""}
            <label class="form-label" for="files">Files:</label>
            <label class="form-upload-drop-target" tabindex="0">
                <input type="file" class="form-input form-input-upload" id="files" name="files${multiple ? "[]" : ""}" accept=".jpg, .pdf" ${multiple ? "multiple" : ""} data-auto-upload="${autoUpload}">
                <span>Drop files here</span>
            </label>
            <button type="submit">Save</button>
        </form>
        ${itemTemplate(commands)}`;
    const form = new Form(document.querySelector("form") as HTMLFormElement, { preventUnloadOnChanges: false });
    const element = document.getElementById("files") as HTMLInputElement;
    return { form, element, input: form.inputs[element.name] as UploadInput };
}

const dropTarget = () => document.querySelector(".form-upload-drop-target") as HTMLElement;

const label = () => (dropTarget().querySelector("span") as HTMLElement).textContent;

/**
 * Make a list of files
 */
function fileList(...files: File[]) {
    const transfer = new DataTransfer();
    for (const file of files) {
        transfer.items.add(file);
    }
    return transfer;
}

/**
 * Choose files in the input as the user does
 */
function choose(element: HTMLInputElement, ...files: File[]) {
    element.files = fileList(...files).files;
    element.dispatchEvent(new Event("change"));
}

/**
 * Drop files on the drop target as the user does
 */
function drop(...files: File[]) {
    const event = new DragEvent("drop", { bubbles: true, cancelable: true });
    // happy-dom does not set the data transfer of drag events from their options, unlike browsers
    Object.defineProperty(event, "dataTransfer", { value: fileList(...files) });
    dropTarget().dispatchEvent(event);
    return event;
}

const image = () => new File(["foo"], "image.jpg", { type: "image/jpeg" });

const document_ = () => new File(["foobar"], "document.pdf", { type: "application/pdf" });

/**
 * Answer the upload of a file
 */
function respondUpload(request: XMLHttpRequestStub, name: string, type = "image") {
    request.respondJson("success", "File uploaded", [
        {
            name,
            type,
            thumbnail: `/site/blog/${name}/thumbnail`,
            uri: `/site/blog/${name}`,
            hash: "abc123",
            mimeType: type === "image" ? "image/jpeg" : "application/pdf",
            lastModifiedTime: "2024-01-05",
            size: "3 B",
            actions: { info: `/panel/pages/blog/files/${name}/edit/`, rename: `/panel/pages/blog/files/${name}/rename/`, replace: `/panel/pages/blog/files/${name}/replace/`, delete: `/panel/pages/blog/files/${name}/delete/` },
        },
    ]);
}

describe("UploadInput", () => {
    beforeEach(() => {
        installXMLHttpRequestStub();
        setTranslation();
        app.modals = {};
    });

    describe("drop target", () => {
        test("shows the chosen files with their size", () => {
            const { element } = makeUpload({ multiple: true });
            choose(element, image(), document_());
            assert.equal(label(), "image.jpg (3.00 B), document.pdf (6.00 B)");
        });

        describe("sizes", () => {
            /**
             * Get the sizes shown for chosen files of the given sizes in bytes
             */
            function shownSizes(...sizes: number[]) {
                const { element } = makeUpload({ multiple: true });
                // The files are empty, as files as large as the largest units cannot be made
                choose(element, ...sizes.map((size, index) => Object.defineProperty(new File([], `${index}.txt`), "size", { value: size })));
                return Array.from(dropTarget().querySelectorAll(".file-size-inline"), (size) => size.textContent);
            }

            test("shows the sizes in bytes", () => {
                assert.deepEqual(shownSizes(1, 1023), ["(1.00 B)", "(1023.00 B)"]);
            });

            test("shows the sizes with the largest unit below them", () => {
                assert.deepEqual(shownSizes(1024, 1536, 1024 ** 2, 1024 ** 3 * 2.5, 1024 ** 4), ["(1.00 KB)", "(1.50 KB)", "(1.00 MB)", "(2.50 GB)", "(1.00 TB)"]);
            });

            test("shows the sizes beyond the largest unit with it", () => {
                assert.deepEqual(shownSizes(1024 ** 5), ["(1024.00 TB)"]);
            });
        });

        test("escapes the names of the files", () => {
            const { element } = makeUpload();
            choose(element, new File(["foo"], "<b>foo</b>.jpg"));
            assert.equal(dropTarget().querySelector("b"), null);
            assert.equal(label(), "<b>foo</b>.jpg (3.00 B)");
        });

        test("shows its text again without files", () => {
            const { element } = makeUpload();
            choose(element, image());
            choose(element);
            assert.equal(label(), "Drop files here");
        });

        test("marks the drop target while files are dragged over it", () => {
            makeUpload();
            const dragover = new DragEvent("dragover", { bubbles: true, cancelable: true });
            dropTarget().dispatchEvent(dragover);
            assert.equal(dragover.defaultPrevented, true);
            assert.equal(dropTarget().classList.contains("drag"), true);
            dropTarget().dispatchEvent(new DragEvent("dragleave", { bubbles: true, cancelable: true }));
            assert.equal(dropTarget().classList.contains("drag"), false);
        });

        test("chooses the dropped files", () => {
            const { element } = makeUpload({ multiple: true });
            const change = mock.fn();
            element.addEventListener("change", change);
            assert.equal(drop(image(), document_()).defaultPrevented, true);
            assert.deepEqual(
                Array.from(element.files ?? [], (file) => file.name),
                ["image.jpg", "document.pdf"],
            );
            assert.equal(change.mock.callCount(), 1);
            assert.equal(dropTarget().classList.contains("drag"), false);
        });

        test("focuses the drop target when the label is clicked", () => {
            makeUpload();
            (document.querySelector("label.form-label") as HTMLElement).click();
            assert.ok(document.activeElement === dropTarget());
        });

        test("opens the file picker with Enter", (t) => {
            const { element } = makeUpload();
            const click = t.mock.method(element, "click", () => undefined);
            dropTarget().dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", bubbles: true }));
            assert.equal(click.mock.callCount(), 1);
        });

        test("blocks the files once the form is submitted", () => {
            const { form, element } = makeUpload();
            choose(element, image());
            form.element.dispatchEvent(new Event("submit"));
            assert.notEqual(dropTarget().querySelector(".spinner"), null);
            drop(document_());
            assert.equal(element.files?.[0].name, "image.jpg");
            const click = new MouseEvent("click", { bubbles: true, cancelable: true });
            dropTarget().dispatchEvent(click);
            assert.equal(click.defaultPrevented, true);
        });
    });

    describe("submission", () => {
        test("submits the form as soon as a file is chosen", (t) => {
            const requestSubmit = t.mock.method(HTMLFormElement.prototype, "requestSubmit", () => undefined);
            const { element } = makeUpload({ autoUpload: true });
            choose(element, image());
            assert.equal(requestSubmit.mock.callCount(), 1);
            assert.ok(requestSubmit.mock.calls[0].arguments[0] === document.querySelector("[type=submit]"));
        });

        test("does not submit the form with other changes", (t) => {
            const requestSubmit = t.mock.method(HTMLFormElement.prototype, "requestSubmit", () => undefined);
            const { element } = makeUpload({ autoUpload: true, fields: '<input name="title" value="Foo">' });
            (document.querySelector("[name=title]") as HTMLInputElement).value = "Bar";
            choose(element, image());
            assert.equal(requestSubmit.mock.callCount(), 0);
        });

        test("does not submit the form without automatic upload", (t) => {
            const requestSubmit = t.mock.method(HTMLFormElement.prototype, "requestSubmit", () => undefined);
            const { element } = makeUpload();
            choose(element, image());
            assert.equal(requestSubmit.mock.callCount(), 0);
        });
    });

    describe("upload", () => {
        test("uploads each chosen file, showing them while pending", () => {
            const { element } = makeUpload({ autoUpload: true, list: true, multiple: true });
            choose(element, image(), document_());
            assert.equal(XMLHttpRequestStub.requests.length, 2);
            assert.equal(XMLHttpRequestStub.requests[0].url, window.location.href);
            assert.equal(((XMLHttpRequestStub.requests[0].body as FormData).get("files[]") as File).name, "image.jpg");
            assert.equal(((XMLHttpRequestStub.requests[1].body as FormData).get("files[]") as File).name, "document.pdf");
            assert.notEqual(dropTarget().querySelector(".spinner"), null);
            respondUpload(XMLHttpRequestStub.requests[0], "image.jpg");
            assert.equal(label(), "document.pdf (6.00 B)");
            respondUpload(XMLHttpRequestStub.requests[1], "document.pdf", "pdf");
            assert.equal(label(), "Drop files here");
            assert.equal(element.value, "");
        });

        test("adds the uploaded files to the list, sorted", () => {
            const { element } = makeUpload({ autoUpload: true, list: true, multiple: true });
            choose(element, image(), document_());
            respondUpload(XMLHttpRequestStub.requests[0], "image.jpg");
            respondUpload(XMLHttpRequestStub.requests[1], "document.pdf", "pdf");
            const list = document.querySelector(".files-list") as HTMLElement;
            assert.equal(list.hidden, false);
            assert.deepEqual(
                Array.from(list.querySelectorAll<HTMLElement>(".files-item"), (item) => item.dataset.filename),
                ["document.pdf", "image.jpg"],
            );
        });

        test("shows the data, the thumbnail and the commands of an uploaded file", () => {
            const { element } = makeUpload({ autoUpload: true, list: true });
            choose(element, image());
            respondUpload(XMLHttpRequestStub.last, "image.jpg");
            const item = document.querySelector('.files-item[data-filename="image.jpg"]') as HTMLElement;
            assert.equal(item.querySelector(".file-name a")?.textContent, "image.jpg");
            assert.equal(item.querySelector(".file-name a")?.getAttribute("href"), "/panel/pages/blog/files/image.jpg/edit/");
            assert.equal(item.querySelector(".file-date")?.textContent, "2024-01-05");
            assert.equal(item.querySelector(".file-size")?.textContent, "3 B");
            assert.equal(item.querySelector("img.file-thumbnail")?.getAttribute("src"), "/site/blog/image.jpg/thumbnail");
            assert.notEqual(item.querySelector(".file-icon svg"), null);
            assert.equal((item.querySelector(".dropdown-button") as HTMLElement).dataset.dropdown, "dropdown-abc123");
            assert.equal(item.querySelector(".dropdown-menu")?.id, "dropdown-abc123");
            assert.equal(item.querySelector("[data-command=previewFile]")?.getAttribute("href"), "/site/blog/image.jpg");
            assert.equal(item.querySelector("[data-command=previewFile]")?.getAttribute("target"), "formwork-preview-file-abc123");
            assert.equal((item.querySelector("[data-command=renameFile]") as HTMLElement).dataset.action, "/panel/pages/blog/files/image.jpg/rename/");
            assert.equal((item.querySelector("[data-command=replaceFile]") as HTMLElement).dataset.mimetype, "image/jpeg");
            assert.equal((item.querySelector("[data-command=deleteFile]") as HTMLElement).dataset.action, "/panel/pages/blog/files/image.jpg/delete/");
        });

        test("shows the uploaded videos as their own thumbnail", () => {
            const { element } = makeUpload({ autoUpload: true, list: true });
            choose(element, new File(["foo"], "video.mp4", { type: "video/mp4" }));
            respondUpload(XMLHttpRequestStub.last, "video.mp4", "video");
            const thumbnail = document.querySelector(".files-item .file-thumbnail") as HTMLVideoElement;
            assert.equal(thumbnail.tagName, "VIDEO");
            assert.equal(thumbnail.getAttribute("src"), "/site/blog/video.mp4/thumbnail");
            assert.equal(thumbnail.preload, "metadata");
        });

        test("removes the thumbnail of files that are not images or videos", () => {
            const { element } = makeUpload({ autoUpload: true, list: true });
            choose(element, document_());
            respondUpload(XMLHttpRequestStub.last, "document.pdf", "pdf");
            assert.equal(document.querySelector(".files-item .file-thumbnail"), null);
        });

        test("adds the uploaded files to the inputs of the files", () => {
            const { element } = makeUpload({
                autoUpload: true,
                list: true,
                fields: '<div class="form-input-wrap"><select class="form-select form-image" id="image" name="image"><option value="">None</option></select></div>',
            });
            choose(element, image());
            respondUpload(XMLHttpRequestStub.last, "image.jpg");
            assert.notEqual(document.querySelector('.dropdown-list[data-for="image"] [data-value="image.jpg"]'), null);
        });

        test("notifies the result of each upload", () => {
            const { element } = makeUpload({ autoUpload: true, list: true, multiple: true });
            choose(element, image(), document_());
            respondUpload(XMLHttpRequestStub.requests[0], "image.jpg");
            XMLHttpRequestStub.requests[1].respondJson("error", "Cannot upload the file");
            assert.deepEqual(
                Array.from(document.querySelectorAll(".notification"), (notification) => notification.textContent?.trim()),
                ["Cannot upload the file", "File uploaded"],
            );
            assert.equal(document.querySelectorAll(".files-item").length, 1);
            assert.equal(label(), "Drop files here");
        });
    });
});
