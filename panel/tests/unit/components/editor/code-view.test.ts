import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { CodeView } from "../../../../src/ts/components/editor/code-view";
import { setTranslation } from "../../../helpers/panel";

beforeEach(() => {
    setTranslation();
});

/**
 * Make a code editor with its toolbar, as the editor input does
 */
function makeEditor(content = "foo", options = {}) {
    document.body.innerHTML = '<div class="editor-wrap"><div class="editor-toolbar"><button type="button" data-command="toggle-markdown"></button></div></div>';
    const handler = mock.fn();
    const editor = new CodeView(document.querySelector(".editor-wrap") as HTMLElement, content, handler, options);
    return { editor, handler };
}

/**
 * Insert a text at the end of the content of an editor
 */
const append = (editor: CodeView, text: string) => editor.view.dispatch({ changes: { from: editor.view.state.doc.length, insert: text } });

// happy-dom does not reflect `ariaLabel` to the attribute, unlike browsers
const button = (label: string) => document.querySelector(`.editor-toolbar [data-tooltip="${label}"]`) as HTMLButtonElement;

describe("CodeView", () => {
    describe("content", () => {
        test("shows the content", () => {
            const { editor } = makeEditor("# foo\n\nbar");
            assert.equal(editor.content, "# foo\n\nbar");
            assert.equal(document.querySelectorAll(".cm-line").length, 3);
        });

        test("calls the handler with the content when it changes", () => {
            const { editor, handler } = makeEditor("foo");
            append(editor, "bar");
            assert.deepEqual(
                handler.mock.calls.map((call) => call.arguments[0]),
                ["foobar"],
            );
        });

        test("sets the content", () => {
            const { editor } = makeEditor("foo");
            editor.content = "bar\nbaz";
            assert.equal(editor.content, "bar\nbaz");
        });

        test("shows the placeholder of an empty editor", () => {
            makeEditor("", { placeholder: "Write here" });
            assert.equal(document.querySelector(".cm-placeholder")?.textContent, "Write here");
        });

        test("marks the editor while focused", () => {
            const { editor } = makeEditor();
            const wrap = document.querySelector(".editor-wrap") as HTMLElement;
            editor.view.contentDOM.dispatchEvent(new FocusEvent("focus"));
            assert.equal(wrap.classList.contains("focused"), true);
            editor.view.contentDOM.dispatchEvent(new FocusEvent("blur"));
            assert.equal(wrap.classList.contains("focused"), false);
        });
    });

    describe("editable", () => {
        test("makes the editor read-only and editable", () => {
            const { editor } = makeEditor();
            assert.equal(editor.editable, true);
            editor.editable = false;
            assert.equal(editor.editable, false);
            assert.equal(editor.view.contentDOM.getAttribute("contenteditable"), "false");
            editor.editable = true;
            assert.equal(editor.view.contentDOM.getAttribute("contenteditable"), "true");
        });

        test("makes a read-only editor", () => {
            const { editor } = makeEditor("foo", { editable: false });
            assert.equal(editor.editable, false);
        });
    });

    describe("toolbar", () => {
        test("adds undo and redo before the other buttons of the toolbar", () => {
            makeEditor();
            const buttons = Array.from(document.querySelectorAll<HTMLElement>(".editor-toolbar button"), (element) => element.dataset.tooltip ?? element.dataset.command);
            assert.deepEqual(buttons, ["Undo", "Redo", "toggle-markdown"]);
        });

        test("removes its buttons from the toolbar when destroyed", () => {
            const { editor } = makeEditor();
            editor.destroy();
            const buttons = Array.from(document.querySelectorAll<HTMLElement>(".editor-toolbar button"), (element) => element.dataset.command);
            assert.deepEqual(buttons, ["toggle-markdown"]);
        });

        test("enables undo and redo when there are changes", () => {
            const { editor } = makeEditor("foo");
            assert.equal(button("Undo").disabled, true);
            assert.equal(button("Redo").disabled, true);
            append(editor, "bar");
            assert.equal(button("Undo").disabled, false);
        });

        test("undoes and redoes the changes with the buttons, keeping the focus in the editor", () => {
            const { editor } = makeEditor("foo");
            append(editor, "bar");
            const mousedown = new MouseEvent("mousedown", { bubbles: true, cancelable: true });
            button("Undo").dispatchEvent(mousedown);
            assert.equal(mousedown.defaultPrevented, true);
            button("Undo").click();
            assert.equal(editor.content, "foo");
            assert.equal(button("Redo").disabled, false);
            button("Redo").click();
            assert.equal(editor.content, "foobar");
        });

        test("removes the buttons with the editor", () => {
            const { editor } = makeEditor();
            editor.destroy();
            assert.equal(button("Undo"), null);
        });

        test("undoes the changes with the button activated with the keyboard", () => {
            const { editor } = makeEditor("foo");
            append(editor, "bar");
            button("Undo").dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, detail: 0 }));
            assert.equal(editor.content, "foo");
        });
    });
});
