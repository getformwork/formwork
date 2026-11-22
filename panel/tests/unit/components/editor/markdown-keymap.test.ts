import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { schema } from "prosemirror-markdown";
import { buildKeymap } from "../../../../src/ts/components/editor/markdown-keymap";
import { makeView, markdown, press, type } from "../../../helpers/prosemirror";

// happy-dom is not a Mac, so Mod is Ctrl
const mod = { ctrlKey: true };

describe("Markdown Keymap", () => {
    describe("keys", () => {
        test("renames keys", () => {
            const keys = buildKeymap(schema, { "Mod-b": "Mod-g" });
            assert.equal("Mod-b" in keys, false);
            assert.equal(typeof keys["Mod-g"], "function");
        });

        test("disables keys", () => {
            const keys = buildKeymap(schema, { "Mod-b": false });
            assert.equal("Mod-b" in keys, false);
            assert.equal(typeof keys["Mod-i"], "function");
        });

        test("binds redo also to Mod-y outside Mac", () => {
            assert.equal(typeof buildKeymap(schema)["Mod-y"], "function");
        });
    });

    describe("marks", () => {
        test("toggles bold, italic and code", () => {
            const view = makeView("|foo|");
            press(view, "b", mod);
            assert.equal(markdown(view), "**foo**");
            press(view, "i", mod);
            assert.equal(markdown(view), "***foo***");
            press(view, "b", mod);
            press(view, "i", mod);
            press(view, "`", mod);
            assert.equal(markdown(view), "`foo`");
        });
    });

    describe("blocks", () => {
        test("makes headings, paragraphs and code blocks", () => {
            const view = makeView("foo|");
            press(view, "2", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "## foo");
            press(view, "0", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "foo");
            press(view, "\\", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "```\nfoo\n```");
        });

        test("wraps in lists and quotes", () => {
            const view = makeView("foo|");
            press(view, "8", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "* foo");

            const ordered = makeView("foo|");
            press(ordered, "9", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(ordered), "1. foo");

            const quote = makeView("foo|");
            press(quote, ">", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(quote), "> foo");
        });

        test("inserts a line break with Shift+Enter", () => {
            const view = makeView("foo|bar");
            press(view, "Enter", { shiftKey: true });
            assert.equal(markdown(view), "foo\\\nbar");
        });

        test("leaves a code block with Mod+Enter", () => {
            const view = makeView("```\nfoo|\n```");
            press(view, "Enter", mod);
            type(view, "bar");
            assert.equal(markdown(view), "```\nfoo\n```\n\nbar");
        });

        test("inserts a horizontal rule", () => {
            const view = makeView("foo\n\n|");
            press(view, "_", mod);
            assert.equal(view.state.doc.child(1).type.name, "horizontal_rule");
        });
    });

    describe("lists", () => {
        test("splits a list item with Enter", () => {
            const view = makeView("* foo|");
            press(view, "Enter");
            type(view, "bar");
            assert.equal(markdown(view), "* foo\n* bar");
        });

        test("indents and outdents list items", () => {
            const view = makeView("* foo\n\n* bar|");
            press(view, "]", mod);
            assert.equal(markdown(view), "* foo\n\n  * bar");
            press(view, "[", mod);
            assert.equal(markdown(view), "* foo\n\n* bar");
        });

        test("lifts a paragraph out of a quote with Mod+[", () => {
            const view = makeView("> foo|");
            assert.equal(press(view, "[", mod), true);
            assert.equal(markdown(view), "foo");
        });
    });

    describe("history", () => {
        test("undoes and redoes changes", () => {
            const view = makeView("foo|");
            type(view, "bar");
            press(view, "z", mod);
            assert.equal(markdown(view), "foo");
            press(view, "z", { ctrlKey: true, shiftKey: true });
            assert.equal(markdown(view), "foobar");
            press(view, "z", mod);
            press(view, "y", mod);
            assert.equal(markdown(view), "foobar");
        });
    });
});
