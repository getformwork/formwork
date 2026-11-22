import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { initLogin } from "../../../src/ts/views/login";

/**
 * Add the login form as the panel renders it and initialize it
 */
function makeLogin() {
    document.body.innerHTML = '<form method="post" data-form="login-form"><input name="username" value="foo"><input type="password" name="password" value="bar"><button type="submit" id="login">Login</button></form>';
    const form = document.querySelector("form") as HTMLFormElement;
    initLogin(form);
    return { form, button: document.getElementById("login") as HTMLButtonElement };
}

/**
 * Dispatch the submission of a form, as the browser does
 */
function submit(form: HTMLFormElement, submitter: HTMLElement | null) {
    const event = new SubmitEvent("submit", { cancelable: true, submitter });
    form.dispatchEvent(event);
}

/**
 * Make the event of a page shown, from the history if persisted
 */
function pageshow(persisted: boolean) {
    const event = new Event("pageshow");
    // happy-dom has no `PageTransitionEvent`, unlike browsers
    Object.defineProperty(event, "persisted", { value: persisted });
    return event;
}

describe("Login View", () => {
    test("disables the button once the form is submitted, so that it is not submitted twice", () => {
        const { form, button } = makeLogin();
        assert.equal(button.disabled, false);
        submit(form, button);
        assert.equal(button.disabled, true);
    });

    test("does nothing for submissions without a button", () => {
        const { form, button } = makeLogin();
        submit(form, null);
        assert.equal(button.disabled, false);
    });

    test("enables the button again when the page is shown from the history", () => {
        const { form, button } = makeLogin();
        submit(form, button);
        window.dispatchEvent(pageshow(true));
        assert.equal(button.disabled, false);
    });

    test("keeps the button disabled when the page is shown otherwise", () => {
        const { form, button } = makeLogin();
        submit(form, button);
        window.dispatchEvent(pageshow(false));
        assert.equal(button.disabled, true);
    });
});
