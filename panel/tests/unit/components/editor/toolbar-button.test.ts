import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { createToolbarButton } from "../../../../src/ts/components/editor/toolbar-button";
import type * as icons from "../../../../src/ts/components/ui/icons";

describe("Editor Toolbar Button", () => {
    test("creates a button with an icon, a tooltip and a label", () => {
        const button = createToolbarButton("bold", "Bold");
        assert.equal(button.tagName, "BUTTON");
        assert.equal(button.type, "button");
        assert.equal(button.className, "button toolbar-button");
        assert.equal(button.dataset.tooltip, "Bold");
        assert.equal(button.ariaLabel, "Bold");
        assert.notEqual(button.querySelector("svg.icon"), null);
    });

    test("creates a button without an icon for unknown icons", () => {
        const button = createToolbarButton("missing" as keyof typeof icons, "Foo");
        assert.equal(button.innerHTML, "");
    });
});
