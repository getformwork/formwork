import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import type { EditorView as CodeMirrorView } from "@codemirror/view";
import type { EditorView as ProseMirrorView } from "prosemirror-view";
import { EditorInput } from "../../../../src/ts/components/inputs/editor-input";
import { app } from "../../../../src/ts/services/app";
import { setTranslation } from "../../../helpers/panel";
import { placeCursor, type } from "../../../helpers/prosemirror";

beforeEach(() => {
    app.config.siteUri = "/site/";
    setTranslation();
    // The menu of the Markdown editor binds the link modal of the panel
    Object.assign(app, { modals: { linkModal: { element: document.createElement("div") } } });
});

/**
 * Get the private state of an input
 */
const internals = (input: EditorInput) => input as unknown as { editorPromise: Promise<void>; editor?: { view: ProseMirrorView | CodeMirrorView } };

/**
 * Make an editor input in the document as the panel renders it, waiting for its editor
 */
async function makeEditor(content = "foo", attributes = "") {
    document.body.innerHTML = `<form data-form="page-editor-form"><label for="content" id="content-label">Content</label><textarea class="form-textarea editor-textarea is-loading" id="content" name="content" ${attributes}>${content}</textarea></form>`;
    const textarea = document.getElementById("content") as HTMLTextAreaElement;
    const input = new EditorInput(textarea);
    const change = mock.fn();
    textarea.addEventListener("change", change);
    await internals(input).editorPromise;
    return { input, textarea, change };
}

const toggle = () => document.querySelector("[data-command=toggle-markdown]") as HTMLButtonElement;

/**
 * Switch the mode of an input with its button, waiting for the new editor
 */
async function switchMode(input: EditorInput) {
    toggle().click();
    await internals(input).editorPromise;
}

const proseMirror = (input: EditorInput) => internals(input).editor?.view as ProseMirrorView;

const codeMirror = (input: EditorInput) => internals(input).editor?.view as CodeMirrorView;

