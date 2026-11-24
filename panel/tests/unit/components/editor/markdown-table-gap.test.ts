import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { undo } from "prosemirror-history";
import { Selection } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { schema } from "../../../../src/ts/components/editor/markdown-schema";
import { TableGap } from "../../../../src/ts/components/editor/markdown-table-gap";
import { makeTableView, markdown, press, type } from "../../../helpers/prosemirror";

const table = "| foo | bar |\n| --- | --- |\n| baz | qux |";

/**
 * Return whether the cursor of an editor is in the gap before a table
 */
function isGapBefore(view: EditorView) {
    const { selection } = view.state;
    return selection instanceof TableGap && selection.side === "before";
}

/**
 * Return whether the cursor of an editor is in the gap after a table
 */
function isGapAfter(view: EditorView) {
    const { selection } = view.state;
    return selection instanceof TableGap && selection.side === "after";
}

/**
 * Place the cursor of an editor in the gap before (`-1`) or after (`1`) its first table
 */
function placeInGap(view: EditorView, side: -1 | 1) {
    let pos = -1;
    view.state.doc.forEach((node, offset) => {
        if (pos < 0 && node.type === schema.nodes.table) {
            pos = side < 0 ? offset : offset + node.nodeSize;
        }
    });
    view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(pos))));
}

/**
 * Get the class of the first table of an editor
 */
const tableClass = (view: EditorView) => (view.dom.querySelector("table") as HTMLElement).className;

