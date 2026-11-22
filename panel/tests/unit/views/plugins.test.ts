import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { Form } from "../../../src/ts/services/form";
import { initPluginToggle } from "../../../src/ts/views/plugins";
import { setTranslation } from "../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../stubs/xhr";

beforeEach((t) => {
    installXMLHttpRequestStub();
    setTranslation();
    app.forms = {};
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout", "Date"] });
});

/**
 * Add the toggle of a plugin as the panel renders it and initialize it
 *
 * @param enabled Whether the plugin is enabled
 * @param form    HTML of the fields of the form of the plugin, if its page is shown
 */
function makeToggle(enabled = false, form: string | null = null) {
    const toggle = `
        <fieldset class="form-togglegroup">
            <label class="form-label"><input class="form-input plugin-status-toggle" type="radio" name="enabled" value="1" ${enabled ? "checked" : ""} data-form-ignore="true" data-action="/panel/plugins/foo/enable/"><span>Enabled</span></label>
            <label class="form-label"><input class="form-input plugin-status-toggle" type="radio" name="enabled" value="0" ${enabled ? "" : "checked"} data-form-ignore="true" data-action="/panel/plugins/foo/disable/"><span>Disabled</span></label>
        </fieldset>`;
    document.body.innerHTML = form === null ? toggle : `<form data-form="plugin-form">${toggle}${form}</form>`;
    if (form !== null) {
        app.forms["plugin-form"] = new Form(document.querySelector("form") as HTMLFormElement, { preventUnloadOnChanges: false });
    }
    for (const input of document.querySelectorAll<HTMLInputElement>(".plugin-status-toggle")) {
        initPluginToggle(input);
    }
}

const radio = (value: string) => document.querySelector(`.plugin-status-toggle[value="${value}"]`) as HTMLInputElement;

const group = () => document.querySelector(".form-togglegroup") as HTMLFieldSetElement;

/**
 * Choose an option of the toggle as the user does, waiting for the request
 */
function choose(t: { mock: { timers: { tick(ms: number): void } } }, value: string) {
    radio(value).checked = true;
    radio(value).dispatchEvent(new Event("change", { bubbles: true }));
    t.mock.timers.tick(500);
}

describe("Plugins View", () => {
    test("enables the plugin, disabling the toggle meanwhile", (t) => {
        makeToggle(false);
        choose(t, "1");
        assert.equal(XMLHttpRequestStub.last.url, "/panel/plugins/foo/enable/");
        assert.equal(group().disabled, true);
    });

    test("disables the plugin", (t) => {
        makeToggle(true);
        choose(t, "0");
        assert.equal(XMLHttpRequestStub.last.url, "/panel/plugins/foo/disable/");
    });

    test("restores the toggle when the server refuses the change", (t) => {
        makeToggle(false);
        choose(t, "1");
        XMLHttpRequestStub.last.respondJson("error", "Cannot enable the plugin");
        assert.equal(radio("0").checked, true);
        assert.equal(group().disabled, false);
        assert.equal(document.querySelector(".notification-danger")?.textContent?.trim(), "Cannot enable the plugin");
    });

    test("does not enable the plugin while its options are invalid", (t) => {
        makeToggle(false, '<input name="foo" required>');
        choose(t, "1");
        assert.equal(XMLHttpRequestStub.requests.length, 0);
        assert.equal(radio("0").checked, true);
    });

    test("disables the plugin also while its options are invalid", (t) => {
        makeToggle(true, '<input name="foo" required>');
        choose(t, "0");
        assert.equal(XMLHttpRequestStub.last.url, "/panel/plugins/foo/disable/");
    });

    test("notifies the change without reloading the page while the options have changes", (t) => {
        makeToggle(false, '<input name="foo" value="bar">');
        const input = document.querySelector("[name=foo]") as HTMLInputElement;
        input.value = "baz";
        input.dispatchEvent(new Event("input", { bubbles: true }));
        choose(t, "1");
        XMLHttpRequestStub.last.respondJson("success", "Plugin enabled");
        assert.equal(document.querySelector(".notification-success")?.textContent?.trim(), "Plugin enabled");
        assert.equal(radio("1").checked, true);
        assert.equal(group().disabled, false);
    });
});
