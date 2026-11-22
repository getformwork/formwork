import "../../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, test } from "node:test";
import { FilesList } from "../../../../src/ts/components/files/files-list";
import { app } from "../../../../src/ts/services/app";
import { Modal } from "../../../../src/ts/services/modal";
import { resetModals, setTranslation, shownNotifications } from "../../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../../stubs/xhr";

beforeEach(() => {
    installXMLHttpRequestStub();
    setTranslation({
        "panel.pages.deleteFile.prompt": "Delete the file?",
        "panel.pages.deleteFile.multiple.prompt": "Delete %d files?",
        "panel.files.deleted.multiple": "%d files deleted",
    });
});

afterEach(() => {
    resetModals();
});

/**
 * Get the HTML of a file of a list as the panel renders it
 */
function item(filename: string, parent?: string) {
    const base = `/panel/pages/blog/files/${filename}`;
    return `
        <div class="files-item" role="row" data-filename="${filename}">
            <img class="file-thumbnail" data-src="${base}/thumbnail/" alt="">
            <div class="files-item-cell file-name" role="cell"><a href="${base}/edit/">${filename}</a></div>
            ${parent ? `<div class="files-item-cell file-parent"><a href="/panel/pages/blog/edit/"><span class="file-parent-title">${parent}</span></a></div>` : ""}
            <div class="files-item-cell file-date" role="cell">2024-01-05</div>
            <div class="files-item-cell file-size" role="cell">1 KB</div>
            <div class="files-item-cell file-actions" role="cell">
                <div class="dropdown">
                    <button type="button" class="dropdown-button" data-dropdown="dropdown-${filename}"></button>
                    <div class="dropdown-menu" id="dropdown-${filename}">
                        <a class="dropdown-item" data-command="infoFile" href="${base}/edit/">Info</a>
                        <a class="dropdown-item" data-command="previewFile" href="/site/blog/${filename}" target="formwork-preview-file">Preview</a>
                        <button type="button" class="dropdown-item" data-command="renameFile" data-modal="renameFileItemModal" data-action="${base}/rename/">Rename</button>
                        <button type="button" class="dropdown-item" data-command="replaceFile" data-action="${base}/replace/" data-mimetype="text/plain">Replace</button>
                        <button type="button" class="dropdown-item" data-command="deleteFile" data-modal="deleteFileItemModal" data-action="${base}/delete/">Delete</button>
                    </div>
                </div>
            </div>
        </div>`;
}

/**
 * Add a list of files, with the modals to rename and delete them, and make it
 *
 * @param filenames        Names of the files of the list
 * @param id               Id of the list, also the id of the input it is for
 * @param selectionActions Whether the user can act on the selected files
 * @param parent           Title of the page of the files, shown by the lists of the files of several pages
 */
function makeList(filenames: string[], { id = "list", selectionActions = true, parent = undefined as string | undefined } = {}) {
    document.body.insertAdjacentHTML(
        "beforeend",
        `<div class="files-list is-list" id="${id}" data-for="${id}">
            <input class="form-input files-search" type="search">
            <div class="files-selection-actions" hidden>${selectionActions ? '<button type="button" class="selection-delete" data-modal="deleteFileItemModal">Delete <span class="files-selection-count"></span></button>' : ""}</div>
            <fieldset class="form-togglegroup files-list-view-as" data-for="${id}">
                <input type="radio" name="${id}-list-view-as" value="list" checked data-form-ignore="true">
                <input type="radio" name="${id}-list-view-as" value="thumbnails" data-form-ignore="true">
            </fieldset>
            <div class="files-items">${filenames.map((filename) => item(filename, parent)).join("")}</div>
        </div>`,
    );
    return new FilesList(document.getElementById(id) as HTMLElement);
}

/**
 * Add the modals to rename and delete files, used by the lists made afterwards
 */
function addModals() {
    document.body.insertAdjacentHTML(
        "beforeend",
        `<div class="modal" id="renameFileItemModal">
            <input id="renameFileItemModal.filename" name="renameFileItemModal[filename]">
            <button type="button" data-command="rename-file">Rename</button>
        </div>
        <div class="modal" id="deleteFileItemModal">
            <p class="modal-text"></p>
            <button type="button" id="confirm-delete" data-command="delete-file">Delete</button>
        </div>`,
    );
    app.modals = {
        renameFileItemModal: new Modal(document.getElementById("renameFileItemModal") as HTMLElement),
        deleteFileItemModal: new Modal(document.getElementById("deleteFileItemModal") as HTMLElement),
    };
}

