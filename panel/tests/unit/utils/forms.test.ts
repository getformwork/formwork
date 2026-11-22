import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, mock, test } from "node:test";
import { serializeForm, triggerDownload } from "../../../src/ts/utils/forms";

/**
 * Make a form in the document with the given HTML
 */
function makeForm(html: string) {
    const form = document.createElement("form");
    form.innerHTML = html;
    document.body.append(form);
    return form;
}

/**
 * Serialize a form made in the document with the given HTML
 */
const serialize = (html: string) => serializeForm(makeForm(html));

describe("Form Utils", () => {
    describe("serializeForm", () => {
        test("serializes fields as a query string", () => {
            const form = makeForm(`
                <input name="foo" value="bar">
                <input type="hidden" name="baz" value="qux">
                <textarea name="text">Hello</textarea>
                <select name="choice"><option value="first">First</option><option value="second" selected>Second</option></select>
            `);
            assert.equal(serializeForm(form), "foo=bar&baz=qux&text=Hello&choice=second");
        });

        test("returns an empty string for a form without fields", () => {
            assert.equal(serialize(""), "");
        });

        test("encodes names and values", () => {
            assert.equal(serialize('<input name="foo[bar]" value="Hello world &amp; more">'), "foo%5Bbar%5D=Hello%20world%20%26%20more");
        });

        test("serializes empty values", () => {
            assert.equal(serialize('<input name="foo">'), "foo=");
        });

        test("serializes fields with the same name in order", () => {
            assert.equal(serialize('<input name="foo" value="first"><input name="foo" value="second">'), "foo=first&foo=second");
        });

        test("serializes the current values of the fields", () => {
            const form = makeForm('<input name="foo" value="bar">');
            (form.elements.namedItem("foo") as HTMLInputElement).value = "baz";
            assert.equal(serializeForm(form), "foo=baz");
        });

        test("serializes only checked checkboxes and radios", () => {
            const form = makeForm(`
                <input type="checkbox" name="foo" value="first" checked>
                <input type="checkbox" name="foo" value="second">
                <input type="radio" name="bar" value="first">
                <input type="radio" name="bar" value="second" checked>
            `);
            assert.equal(serializeForm(form), "foo=first&bar=second");
        });

        test("serializes checkboxes without a value as on", () => {
            assert.equal(serialize('<input type="checkbox" name="foo" checked>'), "foo=on");
        });

        test("serializes every selected option of multiple selects", () => {
            const form = makeForm(`
                <select name="foo" multiple>
                    <option value="first" selected>First</option>
                    <option value="second">Second</option>
                    <option value="third" selected>Third</option>
                </select>
            `);
            assert.equal(serializeForm(form), "foo=first&foo=third");
        });

        test("serializes nothing for multiple selects without selected options", () => {
            assert.equal(serialize('<select name="foo" multiple><option value="first">First</option></select>'), "");
        });

        test("serializes the fields associated with the form from outside", () => {
            const form = makeForm('<input name="foo" value="bar">');
            form.id = "form";
            document.body.insertAdjacentHTML("beforeend", '<input form="form" name="baz" value="qux">');
            assert.equal(serializeForm(form), "foo=bar&baz=qux");
        });

        test("leaves out fields without a name", () => {
            assert.equal(serialize('<input value="foo"><input name="bar" value="baz">'), "bar=baz");
        });

        test("leaves out disabled fields", () => {
            assert.equal(serialize('<input name="foo" value="bar" disabled><input name="baz" value="qux">'), "baz=qux");
        });

        test("leaves out ignored fields", () => {
            assert.equal(serialize('<input name="foo" value="bar" data-form-ignore="true"><input name="baz" value="qux">'), "baz=qux");
        });

        test("serializes fields not ignored explicitly", () => {
            assert.equal(serialize('<input name="foo" value="bar" data-form-ignore="false">'), "foo=bar");
        });

        test("leaves out files and buttons", () => {
            const form = makeForm(`
                <input type="file" name="file">
                <input type="reset" name="reset" value="foo">
                <input type="submit" name="submit" value="foo">
                <input type="button" name="button" value="foo">
                <button name="native" value="foo">Foo</button>
                <button type="button" name="other" value="foo">Foo</button>
                <input name="bar" value="baz">
            `);
            assert.equal(serializeForm(form), "bar=baz");
        });

        test("leaves out fieldsets", () => {
            assert.equal(serialize('<fieldset name="group"><input type="radio" name="foo" value="bar" checked></fieldset>'), "foo=bar");
        });

        test("leaves out the fields of disabled fieldsets", () => {
            assert.equal(serialize('<fieldset disabled><input type="radio" name="foo" value="bar" checked></fieldset><input name="baz" value="qux">'), "baz=qux");
        });

        test("leaves out the fields of fieldsets inside disabled fieldsets", () => {
            assert.equal(serialize('<fieldset disabled><fieldset><legend><input name="foo" value="bar"></legend></fieldset></fieldset>'), "");
        });

        test("serializes the fields in the first legend of disabled fieldsets", () => {
            assert.equal(serialize('<fieldset disabled><legend><input name="foo" value="bar"></legend><legend><input name="baz" value="qux"></legend></fieldset>'), "foo=bar");
        });

        test("leaves out the fields in the legend of a fieldset inside a disabled fieldset", () => {
            assert.equal(serialize('<fieldset disabled><fieldset disabled><legend><input name="foo" value="bar"></legend></fieldset></fieldset>'), "");
        });
    });

    describe("triggerDownload", () => {
        test("submits a POST form with the CSRF token to the URI", (t) => {
            const submit = t.mock.method(HTMLFormElement.prototype, "submit", function (this: HTMLFormElement) {
                assert.equal(this.isConnected, true);
                assert.equal(this.getAttribute("action"), "/panel/backup/download/");
                assert.equal(this.method, "post");
                assert.equal(serializeForm(this), "csrf-token=token");
                assert.equal((this.elements.namedItem("csrf-token") as HTMLInputElement).type, "hidden");
            });

            triggerDownload("/panel/backup/download/", "token");

            assert.equal(submit.mock.callCount(), 1);
        });

        test("removes the form after submitting it", (t) => {
            t.mock.method(HTMLFormElement.prototype, "submit", mock.fn());
            triggerDownload("/panel/backup/download/", "token");
            assert.equal(document.querySelectorAll("form").length, 0);
        });
    });
});
