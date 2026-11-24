import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { TextSelection } from "prosemirror-state";
import { CellSelection } from "prosemirror-tables";
import { app } from "../../../../src/ts/services/app";
import { setTranslation } from "../../../helpers/panel";
import { cellPositions, makeTableEditor, markdown, type } from "../../../helpers/prosemirror";

beforeEach(() => {
    app.config.siteUri = "/site/";
    setTranslation();
    // The menu binds the link modal of the panel
    Object.assign(app, { modals: { linkModal: { element: document.createElement("div") } } });
});

const table = "| foo | bar |\n| --- | --- |\n| baz | qux |";

/**
 * Get the handle of the rows or of the columns
 */
const handle = (axis: "row" | "column") => document.querySelector(`.table-handle-${axis}`) as HTMLElement;

const handleButton = (axis: "row" | "column") => handle(axis).querySelector(".table-handle-button") as HTMLButtonElement;

/**
 * Get an item of the menu of a handle by its text
 */
const handleItem = (axis: "row" | "column", text: string) => Array.from(handle(axis).querySelectorAll<HTMLButtonElement>(".dropdown-item")).find((item) => item.textContent?.trim() === text) as HTMLButtonElement;

/**
 * Give an element a fixed box, as happy-dom does not lay out the document
 */
function setRect(element: Element, left: number, top: number, width: number, height: number) {
    Object.defineProperty(element, "getBoundingClientRect", { value: () => new DOMRect(left, top, width, height) });
}

/**
 * Lay out the first table of the document in rows of 20 pixels and columns of 100 pixels
 */
function layOutTable() {
    const tableElement = document.querySelector(".ProseMirror table") as HTMLElement;
    const rows = Array.from(tableElement.querySelectorAll("tr"));
    setRect(tableElement, 0, 0, rows[0].children.length * 100, rows.length * 20);
    rows.forEach((row, rowIndex) => {
        setRect(row, 0, rowIndex * 20, row.children.length * 100, 20);
        Array.from(row.children).forEach((cell, cellIndex) => {
            setRect(cell, cellIndex * 100, rowIndex * 20, 100, 20);
        });
    });
}

/**
 * Drag a handle with the pointer from a point to another
 */
function drag(button: HTMLElement, from: [number, number], to: [number, number], end: "pointerup" | "Escape" = "pointerup") {
    const init = { bubbles: true, pointerId: 1, button: 0, isPrimary: true };
    button.dispatchEvent(new PointerEvent("pointerdown", { ...init, clientX: from[0], clientY: from[1] }));
    button.dispatchEvent(new PointerEvent("pointermove", { ...init, clientX: to[0], clientY: to[1] }));
    const ghost = document.querySelector(".table-ghost");
    if (end === "Escape") {
        document.dispatchEvent(new KeyboardEvent("keydown", { key: "Escape", bubbles: true, cancelable: true }));
    }
    button.dispatchEvent(new PointerEvent("pointerup", { ...init, clientX: to[0], clientY: to[1] }));
    return ghost;
}

