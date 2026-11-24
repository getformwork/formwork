import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import type { Command } from "prosemirror-state";
import { addRowBefore, CellSelection } from "prosemirror-tables";
import type { EditorView } from "prosemirror-view";
import { schema } from "../../../../src/ts/components/editor/markdown-schema";
import { TableGap } from "../../../../src/ts/components/editor/markdown-table-gap";
import { insertTable } from "../../../../src/ts/components/editor/markdown-tables";
import { app } from "../../../../src/ts/services/app";
import { setTranslation } from "../../../helpers/panel";
import { cellPositions, makeTableEditor, makeTableView, markdown, press, type } from "../../../helpers/prosemirror";

beforeEach(() => {
    app.config.siteUri = "/site/";
    setTranslation();
    // The menu binds the link modal of the panel
    Object.assign(app, { modals: { linkModal: { element: document.createElement("div") } } });
});

const table = "| foo | bar |\n| --- | --- |\n| baz | qux |";

/**
 * Run a command on an editor
 */
const run = (view: EditorView, command: Command) => command(view.state, view.dispatch, view);

/**
 * Select all the cells of the first table of an editor
 */
function selectTable(view: EditorView) {
    const positions = cellPositions(view);
    const { doc } = view.state;
    view.dispatch(view.state.tr.setSelection(CellSelection.create(doc, positions[0], positions[positions.length - 1])));
}

/**
 * Get the types of the cells of the rows of the first table of an editor
 */
function cellTypes(view: EditorView) {
    const rows: string[][] = [];
    view.state.doc.descendants((node) => {
        if (node.type === schema.nodes.table_row) {
            rows.push(Array.from({ length: node.childCount }, (_, index) => node.child(index).type.name));
        }
        return !node.isTextblock;
    });
    return rows;
}

/**
 * Place the cursor of an editor after its first table, as tall as the table
 */
function placeAfterTable(view: EditorView) {
    let after = -1;
    view.state.doc.descendants((node, pos) => {
        if (after < 0 && node.type === schema.nodes.table) {
            after = pos + node.nodeSize;
        }
        return after < 0 && !node.isTextblock;
    });
    view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(after))));
}

