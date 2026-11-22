import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { escapeHtml, escapeRegExp, makeDiacriticsRegExp, makeSlug, validateSlug } from "../../../src/ts/utils/validation";

describe("Validation Utils", () => {
    describe("escapeRegExp", () => {
        test("escapes every character with a special meaning", () => {
            const special = "-[]/{}()*+?.\\^$|";
            assert.ok(new RegExp(`^${escapeRegExp(special)}$`).test(special));
        });

        test("makes special characters match literally", () => {
            assert.equal(new RegExp(escapeRegExp("foo.bar")).test("fooxbar"), false);
            assert.equal(new RegExp(escapeRegExp("foo.bar")).test("foo.bar"), true);
        });
    });

    describe("makeDiacriticsRegExp", () => {
        test("matches letters with diacritics", () => {
            assert.ok(new RegExp(`^${makeDiacriticsRegExp("cafe")}$`).test("café"));
            assert.ok(new RegExp(`^${makeDiacriticsRegExp("naive")}$`).test("naïve"));
        });

        test("matches uppercase letters with diacritics", () => {
            assert.ok(new RegExp(`^${makeDiacriticsRegExp("CAFE")}$`).test("CAFÉ"));
        });

        test("still matches letters without diacritics", () => {
            assert.ok(new RegExp(`^${makeDiacriticsRegExp("cafe")}$`).test("cafe"));
        });

        test("leaves characters without variants unchanged", () => {
            assert.equal(makeDiacriticsRegExp("123 -"), "123 -");
        });
    });

    describe("makeSlug", () => {
        test("lowercases and joins words with hyphens", () => {
            assert.equal(makeSlug("Hello World"), "hello-world");
        });

        test("transliterates characters with diacritics", () => {
            assert.equal(makeSlug("Ça va über straße"), "ca-va-ueber-strasse");
            assert.equal(makeSlug("Ærøskøbing"), "aeroskobing");
        });

        test("replaces separators with hyphens", () => {
            assert.equal(makeSlug("foo_bar/baz\\qux"), "foo-bar-baz-qux");
            assert.equal(makeSlug("foo — bar"), "foo-bar");
            assert.equal(makeSlug("l’école"), "l-ecole");
        });

        test("removes characters not allowed", () => {
            assert.equal(makeSlug("Hello, world!"), "hello-world");
            assert.equal(makeSlug("foo 🙂 bar"), "foo-bar");
        });

        test("collapses consecutive hyphens", () => {
            assert.equal(makeSlug("foo   ---  bar"), "foo-bar");
        });

        test("removes hyphens at the ends", () => {
            assert.equal(makeSlug("  -foo-  "), "foo");
            assert.equal(makeSlug("!foo?"), "foo");
        });

        test("returns an empty slug for text without allowed characters", () => {
            assert.equal(makeSlug("!?"), "");
        });

        test("keeps digits", () => {
            assert.equal(makeSlug("Page 2"), "page-2");
        });
    });

    describe("validateSlug", () => {
        test("lowercases and replaces whitespace with hyphens", () => {
            assert.equal(validateSlug("Foo Bar"), "foo-bar");
        });

        test("preserves hyphens at the ends while typing", () => {
            assert.equal(validateSlug("-foo-"), "-foo-");
            assert.equal(validateSlug("foo "), "foo-");
        });

        test("removes characters not allowed", () => {
            assert.equal(validateSlug("foo!?"), "foo");
        });

        test("replaces characters like makeSlug", () => {
            assert.equal(validateSlug("Café_bar"), "cafe-bar");
            assert.equal(validateSlug("Über straße"), "ueber-strasse");
        });
    });

    describe("escapeHtml", () => {
        test("escapes characters with a special meaning in HTML", () => {
            assert.equal(escapeHtml(`<a href="foo" title='bar'>&</a>`), "&lt;a href&#x3D;&quot;foo&quot; title&#x3D;&#39;bar&#39;&gt;&amp;&lt;&#x2F;a&gt;");
            assert.equal(escapeHtml("`"), "&#x60;");
        });

        test("escapes ampersands of existing entities", () => {
            assert.equal(escapeHtml("&amp;"), "&amp;amp;");
        });

        test("leaves other characters unchanged", () => {
            assert.equal(escapeHtml("Hello world"), "Hello world");
        });
    });
});
