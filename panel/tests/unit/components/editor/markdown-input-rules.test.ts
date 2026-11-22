import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { makeView, markdown, press, type } from "../../../helpers/prosemirror";

describe("Markdown Input Rules", () => {
    describe("blocks", () => {
        test("converts > to a quote", () => {
            const view = makeView("|");
            type(view, "> foo");
            assert.equal(markdown(view), "> foo");
        });

        test("converts -, + and * to a bullet list", () => {
            for (const bullet of ["-", "+", "*"]) {
                const view = makeView("|");
                type(view, `${bullet} foo`);
                assert.equal(view.state.doc.firstChild?.type.name, "bullet_list", bullet);
            }
        });

        test("converts a number to a numbered list starting from it", () => {
            const view = makeView("|");
            type(view, "3. foo");
            assert.equal(view.state.doc.firstChild?.type.name, "ordered_list");
            assert.equal(view.state.doc.firstChild?.attrs.order, 3);
        });

        test("continues the numbered list before with the next number", () => {
            const view = makeView("1. foo\n\n|");
            type(view, "2. bar");
            assert.equal(view.state.doc.childCount, 1);
            assert.equal(markdown(view), "1. foo\n2. bar");
        });

        test("starts a new numbered list with another number", () => {
            const view = makeView("1. foo\n\n|");
            type(view, "5. bar");
            assert.equal(view.state.doc.childCount, 2);
            assert.equal(view.state.doc.lastChild?.attrs.order, 5);
        });

        test("converts three backticks to a code block", () => {
            const view = makeView("|");
            type(view, "```");
            assert.equal(view.state.doc.firstChild?.type.name, "code_block");
            assert.equal(view.state.doc.textContent, "");
        });

        test("converts # to headings up to the sixth level", () => {
            for (let level = 1; level <= 6; level++) {
                const view = makeView("|");
                type(view, `${"#".repeat(level)} foo`);
                assert.equal(markdown(view), `${"#".repeat(level)} foo`);
            }
        });

        test("does not convert more than six #", () => {
            const view = makeView("|");
            type(view, "####### foo");
            assert.equal(view.state.doc.firstChild?.type.name, "paragraph");
        });

        test("converts the shortcuts only at the beginning of the paragraph", () => {
            const view = makeView("|");
            type(view, "foo - bar # baz > qux");
            assert.equal(view.state.doc.firstChild?.type.name, "paragraph");
            assert.equal(view.state.doc.textContent, "foo - bar # baz > qux");
        });

        test("undoes the conversion with Backspace", () => {
            const view = makeView("|");
            type(view, "# ");
            assert.equal(press(view, "Backspace"), true);
            assert.equal(view.state.doc.firstChild?.type.name, "paragraph");
            assert.equal(view.state.doc.textContent, "# ");
        });
    });

    describe("typography", () => {
        test("converts quotes to typographic ones", () => {
            const view = makeView("|");
            type(view, `"foo" 'bar'`);
            assert.equal(view.state.doc.textContent, "“foo” ‘bar’");
        });

        test("converts three dots to an ellipsis and two hyphens to a dash", () => {
            const view = makeView("|");
            type(view, "foo... bar -- baz");
            assert.equal(view.state.doc.textContent, "foo… bar — baz");
        });

        test("does not convert the text of code blocks", () => {
            const view = makeView("```\n|\n```");
            type(view, `"foo" -- ...`);
            assert.equal(view.state.doc.textContent, `"foo" -- ...`);
        });

        test("does not convert inline code", () => {
            const view = makeView("`foo|`");
            type(view, `"bar" --`);
            assert.equal(view.state.doc.textContent, `foo"bar" --`);
        });
    });
});
