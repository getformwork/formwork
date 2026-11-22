import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { addToFileInputs, removeFromFileInputs, replaceInFileInputs } from "../../../../src/ts/components/files/file-inputs";
import type { SelectInput } from "../../../../src/ts/components/inputs/select-input";
import type { TagsInput } from "../../../../src/ts/components/inputs/tags-input";
import { Form } from "../../../../src/ts/services/form";
import { setTranslation } from "../../../helpers/panel";

beforeEach(() => {
    setTranslation();
});

const files = {
    "document.pdf": { value: "document.pdf", icon: "file-pdf" },
    "image.jpg": { value: "image.jpg", icon: "file-image", thumb: "/site/blog/image.jpg/thumbnail" },
};

/**
 * Get the options of a select with the files, as the panel renders it
 */
const fileOptions = (only?: string) =>
    Object.entries(files)
        .filter(([name]) => only === undefined || name.endsWith(only))
        .map(([name, { icon }]) => `<option value="${name}" data-icon="${icon}">${name}</option>`)
        .join("");

/**
 * Make a form with the file and image inputs of a page, as the panel renders them
 */
function makeForm({ file = "", image = "", attachments = "", gallery = "" } = {}) {
    const images = Object.fromEntries(Object.entries(files).filter(([name]) => name.endsWith(".jpg")));
    document.body.innerHTML = `
        <form data-form="page-editor-form">
            <div class="form-input-wrap"><select class="form-select form-file" id="file" name="file"><option value="">None</option>${fileOptions()}</select></div>
            <div class="form-input-wrap"><select class="form-select form-image" id="image" name="image"><option value="">None</option>${fileOptions(".jpg")}</select></div>
            <div class="form-input-wrap"><input class="form-input form-input-tags form-files" type="text" id="attachments" name="attachments" value="${attachments}" data-options='${JSON.stringify(files)}' data-accept="options"></div>
            <div class="form-input-wrap"><input class="form-input form-input-tags form-images" type="text" id="gallery" name="gallery" value="${gallery}" data-options='${JSON.stringify(images)}' data-accept="options"></div>
            <div class="form-input-wrap"><select class="form-select" id="other" name="other"><option value="foo">Foo</option></select></div>
        </form>`;
    // happy-dom selects the option before the one with `selected` when parsing, unlike browsers
    (document.querySelector("select[name=file]") as HTMLSelectElement).value = file;
    (document.querySelector("select[name=image]") as HTMLSelectElement).value = image;
    const form = new Form(document.querySelector("form") as HTMLFormElement, { preventUnloadOnChanges: false });
    return {
        form,
        file: form.inputs.file as SelectInput,
        image: form.inputs.image as SelectInput,
        attachments: form.inputs.attachments as TagsInput,
        gallery: form.inputs.gallery as TagsInput,
    };
}

/**
 * Get the values of the options of an input
 */
const options = (name: string) => Array.from(document.querySelectorAll<HTMLElement>(`.dropdown-list[data-for="${name}"] .dropdown-item`), (item) => item.dataset.value);

/**
 * Get the values of the suggestions of a tags input
 */
const suggestions = (name: string) => Array.from((document.getElementById(name) as HTMLElement).parentElement?.querySelectorAll<HTMLElement>(".dropdown-list .dropdown-item") ?? [], (item) => item.dataset.value);

describe("File Inputs", () => {
    describe("addToFileInputs", () => {
        test("adds a file to the inputs of the files, sorted", () => {
            const { form } = makeForm();
            addToFileInputs(form, { name: "archive.zip", type: "archive" });
            assert.deepEqual(options("file"), ["", "archive.zip", "document.pdf", "image.jpg"]);
            assert.deepEqual(suggestions("attachments"), ["archive.zip", "document.pdf", "image.jpg"]);
        });

        test("adds images also to the inputs of the images", () => {
            const { form } = makeForm();
            addToFileInputs(form, { name: "photo.jpg", type: "image", thumbnail: "/site/blog/photo.jpg/thumbnail" });
            assert.deepEqual(options("image"), ["", "image.jpg", "photo.jpg"]);
            assert.deepEqual(suggestions("gallery"), ["image.jpg", "photo.jpg"]);
            const thumbnail = document.querySelector('.dropdown-list[data-for="image"] [data-value="photo.jpg"] img') as HTMLImageElement;
            assert.equal(thumbnail.getAttribute("src"), "/site/blog/photo.jpg/thumbnail");
        });

        test("does not add other files to the inputs of the images", () => {
            const { form } = makeForm();
            addToFileInputs(form, { name: "archive.zip", type: "archive" });
            assert.deepEqual(options("image"), ["", "image.jpg"]);
            assert.deepEqual(suggestions("gallery"), ["image.jpg"]);
        });

        test("leaves the other inputs unchanged", () => {
            const { form } = makeForm();
            addToFileInputs(form, { name: "archive.zip", type: "archive" });
            assert.deepEqual(options("other"), ["foo"]);
        });

        test("does nothing without a form", () => {
            assert.doesNotThrow(() => addToFileInputs(undefined, { name: "archive.zip", type: "archive" }));
        });
    });

    describe("removeFromFileInputs", () => {
        test("removes a file from the inputs", () => {
            const { form } = makeForm();
            removeFromFileInputs(form, "image.jpg");
            assert.deepEqual(options("file"), ["", "document.pdf"]);
            assert.deepEqual(options("image"), [""]);
            assert.deepEqual(suggestions("attachments"), ["document.pdf"]);
            assert.deepEqual(suggestions("gallery"), []);
        });

        test("removes a deleted file from the values of the inputs", () => {
            const { form, file, image, attachments, gallery } = makeForm({ file: "image.jpg", image: "image.jpg", attachments: "document.pdf, image.jpg", gallery: "image.jpg" });
            removeFromFileInputs(form, "image.jpg");
            assert.equal(file.value, "");
            assert.equal(image.value, "");
            assert.equal(attachments.value, "document.pdf");
            assert.equal(gallery.value, "");
        });
    });

    describe("replaceInFileInputs", () => {
        test("replaces a renamed file in the inputs", () => {
            const { form } = makeForm();
            replaceInFileInputs(form, "document.pdf", { name: "renamed.pdf", type: "pdf" });
            assert.deepEqual(options("file"), ["", "image.jpg", "renamed.pdf"]);
            assert.deepEqual(suggestions("attachments"), ["image.jpg", "renamed.pdf"]);
        });

        test("does not add a renamed file to the inputs of the images if not an image", () => {
            const { form } = makeForm();
            replaceInFileInputs(form, "document.pdf", { name: "renamed.pdf", type: "pdf" });
            assert.deepEqual(options("image"), ["", "image.jpg"]);
            assert.deepEqual(suggestions("gallery"), ["image.jpg"]);
        });

        test("keeps a renamed file in the values of the inputs with the new name", () => {
            const { form, file, image, attachments, gallery } = makeForm({ file: "image.jpg", image: "image.jpg", attachments: "image.jpg, document.pdf", gallery: "image.jpg" });
            replaceInFileInputs(form, "image.jpg", { name: "renamed.jpg", type: "image" });
            assert.equal(file.value, "renamed.jpg");
            assert.equal(image.value, "renamed.jpg");
            assert.equal(attachments.value, "renamed.jpg, document.pdf");
            assert.equal(gallery.value, "renamed.jpg");
        });
    });
});
