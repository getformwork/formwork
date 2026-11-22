import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { schema } from "prosemirror-markdown";
import { TextSelection } from "prosemirror-state";
import { MarkdownView, type MarkdownViewOptions } from "../../../../src/ts/components/editor/markdown-view";
import { app } from "../../../../src/ts/services/app";
import { setTranslation } from "../../../helpers/panel";

beforeEach(() => {
    app.config.siteUri = "/site/";
    setTranslation();
    // The menu of the editor binds the link modal of the panel
    Object.assign(app, { modals: { linkModal: { element: document.createElement("div") } } });
});

/**
 * Make a Markdown editor of a page in the document
 */
function makeEditor(content: string, options: MarkdownViewOptions = {}) {
    const target = document.createElement("div");
    document.body.append(target);
    return new MarkdownView("editor", target, content, mock.fn(), { baseUri: "/site/blog/post/", ...options });
}

/**
 * Get the images of the content of an editor
 */
const images = (editor: MarkdownView) => Array.from(editor.view.dom.querySelectorAll("img:not(.ProseMirror-separator)"));

/**
 * Replace the content of an editor with a link, as pasting or the link modal do, which unlike the
 * Markdown parser do not normalize the address
 */
function insertLink(editor: MarkdownView, href: string) {
    const { state } = editor.view;
    const text = schema.text("Link", [schema.marks.link.create({ href })]);
    editor.view.dispatch(state.tr.replaceWith(0, state.doc.content.size, schema.nodes.paragraph.create(null, text)));
}

/**
 * Move the cursor inside the first link of an editor and wait for its tooltip
 */
async function showLinkTooltip(editor: MarkdownView) {
    const { state } = editor.view;
    let position = -1;
    state.doc.descendants((node, offset) => {
        if (position === -1 && node.isText && schema.marks.link.isInSet(node.marks)) {
            position = offset + 1;
        }
    });
    editor.view.dispatch(state.tr.setSelection(TextSelection.create(state.doc, position)));
    // The tooltip of the links is shown without delay
    await new Promise((resolve) => setTimeout(resolve));
    return document.querySelector(".editor-link-tooltip") as HTMLElement;
}

describe("MarkdownView", () => {
    describe("images", () => {
        test("shows images resolving their source against the page", () => {
            const [image] = images(makeEditor('![foo](image.jpg "Title")'));
            assert.equal(image.getAttribute("src"), "/site/blog/post/image.jpg");
            assert.equal(image.getAttribute("alt"), "foo");
            assert.equal(image.getAttribute("title"), "Title");
        });

        test("shows images resolving their source as the site does", () => {
            const sources = images(makeEditor("![foo](/image.jpg) ![bar](https://example.test/image.jpg) ![baz](../image.jpg)")).map((image) => image.getAttribute("src"));
            assert.deepEqual(sources, ["/site/image.jpg", "https://example.test/image.jpg", "/site/blog/image.jpg"]);
        });

        test("does not change the source of images in the content", () => {
            const content = '![foo](image.jpg "Title") ![bar](/image.jpg)';
            assert.equal(makeEditor(content).content, content);
        });

        test("shows images without a title", () => {
            const [image] = images(makeEditor("![foo](image.jpg)"));
            assert.equal(image.hasAttribute("title"), false);
        });
    });

    describe("link tooltip", () => {
        test("shows the address of the link resolved against the page", async () => {
            const tooltip = await showLinkTooltip(makeEditor("Hello [world](other/)"));
            const link = tooltip.querySelector("a") as HTMLAnchorElement;
            assert.equal(link.getAttribute("href"), "/site/blog/post/other/");
            assert.equal(link.getAttribute("target"), "_blank");
            assert.equal(link.textContent, "other/");
        });

        test("shows the address of links outside the site as it is", async () => {
            const tooltip = await showLinkTooltip(makeEditor("[foo](https://example.test/page)"));
            assert.equal(tooltip.querySelector("a")?.getAttribute("href"), "https://example.test/page");
        });

        test("escapes the address", async () => {
            const editor = makeEditor("");
            insertLink(editor, '"><img src="x" id="injected">');
            const tooltip = await showLinkTooltip(editor);
            assert.ok(document.getElementById("injected") === null);
            assert.equal(tooltip.textContent?.trim(), '"><img src="x" id="injected">');
        });

        test("does not link unsafe addresses", async () => {
            const editor = makeEditor("");
            insertLink(editor, "javascript:alert(1)");
            const tooltip = await showLinkTooltip(editor);
            assert.equal(tooltip.querySelector("a"), null);
            assert.equal(tooltip.textContent?.trim(), "javascript:alert(1)");
        });

        test("hides the tooltip when the cursor leaves the link", async (t) => {
            const editor = makeEditor("[foo](page) bar");
            assert.notEqual(await showLinkTooltip(editor), null);
            // The tooltip is removed after a delay
            t.mock.timers.enable({ apis: ["setTimeout"] });
            const { state } = editor.view;
            editor.view.dispatch(state.tr.setSelection(TextSelection.atEnd(state.doc)));
            t.mock.timers.tick(100);
            assert.ok(document.querySelector(".editor-link-tooltip") === null);
        });
    });
});
