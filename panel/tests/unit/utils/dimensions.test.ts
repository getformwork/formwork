import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { getOuterHeight, getOuterWidth } from "../../../src/ts/utils/dimensions";

/**
 * Add an element with the given size and margins to the document
 */
function makeElement(width: number, height: number, margin: string) {
    const element = document.createElement("div");
    element.style.margin = margin;
    document.body.append(element);
    // happy-dom has no layout, unlike browsers
    Object.defineProperties(element, { offsetWidth: { value: width }, offsetHeight: { value: height } });
    return element;
}

describe("Dimensions Utils", () => {
    describe("getOuterWidth", () => {
        test("adds the horizontal margins to the width", () => {
            assert.equal(getOuterWidth(makeElement(100, 50, "1px 2px 3px 4px")), 106);
        });
    });

    describe("getOuterHeight", () => {
        test("adds the vertical margins to the height", () => {
            assert.equal(getOuterHeight(makeElement(100, 50, "1px 2px 3px 4px")), 54);
        });
    });

    describe("getOuterWidth and getOuterHeight", () => {
        test("return the size without margins", () => {
            const element = makeElement(100, 50, "0");
            assert.equal(getOuterWidth(element), 100);
            assert.equal(getOuterHeight(element), 50);
        });
    });
});
