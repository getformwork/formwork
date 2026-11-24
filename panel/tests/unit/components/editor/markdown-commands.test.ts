import "../../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, test } from "node:test";
import type { EditorView } from "prosemirror-view";
import { canInsert, getMarkRange, insertImage, insertLink, isMarkActive, redo, removeLink, undo } from "../../../../src/ts/components/editor/markdown-commands";
import { schema } from "../../../../src/ts/components/editor/markdown-schema";
import { app } from "../../../../src/ts/services/app";
import { Modal } from "../../../../src/ts/services/modal";
import { resetModals, setTranslation } from "../../../helpers/panel";
import { makeView, markdown, type } from "../../../helpers/prosemirror";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../../stubs/xhr";

beforeEach(() => {
    installXMLHttpRequestStub();
    setTranslation();
    app.modals = {};
});

afterEach(() => {
    resetModals();
});

/**
 * Run a command on an editor
 */
const run = (view: EditorView, command: typeof insertLink) => command(view.state, view.dispatch, view);

/**
 * Check whether a command can run on an editor, as the menu does
 */
const canRun = (view: EditorView, command: typeof insertLink) => command(view.state, undefined, view);

describe("Markdown Commands", () => {
    describe("getMarkRange", () => {
        test("returns the range of the link at the cursor", () => {
            const view = makeView("foo [b|ar](https://example.test/) baz");
            const range = getMarkRange(view.state, schema.marks.link);
            assert.equal(range?.from, 5);
            assert.equal(range?.to, 8);
            assert.equal(range?.mark.attrs.href, "https://example.test/");
        });

        test("returns the range of a link with formatted parts", () => {
            const view = makeView("foo [b|ar **baz**](https://example.test/) qux");
            const range = getMarkRange(view.state, schema.marks.link);
            assert.equal(view.state.doc.textBetween(range?.from ?? 0, range?.to ?? 0), "bar baz");
        });

        test("returns the range of the link at the cursor between two links", () => {
            const view = makeView("[foo](https://example.test/foo/)|[bar](https://example.test/bar/)");
            assert.equal(getMarkRange(view.state, schema.marks.link)?.mark.attrs.href, "https://example.test/bar/");
        });

        test("returns null outside links", () => {
            const view = makeView("f|oo [bar](https://example.test/)");
            assert.equal(getMarkRange(view.state, schema.marks.link), null);
        });
    });

    describe("isMarkActive", () => {
        test("returns whether the text at the cursor has a mark", () => {
            assert.equal(isMarkActive(makeView("**f|oo** bar").state, schema.marks.strong), true);
            assert.equal(isMarkActive(makeView("**foo** b|ar").state, schema.marks.strong), false);
        });

        test("returns whether the selection has a mark", () => {
            assert.equal(isMarkActive(makeView("f|oo **b|ar**").state, schema.marks.strong), true);
            assert.equal(isMarkActive(makeView("|foo| **bar**").state, schema.marks.strong), false);
        });
    });

    describe("canInsert", () => {
        test("returns whether a node can be inserted at the selection", () => {
            assert.equal(canInsert(makeView("foo|").state, schema.nodes.image), true);
            assert.equal(canInsert(makeView("```\nfoo|\n```").state, schema.nodes.image), false);
        });
    });

    describe("undo and redo", () => {
        test("are available only with changes to undo and redo", () => {
            const view = makeView("foo|");
            assert.equal(canRun(view, undo), false);
            type(view, "bar");
            assert.equal(canRun(view, undo), true);
            assert.equal(canRun(view, redo), false);
            run(view, undo);
            assert.equal(markdown(view), "foo");
            assert.equal(canRun(view, redo), true);
            run(view, redo);
            assert.equal(markdown(view), "foobar");
        });
    });

    describe("removeLink", () => {
        test("removes the whole link at the cursor", () => {
            const view = makeView("foo [b|ar](https://example.test/) baz");
            assert.equal(canRun(view, removeLink), true);
            run(view, removeLink);
            assert.equal(markdown(view), "foo bar baz");
        });

        test("is not available outside links", () => {
            assert.equal(canRun(makeView("f|oo"), removeLink), false);
        });
    });

    describe("insertLink", () => {
        /**
         * Add the modal to insert links as the panel renders it
         */
        function addLinkModal() {
            document.body.insertAdjacentHTML(
                "beforeend",
                `<div class="modal" id="linkModal"><div class="modal-container">
                    <input id="linkModal.text"><textarea id="linkModal.uri"></textarea>
                    <button type="button" id="remove-link" data-command="remove-link">Delete</button>
                    <button type="button" id="insert-link" data-command="insert-link">Continue</button>
                </div></div>`,
            );
            app.modals.linkModal = new Modal(document.getElementById("linkModal") as HTMLElement);
            return {
                modal: app.modals.linkModal,
                text: document.getElementById("linkModal.text") as HTMLInputElement,
                uri: document.getElementById("linkModal.uri") as HTMLTextAreaElement,
                remove: document.getElementById("remove-link") as HTMLButtonElement,
                insert: document.getElementById("insert-link") as HTMLButtonElement,
            };
        }

        test("opens the modal with the selected text and an address to complete", () => {
            const { modal, text, uri, remove } = addLinkModal();
            const view = makeView("foo |bar| baz");
            run(view, insertLink);
            assert.equal(modal.isOpen, true);
            assert.equal(text.value, "bar");
            assert.equal(uri.value, "https://");
            assert.equal(remove.disabled, true);
            assert.ok(document.activeElement === uri);
        });

        test("links the selected text", () => {
            const { modal, uri, insert } = addLinkModal();
            const view = makeView("foo |bar| baz");
            run(view, insertLink);
            uri.value = "https://example.test/";
            insert.click();
            assert.equal(markdown(view), "foo [bar](https://example.test/) baz");
            assert.equal(modal.isClosed, true);
        });

        test("inserts a link with the address as text without a text", () => {
            const { uri, insert } = addLinkModal();
            const view = makeView("foo |");
            run(view, insertLink);
            uri.value = "https://example.test/";
            insert.click();
            assert.equal(markdown(view), "foo <https://example.test/>");
        });

        test("opens the modal with the text and the address of the link at the cursor", () => {
            const { text, uri, remove } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz");
            run(view, insertLink);
            assert.equal(text.value, "bar");
            assert.equal(uri.value, "https://example.test/");
            assert.equal(remove.disabled, false);
        });

        test("changes the link at the cursor", () => {
            const { text, uri, insert } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz");
            run(view, insertLink);
            text.value = "qux";
            uri.value = "https://example.test/qux/";
            insert.click();
            assert.equal(markdown(view), "foo [qux](https://example.test/qux/) baz");
        });

        test("leaves the content unchanged when the link is unchanged", () => {
            const { modal, insert } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz");
            const doc = view.state.doc;
            run(view, insertLink);
            insert.click();
            assert.ok(view.state.doc === doc);
            assert.equal(modal.isClosed, true);
        });

        test("removes the link without an address", () => {
            const { uri, insert } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz");
            run(view, insertLink);
            uri.value = "";
            insert.click();
            assert.equal(markdown(view), "foo bar baz");
        });

        test("removes the link with the command of the modal", () => {
            const { modal, remove } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz");
            run(view, insertLink);
            remove.click();
            assert.equal(markdown(view), "foo bar baz");
            assert.equal(modal.isClosed, true);
        });

        test("gives the focus back to the editor when closed", () => {
            const { modal } = addLinkModal();
            const view = makeView("foo |bar|");
            view.focus();
            run(view, insertLink);
            modal.close();
            assert.ok(document.activeElement === view.dom);
        });

        test("preserves the selected text after a link", () => {
            const { uri, insert } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz|");
            run(view, insertLink);
            uri.value = "https://example.test/qux/";
            insert.click();
            assert.equal(view.state.doc.textContent, "foo bar baz");
        });

        test("links a selection going beyond a link as new text", () => {
            const { text, uri, remove } = addLinkModal();
            const view = makeView("foo [b|ar](https://example.test/) baz|");
            run(view, insertLink);
            assert.equal(text.value, "ar baz");
            assert.equal(uri.value, "https://");
            assert.equal(remove.disabled, true);
        });

        test("changes the whole link with formatted parts", () => {
            const { text, uri, insert } = addLinkModal();
            const view = makeView("[f|oo **bar**](https://example.test/)");
            run(view, insertLink);
            assert.equal(text.value, "foo bar");
            uri.value = "https://example.test/qux/";
            insert.click();
            assert.equal(markdown(view), "[foo **bar**](https://example.test/qux/)");
        });
    });

    describe("insertImage", () => {
        /**
         * Add the modal to choose images as the panel renders it
         */
        function addImagesModal() {
            document.body.insertAdjacentHTML(
                "beforeend",
                `<div class="modal" id="imagesModal"><div class="modal-container"><form>
                    <input class="image-picker" type="hidden" name="imagesModal[imagepicker]" data-src="/panel/pages/blog/files/">
                    <div class="image-picker-empty-state">No images</div>
                    <button type="button" id="pick-image" data-command="pick-image">Continue</button>
                </form></div></div>`,
            );
            app.modals.imagesModal = new Modal(document.getElementById("imagesModal") as HTMLElement);
            return app.modals.imagesModal;
        }

        /**
         * Answer the request of the images of the page
         */
        function respondImages() {
            XMLHttpRequestStub.last.respondJson("success", "", {
                "image.jpg": { type: "image", filename: "image.jpg", uri: "/site/blog/image.jpg", thumbnail: "/site/blog/image.jpg/thumbnail" },
                "document.pdf": { type: "document", filename: "document.pdf", uri: "/site/blog/document.pdf" },
            });
        }

        const thumbnails = () => Array.from(document.querySelectorAll<HTMLElement>(".image-picker-thumbnail"), (thumbnail) => thumbnail.dataset.filename);

        test("is available only where images can be inserted", () => {
            assert.equal(canRun(makeView("foo|"), insertImage), true);
            assert.equal(canRun(makeView("```\nfoo|\n```"), insertImage), false);
        });

        test("opens the modal with the images of the page", () => {
            const modal = addImagesModal();
            run(makeView("foo|"), insertImage);
            assert.equal(modal.isOpen, true);
            assert.equal(XMLHttpRequestStub.last.url, "/panel/pages/blog/files/");
            respondImages();
            assert.deepEqual(thumbnails(), ["image.jpg"]);
            assert.equal((document.querySelector(".image-picker-empty-state") as HTMLElement).style.display, "none");
        });

        test("inserts the chosen image with its name, relative to the page", () => {
            const modal = addImagesModal();
            const view = makeView("foo |bar|");
            run(view, insertImage);
            respondImages();
            (document.querySelector(".image-picker-thumbnail") as HTMLElement).click();
            (document.getElementById("pick-image") as HTMLElement).click();
            assert.equal(markdown(view), "foo ![](image.jpg)");
            assert.equal(modal.isClosed, true);
        });

        test("inserts an image when double clicked", () => {
            addImagesModal();
            const view = makeView("foo|");
            run(view, insertImage);
            respondImages();
            (document.querySelector(".image-picker-thumbnail") as HTMLElement).dispatchEvent(new MouseEvent("dblclick", { bubbles: true }));
            assert.equal(markdown(view), "foo![](image.jpg)");
        });

        test("does not insert images without a choice", () => {
            const modal = addImagesModal();
            const view = makeView("foo|");
            run(view, insertImage);
            respondImages();
            (document.getElementById("pick-image") as HTMLElement).click();
            assert.equal(markdown(view), "foo");
            assert.equal(modal.isClosed, true);
        });

        test("shows the images loaded again when opened again", () => {
            const modal = addImagesModal();
            const view = makeView("foo|");
            run(view, insertImage);
            respondImages();
            modal.close();
            run(view, insertImage);
            respondImages();
            assert.deepEqual(thumbnails(), ["image.jpg"]);
        });
    });
});
