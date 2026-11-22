import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { ImagePicker } from "../../../../src/ts/components/inputs/image-picker";
import { app } from "../../../../src/ts/services/app";
import type { Modal } from "../../../../src/ts/services/modal";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../../stubs/xhr";

beforeEach(() => {
    installXMLHttpRequestStub();
    app.modals = {};
});

/**
 * Make an image picker in a modal, as the panel renders it
 */
function makePicker() {
    document.body.innerHTML = `
        <div class="modal" id="imagesModal"><div class="modal-container"><form>
            <div class="image-picker-field">
                <input class="image-picker" type="hidden" name="imagesModal[imagepicker]" data-src="/panel/pages/blog/files/">
                <div class="image-picker-empty-state">No images</div>
            </div>
        </form></div></div>`;
    return new ImagePicker(document.querySelector(".image-picker") as HTMLInputElement);
}

/**
 * Answer the request of the files of the page
 */
function respondFiles(files: Record<string, unknown> = {}) {
    XMLHttpRequestStub.last.respondJson("success", "", files);
}

const images = {
    "first.jpg": { type: "image", filename: "first.jpg", uri: "/site/blog/first.jpg", thumbnail: "/site/blog/first.jpg/thumbnail" },
    "document.pdf": { type: "document", filename: "document.pdf", uri: "/site/blog/document.pdf" },
    "second.png": { type: "image", filename: "second.png", uri: "/site/blog/second.png", thumbnail: "/site/blog/second.png/thumbnail" },
};

const thumbnails = () => Array.from(document.querySelectorAll<HTMLElement>(".image-picker-thumbnail"));

const emptyState = () => document.querySelector(".image-picker-empty-state") as HTMLElement;

describe("ImagePicker", () => {
    test("shows the thumbnails of the images of the page", () => {
        const picker = makePicker();
        picker.update();
        assert.equal(XMLHttpRequestStub.last.url, "/panel/pages/blog/files/");
        respondFiles(images);
        assert.deepEqual(
            thumbnails().map((thumbnail) => thumbnail.dataset.filename),
            ["first.jpg", "second.png"],
        );
        assert.equal(thumbnails()[0].style.backgroundImage, 'url("/site/blog/first.jpg/thumbnail")');
        assert.equal(emptyState().style.display, "none");
    });

    test("shows the empty state without images", () => {
        const picker = makePicker();
        picker.update();
        respondFiles({ "document.pdf": images["document.pdf"] });
        assert.deepEqual(thumbnails(), []);
        assert.equal(emptyState().style.display, "");
    });

    test("shows the empty state when the images cannot be loaded", () => {
        const picker = makePicker();
        picker.update();
        XMLHttpRequestStub.last.respondJson("error", "Cannot load the files");
        assert.deepEqual(thumbnails(), []);
        assert.equal(emptyState().style.display, "");
    });

    test("selects an image when clicked, holding its address", () => {
        const picker = makePicker();
        picker.update();
        respondFiles(images);
        thumbnails()[0].click();
        thumbnails()[1].click();
        assert.deepEqual(
            thumbnails().map((thumbnail) => thumbnail.classList.contains("selected")),
            [false, true],
        );
        assert.equal(picker.value, "/site/blog/second.png");
    });

    test("picks an image when double clicked", () => {
        const picker = makePicker();
        const triggerCommand = mock.fn();
        app.modals.imagesModal = { triggerCommand } as unknown as Modal;
        picker.update();
        respondFiles(images);
        thumbnails()[1].dispatchEvent(new MouseEvent("dblclick", { bubbles: true }));
        assert.equal(picker.value, "/site/blog/second.png");
        assert.deepEqual(triggerCommand.mock.calls[0].arguments, ["pick-image"]);
    });

    test("replaces the thumbnails when updated again", () => {
        const picker = makePicker();
        picker.update();
        respondFiles(images);
        picker.update();
        assert.deepEqual(thumbnails(), []);
        respondFiles(images);
        assert.equal(thumbnails().length, 2);
    });
});
