import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { post, Request } from "../../../src/ts/services/request";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../stubs/xhr";

installXMLHttpRequestStub();

/**
 * Callback of the requests whose response is not checked
 */
const ignoreResponse = () => undefined;

const json = (status: string, message: string, data: unknown = {}) => JSON.stringify({ status, message, data });

describe("Request Service", () => {
    describe("Request", () => {
        test("sends an asynchronous GET request by default", () => {
            new Request({}, ignoreResponse);
            const request = XMLHttpRequestStub.last;
            assert.equal(request.method, "GET");
            assert.equal(request.url, "");
            assert.equal(request.async, true);
        });

        test("sends the request with the given method, URL and headers", () => {
            new Request({ method: "POST", url: "/panel/foo/", headers: { "X-Foo": "bar" } }, ignoreResponse);
            const request = XMLHttpRequestStub.last;
            assert.equal(request.method, "POST");
            assert.equal(request.url, "/panel/foo/");
            assert.deepEqual(request.headers, { "X-Foo": "bar", "X-Requested-With": "XMLHttpRequest" });
        });

        test("preserves the given X-Requested-With header", () => {
            new Request({ headers: { "X-Requested-With": "foo" } }, ignoreResponse);
            assert.equal(XMLHttpRequestStub.last.headers["X-Requested-With"], "foo");
        });

        test("does not change the default headers", () => {
            new Request({}, ignoreResponse);
            new Request({ headers: { "X-Foo": "bar" } }, ignoreResponse);
            new Request({}, ignoreResponse);
            assert.deepEqual(XMLHttpRequestStub.last.headers, { "X-Requested-With": "XMLHttpRequest" });
        });

        test("sends objects as URL encoded data", () => {
            new Request({ data: { foo: "bar", baz: "Hello world" } }, ignoreResponse);
            const body = XMLHttpRequestStub.last.body;
            assert.ok(body instanceof URLSearchParams);
            assert.equal(body.toString(), "foo=bar&baz=Hello+world");
        });

        test("sends form data, URL parameters and blobs as they are", () => {
            for (const data of [new FormData(), new URLSearchParams("foo=bar"), new Blob(["foo"])]) {
                new Request({ data }, ignoreResponse);
                assert.equal(XMLHttpRequestStub.last.body, data);
            }
        });

        test("leaves out undefined values", () => {
            new Request({ data: { foo: "bar", baz: undefined } }, ignoreResponse);
            assert.equal(String(XMLHttpRequestStub.last.body), "foo=bar");
        });

        test("passes the JSON response and the request to the callback", () => {
            const callback = mock.fn();
            new Request({}, callback);
            const request = XMLHttpRequestStub.last;
            request.respond(200, json("success", "Done", { foo: "bar" }));
            assert.equal(callback.mock.callCount(), 1);
            assert.deepEqual(callback.mock.calls[0].arguments, [{ status: "success", message: "Done", data: { foo: "bar" } }, request]);
        });

        test("reports non-JSON responses with the status text", () => {
            const callback = mock.fn();
            new Request({}, callback);
            XMLHttpRequestStub.last.respond(500, "<html>Error</html>", "Internal Server Error");
            assert.deepEqual(callback.mock.calls[0].arguments[0], { status: "error", message: "Internal Server Error", data: {} });
        });

        test("reports network errors", () => {
            const callback = mock.fn();
            new Request({}, callback);
            XMLHttpRequestStub.last.fail();
            assert.deepEqual(callback.mock.calls[0].arguments[0], { status: "error", message: "The request failed", data: {} });
        });
    });

    describe("post", () => {
        beforeEach(() => {
            app.config.csrfToken = "token";
        });

        test("sends a POST request with the CSRF token", () => {
            post("/panel/foo/", { foo: "bar" }, ignoreResponse);
            const request = XMLHttpRequestStub.last;
            assert.equal(request.method, "POST");
            assert.equal(request.url, "/panel/foo/");
            assert.equal(String(request.body), "foo=bar&csrf-token=token");
        });

        test("adds the CSRF token to form data", () => {
            const data = new FormData();
            data.append("foo", "bar");
            post("/panel/foo/", data, ignoreResponse);
            assert.equal(XMLHttpRequestStub.last.body, data);
            assert.deepEqual(
                [...data.entries()],
                [
                    ["foo", "bar"],
                    ["csrf-token", "token"],
                ],
            );
        });

        test("does not change the given object", () => {
            const data = { foo: "bar" };
            post("/panel/foo/", data, ignoreResponse);
            assert.deepEqual(data, { foo: "bar" });
        });

        test("passes the response to the callback", () => {
            const callback = mock.fn();
            post("/panel/foo/", {}, callback);
            XMLHttpRequestStub.last.respond(200, json("success", "Done"));
            assert.equal(callback.mock.calls[0].arguments[0].message, "Done");
        });
    });
});
