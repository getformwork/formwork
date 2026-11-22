import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { readStorage, writeStorage } from "../../../src/ts/utils/storage";

const denied = () => {
    throw new DOMException("The operation is insecure.", "SecurityError");
};

describe("Storage Utils", () => {
    describe("readStorage", () => {
        test("reads a value", () => {
            window.localStorage.setItem("foo", "bar");
            assert.equal(readStorage("foo"), "bar");
        });

        test("returns null for missing values", () => {
            assert.equal(readStorage("foo"), null);
        });

        test("returns null without access to the storage", (t) => {
            t.mock.method(Storage.prototype, "getItem", denied);
            assert.equal(readStorage("foo"), null);
        });
    });

    describe("writeStorage", () => {
        test("writes a value", () => {
            writeStorage("foo", "bar");
            assert.equal(window.localStorage.getItem("foo"), "bar");
        });

        test("removes the value with null", () => {
            window.localStorage.setItem("foo", "bar");
            writeStorage("foo", null);
            assert.equal(window.localStorage.getItem("foo"), null);
        });

        test("does nothing without access to the storage", (t) => {
            t.mock.method(Storage.prototype, "setItem", denied);
            t.mock.method(Storage.prototype, "removeItem", denied);
            assert.doesNotThrow(() => writeStorage("foo", "bar"));
            assert.doesNotThrow(() => writeStorage("foo", null));
        });
    });
});
