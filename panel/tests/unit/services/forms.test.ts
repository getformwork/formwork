import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { Form } from "../../../src/ts/services/form";
import { Forms } from "../../../src/ts/services/forms";
import { leavePage } from "../../helpers/panel";

beforeEach(() => {
    app.modals = {};
});

describe("Forms", () => {
    test("collects the forms of the page by name", () => {
        document.body.innerHTML = '<form data-form="first"></form><form data-form="second"></form><form></form>';
        const forms = new Forms();
        assert.deepEqual(Object.keys(forms), ["first", "second"]);
        assert.ok(forms.first instanceof Form);
        assert.ok(forms.first.element === document.querySelector("[data-form=first]"));
        assert.ok(forms.second.element === document.querySelector("[data-form=second]"));
    });

    test("ignores the forms with an empty name", () => {
        document.body.innerHTML = '<form data-form=""></form>';
        assert.deepEqual(Object.keys(new Forms()), []);
    });

    test("is empty without forms", () => {
        document.body.innerHTML = "";
        assert.deepEqual(Object.keys(new Forms()), []);
    });

    test("asks for confirmation before leaving the page with unsaved changes", () => {
        document.body.innerHTML = '<form data-form="first"><input id="foo" name="foo" value="bar"></form>';
        new Forms();
        (document.getElementById("foo") as HTMLInputElement).value = "baz";
        assert.equal(leavePage(), true);
    });

    test("does not ask for confirmation for the forms ignoring their changes", () => {
        document.body.innerHTML = '<form data-form="first" data-ignore-changes="true"><input id="foo" name="foo" value="bar"></form>';
        new Forms();
        (document.getElementById("foo") as HTMLInputElement).value = "baz";
        assert.equal(leavePage(), false);
    });
});