describe("Markdown Table Handles", () => {
    test("are shown only in tables", () => {
        const editor = makeTableEditor(`foo^\n\n${table}`);
        assert.equal(handle("row").hidden, true);
        assert.equal(handle("column").hidden, true);

        const positions = cellPositions(editor.view);
        editor.view.dispatch(editor.view.state.tr.setSelection(CellSelection.create(editor.view.state.doc, positions[3])));
        assert.equal(handle("row").hidden, false);
        assert.equal(handle("column").hidden, false);
    });

    test("select their row or column when clicked, hiding the other handle", () => {
        const editor = makeTableEditor(table.replace("qux", "qux^"));

        handleButton("row").click();
        const { selection } = editor.view.state;
        assert.ok(selection instanceof CellSelection && selection.isRowSelection() && !selection.isColSelection());
        assert.equal(handle("column").hidden, true);
        assert.equal(handleButton("row").classList.contains("is-active"), true);

        handleButton("column").click();
        const columnSelection = editor.view.state.selection;
        assert.ok(columnSelection instanceof CellSelection && columnSelection.isColSelection());
    });

    test("show only the clicked handle when its row or column is the whole table", () => {
        const editor = makeTableEditor("| foo^ | bar |\n| --- | --- |");

        handleButton("row").click();
        assert.equal(handle("row").hidden, false);
        assert.equal(handle("column").hidden, true);

        // The handle is remembered while the selection changes with the document
        handleItem("row", "Clear row").click();
        assert.equal(handle("column").hidden, true);

        const positions = cellPositions(editor.view);
        editor.view.dispatch(editor.view.state.tr.setSelection(TextSelection.create(editor.view.state.doc, positions[1] + 2)));
        assert.equal(handle("row").hidden, false);
        assert.equal(handle("column").hidden, false);
    });

    test("add rows and columns moving the cursor into them", () => {
        const editor = makeTableEditor(table.replace("qux", "qux^"));
        handleItem("row", "Add row above").click();
        type(editor.view, "1");
        handleItem("column", "Add column left").click();
        type(editor.view, "2");
        assert.equal(markdown(editor.view), "| foo |     | bar |\n| --- | --- | --- |\n|     | 2   | 1   |\n| baz |     | qux |");
    });

    test("add rows below and columns to the right of the cursor", () => {
        const editor = makeTableEditor(table.replace("foo", "foo^"));
        handleItem("row", "Add row below").click();
        type(editor.view, "1");
        handleItem("column", "Add column right").click();
        type(editor.view, "2");
        assert.equal(markdown(editor.view), "| foo |     | bar |\n| --- | --- | --- |\n| 1   | 2   |     |\n| baz |     | qux |");
    });

    test("clear a row or a column, then disable the item", () => {
        const editor = makeTableEditor(table.replace("baz", "baz^"));
        handleItem("row", "Clear row").click();
        assert.equal(markdown(editor.view), "| foo | bar |\n| --- | --- |\n|     |     |");
        assert.equal(handleItem("row", "Clear row").disabled, true);
        handleItem("column", "Clear column").click();
        assert.equal(markdown(editor.view), "|     | bar |\n| --- | --- |\n|     |     |");
    });

    test("delete a row or a column keeping the cursor in the table", () => {
        const editor = makeTableEditor(table.replace("qux", "qux^"));
        handleItem("row", "Delete row").click();
        assert.equal(markdown(editor.view), "| foo | bar |\n| --- | --- |");
        handleItem("column", "Delete column").click();
        assert.equal(markdown(editor.view), "| foo |\n| --- |");
        type(editor.view, "1");
        assert.equal(markdown(editor.view), "| 1foo |\n| ---- |");
    });

    test("empty and delete their row or column with Backspace and Delete while they have the focus", () => {
        const editor = makeTableEditor(`${table}\n| one^ | two |`);
        const keydown = (target: HTMLElement, key: string) => target.dispatchEvent(new KeyboardEvent("keydown", { key, bubbles: true, cancelable: true }));

        handleButton("row").click();
        handleButton("row").focus();
        keydown(handleButton("row"), "Backspace");
        assert.equal(markdown(editor.view), `${table}\n|     |     |`);
        assert.ok(editor.view.hasFocus());

        handleButton("row").focus();
        keydown(handleItem("row", "Add row above"), "Delete");
        assert.equal(markdown(editor.view), table);
    });

    test("keep the editor looking focused while they have the focus", () => {
        const editor = makeTableEditor(table.replace("foo", "foo^"));
        const container = document.querySelector(".editor-wrap") as HTMLElement;
        const outside = document.createElement("button");
        document.body.append(outside);

        handleButton("column").focus();
        assert.equal(container.classList.contains("focused"), true);
        handleItem("column", "Align left").focus();
        assert.equal(container.classList.contains("focused"), true);
        editor.view.focus();
        assert.equal(container.classList.contains("focused"), true);

        handleButton("row").focus();
        outside.focus();
        assert.equal(container.classList.contains("focused"), false);
    });

    test("replace the items deleting the last row or the last column with one deleting the table", () => {
        const editor = makeTableEditor("| foo^ | bar |\n| --- | --- |");
        assert.equal(handleItem("row", "Delete row").hidden, true);
        assert.equal(handleItem("row", "Delete table").hidden, false);
        assert.equal(handleItem("column", "Delete column").hidden, false);
        assert.equal(handleItem("column", "Delete table").hidden, true);

        handleItem("column", "Delete column").click();
        assert.equal(markdown(editor.view), "| bar |\n| --- |");
        assert.equal(handleItem("column", "Delete column").hidden, true);
        assert.equal(handleItem("column", "Delete table").hidden, false);
    });

    test("offer to delete the table when all the rows are selected", () => {
        const editor = makeTableEditor(table.replace("foo", "foo^"));
        assert.equal(handleItem("row", "Delete table").hidden, true);
        const positions = cellPositions(editor.view);
        const { doc } = editor.view.state;
        editor.view.dispatch(editor.view.state.tr.setSelection(CellSelection.rowSelection(doc.resolve(positions[0]), doc.resolve(positions[2]))));
        assert.equal(handleItem("row", "Delete row").hidden, true);
        assert.equal(handleItem("row", "Delete table").hidden, false);
    });

    test("delete the table", () => {
        const editor = makeTableEditor(`foo\n\n| bar^ |\n| --- |\n\nbaz`);
        handleItem("row", "Delete table").click();
        assert.equal(markdown(editor.view), "foo\n\nbaz");
        assert.equal(handle("row").hidden, true);
    });

    test("group the items of their menus with separators", () => {
        makeTableEditor(table.replace("foo", "foo^"));
        const menu = (axis: "row" | "column") =>
            Array.from(handle(axis).querySelector(".dropdown-menu")?.children ?? [])
                .filter((child) => !(child as HTMLElement).hidden)
                .map((child) => (child.tagName === "HR" ? "---" : child.textContent?.trim()));
        assert.deepEqual(menu("row"), ["Add row above", "Add row below", "Clear row", "---", "Delete row"]);
        assert.deepEqual(menu("column"), ["Align left", "Align center", "Align right", "---", "Add column left", "Add column right", "Clear column", "---", "Delete column"]);
    });

    test("align the column of the cursor, showing its alignment", () => {
        const editor = makeTableEditor(table.replace("qux", "qux^"));
        assert.equal(handleItem("column", "Align left").disabled, false);

        handleItem("column", "Align right").click();
        assert.equal(markdown(editor.view), "| foo | bar |\n| --- | --: |\n| baz | qux |");
        assert.equal(handleItem("column", "Align right").classList.contains("is-active"), true);
        assert.equal(handleItem("column", "Align right").disabled, true);

        handleItem("column", "Align center").click();
        assert.equal(markdown(editor.view), "| foo | bar |\n| --- | :-: |\n| baz | qux |");
        assert.equal(handleItem("column", "Align right").classList.contains("is-active"), false);
        assert.equal(handleItem("column", "Align center").classList.contains("is-active"), true);
    });

    test("align all the selected columns", () => {
        const editor = makeTableEditor(table.replace("foo", "foo^"));
        const positions = cellPositions(editor.view);
        const { doc } = editor.view.state;
        editor.view.dispatch(editor.view.state.tr.setSelection(CellSelection.colSelection(doc.resolve(positions[0]), doc.resolve(positions[1]))));
        handleItem("column", "Align left").click();
        assert.equal(markdown(editor.view), "| foo | bar |\n| :-- | :-- |\n| baz | qux |");
    });

    test("move a row when dragged, showing a copy of it while dragging", () => {
        const editor = makeTableEditor(`${table}\n| one^ | two |`);
        layOutTable();
        const ghost = drag(handleButton("row"), [0, 50], [0, 30]);
        assert.ok(ghost?.classList.contains("table-ghost"));
        assert.equal(document.querySelector(".table-ghost"), null);
        assert.equal(markdown(editor.view), "| foo | bar |\n| --- | --- |\n| one | two |\n| baz | qux |");
    });

    test("move a column when dragged", () => {
        const editor = makeTableEditor(table.replace("bar", "bar^"));
        layOutTable();
        drag(handleButton("column"), [150, 0], [50, 0]);
        assert.equal(markdown(editor.view), "| bar | foo |\n| --- | --- |\n| qux | baz |");
    });

    test("leave the table unchanged when the drag is cancelled with Escape", () => {
        const editor = makeTableEditor(`${table}\n| one^ | two |`);
        layOutTable();
        drag(handleButton("row"), [0, 50], [0, 10], "Escape");
        assert.equal(markdown(editor.view), `${table}\n| one | two |`);
        assert.equal(document.querySelector(".table-ghost"), null);
    });

    test("are removed with the editor", () => {
        const editor = makeTableEditor(table.replace("foo", "foo^"));
        editor.destroy();
        assert.equal(document.querySelector(".table-handle"), null);
        assert.equal(document.querySelector(".table-drop-indicator"), null);
    });
});