describe("Markdown Table Gap", () => {
    describe("TableGap", () => {
        test("is valid only next to a table", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            const { doc } = view.state;
            const tableSize = doc.child(1).nodeSize;
            const before = doc.child(0).nodeSize;
            assert.equal(TableGap.valid(doc.resolve(before)), true);
            assert.equal(TableGap.valid(doc.resolve(before + tableSize)), true);
            assert.equal(TableGap.valid(doc.resolve(0)), false);
            assert.equal(TableGap.valid(doc.resolve(1)), false);
        });

        test("is restored from JSON with its side", () => {
            const view = makeTableView(`${table}\n\n${table}`);
            const between = view.state.doc.child(0).nodeSize;
            view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(between), "before")));
            const restored = Selection.fromJSON(view.state.doc, view.state.selection.toJSON());
            assert.ok(restored instanceof TableGap);
            assert.ok(restored.eq(view.state.selection));
            assert.equal(restored.side, "before");
        });

        test("follows the changes of the document", () => {
            const view = makeTableView(`foo\n\n${table}`);
            placeInGap(view, 1);
            view.dispatch(view.state.tr.insertText("bar", 1));
            assert.equal(isGapAfter(view), true);
        });

        test("is drawn by the table on its side", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, -1);
            assert.equal(tableClass(view), "table-gap-before");
            placeInGap(view, 1);
            assert.equal(tableClass(view), "table-gap-after");
            view.dispatch(view.state.tr.setSelection(Selection.atStart(view.state.doc)));
            assert.equal(tableClass(view), "");
        });
    });

    describe("between two tables", () => {
        const tables = (view: EditorView) => Array.from(view.dom.querySelectorAll("table"), (element) => element.className);

        test("moves from after the first table to before the second one with the arrows", () => {
            const view = makeTableView(`${table.replace("qux", "qux^")}\n\n${table}`);
            press(view, "ArrowRight");
            assert.deepEqual(tables(view), ["table-gap-after", ""]);
            press(view, "ArrowRight");
            assert.deepEqual(tables(view), ["", "table-gap-before"]);
            press(view, "ArrowLeft");
            assert.deepEqual(tables(view), ["table-gap-after", ""]);
            press(view, "ArrowRight");
            press(view, "ArrowRight");
            type(view, "1");
            assert.equal(markdown(view), `${table}\n\n| 1foo | bar |\n| ---- | --- |\n| baz  | qux |`);
        });

        test("places the cursor before the second table when clicking at its left", () => {
            const view = makeTableView(`${table}\n\n${table}`);
            const [first, second] = Array.from(view.dom.querySelectorAll("table"));
            Object.defineProperty(first, "getBoundingClientRect", { value: () => new DOMRect(20, 100, 200, 40) });
            Object.defineProperty(second, "getBoundingClientRect", { value: () => new DOMRect(20, 150, 200, 40) });
            view.dom.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0, clientX: 10, clientY: 170 }));
            assert.deepEqual(tables(view), ["", "table-gap-before"]);
            view.dom.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0, clientX: 230, clientY: 120 }));
            assert.deepEqual(tables(view), ["table-gap-after", ""]);
        });

        test("adds a paragraph keeping the cursor on its side", () => {
            const view = makeTableView(`${table}\n\n${table}`);
            const between = view.state.doc.child(0).nodeSize;

            view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(between), "before")));
            press(view, "Enter");
            assert.deepEqual(tables(view), ["", "table-gap-before"]);

            view.dispatch(view.state.tr.setSelection(new TableGap(view.state.doc.resolve(between), "after")));
            press(view, "Enter");
            type(view, "foo");
            assert.equal(markdown(view), `${table}\n\nfoo\n\n${table}`);
        });
    });

    describe("removal", () => {
        test("removes the table with Backspace after it", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, 1);
            press(view, "Backspace");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("removes the table with Delete before it", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, -1);
            press(view, "Delete");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("leaves an empty paragraph when the table was the only content", () => {
            const view = makeTableView(table);
            placeInGap(view, 1);
            press(view, "Backspace");
            assert.equal(view.state.doc.childCount, 1);
            assert.equal(view.state.doc.child(0).type, schema.nodes.paragraph);
        });

        test("restores the table and the gap with undo", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, 1);
            press(view, "Backspace");
            undo(view.state, view.dispatch);
            assert.equal(markdown(view), `foo\n\n${table}\n\nbar`);
            assert.equal(isGapAfter(view), true);
        });

        test("leaves the gap instead of removing the table on the other side", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, -1);
            press(view, "Backspace");
            type(view, "1");
            placeInGap(view, 1);
            press(view, "Delete");
            type(view, "2");
            assert.equal(markdown(view), `foo1\n\n${table}\n\n2bar`);
        });
    });

    describe("textblocks next to a table", () => {
        test("move the cursor to the gap with Backspace at their start, then remove the table", () => {
            const view = makeTableView(`foo\n\n${table}\n\n^bar`);
            press(view, "Backspace");
            assert.equal(isGapAfter(view), true);
            press(view, "Backspace");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("move the cursor to the gap with Delete at their end, then remove the table", () => {
            const view = makeTableView(`foo^\n\n${table}\n\nbar`);
            press(view, "Delete");
            assert.equal(isGapBefore(view), true);
            press(view, "Delete");
            assert.equal(markdown(view), "foo\n\nbar");
        });

        test("are removed when empty, moving the cursor to the gap", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, 1);
            press(view, "Enter");
            press(view, "Backspace");
            assert.equal(isGapAfter(view), true);
            assert.equal(markdown(view), `foo\n\n${table}\n\nbar`);
        });

        test("leave Backspace and Delete inside their text to the browser", () => {
            const view = makeTableView(`fo^o\n\n${table}`);
            assert.equal(press(view, "Backspace"), false);
            assert.equal(press(view, "Delete"), false);
        });
    });

    describe("arrows", () => {
        test("move across the gaps from the textblocks next to the table", () => {
            const view = makeTableView(`foo^\n\n${table}\n\nbar`);
            press(view, "ArrowRight");
            assert.equal(isGapBefore(view), true);
            press(view, "ArrowLeft");
            type(view, "1");
            assert.equal(markdown(view), `foo1\n\n${table}\n\nbar`);
        });

        test("enter the table from the gaps", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, -1);
            press(view, "ArrowRight");
            type(view, "1");
            placeInGap(view, 1);
            press(view, "ArrowLeft");
            type(view, "2");
            assert.equal(markdown(view), "foo\n\n| 1foo | bar  |\n| ---- | ---- |\n| baz  | qux2 |\n\nbar");
        });

        test("leave the first and the last cell to the gaps", () => {
            const view = makeTableView(`foo\n\n${table.replace("foo", "^foo")}\n\nbar`);
            press(view, "ArrowLeft");
            assert.equal(isGapBefore(view), true);

            const end = makeTableView(`foo\n\n${table.replace("qux", "qux^")}\n\nbar`);
            press(end, "ArrowRight");
            assert.equal(isGapAfter(end), true);
        });

        test("move up and down from the gaps", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            placeInGap(view, -1);
            press(view, "ArrowUp");
            type(view, "1");
            placeInGap(view, 1);
            press(view, "ArrowDown");
            type(view, "2");
            assert.equal(markdown(view), `foo1\n\n${table}\n\n2bar`);
        });
    });

    describe("Enter", () => {
        test("adds a paragraph before the table, keeping the cursor in the gap", () => {
            const view = makeTableView(table);
            placeInGap(view, -1);
            press(view, "Enter");
            assert.equal(isGapBefore(view), true);
            assert.equal(view.state.doc.child(0).type, schema.nodes.paragraph);
        });

        test("adds a paragraph after the table, moving the cursor into it", () => {
            const view = makeTableView(`${table}\n\nfoo`);
            placeInGap(view, 1);
            press(view, "Enter");
            type(view, "bar");
            assert.equal(markdown(view), `${table}\n\nbar\n\nfoo`);
        });
    });

    describe("mouse", () => {
        test("places the cursor in the gap when clicking beside the table", () => {
            const view = makeTableView(`foo\n\n${table}\n\nbar`);
            const tableElement = view.dom.querySelector("table") as HTMLElement;
            Object.defineProperty(tableElement, "getBoundingClientRect", { value: () => new DOMRect(20, 100, 200, 40) });

            view.dom.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0, clientX: 10, clientY: 120 }));
            assert.equal(isGapBefore(view), true);

            view.dom.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0, clientX: 230, clientY: 120 }));
            assert.equal(isGapAfter(view), true);
        });

        test("leaves the clicks above, below and inside the table to the editor", () => {
            const view = makeTableView(`foo^\n\n${table}\n\nbar`);
            const tableElement = view.dom.querySelector("table") as HTMLElement;
            Object.defineProperty(tableElement, "getBoundingClientRect", { value: () => new DOMRect(20, 100, 200, 40) });

            for (const [clientX, clientY] of [
                [10, 90],
                [10, 150],
                [100, 120],
            ]) {
                view.dom.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0, clientX, clientY }));
                assert.equal(view.state.selection instanceof TableGap, false);
            }
        });
    });
});