const fileItem = (filename: string) => document.querySelector(`.files-item[data-filename="${filename}"]`) as HTMLElement;

const filenames = (list = "list") => Array.from(document.querySelectorAll<HTMLElement>(`#${list} .files-item`), (element) => element.dataset.filename);

const selected = () => Array.from(document.querySelectorAll<HTMLElement>(".files-item.is-selected"), (element) => element.dataset.filename);

/**
 * Click an element with the given modifiers
 */
function click(target: Element, init: MouseEventInit = {}) {
    const event = new MouseEvent("click", { bubbles: true, cancelable: true, ...init });
    target.dispatchEvent(event);
    return event;
}

/**
 * Search a text in a list
 */
function search(text: string, list = "list") {
    const input = document.querySelector(`#${list} .files-search`) as HTMLInputElement;
    input.value = text;
    input.dispatchEvent(new Event("search"));
}

const visible = (list = "list") =>
    Array.from(document.querySelectorAll<HTMLElement>(`#${list} .files-item`))
        .filter((element) => element.style.display !== "none")
        .map((element) => element.dataset.filename);

describe("FilesList", () => {
    test("loads the thumbnails", () => {
        makeList(["first.txt"]);
        assert.equal(document.querySelector(".file-thumbnail")?.getAttribute("src"), "/panel/pages/blog/files/first.txt/thumbnail/");
    });

    test("sorts the files by name", () => {
        const list = makeList(["second.txt", "first.txt", "third.txt"]);
        list.sort();
        assert.deepEqual(filenames(), ["first.txt", "second.txt", "third.txt"]);
    });

    describe("search", () => {
        test("shows only the files matching the search, highlighting it", () => {
            makeList(["first.txt", "second.txt"]);
            search("first");
            assert.deepEqual(visible(), ["first.txt"]);
            assert.equal(fileItem("first.txt").querySelector(".file-name a")?.innerHTML, "<mark>first</mark>.txt");
        });

        test("searches the title of the parent", () => {
            makeList(["first.txt"], { parent: "Blog" });
            search("blog");
            assert.deepEqual(visible(), ["first.txt"]);
            assert.equal(fileItem("first.txt").querySelector(".file-parent-title")?.innerHTML, "<mark>Blog</mark>");
        });

        test("shows every file without highlights when the search is cleared", () => {
            makeList(["first.txt", "second.txt"]);
            search("first");
            search("");
            assert.deepEqual(visible(), ["first.txt", "second.txt"]);
            assert.equal(document.querySelectorAll("mark").length, 0);
        });

        test("filters only the files of its list", () => {
            makeList(["first.txt"], { id: "list" });
            makeList(["second.txt"], { id: "other" });
            search("first", "list");
            assert.deepEqual(visible("other"), ["second.txt"]);
        });
    });

    describe("view", () => {
        test("switches between list and thumbnails, remembering the choice", () => {
            makeList(["first.txt"]);
            const list = document.getElementById("list") as HTMLElement;
            const thumbnails = list.querySelector('input[value="thumbnails"]') as HTMLInputElement;
            thumbnails.checked = true;
            thumbnails.dispatchEvent(new Event("input"));
            assert.equal(list.classList.contains("is-thumbnails"), true);
            assert.equal(list.classList.contains("is-list"), false);
            assert.equal(window.localStorage.getItem("formwork.filesListViewAs[list]"), "thumbnails");
        });

        test("restores the remembered view", () => {
            window.localStorage.setItem("formwork.filesListViewAs[list]", "thumbnails");
            makeList(["first.txt"]);
            const list = document.getElementById("list") as HTMLElement;
            assert.equal(list.classList.contains("is-thumbnails"), true);
            assert.equal((list.querySelector('input[value="thumbnails"]') as HTMLInputElement).checked, true);
        });

        test("works without access to the local storage", (t) => {
            t.mock.method(Storage.prototype, "getItem", () => {
                throw new DOMException("The operation is insecure.", "SecurityError");
            });
            makeList(["first.txt", "second.txt"]);
            search("first");
            assert.deepEqual(visible(), ["first.txt"]);
        });

        test("ignores an unknown remembered view", () => {
            window.localStorage.setItem("formwork.filesListViewAs[list]", "grid");
            makeList(["first.txt"]);
            assert.equal((document.querySelector('input[value="list"]') as HTMLInputElement).checked, true);
        });

        test("opens files when clicked in the thumbnails view", () => {
            window.localStorage.setItem("formwork.filesListViewAs[list]", "thumbnails");
            makeList(["first.txt"]);
            click(fileItem("first.txt").querySelector(".file-size") as HTMLElement);
            assert.equal(window.location.href, "http://example.test/panel/pages/blog/files/first.txt/edit/");
        });

        test("does not open files when their menu is used in the thumbnails view", () => {
            window.localStorage.setItem("formwork.filesListViewAs[list]", "thumbnails");
            makeList(["first.txt"], { selectionActions: false });
            click(fileItem("first.txt").querySelector(".dropdown-button") as HTMLElement);
            assert.equal(window.location.href, "http://example.test/");
        });
    });

    describe("selection", () => {
        test("toggles files with Ctrl+click and Cmd+click", () => {
            makeList(["first.txt", "second.txt", "third.txt"]);
            assert.equal(click(fileItem("first.txt").querySelector(".file-size") as HTMLElement, { ctrlKey: true }).defaultPrevented, true);
            click(fileItem("third.txt").querySelector(".file-size") as HTMLElement, { metaKey: true });
            assert.deepEqual(selected(), ["first.txt", "third.txt"]);
            click(fileItem("first.txt").querySelector(".file-size") as HTMLElement, { ctrlKey: true });
            assert.deepEqual(selected(), ["third.txt"]);
        });

        test("shows the actions with the number of selected files", () => {
            makeList(["first.txt", "second.txt"]);
            const actions = document.querySelector(".files-selection-actions") as HTMLElement;
            assert.equal(actions.hidden, true);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(fileItem("second.txt"), { ctrlKey: true });
            assert.equal(actions.hidden, false);
            assert.equal(actions.querySelector(".files-selection-count")?.textContent, "(2)");
        });

        test("selects a range of files with Shift+click", () => {
            makeList(["first.txt", "second.txt", "third.txt", "fourth.txt"]);
            click(fileItem("second.txt"), { ctrlKey: true });
            click(fileItem("fourth.txt"), { shiftKey: true });
            assert.deepEqual(selected(), ["second.txt", "third.txt", "fourth.txt"]);
            click(fileItem("first.txt"), { shiftKey: true });
            assert.deepEqual(selected(), ["first.txt", "second.txt"]);
        });

        test("clears the selection when something else is clicked", () => {
            document.body.innerHTML = '<button type="button" id="outside">Outside</button>';
            makeList(["first.txt", "second.txt"]);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(document.getElementById("outside") as HTMLElement);
            assert.deepEqual(selected(), []);
            assert.equal((document.querySelector(".files-selection-actions") as HTMLElement).hidden, true);
        });

        test("clears the selection when the menu of a file is used", () => {
            makeList(["first.txt", "second.txt"]);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(fileItem("second.txt").querySelector(".dropdown-button") as HTMLElement);
            assert.deepEqual(selected(), []);
        });

        test("preserves the selection when the selection actions are used", () => {
            makeList(["first.txt", "second.txt"]);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(document.querySelector(".selection-delete") as HTMLElement);
            assert.deepEqual(selected(), ["first.txt"]);
        });

        test("does not select without selection actions", () => {
            makeList(["first.txt"], { selectionActions: false });
            assert.equal(click(fileItem("first.txt"), { ctrlKey: true }).defaultPrevented, false);
            assert.deepEqual(selected(), []);
        });
    });

    describe("rename", () => {
        beforeEach(() => {
            addModals();
        });

        test("renames a file with the modal", () => {
            makeList(["first.txt", "second.txt"]);
            click(fileItem("second.txt").querySelector("[data-command=renameFile]") as HTMLElement);

            const input = document.getElementById("renameFileItemModal.filename") as HTMLInputElement;
            assert.equal(input.value, "second.txt");
            assert.equal(input.selectionStart, 0);
            assert.equal(input.selectionEnd, "second".length);

            input.value = "a-renamed.txt";
            input.dispatchEvent(new KeyboardEvent("keydown", { key: "Enter", cancelable: true }));

            const request = XMLHttpRequestStub.last;
            assert.equal(request.url, "/panel/pages/blog/files/second.txt/rename/");
            assert.match(String(request.body), /^filename=second\.txt&renameFileItemModal%5Bfilename%5D=a-renamed\.txt/);

            const base = "/panel/pages/blog/files/a-renamed.txt";
            request.respondJson("success", "File renamed", {
                filename: "a-renamed.txt",
                type: "text",
                uri: "/site/blog/a-renamed.txt",
                actions: { info: `${base}/edit/`, rename: `${base}/rename/`, replace: `${base}/replace/`, delete: `${base}/delete/` },
            });

            const renamed = fileItem("a-renamed.txt");
            assert.equal(renamed.querySelector(".file-name a")?.textContent, "a-renamed.txt");
            assert.equal((renamed.querySelector("[data-command=deleteFile]") as HTMLElement).dataset.action, `${base}/delete/`);
            assert.deepEqual(filenames(), ["a-renamed.txt", "first.txt"]);
            assert.deepEqual(shownNotifications(), ["success: File renamed"]);
            assert.equal(app.modals.renameFileItemModal.isClosed, true);
        });

        test("reports a failed renaming", () => {
            makeList(["first.txt"]);
            click(fileItem("first.txt").querySelector("[data-command=renameFile]") as HTMLElement);
            app.modals.renameFileItemModal.triggerCommand("rename-file");
            XMLHttpRequestStub.last.respondJson("error", "Cannot rename the file");
            assert.deepEqual(filenames(), ["first.txt"]);
            assert.deepEqual(shownNotifications(), ["danger: Cannot rename the file"]);
        });
    });

    describe("replace", () => {
        beforeEach(() => {
            makeList(["first.txt"]);
        });

        /**
         * Use the command to replace a file, returning the file input it asks a file with
         */
        function replace(t: { mock: { method: typeof import("node:test").mock.method } }, filename: string) {
            let picker: HTMLInputElement | undefined;
            // The browser would open the file picker
            t.mock.method(HTMLInputElement.prototype, "click", function (this: HTMLInputElement) {
                picker = this;
            });
            click(fileItem(filename).querySelector("[data-command=replaceFile]") as HTMLElement);
            return picker as HTMLInputElement;
        }

        /**
         * Choose a file in a file input as the user does
         */
        function choose(input: HTMLInputElement, file?: File) {
            const transfer = new DataTransfer();
            if (file) {
                transfer.items.add(file);
            }
            input.files = transfer.files;
            input.dispatchEvent(new Event("change"));
        }

        const replaced = (name: string, thumbnail?: string) => ({ filename: name, uri: `/site/blog/${name}`, size: "2 KB", lastModifiedTime: "2024-02-01", type: thumbnail ? "image" : "text", thumbnail: thumbnail ?? null, actions: {} });

        const shownData = (filename: string) => {
            const item = fileItem(filename);
            return [item.querySelector(".file-thumbnail")?.getAttribute("src"), item.querySelector(".file-date")?.textContent, item.querySelector(".file-size")?.textContent];
        };

        test("asks for a file of the same type", (t) => {
            const picker = replace(t, "first.txt");
            assert.equal(picker.type, "file");
            assert.equal(picker.accept, "text/plain");
        });

        test("uploads the chosen file", (t) => {
            choose(replace(t, "first.txt"), new File(["foo"], "new.txt", { type: "text/plain" }));
            const request = XMLHttpRequestStub.last;
            assert.equal(request.method, "POST");
            assert.equal(request.url, "/panel/pages/blog/files/first.txt/replace/");
            assert.equal(((request.body as FormData).get("file") as File).name, "new.txt");
        });

        test("does not upload without a chosen file", (t) => {
            choose(replace(t, "first.txt"));
            assert.equal(XMLHttpRequestStub.requests.length, 0);
        });

        test("shows the new thumbnail, date and size of an image and notifies the replacement", (t) => {
            choose(replace(t, "first.txt"), new File(["foo"], "new.jpg", { type: "image/jpeg" }));
            XMLHttpRequestStub.last.respondJson("success", "File uploaded", replaced("first.txt", "/panel/pages/blog/files/first.txt/thumbnail/new/"));
            assert.deepEqual(shownData("first.txt"), ["/panel/pages/blog/files/first.txt/thumbnail/new/", "2024-02-01", "2 KB"]);
            assert.deepEqual(shownNotifications(), ["success: File uploaded"]);
        });

        test("keeps the file when the replacement fails", (t) => {
            choose(replace(t, "first.txt"), new File(["foo"], "new.txt", { type: "text/plain" }));
            XMLHttpRequestStub.last.respondJson("error", "Cannot upload the file");
            assert.deepEqual(shownData("first.txt"), ["/panel/pages/blog/files/first.txt/thumbnail/", "2024-01-05", "1 KB"]);
            assert.deepEqual(shownNotifications(), ["danger: Cannot upload the file"]);
        });
    });

    describe("delete", () => {
        beforeEach(() => {
            addModals();
        });

        test("deletes a file with the modal", () => {
            makeList(["first.txt", "second.txt"]);
            click(fileItem("first.txt").querySelector("[data-command=deleteFile]") as HTMLElement);
            assert.equal(document.querySelector("#deleteFileItemModal .modal-text")?.textContent, "Delete the file?");

            click(document.getElementById("confirm-delete") as HTMLElement);
            const request = XMLHttpRequestStub.last;
            assert.equal(request.url, "/panel/pages/blog/files/first.txt/delete/");
            request.respondJson("success", "File deleted");

            assert.deepEqual(filenames(), ["second.txt"]);
            assert.deepEqual(shownNotifications(), ["success: File deleted"]);
        });

        test("hides the list when its last file is deleted", () => {
            makeList(["first.txt"]);
            click(fileItem("first.txt").querySelector("[data-command=deleteFile]") as HTMLElement);
            click(document.getElementById("confirm-delete") as HTMLElement);
            XMLHttpRequestStub.last.respondJson("success", "File deleted");
            assert.equal((document.getElementById("list") as HTMLElement).hidden, true);
        });

        test("preserves a file that cannot be deleted", () => {
            makeList(["first.txt"]);
            click(fileItem("first.txt").querySelector("[data-command=deleteFile]") as HTMLElement);
            click(document.getElementById("confirm-delete") as HTMLElement);
            XMLHttpRequestStub.last.respondJson("error", "Cannot delete the file");
            assert.deepEqual(filenames(), ["first.txt"]);
            assert.deepEqual(shownNotifications(), ["danger: Cannot delete the file"]);
        });

        test("deletes the selected files", () => {
            makeList(["first.txt", "second.txt", "third.txt"]);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(fileItem("third.txt"), { ctrlKey: true });
            click(document.querySelector(".selection-delete") as HTMLElement);
            assert.equal(document.querySelector("#deleteFileItemModal .modal-text")?.textContent, "Delete 2 files?");

            click(document.getElementById("confirm-delete") as HTMLElement);
            assert.deepEqual(
                XMLHttpRequestStub.requests.map((request) => request.url),
                ["/panel/pages/blog/files/first.txt/delete/", "/panel/pages/blog/files/third.txt/delete/"],
            );
            for (const request of XMLHttpRequestStub.requests) {
                request.respondJson("success", "File deleted");
            }

            assert.deepEqual(filenames(), ["second.txt"]);
            assert.deepEqual(selected(), []);
            assert.deepEqual(shownNotifications(), ["success: 2 files deleted"]);
        });

        test("asks to delete a single selected file as a file", () => {
            makeList(["first.txt", "second.txt"]);
            click(fileItem("first.txt"), { ctrlKey: true });
            click(document.querySelector(".selection-delete") as HTMLElement);
            assert.equal(document.querySelector("#deleteFileItemModal .modal-text")?.textContent, "Delete the file?");
        });
    });
});
