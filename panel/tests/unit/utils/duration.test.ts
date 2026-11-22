import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { intervalsToSeconds, secondsToIntervals } from "../../../src/ts/utils/duration";

describe("Duration Utils", () => {
    describe("secondsToIntervals", () => {
        test("splits seconds into all intervals by default", () => {
            assert.deepEqual(secondsToIntervals(90061), { years: 0, months: 0, weeks: 0, days: 1, hours: 1, minutes: 1, seconds: 1 });
        });

        test("splits seconds only into the given intervals", () => {
            assert.deepEqual(secondsToIntervals(90061, ["hours", "seconds"]), { hours: 25, seconds: 61 });
        });

        test("carries the remainder of a missing interval to the smaller ones", () => {
            assert.deepEqual(secondsToIntervals(8 * 86400, ["weeks", "hours"]), { weeks: 1, hours: 24 });
        });

        test("drops the remainder smaller than the smallest interval", () => {
            assert.deepEqual(secondsToIntervals(119, ["minutes"]), { minutes: 1 });
        });

        test("ignores the order of the given intervals", () => {
            assert.deepEqual(secondsToIntervals(3661, ["seconds", "hours"]), { hours: 1, seconds: 61 });
        });

        test("returns zero intervals for zero seconds", () => {
            assert.deepEqual(secondsToIntervals(0, ["days", "seconds"]), { days: 0, seconds: 0 });
        });

        test("limits seconds to safe integers", () => {
            assert.deepEqual(secondsToIntervals(Number.MAX_SAFE_INTEGER * 2, ["seconds"]), { seconds: Number.MAX_SAFE_INTEGER });
        });
    });

    describe("intervalsToSeconds", () => {
        test("sums intervals into seconds", () => {
            assert.equal(intervalsToSeconds({ years: 1, months: 1, weeks: 1, days: 1, hours: 1, minutes: 1, seconds: 1 }), 31536000 + 2592000 + 604800 + 90061);
        });

        test("returns zero for empty intervals", () => {
            assert.equal(intervalsToSeconds({}), 0);
        });

        test("limits seconds to safe integers", () => {
            assert.equal(intervalsToSeconds({ years: Number.MAX_SAFE_INTEGER }), Number.MAX_SAFE_INTEGER);
            assert.equal(intervalsToSeconds({ years: -Number.MAX_SAFE_INTEGER }), -Number.MAX_SAFE_INTEGER);
        });

        test("is the inverse of secondsToIntervals", () => {
            assert.equal(intervalsToSeconds(secondsToIntervals(123456789)), 123456789);
            assert.equal(intervalsToSeconds(secondsToIntervals(123456789, ["days", "minutes", "seconds"])), 123456789);
        });
    });
});
