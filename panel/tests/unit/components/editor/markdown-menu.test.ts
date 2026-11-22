import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { TextSelection } from "prosemirror-state";
import { MarkdownView } from "../../../../src/ts/components/editor/markdown-view";
import { Toolbar } from "../../../../src/ts/components/editor/toolbar";
import { app } from "../../../../src/ts/services/app";
import { setTranslation } from "../../../helpers/panel";
import { markdown, placeCursor, type } from "../../../helpers/prosemirror";

beforeEach(() => {
    app.config.siteUri = "/site/";
    setTranslation();
    // The menu binds the link modal of the panel
    Object.assign(app, { modals: { linkModal: { element: document.createElement("div") } } });
});

/**
 * Make a Markdown editor with its toolbar, as the editor input does, placing the cursor at `|`
 */
function makeEditor(content: string, editable = true) {
    document.body.innerHTML = '<div class="editor-toolbar"></div><div class="editor-wrap"></div>';
    const toolbar = new Toolbar(document.querySelector(".editor-toolbar") as HTMLElement);
    const editor = new MarkdownView("content", document.querySelector(".editor-wrap") as HTMLElement, content, mock.fn(), { editable, toolbar });
    placeCursor(editor.view);
    return editor;
}

/**
 * Get a button of the toolbar by its label, which is also its tooltip
 */
// happy-dom does not reflect `ariaLabel` to the attribute, unlike browsers
const button = (label: string) => document.querySelector(`.editor-toolbar [data-tooltip="${label}"]`) as HTMLButtonElement;

/**
 * Get an item of the menu of the block types by its text
 */
const blockItem = (text: string) => Array.from(document.querySelectorAll<HTMLButtonElement>(".editor-toolbar .dropdown-item")).find((item) => item.textContent === text) as HTMLButtonElement;

const blockMenuButton = () => document.querySelector(".editor-toolbar .dropdown-button") as HTMLButtonElement;

const activeBlockItems = () => Array.from(document.querySelectorAll(".editor-toolbar .dropdown-item.is-active"), (item) => item.textContent);

const isActive = (element: HTMLElement) => element.classList.contains("is-active");

