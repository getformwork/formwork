import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { UpdatesModel } from "../../../src/ts/models/updates";

describe("UpdatesModel", () => {
    test("starts checking for updates", () => {
        const updates = new UpdatesModel();
        assert.equal(updates.phase.value, "checking");
        assert.equal(updates.spinnerStatus.value, "pending");
        assert.equal(updates.message.value, null);
        assert.equal(updates.releaseName.value, null);
        assert.equal(updates.newVersionVisible.value, false);
        assert.equal(updates.currentVersionVisible.value, false);
    });

    test("shows the new version when outdated", () => {
        const updates = new UpdatesModel();
        updates.checked({ status: "success", message: "Update found", data: { uptodate: false, release: { name: "2.1.0" } } });
        assert.equal(updates.phase.value, "outdated");
        assert.equal(updates.spinnerStatus.value, "info");
        assert.equal(updates.message.value, "Update found");
        assert.equal(updates.releaseName.value, "2.1.0");
        assert.equal(updates.newVersionVisible.value, true);
        assert.equal(updates.currentVersionVisible.value, false);
    });

    test("shows the current version when up to date", () => {
        const updates = new UpdatesModel();
        updates.checked({ status: "success", message: "Up to date", data: { uptodate: true, release: { name: "2.0.0" } } });
        assert.equal(updates.phase.value, "current");
        assert.equal(updates.spinnerStatus.value, "success");
        assert.equal(updates.message.value, "Up to date");
        assert.equal(updates.releaseName.value, null);
        assert.equal(updates.newVersionVisible.value, false);
        assert.equal(updates.currentVersionVisible.value, true);
    });

    test("reports a failed check", () => {
        const updates = new UpdatesModel();
        updates.checked({ status: "error", message: "Cannot check for updates", data: { uptodate: false, release: { name: "2.1.0" } } });
        assert.equal(updates.phase.value, "error");
        assert.equal(updates.spinnerStatus.value, "danger");
        assert.equal(updates.message.value, "Cannot check for updates");
        assert.equal(updates.releaseName.value, null);
        assert.equal(updates.newVersionVisible.value, false);
        assert.equal(updates.currentVersionVisible.value, false);
    });

    test("reports a check failed by a network error", () => {
        const updates = new UpdatesModel();
        updates.checked({ status: "error", message: "The request failed", data: {} as never });
        assert.equal(updates.phase.value, "error");
        assert.equal(updates.message.value, "The request failed");
    });

    test("shows the installation in progress", () => {
        const updates = new UpdatesModel();
        updates.checked({ status: "success", message: "Update found", data: { uptodate: false, release: { name: "2.1.0" } } });
        updates.installing("Installing…");
        assert.equal(updates.phase.value, "installing");
        assert.equal(updates.spinnerStatus.value, "pending");
        assert.equal(updates.message.value, "Installing…");
        assert.equal(updates.newVersionVisible.value, false);
        assert.equal(updates.currentVersionVisible.value, false);
    });

    test("shows the current version once installed", () => {
        const updates = new UpdatesModel();
        updates.installing("Installing…");
        updates.installed({ status: "success", message: "Installed", data: { status: "Up to date" } });
        assert.equal(updates.phase.value, "installed");
        assert.equal(updates.spinnerStatus.value, "success");
        assert.equal(updates.message.value, "Up to date");
        assert.equal(updates.currentVersionVisible.value, true);
    });

    test("reports a failed installation", () => {
        const updates = new UpdatesModel();
        updates.installing("Installing…");
        updates.installed({ status: "error", message: "Cannot install", data: { status: "Cannot install" } });
        assert.equal(updates.phase.value, "error");
        assert.equal(updates.spinnerStatus.value, "danger");
        assert.equal(updates.message.value, "Cannot install");
        assert.equal(updates.currentVersionVisible.value, false);
    });

    test("reports an installation failed by a network error", () => {
        const updates = new UpdatesModel();
        updates.installing("Installing…");
        updates.installed({ status: "error", message: "The request failed", data: {} as never });
        assert.equal(updates.phase.value, "error");
        assert.equal(updates.message.value, "The request failed");
    });
});
