import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { getSafeInteger } from "../../../src/ts/utils/numbers";

describe("Number Utils", () => {
    test("returns safe values unchanged", () => {
        assert.equal(getSafeInteger(42), 42);
        assert.equal(getSafeInteger(Number.MAX_SAFE_INTEGER), Number.MAX_SAFE_INTEGER);
    });

    test("limits values to the safe integers", () => {
        assert.equal(getSafeInteger(Number.MAX_SAFE_INTEGER + 2), Number.MAX_SAFE_INTEGER);
        assert.equal(getSafeInteger(-Number.MAX_SAFE_INTEGER - 2), -Number.MAX_SAFE_INTEGER);
        assert.equal(getSafeInteger(Number.POSITIVE_INFINITY), Number.MAX_SAFE_INTEGER);
        assert.equal(getSafeInteger(Number.NEGATIVE_INFINITY), -Number.MAX_SAFE_INTEGER);
    });
});
