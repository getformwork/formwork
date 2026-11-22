import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { clamp, mod } from "../../../src/ts/utils/math";

describe("Math Utils", () => {
    describe("clamp", () => {
        test("returns values inside the range unchanged", () => {
            assert.equal(clamp(5, 0, 10), 5);
        });

        test("limits values to the bounds", () => {
            assert.equal(clamp(-1, 0, 10), 0);
            assert.equal(clamp(11, 0, 10), 10);
        });

        test("accepts values equal to the bounds", () => {
            assert.equal(clamp(0, 0, 10), 0);
            assert.equal(clamp(10, 0, 10), 10);
        });
    });

    describe("mod", () => {
        test("equals the remainder for positive operands", () => {
            assert.equal(mod(7, 3), 1);
        });

        test("has the sign of the divisor", () => {
            assert.equal(mod(-1, 12), 11);
            assert.equal(mod(-13, 12), 11);
            assert.equal(mod(7, -3), -2);
        });

        test("returns zero for multiples of the divisor", () => {
            assert.equal(mod(-24, 12), 0);
            assert.equal(mod(24, 12), 0);
        });
    });
});
