import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { getCookies, setCookie } from "../../../src/ts/utils/cookies";

// happy-dom does not remove cookies with `max-age=0`, a date in the past is used instead
const expired = "Thu, 01 Jan 1970 00:00:00 GMT";

/**
 * Set a cookie of the document as the browser does
 */
function setDocumentCookie(cookie: string) {
    // biome-ignore lint/suspicious/noDocumentCookie: The cookies of the document are under test
    document.cookie = cookie;
}

describe("Cookies Utils", () => {
    describe("getCookies", () => {
        test("returns the cookies by name", () => {
            setDocumentCookie("foo=bar");
            setDocumentCookie("baz=qux");
            assert.deepEqual(getCookies(), { foo: "bar", baz: "qux" });
        });

        test("returns no cookies without cookies", () => {
            assert.deepEqual(getCookies(), {});
        });

        test("decodes values", () => {
            setDocumentCookie("foo=Hello%20world");
            assert.deepEqual(getCookies(), { foo: "Hello world" });
        });

        test("preserves empty values", () => {
            setDocumentCookie("foo=");
            setDocumentCookie("bar=baz");
            assert.deepEqual(getCookies(), { foo: "", bar: "baz" });
        });

        test("preserves equal signs in values", () => {
            setDocumentCookie("foo=YmFy==");
            setDocumentCookie("bar=key=value");
            assert.deepEqual(getCookies(), { foo: "YmFy==", bar: "key=value" });
        });

        test("preserves values that cannot be decoded", () => {
            setDocumentCookie("foo=100%");
            setDocumentCookie("bar=baz");
            assert.deepEqual(getCookies(), { foo: "100%", bar: "baz" });
        });
    });

    describe("setCookie", () => {
        test("sets a cookie", () => {
            setCookie("foo", "bar", {});
            assert.deepEqual(getCookies(), { foo: "bar" });
        });

        test("replaces a cookie with the same name", () => {
            setCookie("foo", "bar", {});
            setCookie("foo", "baz", {});
            assert.deepEqual(getCookies(), { foo: "baz" });
        });

        test("sets the attributes of the cookie", () => {
            setCookie("foo", "bar", { path: "/", "max-age": 3600 });
            assert.deepEqual(getCookies(), { foo: "bar" });

            setCookie("foo", "bar", { path: "/", expires: expired });
            assert.deepEqual(getCookies(), {});
        });

        test("sets cookies only for their path", () => {
            setCookie("foo", "bar", { path: "/other/" });
            assert.deepEqual(getCookies(), {});
        });

        test("sets values without special characters as they are", () => {
            setCookie("foo", "light", {});
            assert.equal(document.cookie, "foo=light");
        });
    });
});
