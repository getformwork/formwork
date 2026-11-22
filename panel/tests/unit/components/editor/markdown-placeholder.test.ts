import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { placeholderPlugin } from "../../../../src/ts/components/editor/markdown-placeholder";
import { makeView, type } from "../../../helpers/prosemirror";

/**
 * Get the text of the placeholder shown in the document
 */
const placeholder = () => document.querySelector(".pm-placeholder")?.textContent ?? null;

describe("Markdown Placeholder", () => {
    test("shows the placeholder in an empty editor", () => {
        makeView("", [placeholderPlugin("Write here")]);
        assert.equal(placeholder(), "Write here");
    });

    test("hides the placeholder when the editor has content", () => {
        const view = makeView("", [placeholderPlugin("Write here")]);
        type(view, "foo");
        assert.equal(placeholder(), null);
    });

    test("shows the placeholder in an empty heading", () => {
        makeView("# |", [placeholderPlugin("Write here")]);
        assert.equal(placeholder(), "Write here");
    });

    test("does not show the placeholder with more blocks", () => {
        const view = makeView("foo", [placeholderPlugin("Write here")]);
        view.dispatch(view.state.tr.insert(view.state.doc.content.size, view.state.schema.nodes.paragraph.create()).delete(1, 4));
        assert.equal(view.state.doc.childCount, 2);
        assert.equal(placeholder(), null);
    });

    test("shows nothing without a text", () => {
        makeView("", [placeholderPlugin("")]);
        assert.equal(document.querySelector(".pm-placeholder"), null);
    });
});
