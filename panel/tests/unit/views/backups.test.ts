import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { app } from "../../../src/ts/services/app";
import { initBackups } from "../../../src/ts/views/backups";
import { setTranslation } from "../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../stubs/xhr";

beforeEach(() => {
    installXMLHttpRequestStub();
    app.config.baseUri = "/panel/";
    app.config.csrfToken = "token";
    setTranslation({ "date.now": "Now" });
});

/**
 * Get the HTML of a backup in the table
 */
const backupRow = (filename: string) => `<tr><td class="table-cell"><a href="/panel/backup/download/${filename}/">${filename}</a></td><td class="table-cell">2024-01-05</td><td class="table-cell">1 MB</td><td class="table-cell"></td></tr>`;

/**
 * Add the view of the backups as the panel renders it and initialize it
 */
function makeBackups(filenames: string[] = []) {
    document.body.innerHTML = `
        <div data-view="backups">
            <section class="section">
                <button type="button" class="button" data-command="make-backup">Backup</button>
                <p>Latest backup: <span class="backup-last-time">Never</span></p>
            </section>
            <section id="backups-section" class="section" ${filenames.length === 0 ? "hidden" : ""}>
                <table id="backups-table">
                    <thead><tr><th>File</th><th>Date</th><th>Size</th><th>Actions</th></tr></thead>
                    <tbody>${filenames.map(backupRow).join("")}</tbody>
                </table>
            </section>
        </div>
        <template id="backups-row"></template>`;
    // happy-dom drops the rows of tables in templates when parsing, unlike browsers
    const table = document.createElement("table");
    table.innerHTML = `<tbody><tr>
        <td class="table-cell"><a class="backup-uri" href=""></a></td>
        <td class="table-cell backup-date"></td>
        <td class="table-cell backup-size"></td>
        <td class="table-cell"><button type="button" class="button backup-delete" data-modal="deleteFileModal" data-modal-action="">Delete</button></td>
    </tr></tbody>`;
    (document.getElementById("backups-row") as HTMLTemplateElement).content.append(table.querySelector("tr") as HTMLElement);
    initBackups(document.querySelector("[data-view=backups]") as HTMLElement);
}

const button = () => document.querySelector("[data-command=make-backup]") as HTMLButtonElement;

const spinner = () => document.querySelector(".spinner") as HTMLElement;

const backups = () => Array.from(document.querySelectorAll("#backups-table tbody tr"), (row) => row.querySelector("a")?.textContent);

/**
 * Answer the request to make a backup
 */
function respondBackup(filename = "backup.zip", maxFiles = 3) {
    XMLHttpRequestStub.last.respondJson("success", "Backup ready", {
        uri: `/panel/backup/download/${filename}/`,
        filename,
        date: "2024-01-06",
        size: "2 MB",
        deleteUri: `/panel/backup/delete/${filename}/`,
        maxFiles,
    });
}

describe("Backups View", () => {
    test("makes a backup, showing that it is pending", () => {
        makeBackups();
        button().click();
        assert.equal(XMLHttpRequestStub.last.url, "/panel/backup/make/");
        assert.equal(button().disabled, true);
        assert.equal(spinner().className, "spinner");
    });

    test("adds the backup to the table and notifies it", () => {
        makeBackups();
        button().click();
        respondBackup();
        assert.equal(button().disabled, false);
        assert.equal(spinner().className, "spinner spinner-success");
        assert.deepEqual(backups(), ["backup.zip"]);
        const row = document.querySelector("#backups-table tbody tr") as HTMLElement;
        assert.equal(row.querySelector("a")?.getAttribute("href"), "/panel/backup/download/backup.zip/");
        assert.equal(row.querySelector(".backup-date")?.textContent, "2024-01-06");
        assert.equal(row.querySelector(".backup-size")?.textContent, "2 MB");
        assert.equal((row.querySelector(".backup-delete") as HTMLElement).dataset.modalAction, "/panel/backup/delete/backup.zip/");
        assert.equal(document.querySelector(".backup-last-time")?.textContent, "Now");
        assert.equal((document.getElementById("backups-section") as HTMLElement).hidden, false);
        assert.equal(document.querySelector(".notification")?.textContent?.trim(), "Backup ready");
    });

    test("downloads the backup", (t) => {
        t.mock.timers.enable({ apis: ["setTimeout"] });
        const submit = t.mock.method(HTMLFormElement.prototype, "submit", () => undefined);
        makeBackups();
        button().click();
        respondBackup();
        t.mock.timers.tick(1000);
        assert.equal(submit.mock.callCount(), 1);
        const form = submit.mock.calls[0].this as HTMLFormElement;
        assert.equal(form.getAttribute("action"), "/panel/backup/download/backup.zip/");
        assert.equal((form.querySelector("[name=csrf-token]") as HTMLInputElement).value, "token");
    });

    test("reports the failure", () => {
        makeBackups();
        button().click();
        XMLHttpRequestStub.last.respondJson("error", "Cannot make the backup");
        assert.equal(button().disabled, false);
        assert.equal(spinner().className, "spinner spinner-danger");
        assert.deepEqual(backups(), []);
        assert.equal(document.querySelector(".notification-danger")?.textContent?.trim(), "Cannot make the backup");
    });
});
