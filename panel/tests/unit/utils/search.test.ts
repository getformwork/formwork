import "../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, mock, test } from "node:test";
import { initSearchInput, makeSearchRegExp } from "../../../src/ts/utils/search";

describe("Search Utils", () => {
    describe("makeSearchRegExp", () => {
        test("matches at the beginning of words by default", () => {
            const regExp = makeSearchRegExp("bar");
            assert.ok(regExp.test("bar"));
            assert.ok(regExp.test("foo bar"));
            assert.ok(regExp.test("foo-bar"));
            assert.equal(regExp.test("foobar"), false);
        });

        test("matches anywhere without word start", () => {
            assert.ok(makeSearchRegExp("bar", { wordStart: false }).test("foobar"));
        });

        test("ignores case", () => {
            assert.ok(makeSearchRegExp("foo").test("FOO"));
            assert.ok(makeSearchRegExp("FOO").test("foo"));
        });

        test("ignores diacritics of the searched text", () => {
            assert.ok(makeSearchRegExp("cafe").test("Café"));
        });

        test("matches special characters literally", () => {
            assert.ok(makeSearchRegExp("foo.bar").test("foo.bar"));
            assert.equal(makeSearchRegExp("foo.bar").test("fooxbar"), false);
            assert.ok(makeSearchRegExp("(foo)", { wordStart: false }).test("bar (foo)"));
        });

        test("adds the global flag only when requested", () => {
            assert.equal(makeSearchRegExp("foo").global, false);
            assert.equal(makeSearchRegExp("foo", { global: true }).global, true);
        });

        test("matches hyphens and slashes literally", () => {
            assert.ok(makeSearchRegExp("foo-bar/baz").test("foo-bar/baz"));
            assert.ok(makeSearchRegExp("foo-bar", { wordStart: false }).test("xfoo-bar"));
        });

        test("does not match after digits or underscores at word start", () => {
            assert.equal(makeSearchRegExp("foo").test("1foo"), false);
            assert.equal(makeSearchRegExp("foo").test("bar_foo"), false);
        });

        test("finds every occurrence with the global flag", () => {
            assert.deepEqual("foo bar foo".match(makeSearchRegExp("foo", { global: true })), ["foo", "foo"]);
        });
    });

    describe("initSearchInput", () => {
        beforeEach(() => {
            mock.timers.enable({ apis: ["setTimeout"] });
        });

        afterEach(() => {
            mock.timers.reset();
        });

        /**
         * Make a search input calling a mock function on search, with another field in the document
         */
        function makeSearchInput() {
            document.body.innerHTML = '<input type="search" id="search"><input id="other">';
            const input = document.getElementById("search") as HTMLInputElement;
            const onSearch = mock.fn();
            initSearchInput(input, onSearch);
            return { input, onSearch, other: document.getElementById("other") as HTMLInputElement };
        }

        const findShortcut = (modifier: "ctrlKey" | "metaKey") => new KeyboardEvent("keydown", { key: "f", [modifier]: true, bubbles: true, cancelable: true });

        test("searches once after typing", () => {
            const { input, onSearch } = makeSearchInput();
            input.dispatchEvent(new KeyboardEvent("keyup", { key: "f" }));
            mock.timers.tick(50);
            input.dispatchEvent(new KeyboardEvent("keyup", { key: "o" }));
            mock.timers.tick(99);
            assert.equal(onSearch.mock.callCount(), 0);
            mock.timers.tick(1);
            assert.equal(onSearch.mock.callCount(), 1);
        });

        test("searches at once when the field is cleared", () => {
            const { input, onSearch } = makeSearchInput();
            input.dispatchEvent(new Event("search"));
            assert.equal(onSearch.mock.callCount(), 1);
        });

        test("focuses the field with Ctrl+F and Cmd+F", () => {
            for (const modifier of ["ctrlKey", "metaKey"] as const) {
                const { input, other } = makeSearchInput();
                other.focus();
                const event = findShortcut(modifier);
                other.dispatchEvent(event);
                assert.ok(document.activeElement === input, modifier);
                assert.equal(event.defaultPrevented, true, modifier);
            }
        });

        test("leaves Ctrl+F to the browser when the field is focused", () => {
            const { input } = makeSearchInput();
            input.focus();
            const event = findShortcut("ctrlKey");
            input.dispatchEvent(event);
            assert.equal(event.defaultPrevented, false);
        });

        test("ignores F without modifiers", () => {
            const { input, other } = makeSearchInput();
            other.focus();
            const event = new KeyboardEvent("keydown", { key: "f", bubbles: true, cancelable: true });
            other.dispatchEvent(event);
            assert.ok(document.activeElement !== input);
            assert.equal(event.defaultPrevented, false);
        });
    });
});