describe("Markdown Menu", () => {
    describe("toolbar", () => {
        test("adds the buttons to the toolbar, separating their groups", () => {
            makeEditor("foo|");
            const toolbar = document.querySelector(".editor-toolbar > div") as HTMLElement;
            const children = Array.from(toolbar.children, (child) => (child as HTMLElement).ariaLabel ?? child.className);
            assert.deepEqual(children, ["dropdown", "Bold", "Italic", "Code", "separator", "Bullet list", "Numbered list", "Quote", "Increase indent", "Decrease indent", "separator", "Image", "Link", "separator", "Undo", "Redo"]);
        });

        test("removes its buttons from the toolbar when the editor is destroyed", () => {
            makeEditor("foo|").destroy();
            assert.equal((document.querySelector(".editor-toolbar") as HTMLElement).children.length, 0);
        });

        test("lists the types of the blocks in a menu", () => {
            makeEditor("foo|");
            const items = Array.from(document.querySelectorAll(".editor-toolbar .dropdown-item"), (item) => item.textContent);
            assert.deepEqual(items, ["Paragraph", "Code", "Heading 1", "Heading 2", "Heading 3", "Heading 4", "Heading 5", "Heading 6"]);
            assert.equal(blockMenuButton().dataset.dropdown, "dropdown-content-editor-level");
            assert.equal(document.getElementById("dropdown-content-editor-level")?.classList.contains("dropdown-menu"), true);
        });

        test("translates the labels", () => {
            setTranslation({ "panel.editor.bold": "Grassetto" }, "it");
            makeEditor("foo|");
            assert.notEqual(button("Grassetto"), null);
        });
    });

    describe("state", () => {
        test("shows the type of the block at the cursor", () => {
            const editor = makeEditor("## foo|\n\nbar");
            assert.equal(blockMenuButton().textContent, "Heading 2");
            assert.deepEqual(activeBlockItems(), ["Heading 2"]);
            editor.view.dispatch(editor.view.state.tr.setSelection(TextSelection.atEnd(editor.view.state.doc)));
            assert.equal(blockMenuButton().textContent, "Paragraph");
            assert.deepEqual(activeBlockItems(), ["Paragraph"]);
        });

        test("shows the type of a code block", () => {
            makeEditor("```\nfoo|\n```");
            assert.equal(blockMenuButton().textContent, "Code");
        });

        test("marks the formatting at the cursor", () => {
            makeEditor("**foo|** bar");
            assert.equal(isActive(button("Bold")), true);
            assert.equal(isActive(button("Italic")), false);
        });

        test("marks the link at the cursor", () => {
            makeEditor("[fo|o](https://example.test/)");
            assert.equal(isActive(button("Link")), true);
        });

        test("disables the buttons that cannot be used at the cursor", () => {
            makeEditor("```\nfoo|\n```");
            assert.equal(button("Bold").disabled, true);
            assert.equal(button("Link").disabled, false);
            assert.equal(button("Image").disabled, true);
            assert.equal(button("Increase indent").disabled, true);
        });

        test("enables the indentation in lists", () => {
            makeEditor("* foo\n* bar|");
            assert.equal(button("Increase indent").disabled, false);
            assert.equal(button("Decrease indent").disabled, false);
        });

        test("enables undo and redo when there are changes", () => {
            const editor = makeEditor("foo|");
            assert.equal(button("Undo").disabled, true);
            type(editor.view, "bar");
            assert.equal(button("Undo").disabled, false);
            assert.equal(button("Redo").disabled, true);
            button("Undo").click();
            assert.equal(button("Redo").disabled, false);
        });

        test("disables the toolbar of a read-only editor", () => {
            makeEditor("foo|", false);
            assert.equal(button("Bold").disabled, true);
            assert.equal(button("Undo").disabled, true);
            assert.equal(blockMenuButton().disabled, true);
        });

        test("updates the toolbar when the editor becomes read-only", () => {
            const editor = makeEditor("foo|");
            editor.editable = false;
            assert.equal(button("Bold").disabled, true);
            editor.editable = true;
            assert.equal(button("Bold").disabled, false);
        });

        test("shows the type of the block of a read-only editor", () => {
            makeEditor("foo|", false);
            assert.equal(blockMenuButton().textContent, "Paragraph");
            assert.deepEqual(activeBlockItems(), ["Paragraph"]);
        });

        test("shows the type of the block in a list", () => {
            makeEditor("* foo|");
            assert.equal(blockMenuButton().textContent, "Paragraph");
            assert.deepEqual(activeBlockItems(), ["Paragraph"]);
        });
    });

    describe("commands", () => {
        test("runs the command of a button, giving the focus back to the editor", () => {
            const editor = makeEditor("|foo|");
            button("Bold").click();
            assert.equal(markdown(editor.view), "**foo**");
            assert.ok(document.activeElement === editor.view.dom);
            assert.equal(isActive(button("Bold")), true);
        });

        test("sets the type of the block with the menu", () => {
            const editor = makeEditor("foo|");
            blockItem("Heading 3").click();
            assert.equal(markdown(editor.view), "### foo");
            assert.equal(blockMenuButton().textContent, "Heading 3");
            blockItem("Paragraph").click();
            assert.equal(markdown(editor.view), "foo");
        });

        test("wraps in lists and quotes", () => {
            const editor = makeEditor("foo|");
            button("Quote").click();
            assert.equal(markdown(editor.view), "> foo");
            button("Decrease indent").click();
            button("Numbered list").click();
            assert.equal(markdown(editor.view), "1. foo");
        });

        test("does not run commands when the button of the menu is clicked", () => {
            const editor = makeEditor("foo|");
            blockMenuButton().click();
            assert.equal(markdown(editor.view), "foo");
        });
    });
});
