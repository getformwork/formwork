import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { hasSafeScheme, resolveUri } from "../../../src/ts/utils/uri";

describe("URI Utils", () => {
    describe("resolveUri", () => {
        const siteUri = "/site/";
        const pageUri = "/site/blog/post/";

        test("resolves relative URIs against the page", () => {
            assert.equal(resolveUri("image.jpg", siteUri, pageUri), "/site/blog/post/image.jpg");
            assert.equal(resolveUri("./image.jpg", siteUri, pageUri), "/site/blog/post/image.jpg");
            assert.equal(resolveUri("files/image.jpg", siteUri, pageUri), "/site/blog/post/files/image.jpg");
        });

        test("resolves parent segments", () => {
            assert.equal(resolveUri("../other/", siteUri, pageUri), "/site/blog/other/");
        });

        test("does not resolve parent segments beyond the root of the site", () => {
            assert.equal(resolveUri("../../../page/", siteUri, pageUri), "/site/page/");
        });

        test("resolves URIs starting with a slash against the root of the site", () => {
            assert.equal(resolveUri("/image.jpg", siteUri, pageUri), "/site/image.jpg");
            assert.equal(resolveUri("/", siteUri, pageUri), "/site/");
        });

        test("resolves against the parent of pages without a trailing slash", () => {
            assert.equal(resolveUri("image.jpg", siteUri, "/site/blog/post"), "/site/blog/image.jpg");
        });

        test("resolves against the home page", () => {
            assert.equal(resolveUri("image.jpg", siteUri, siteUri), "/site/image.jpg");
        });

        test("resolves against a site at the root", () => {
            assert.equal(resolveUri("image.jpg", "/", "/blog/post/"), "/blog/post/image.jpg");
            assert.equal(resolveUri("/page/", "/", "/blog/post/"), "/page/");
            assert.equal(resolveUri("../../../page/", "/", "/blog/post/"), "/page/");
        });

        test("resolves against a site URI without a trailing slash", () => {
            assert.equal(resolveUri("image.jpg", "/site", pageUri), "/site/blog/post/image.jpg");
        });

        test("resolves against absolute site and page URIs", () => {
            assert.equal(resolveUri("image.jpg", "http://example.test/site/", "http://example.test/site/blog/post/"), "/site/blog/post/image.jpg");
        });

        test("resolves against the root of the site for pages outside it", () => {
            assert.equal(resolveUri("image.jpg", siteUri, "/other/page/"), "/site/other/page/image.jpg");
        });

        test("resolves against the root of the site without a page", () => {
            assert.equal(resolveUri("image.jpg", siteUri, ""), "/site/image.jpg");
        });

        test("preserves the query and the fragment", () => {
            assert.equal(resolveUri("image.jpg?v=1#top", siteUri, pageUri), "/site/blog/post/image.jpg?v=1#top");
        });

        test("resolves queries against the page", () => {
            assert.equal(resolveUri("?page=2", siteUri, pageUri), "/site/blog/post/?page=2");
        });

        test("leaves fragments unchanged", () => {
            assert.equal(resolveUri("#foo", siteUri, pageUri), "#foo");
        });

        test("leaves empty URIs unchanged", () => {
            assert.equal(resolveUri("", siteUri, pageUri), "");
        });

        test("leaves URIs with a scheme unchanged", () => {
            for (const uri of ["http://example.test/page", "https://example.test/page", "HTTPS://example.test/page", "mailto:user@example.test", "tel:+10000000000", "data:image/png;base64,AAAA", "javascript:alert(1)"]) {
                assert.equal(resolveUri(uri, siteUri, pageUri), uri);
            }
        });

        test("leaves protocol-relative URIs unchanged", () => {
            assert.equal(resolveUri("//example.test/page", siteUri, pageUri), "//example.test/page");
        });

        test("encodes characters not allowed in URIs", () => {
            assert.equal(resolveUri("my image.jpg", siteUri, pageUri), "/site/blog/post/my%20image.jpg");
        });
    });

    describe("hasSafeScheme", () => {
        test("accepts relative URIs", () => {
            for (const uri of ["page", "/page", "../page", "#foo", "?foo=bar", "//example.test/page", ""]) {
                assert.equal(hasSafeScheme(uri), true, uri);
            }
        });

        test("accepts safe schemes", () => {
            for (const uri of ["http://example.test", "https://example.test", "HTTPS://example.test", "mailto:user@example.test", "tel:+10000000000", "ftp://example.test/file.txt"]) {
                assert.equal(hasSafeScheme(uri), true, uri);
            }
        });

        test("rejects unsafe schemes", () => {
            for (const uri of ["javascript:alert(1)", "JavaScript:alert(1)", "vbscript:foo", "data:text/html,foo", "file:///etc/passwd"]) {
                assert.equal(hasSafeScheme(uri), false, uri);
            }
        });

        test("rejects unsafe schemes hidden by whitespace and control characters", () => {
            for (const uri of [" javascript:alert(1)", "java\nscript:alert(1)", "java\tscript:alert(1)", "\u0000javascript:alert(1)"]) {
                assert.equal(hasSafeScheme(uri), false, JSON.stringify(uri));
            }
        });

        test("accepts colons after the first path segment", () => {
            assert.equal(hasSafeScheme("page/foo:bar"), true);
            assert.equal(hasSafeScheme("?foo=javascript:alert(1)"), true);
        });
    });
});
