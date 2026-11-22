import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { toCamelCase } from "../../../src/ts/utils/strings";

describe("String Utils", () => {
    test("converts kebab, snake and space separated strings", () => {
        assert.equal(toCamelCase("foo-bar"), "fooBar");
        assert.equal(toCamelCase("foo_bar"), "fooBar");
        assert.equal(toCamelCase("foo bar baz"), "fooBarBaz");
    });

    test("treats consecutive separators as one", () => {
        assert.equal(toCamelCase("foo--bar__baz"), "fooBarBaz");
    });

    test("lowercases the rest of each part", () => {
        assert.equal(toCamelCase("FOO-BAR"), "fooBar");
    });

    test("returns single words in lowercase", () => {
        assert.equal(toCamelCase("Foo"), "foo");
    });
});
