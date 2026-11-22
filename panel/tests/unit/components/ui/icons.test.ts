import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import * as icons from "../../../../src/ts/components/ui/icons";

describe("Icons Component", () => {
    test("are SVG images with the color of the text, sized as their view box", () => {
        for (const [name, svg] of Object.entries(icons)) {
            const element = document.createElement("div");
            element.innerHTML = svg;
            const image = element.firstElementChild as SVGElement;
            assert.equal(element.children.length, 1, name);
            assert.equal(image.tagName.toLowerCase(), "svg", name);
            assert.equal(image.getAttribute("class"), "icon", name);
            assert.equal(image.getAttribute("viewBox"), `0 0 ${image.getAttribute("width")} ${image.getAttribute("height")}`, name);
            assert.equal(image.getAttribute("width"), "16", name);
            assert.equal(image.getAttribute("fill"), "currentColor", name);
        }
    });

    test("have no scripts or event handlers", () => {
        for (const [name, svg] of Object.entries(icons)) {
            assert.doesNotMatch(svg, /<script|\son\w+=/i, name);
        }
    });

    test("have unique ids, as they share the document", () => {
        const ids = Object.values(icons).flatMap((svg) => Array.from(svg.matchAll(/\sid="([^"]+)"/g), (match) => match[1]));
        assert.deepEqual(
            ids.filter((id, index) => ids.indexOf(id) !== index),
            [],
        );
    });

    test("include the icons of the files and of the statuses", () => {
        for (const name of ["file", "fileImage", "filePdf", "check", "exclamation", "info", "infoCircle", "checkCircle", "exclamationTriangle", "exclamationOctagon"]) {
            assert.equal(typeof (icons as Record<string, string>)[name], "string", name);
        }
    });
});
