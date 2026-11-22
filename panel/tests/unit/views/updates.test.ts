import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { checkCircle, exclamationOctagon } from "../../../src/ts/components/ui/icons";
import { app } from "../../../src/ts/services/app";
import { initUpdates } from "../../../src/ts/views/updates";
import { iconMarkup } from "../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../stubs/xhr";

beforeEach((t) => {
    installXMLHttpRequestStub();
    app.config.baseUri = "/panel/";
    app.config.csrfToken = "token";
    // The updates are checked a second after the page is loaded
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
});

/**
 * Add the updater as the panel renders it and initialize it
 */
function makeUpdater() {
    document.body.innerHTML = `
        <section id="updater-component">
            <div class="checker"><span class="spinner"></span><span class="update-status" data-checking-text="Checking for updates..." data-installing-text="Installing...">Checking for updates...</span></div>
            <div class="new-version" style="display: none;">
                <p><strong class="new-version-name">Formwork x.x.x</strong> is available</p>
                <button type="button" data-command="install-updates">Install</button>
            </div>
            <div class="current-version" style="display: none;">
                <p><strong class="current-version-name">Formwork 2.0.0</strong> is the latest version</p>
            </div>
        </section>`;
    initUpdates(document.getElementById("updater-component") as HTMLElement);
}

const element = (selector: string) => document.querySelector(selector) as HTMLElement;

/**
 * Get what the updater shows: the status, the spinner and the visible versions
 */
function shown() {
    return {
        status: element(".update-status").textContent,
        spinner: element(".spinner").className,
        newVersion: element(".new-version").style.display === "block" ? element(".new-version-name").textContent : null,
        currentVersion: element(".current-version").style.display === "block" ? element(".current-version-name").textContent : null,
    };
}

/**
 * Check for updates and answer that a new version is available
 */
function checkOutdated(t: { mock: { timers: { tick(ms: number): void } } }) {
    makeUpdater();
    t.mock.timers.tick(1000);
    XMLHttpRequestStub.last.respondJson("success", "A new version is available", { uptodate: false, release: { name: "Formwork 2.1.0" } });
}

const install = () => element("[data-command=install-updates]").click();

describe("Updates View", () => {
    test("checks for updates a second after loading, showing that it is pending", (t) => {
        makeUpdater();
        assert.deepEqual(shown(), { status: "Checking for updates...", spinner: "spinner", newVersion: null, currentVersion: null });
        t.mock.timers.tick(999);
        assert.equal(XMLHttpRequestStub.requests.length, 0);
        t.mock.timers.tick(1);
        assert.equal(XMLHttpRequestStub.last.method, "POST");
        assert.equal(XMLHttpRequestStub.last.url, "/panel/updates/check/");
    });

    test("shows the new version when outdated", (t) => {
        checkOutdated(t);
        assert.deepEqual(shown(), { status: "A new version is available", spinner: "spinner spinner-info", newVersion: "Formwork 2.1.0", currentVersion: null });
    });

    test("shows the current version when up to date", (t) => {
        makeUpdater();
        t.mock.timers.tick(1000);
        XMLHttpRequestStub.last.respondJson("success", "Formwork is up to date", { uptodate: true, release: { name: "Formwork 2.0.0" } });
        assert.deepEqual(shown(), { status: "Formwork is up to date", spinner: "spinner spinner-success", newVersion: null, currentVersion: "Formwork 2.0.0" });
    });

    test("reports a failed check", (t) => {
        makeUpdater();
        t.mock.timers.tick(1000);
        XMLHttpRequestStub.last.respondJson("error", "Cannot check for updates");
        assert.deepEqual(shown(), { status: "Cannot check for updates", spinner: "spinner spinner-danger", newVersion: null, currentVersion: null });
    });

    test("installs the new version, showing that it is pending", (t) => {
        checkOutdated(t);
        install();
        assert.equal(XMLHttpRequestStub.last.url, "/panel/updates/update/");
        assert.deepEqual(shown(), { status: "Installing...", spinner: "spinner", newVersion: null, currentVersion: null });
    });

    test("shows the installed version as the current one and notifies it", (t) => {
        checkOutdated(t);
        install();
        XMLHttpRequestStub.last.respondJson("success", "Formwork updated", { status: "Formwork 2.1.0 installed" });
        assert.deepEqual(shown(), { status: "Formwork 2.1.0 installed", spinner: "spinner spinner-success", newVersion: null, currentVersion: "Formwork 2.1.0" });
        assert.equal(document.querySelector(".notification-success")?.textContent?.trim(), "Formwork updated");
    });

    test("reports a failed installation", (t) => {
        checkOutdated(t);
        install();
        XMLHttpRequestStub.last.respondJson("error", "Cannot install the update");
        assert.deepEqual(shown(), { status: "Cannot install the update", spinner: "spinner spinner-danger", newVersion: null, currentVersion: null });
        assert.equal(document.querySelector(".notification-danger")?.textContent?.trim(), "Cannot install the update");
    });

    test("notifies the failed installation with the icon of the errors", (t) => {
        checkOutdated(t);
        install();
        XMLHttpRequestStub.last.respondJson("error", "Cannot install the update");
        assert.equal(document.querySelector(".notification")?.firstElementChild?.outerHTML, iconMarkup(exclamationOctagon));
        assert.notEqual(iconMarkup(exclamationOctagon), iconMarkup(checkCircle));
    });
});