describe("EditorInput", () => {
    describe("Markdown editor", () => {
        test("replaces the textarea with the Markdown editor and its toolbar", async () => {
            const { textarea } = await makeEditor("**foo**");
            assert.equal(textarea.style.display, "none");
            assert.equal(textarea.classList.contains("is-loading"), false);
            assert.equal(document.querySelector(".editor-wrap .ProseMirror strong")?.textContent, "foo");
            assert.equal(document.querySelector(".editor-toolbar")?.getAttribute("role"), "toolbar");
            assert.equal(document.querySelector(".editor-toolbar")?.getAttribute("aria-labelledby"), "content-label");
        });

        test("describes the editor to assistive technologies", async () => {
            await makeEditor("foo", 'required aria-describedby="content-description"');
            const editor = document.querySelector(".ProseMirror") as HTMLElement;
            assert.equal(editor.getAttribute("role"), "textbox");
            assert.equal(editor.getAttribute("aria-multiline"), "true");
            assert.equal(editor.getAttribute("aria-labelledby"), "content-label");
            assert.equal(editor.getAttribute("aria-describedby"), "content-description");
            assert.equal(editor.getAttribute("aria-required"), "true");
        });

        test("updates the textarea with the content", async () => {
            const { input, textarea } = await makeEditor("foo|");
            placeCursor(proseMirror(input));
            type(proseMirror(input), "bar");
            assert.equal(textarea.value, "foobar");
            assert.equal(input.value, "foobar");
        });

        test("notifies the changes when the user stops typing", async (t) => {
            const { input, change } = await makeEditor("foo");
            t.mock.timers.enable({ apis: ["setTimeout"] });
            type(proseMirror(input), "bar");
            assert.equal(change.mock.callCount(), 0);
            t.mock.timers.tick(500);
            assert.equal(change.mock.callCount(), 1);
            type(proseMirror(input), "baz");
            t.mock.timers.tick(500);
            assert.equal(change.mock.callCount(), 2);
        });

        test("sets the content", async () => {
            const { input, textarea } = await makeEditor("foo");
            input.value = "# bar";
            await internals(input).editorPromise;
            assert.equal(textarea.value, "# bar");
            assert.equal(document.querySelector(".ProseMirror h1")?.textContent, "bar");
        });

        test("makes a read-only editor for a disabled textarea", async () => {
            const { input } = await makeEditor("foo", "disabled");
            assert.equal(input.disabled, true);
            assert.equal(document.querySelector(".ProseMirror")?.getAttribute("contenteditable"), "false");
            assert.equal(toggle().disabled, true);
        });

        test("makes the editor read-only and editable", async () => {
            const { input } = await makeEditor("foo");
            input.disabled = true;
            await internals(input).editorPromise;
            assert.equal(input.disabled, true);
            assert.equal(toggle().disabled, true);
            input.disabled = false;
            await internals(input).editorPromise;
            assert.equal(input.disabled, false);
            assert.equal(document.querySelector(".ProseMirror")?.getAttribute("contenteditable"), "true");
        });

        test("focuses the editor when the label is clicked", async () => {
            const { input } = await makeEditor("foo");
            (document.querySelector("label") as HTMLElement).click();
            await internals(input).editorPromise;
            assert.ok(document.activeElement === proseMirror(input).dom);
        });
    });

    describe("modes", () => {
        test("switches to the code editor and back, keeping the content", async () => {
            const { input } = await makeEditor("* foo");
            await switchMode(input);
            assert.equal(document.querySelector(".ProseMirror"), null);
            assert.equal(codeMirror(input).state.doc.toString(), "* foo");
            assert.equal(toggle().classList.contains("is-active"), true);
            await switchMode(input);
            assert.equal(document.querySelector(".cm-editor"), null);
            assert.equal(document.querySelector(".ProseMirror li")?.textContent, "foo");
            assert.equal(toggle().classList.contains("is-active"), false);
        });

        test("keeps the changes made in the code editor", async () => {
            const { input, textarea } = await makeEditor("foo");
            await switchMode(input);
            codeMirror(input).dispatch({ changes: { from: 0, insert: "## " } });
            assert.equal(textarea.value, "## foo");
            await switchMode(input);
            assert.equal(document.querySelector(".ProseMirror h2")?.textContent, "foo");
        });

        test("replaces the toolbar of the editor", async () => {
            const { input } = await makeEditor("foo");
            await switchMode(input);
            assert.equal(document.querySelector(".editor-toolbar .dropdown"), null);
            assert.equal(document.querySelectorAll(".editor-toolbar [data-command=toggle-markdown]").length, 1);
        });

        test("disables the button while switching", async () => {
            const { input } = await makeEditor("foo");
            toggle().click();
            assert.equal(toggle().disabled, true);
            await internals(input).editorPromise;
            await Promise.resolve();
            assert.equal(toggle().disabled, false);
        });

        test("remembers the mode for the field of the form", async () => {
            const { input } = await makeEditor("foo");
            await switchMode(input);
            assert.equal(window.localStorage.getItem("formwork.editorMode[page-editor-form.content]"), "code");
            const { input: restored } = await makeEditor("foo");
            assert.equal(typeof codeMirror(restored).state.doc.toString, "function");
            assert.equal(document.querySelector(".cm-editor") !== null, true);
        });

        test("works without access to the local storage", async (t) => {
            t.mock.method(Storage.prototype, "getItem", () => {
                throw new DOMException("The operation is insecure.", "SecurityError");
            });
            t.mock.method(Storage.prototype, "setItem", () => {
                throw new DOMException("The operation is insecure.", "SecurityError");
            });
            const { input } = await makeEditor("foo");
            await switchMode(input);
            assert.equal(document.querySelector(".cm-editor") !== null, true);
        });

        test("makes a read-only code editor for a disabled textarea", async () => {
            window.localStorage.setItem("formwork.editorMode[page-editor-form.content]", "code");
            const { input } = await makeEditor("foo", "disabled");
            assert.equal(input.disabled, true);
        });

        test("removes the tooltip of the links when switching to the code editor", async (t) => {
            const { input } = await makeEditor("[fo|o](https://example.test/)");
            // The tooltip is removed after a delay and the resizes of the window are throttled
            t.mock.timers.enable({ apis: ["setTimeout", "Date"] });
            placeCursor(proseMirror(input));
            t.mock.timers.tick(0);
            await switchMode(input);
            // Errors are compared by message, printing the events would walk the document
            const errors: string[] = [];
            t.mock.method(console, "error", (...args: unknown[]) => errors.push(String(args[0])));
            window.addEventListener("error", (event) => errors.push(event.message));
            t.mock.timers.tick(100);
            window.dispatchEvent(new Event("resize"));
            t.mock.timers.tick(100);
            assert.deepEqual(errors, []);
            assert.equal(document.querySelector(".editor-link-tooltip"), null);
        });
    });
});