describe("Markdown Tables", () => {
    describe("insertTable", () => {
        test("inserts a table with a header row and a body row of two columns, with the cursor in its first cell", () => {
            const view = makeTableView("foo\n\n^bar");
            run(view, insertTable);
            type(view, "baz");
            assert.equal(markdown(view), "foo\n\n| baz |     |\n| --- | --- |\n|     |     |\n\nbar");
        });

        test("adds a paragraph after a table at the end of the document", () => {
            const view = makeTableView("foo^");
            run(view, insertTable);
            const { doc } = view.state;
            assert.equal(doc.childCount, 3);
            assert.equal(doc.child(1).type, schema.nodes.table);
            assert.equal(doc.child(2).type, schema.nodes.paragraph);
        });

        test("is not available in tables", () => {
            const view = makeTableView("| foo^ |\n| --- |");
            assert.equal(insertTable(view.state), false);
        });
    });

    describe("keys", () => {
        test("selects the next cell with Tab, adding a row after the last one", () => {
            const view = makeTableView(table.replace("baz", "baz^"));
            press(view, "Tab");
            type(view, "1");
            press(view, "Tab");
            type(view, "2");
            assert.equal(markdown(view), "| foo | bar |\n| --- | --- |\n| baz | 1   |\n| 2   |     |");
        });

        test("selects the previous cell with Shift+Tab", () => {
            const view = makeTableView(table.replace("baz", "^baz"));
            press(view, "Tab", { shiftKey: true });
            type(view, "1");
            assert.equal(markdown(view), "| foo | 1   |\n| --- | --- |\n| baz | qux |");
        });

        test("moves to the cell below with Enter, leaving the table after the last row", () => {
            const view = makeTableView(`${table.replace("bar", "^bar")}\n\nfoo`);
            press(view, "Enter");
            type(view, "1");
            press(view, "Enter");
            type(view, "2");
            assert.equal(markdown(view), "| foo | bar  |\n| --- | ---- |\n| baz | 1qux |\n\n2foo");
        });

        test("adds a paragraph when leaving a table at the end of the document", () => {
            const view = makeTableView(table.replace("baz", "^baz"));
            press(view, "Enter");
            type(view, "1");
            assert.equal(markdown(view), `${table}\n\n1`);
        });

        test("moves to the cell above with Shift+Enter, staying in the first row", () => {
            const view = makeTableView(table.replace("qux", "^qux"));
            press(view, "Enter", { shiftKey: true });
            press(view, "Enter", { shiftKey: true });
            type(view, "1");
            assert.equal(markdown(view), "| foo | 1bar |\n| --- | ---- |\n| baz | qux  |");
        });

        test("leaves the table with Mod+Enter", () => {
            const view = makeTableView(`${table.replace("foo", "^foo")}\n\nfoo`);
            press(view, "Enter", { ctrlKey: true });
            type(view, "1");
            assert.equal(markdown(view), `${table}\n\n1foo`);
        });

        test("lifts a table out of a list with Mod+[ and the cursor after it", () => {
            const view = makeTableView("* foo\n\n  | bar |\n  | --- |\n* baz");
            placeAfterTable(view);
            press(view, "[", { ctrlKey: true });
            assert.equal(markdown(view), "* foo\n\n| bar |\n| --- |\n\n* baz");
            assert.ok(view.state.selection instanceof TableGap);
        });

        test("wraps a table in a list with Shift+Ctrl+8 and Shift+Ctrl+9 and the cursor after it", () => {
            const view = makeTableView(`foo\n\n${table}`);
            placeAfterTable(view);
            press(view, "8", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "foo\n\n* | foo | bar |\n  | --- | --- |\n  | baz | qux |");
            assert.ok(view.state.selection instanceof TableGap);

            // Pressed again, the keys remove the list
            press(view, "8", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), `foo\n\n${table}`);

            const ordered = makeTableView(table);
            placeAfterTable(ordered);
            press(ordered, "9", { ctrlKey: true, shiftKey: true });
            assert.equal(ordered.state.doc.child(0).type, schema.nodes.ordered_list);
        });

        test("empties the selected cells, then removes the whole table once empty", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            selectTable(view);
            press(view, "Backspace");
            assert.equal(markdown(view), "foo\n\n|     |     |\n| --- | --- |\n|     |     |\n\nbar");
            assert.ok(view.state.selection instanceof CellSelection);
            press(view, "Backspace");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("removes an empty table also with Delete", () => {
            const view = makeTableView(`foo\n\n|  |\n| --- |\n\nbar`);
            selectTable(view);
            press(view, "Delete");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("empties the selected rows, then deletes them once empty keeping the cursor in the table", () => {
            const view = makeTableView(`${table}\n| one | two |`);
            const positions = cellPositions(view);
            view.dispatch(view.state.tr.setSelection(CellSelection.rowSelection(view.state.doc.resolve(positions[2]))));
            press(view, "Backspace");
            assert.equal(markdown(view), "| foo | bar |\n| --- | --- |\n|     |     |\n| one | two |");
            press(view, "Backspace");
            assert.equal(markdown(view), "| foo | bar |\n| --- | --- |\n| one | two |");
            type(view, "1");
            assert.equal(markdown(view), "| foo  | bar |\n| ---- | --- |\n| 1one | two |");
        });

        test("empties the selected columns, then deletes them once empty", () => {
            const view = makeTableView(table);
            const positions = cellPositions(view);
            view.dispatch(view.state.tr.setSelection(CellSelection.colSelection(view.state.doc.resolve(positions[0]))));
            press(view, "Delete");
            assert.equal(markdown(view), "|     | bar |\n| --- | --- |\n|     | qux |");
            press(view, "Delete");
            assert.equal(markdown(view), "| bar |\n| --- |\n| qux |");
        });

        test("makes the next row the header when the header row is deleted", () => {
            const view = makeTableView("|  |  |\n| --- | --- |\n| baz | qux |");
            const positions = cellPositions(view);
            view.dispatch(view.state.tr.setSelection(CellSelection.rowSelection(view.state.doc.resolve(positions[0]))));
            press(view, "Backspace");
            assert.equal(markdown(view), "| baz | qux |\n| --- | --- |");
            assert.deepEqual(cellTypes(view), [["table_header", "table_header"]]);
        });

        test("empties the selected cells without deleting anything when they are not whole rows or columns", () => {
            const view = makeTableView("|  |  |\n| --- | --- |\n|  |  |");
            const positions = cellPositions(view);
            view.dispatch(view.state.tr.setSelection(CellSelection.create(view.state.doc, positions[0])));
            press(view, "Backspace");
            assert.deepEqual(cellTypes(view), [
                ["table_header", "table_header"],
                ["table_cell", "table_cell"],
            ]);
        });
    });

    describe("normalizeTables", () => {
        test("keeps the first row as the header", () => {
            const view = makeTableView(table.replace("foo", "^foo"));
            run(view, addRowBefore);
            assert.deepEqual(cellTypes(view), [
                ["table_header", "table_header"],
                ["table_cell", "table_cell"],
                ["table_cell", "table_cell"],
            ]);
            assert.equal(markdown(view), "|     |     |\n| --- | --- |\n| foo | bar |\n| baz | qux |");
        });

        test("gives the cells the alignment of their column", () => {
            const view = makeTableView("| foo | bar |\n| :-: | --: |\n| baz | qux^ |");
            press(view, "Tab");
            const row = view.state.doc.child(0).child(2);
            assert.deepEqual([row.child(0).attrs.align, row.child(1).attrs.align], ["center", "right"]);
        });
    });

    describe("toolbar", () => {
        test("inserts a table with its button", () => {
            const editor = makeTableEditor("foo^");
            (document.querySelector('.editor-toolbar [data-tooltip="Table"]') as HTMLButtonElement).click();
            type(editor.view, "bar");
            assert.equal(markdown(editor.view), "foo\n\n| bar |     |\n| --- | --- |\n|     |     |");
        });

        test("lifts a table out of a list with the button decreasing the indent and the cursor after it", () => {
            const editor = makeTableEditor("* foo\n\n  | bar^ |\n  | --- |");
            const button = document.querySelector('.editor-toolbar [data-tooltip="Decrease indent"]') as HTMLButtonElement;
            // The cells cannot be lifted out of the table
            assert.equal(button.disabled, true);

            placeAfterTable(editor.view);
            assert.equal(button.disabled, false);
            button.click();
            assert.equal(markdown(editor.view), "* foo\n\n| bar |\n| --- |");
            assert.equal(button.disabled, true);
        });

        test("wraps a table in a list with the buttons of the lists and the cursor before it", () => {
            const editor = makeTableEditor(`foo^\n\n${table}`);
            const { doc } = editor.view.state;
            editor.view.dispatch(editor.view.state.tr.setSelection(new TableGap(doc.resolve(doc.child(0).nodeSize), "before")));
            const button = document.querySelector('.editor-toolbar [data-tooltip="Numbered list"]') as HTMLButtonElement;
            assert.equal(button.disabled, false);
            button.click();
            assert.equal(editor.view.state.doc.child(1).type, schema.nodes.ordered_list);
            assert.equal(editor.view.state.doc.child(1).firstChild?.firstChild?.type, schema.nodes.table);
        });

        test("toggles the list of a table with the buttons of the lists and the cursor before it", () => {
            const editor = makeTableEditor(`foo^\n\n${table}`);
            const { doc } = editor.view.state;
            editor.view.dispatch(editor.view.state.tr.setSelection(new TableGap(doc.resolve(doc.child(0).nodeSize), "before")));
            const bullet = document.querySelector('.editor-toolbar [data-tooltip="Bullet list"]') as HTMLButtonElement;
            const numbered = document.querySelector('.editor-toolbar [data-tooltip="Numbered list"]') as HTMLButtonElement;

            bullet.click();
            assert.equal(bullet.classList.contains("is-active"), true);
            numbered.click();
            assert.equal(editor.view.state.doc.child(1).type, schema.nodes.ordered_list);
            assert.equal(numbered.classList.contains("is-active"), true);
            numbered.click();
            assert.equal(markdown(editor.view), `foo\n\n${table}`);
            assert.ok(editor.view.state.selection instanceof TableGap);
        });

        test("leaves the lists around a table unchanged from its cells", () => {
            makeTableEditor("* foo\n\n  | bar^ |\n  | --- |");
            for (const label of ["Bullet list", "Numbered list", "Decrease indent"]) {
                assert.equal((document.querySelector(`.editor-toolbar [data-tooltip="${label}"]`) as HTMLButtonElement).disabled, true);
            }
        });

        test("shows the cells as paragraphs, disabling the other block types", () => {
            makeTableEditor(table.replace("foo", "foo^"));
            const menuButton = document.querySelector(".editor-toolbar .dropdown-button") as HTMLButtonElement;
            const items = Array.from(document.querySelectorAll<HTMLButtonElement>(".editor-toolbar .dropdown-item"));
            assert.equal(menuButton.textContent, "Paragraph");
            assert.equal(menuButton.disabled, false);
            assert.deepEqual(
                items.filter((item) => item.classList.contains("is-active")).map((item) => item.textContent),
                ["Paragraph"],
            );
            assert.equal(
                items.every((item) => item.disabled),
                true,
            );
        });
    });
});
