import assert from "node:assert/strict";
import { describe, test } from "node:test";
import type { App } from "../../../src/ts/services/app";
import { Translation } from "../../../src/ts/services/translation";

const translation = new Translation({
    config: {
        translation: {
            code: "en",
            data: {
                "panel.foo": "Foo",
                "panel.empty": "",
                "panel.items": ["item", "items"],
            },
        },
    },
} as unknown as App);

describe("Translation", () => {
    test("exposes the language code", () => {
        assert.equal(translation.code, "en");
    });

    test("tells whether a key exists", () => {
        assert.equal(translation.has("panel.foo"), true);
        assert.equal(translation.has("panel.items"), true);
        assert.equal(translation.has("panel.missing"), false);
    });

    describe("get", () => {
        test("returns the string of a key", () => {
            assert.equal(translation.get("panel.foo"), "Foo");
        });

        test("returns empty strings instead of the default value", () => {
            assert.equal(translation.get("panel.empty", "Default"), "");
        });

        test("returns the default value for missing keys", () => {
            assert.equal(translation.get("panel.missing", "Default"), "Default");
        });

        test("rejects missing keys without a default value", () => {
            assert.throws(() => translation.get("panel.missing"), { message: 'Translation for key "panel.missing" is not a string.' });
        });

        test("rejects keys with a list", () => {
            assert.throws(() => translation.get("panel.items", "Default"), { message: 'Translation for key "panel.items" is not a string.' });
        });
    });

    describe("getStrings", () => {
        test("returns the strings of a key", () => {
            assert.deepEqual(translation.getStrings("panel.items"), ["item", "items"]);
        });

        test("returns the default value for missing keys", () => {
            assert.deepEqual(translation.getStrings("panel.missing", ["first", "second"]), ["first", "second"]);
        });

        test("rejects missing keys without a default value", () => {
            assert.throws(() => translation.getStrings("panel.missing"), { message: 'Translation for key "panel.missing" is not an array.' });
        });

        test("rejects keys with a string", () => {
            assert.throws(() => translation.getStrings("panel.foo", ["first"]), { message: 'Translation for key "panel.foo" is not an array.' });
        });
    });
});
