import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { undo } from "prosemirror-history";
import type { Command } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { decreaseIndent, isInList, toggleList } from "../../../../src/ts/components/editor/markdown-lists";
import { schema } from "../../../../src/ts/components/editor/markdown-schema";
import { makeView, markdown, press, type } from "../../../helpers/prosemirror";

const { bullet_list, ordered_list } = schema.nodes;

/**
 * Run a command on an editor
 */
const run = (view: EditorView, command: Command) => command(view.state, view.dispatch, view);

describe("Markdown Lists", () => {
    describe("toggleList", () => {
        test("wraps the selection in a list, and removes the list when toggled again", () => {
            const view = makeView("foo|");
            run(view, toggleList(bullet_list));
            assert.equal(markdown(view), "* foo");
            run(view, toggleList(bullet_list));
            assert.equal(markdown(view), "foo");
        });

        test("changes the type of the whole list", () => {
            const view = makeView("* foo\n* b|ar");
            run(view, toggleList(ordered_list));
            assert.equal(markdown(view), "1. foo\n2. bar");
            run(view, toggleList(bullet_list));
            assert.equal(markdown(view), "* foo\n* bar");
        });

        test("changes only the nearest list", () => {
            const view = makeView("* foo\n  * b|ar");
            run(view, toggleList(ordered_list));
            assert.equal(markdown(view), "* foo\n  1. bar");
        });

        test("removes the list of an item at any level, in a single change", () => {
            const view = makeView("* foo\n  * b|ar");
            run(view, toggleList(bullet_list));
            assert.equal(markdown(view), "* foo\n\nbar");
            undo(view.state, view.dispatch);
            assert.equal(markdown(view), "* foo\n  * bar");
        });

        test("toggles the lists with Shift+Ctrl+8 and Shift+Ctrl+9", () => {
            const view = makeView("foo|");
            press(view, "8", { ctrlKey: true, shiftKey: true });
            press(view, "9", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "1. foo");
            press(view, "9", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "foo");
        });
    });

    describe("tightness", () => {
        test("makes the lists created in the editor tight", () => {
            const view = makeView("foo|");
            run(view, toggleList(bullet_list));
            press(view, "Enter");
            type(view, "bar");
            press(view, "]", { ctrlKey: true });
            press(view, "Enter");
            type(view, "baz");
            assert.equal(markdown(view), "* foo\n  * bar\n  * baz");
        });

        test("makes the loose lists of the Markdown tight", () => {
            const view = makeView("* foo\n\n* bar|\n\n1. baz\n\n2. qux");
            press(view, "Enter");
            type(view, "1");
            assert.equal(markdown(view), "* foo\n* bar\n* 1\n\n1. baz\n2. qux");
        });
    });

    describe("isInList", () => {
        test("tells the type of the nearest list", () => {
            const view = makeView("1. foo\n   * b|ar");
            assert.equal(isInList(view.state, bullet_list), true);
            assert.equal(isInList(view.state, ordered_list), false);
            assert.equal(isInList(makeView("foo|").state, bullet_list), false);
        });
    });

    describe("decreaseIndent", () => {
        test("makes an item of a nested list an item of the parent list", () => {
            const view = makeView("* foo\n  * b|ar");
            run(view, decreaseIndent);
            assert.equal(markdown(view), "* foo\n* bar");
        });

        test("makes an item of a list a paragraph out of the list", () => {
            const view = makeView("* fo|o\n* bar");
            run(view, decreaseIndent);
            assert.equal(markdown(view), "foo\n\n* bar");
        });

        test("lifts the other blocks out of their parent", () => {
            const view = makeView("> fo|o");
            run(view, decreaseIndent);
            assert.equal(markdown(view), "foo");
        });

        test("is bound to Mod+[", () => {
            const view = makeView("* foo\n  * b|ar");
            press(view, "[", { ctrlKey: true });
            assert.equal(markdown(view), "* foo\n* bar");
        });
    });
});
