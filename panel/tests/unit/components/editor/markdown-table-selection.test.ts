import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { Fragment, Slice } from "prosemirror-model";
import { AllSelection, TextSelection } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { schema } from "../../../../src/ts/components/editor/markdown-schema";
import { makeTableView, markdown, press } from "../../../helpers/prosemirror";

const table = "| foo | bar |\n| --- | --- |\n| baz | qux |";

/**
 * Select from a text of an editor back to another one, at the given offsets in them
 */
function selectBackward(view: EditorView, anchorText: string, anchorOffset: number, headText: string, headOffset: number) {
    const positions: Record<string, number> = {};
    view.state.doc.descendants((node, pos) => {
        if (node.text !== undefined && !(node.text in positions)) {
            positions[node.text] = pos;
        }
    });
    const selection = TextSelection.create(view.state.doc, positions[anchorText] + anchorOffset, positions[headText] + headOffset);
    view.dispatch(view.state.tr.setSelection(selection));
}

describe("Markdown Table Selection", () => {
    /**
     * Get the class of the first table of an editor
     */
    const tableClass = (view: EditorView) => (view.dom.querySelector("table") as HTMLElement).className;

    test("extends a selection entering a table over the whole table, without joining the blocks around it", () => {
        const view = makeTableView(`f^oo\n\n${table.replace("baz", "b^az")}\n\nbar`);
        press(view, "Backspace");
        assert.equal(markdown(view), "f\n\nbar");
    });

    test("extends a selection entering a table from after it over the whole table", () => {
        const view = makeTableView(`foo\n\n${table}\n\nlast`);
        selectBackward(view, "last", 2, "bar", 1);
        press(view, "Backspace");
        assert.equal(markdown(view), "foo\n\nst");
    });

    test("extends a selection over all the tables it enters", () => {
        const view = makeTableView(`f^oo\n\n${table}\n\n${table.replace("qux", "q^ux")}\n\nbar`);
        press(view, "Backspace");
        assert.equal(markdown(view), "f\n\nbar");
    });

    test("extends a selection over a table at the end of the document", () => {
        const view = makeTableView(`f^oo\n\n${table.replace("baz", "b^az")}`);
        press(view, "Backspace");
        assert.equal(markdown(view), "f");
    });

    test("replaces a selection covering a table with the typed text, outside the table", () => {
        const input = (view: EditorView) => view.dom.dispatchEvent(new InputEvent("beforeinput", { inputType: "insertText", data: "1", bubbles: true, cancelable: true }));

        const view = makeTableView(`f^oo\n\n${table.replace("baz", "b^az")}\n\nbar`);
        assert.equal(input(view), false);
        assert.equal(markdown(view), "f1\n\nbar");

        const backward = makeTableView(`foo\n\n${table}\n\nlast`);
        selectBackward(backward, "last", 2, "bar", 1);
        input(backward);
        assert.equal(markdown(backward), "foo\n\n1st");
    });

    test("replaces a selection covering a table with the pasted content, outside the table", () => {
        const view = makeTableView(`foo\n\n${table}\n\nlast`);
        selectBackward(view, "last", 2, "bar", 1);
        const slice = new Slice(Fragment.from(schema.text("1")), 0, 0);
        view.someProp("handlePaste", (handle) => handle(view, new Event("paste") as ClipboardEvent, slice));
        assert.equal(markdown(view), "foo\n\n1st");
    });

    test("leaves the other input to the editor", () => {
        const view = makeTableView(`f^oo\n\n${table.replace("baz", "b^az")}`);
        const event = new InputEvent("beforeinput", { inputType: "insertParagraph", bubbles: true, cancelable: true });
        view.dom.dispatchEvent(event);
        assert.equal(event.defaultPrevented, false);

        const inside = makeTableView(table.replace("baz", "^baz^"));
        const text = new InputEvent("beforeinput", { inputType: "insertText", data: "1", bubbles: true, cancelable: true });
        inside.dom.dispatchEvent(text);
        assert.equal(text.defaultPrevented, false);
    });

    test("goes over a table with Shift and the arrows, forward and back", () => {
        const view = makeTableView(`foo^\n\n${table}\n\nbar`);
        assert.equal(press(view, "ArrowRight", { shiftKey: true }), true);
        assert.equal(tableClass(view), "table-selected");
        assert.equal(view.state.selection.$head.parent.textContent, "bar");

        press(view, "ArrowLeft", { shiftKey: true });
        assert.equal(view.state.selection.empty, true);
        assert.equal(tableClass(view), "");
    });

    test("goes over a table backwards with Shift and the arrows", () => {
        const view = makeTableView(`foo\n\n${table}\n\n^bar`);
        press(view, "ArrowLeft", { shiftKey: true });
        assert.equal(tableClass(view), "table-selected");
        press(view, "Backspace");
        assert.equal(markdown(view), "foo\n\nbar");
    });

    test("goes over consecutive tables with Shift and the arrows", () => {
        const view = makeTableView(`foo^\n\n${table}\n\n${table}\n\nbar`);
        press(view, "ArrowRight", { shiftKey: true });
        assert.equal(view.state.selection.$head.parent.textContent, "bar");
    });

    test("leaves Shift and the arrows inside the text to the editor", () => {
        const view = makeTableView(`fo^o\n\n${table}`);
        assert.equal(press(view, "ArrowRight", { shiftKey: true }), false);
    });

    test("leaves the selections inside a table", () => {
        const view = makeTableView(table.replace("baz", "b^a^z"));
        assert.ok(view.state.selection instanceof TextSelection);
        assert.equal(view.state.doc.textBetween(view.state.selection.from, view.state.selection.to), "a");
    });

    test("shows the tables in a selection as selected", () => {
        const view = makeTableView(`f^oo\n\n${table}\n\nb^ar`);
        assert.equal(tableClass(view), "table-selected");

        view.dispatch(view.state.tr.setSelection(TextSelection.create(view.state.doc, 1)));
        assert.equal(tableClass(view), "");

        view.dispatch(view.state.tr.setSelection(new AllSelection(view.state.doc)));
        assert.equal(tableClass(view), "table-selected");
    });

    test("does not show a table as selected for a selection inside it", () => {
        const view = makeTableView(table.replace("baz", "^baz^"));
        assert.equal(tableClass(view), "");
    });
});
