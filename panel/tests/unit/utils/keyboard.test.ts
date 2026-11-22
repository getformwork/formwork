import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { getNextIndex } from "../../../src/ts/utils/keyboard";

describe("Keyboard Utils", () => {
    test("moves to the next and previous items with the arrows", () => {
        assert.equal(getNextIndex("ArrowRight", 1, 3), 2);
        assert.equal(getNextIndex("ArrowLeft", 1, 3), 0);
    });

    test("wraps around at the ends with the arrows", () => {
        assert.equal(getNextIndex("ArrowRight", 3, 3), 0);
        assert.equal(getNextIndex("ArrowLeft", 0, 3), 3);
    });

    test("moves to the first and last items with Home and End", () => {
        assert.equal(getNextIndex("Home", 2, 3), 0);
        assert.equal(getNextIndex("End", 0, 3), 3);
    });

    test("stays on the only item", () => {
        assert.equal(getNextIndex("ArrowRight", 0, 0), 0);
        assert.equal(getNextIndex("ArrowLeft", 0, 0), 0);
    });

    test("returns undefined for keys that do not move the focus", () => {
        assert.equal(getNextIndex("Enter", 1, 3), undefined);
        assert.equal(getNextIndex("ArrowDown", 1, 3), undefined);
    });
});
