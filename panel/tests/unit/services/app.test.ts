import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, mock, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import type { Modal } from "../../../src/ts/services/modal";
import type { Translation } from "../../../src/ts/services/translation";

type App = typeof app;

/**
 * Make an application without registrations, like the one of the panel
 */
const makeApp = () => new (app.constructor as new () => App)();

const config = { siteUri: "/site/", baseUri: "/site/panel/", translation: { code: "it", data: { foo: "bar" } } };

describe("App", () => {
    test("has a default configuration", () => {
        assert.deepEqual(makeApp().config, { siteUri: "/", baseUri: "/", translation: { code: "en", data: {} } });
    });

    test("loads the configuration", () => {
        const application = makeApp();
        application.load({ ...config, csrfToken: "token" });
        assert.equal(application.config.baseUri, "/site/panel/");
        assert.equal(application.config.csrfToken, "token");
        assert.deepEqual(application.config.translation, config.translation);
    });

    test("merges a configuration into the current one", () => {
        const application = makeApp();
        application.loadConfig(config);
        application.loadConfig({ ...config, colorScheme: "dark" });
        assert.equal(application.config.siteUri, "/site/");
        assert.equal(application.config.colorScheme, "dark");
    });

    test("starts the services, storing them, and the components in the order they are registered", () => {
        const application = makeApp();
        const calls: string[] = [];
        application.service(
            "translation",
            class {
                constructor(started: App) {
                    calls.push(`translation:${started.config.translation.code}`);
                }
            } as unknown as new (
                app: App,
            ) => Translation,
        );
        application.component(
            class {
                constructor(started: App) {
                    calls.push(`component:${started.translation !== undefined}`);
                }
            },
        );
        assert.deepEqual(calls, []);
        application.load(config);
        assert.deepEqual(calls, ["translation:it", "component:true"]);
        assert.equal(typeof application.translation, "object");
    });

    test("starts a component immediately", () => {
        const application = makeApp();
        const component = mock.fn();
        application.loadComponent(
            class {
                constructor(started: App) {
                    component(started);
                }
            },
        );
        assert.ok(component.mock.calls[0].arguments[0] === application);
    });

    describe("views", () => {
        test("starts a view with the first element matching its selector", () => {
            document.body.innerHTML = '<div class="foo" id="first"></div><div class="foo" id="second"></div>';
            const application = makeApp();
            const init = mock.fn((element: HTMLElement) => element.id);
            application.view(".foo", init);
            application.load(config);
            assert.deepEqual(
                init.mock.calls.map((call) => call.result),
                ["first"],
            );
        });

        test("starts a view with each element matching its selector", () => {
            document.body.innerHTML = '<div class="foo" id="first"></div><div class="foo" id="second"></div>';
            const application = makeApp();
            const init = mock.fn((element: HTMLElement) => element.id);
            application.views(".foo", init);
            application.load(config);
            assert.deepEqual(
                init.mock.calls.map((call) => call.result),
                ["first", "second"],
            );
        });

        test("does not start the views of the elements missing from the page", () => {
            document.body.innerHTML = "";
            const application = makeApp();
            const init = mock.fn();
            application.view(".foo", init);
            application.views(".foo", init);
            application.load(config);
            assert.equal(init.mock.callCount(), 0);
        });

        test("starts a modal with the modal of its id, if the page has it", () => {
            const application = makeApp();
            const modal = { element: document.createElement("div") } as unknown as Modal;
            application.modals = { fooModal: modal };
            const init = mock.fn();
            const missing = mock.fn();
            application.modal("fooModal", init);
            application.modal("barModal", missing);
            application.load(config);
            assert.ok(init.mock.calls[0].arguments[0] === modal);
            assert.equal(missing.mock.callCount(), 0);
        });

        test("looks for the elements when loaded", () => {
            document.body.innerHTML = "";
            const application = makeApp();
            const init = mock.fn();
            application.view(".foo", init);
            document.body.innerHTML = '<div class="foo"></div>';
            application.load(config);
            assert.equal(init.mock.callCount(), 1);
        });
    });
});
