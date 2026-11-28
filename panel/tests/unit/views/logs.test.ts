import "../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { initLogs } from "../../../src/ts/views/logs";

/**
 * Add the logs view as the panel renders it and initialize it
 */
function makeView(messages: string[] = ["Page saved", "Cache cleared", "Failed to save <b>file</b>"]) {
    const rows = messages.map((message) => `<tr><td class="table-cell log-message">${message.replace(/</g, "&lt;")}</td></tr>`).join("");
    document.body.innerHTML = `<div data-view="logs"><input class="logs-search" type="search"><table><tbody>${rows}</tbody></table></div>`;
    const view = document.querySelector("[data-view=logs]") as HTMLElement;
    initLogs(view);
    return {
        input: view.querySelector(".logs-search") as HTMLInputElement,
        rows: Array.from(view.querySelectorAll("tbody tr")) as HTMLElement[],
    };
}

/**
 * Type a text in the search field
 */
function search(input: HTMLInputElement, text: string) {
    input.value = text;
    input.dispatchEvent(new Event("search"));
}

beforeEach(() => {
    document.body.innerHTML = "";
});

describe("Logs View", () => {
    test("shows all the entries without a search", () => {
        const { rows } = makeView();
        assert.deepEqual(
            rows.map((row) => row.style.display),
            ["", "", ""],
        );
    });

    test("hides the entries not matching the search", () => {
        const { input, rows } = makeView();
        search(input, "cache");
        assert.deepEqual(
            rows.map((row) => row.style.display),
            ["none", "", "none"],
        );
    });

    test("matches inside words and ignores case", () => {
        const { input, rows } = makeView();
        search(input, "SAV");
        assert.deepEqual(
            rows.map((row) => row.style.display),
            ["", "none", ""],
        );
    });

    test("highlights the matches", () => {
        const { input, rows } = makeView();
        search(input, "cache");
        assert.equal(rows[1].querySelector(".log-message")?.innerHTML, "<mark>Cache</mark> cleared");
    });

    test("escapes the messages", () => {
        const { input, rows } = makeView();
        search(input, "file");
        assert.equal(rows[2].querySelector(".log-message")?.innerHTML, "Failed to save &lt;b&gt;<mark>file</mark>&lt;/b&gt;");
        assert.equal(rows[2].querySelector("b"), null);
    });

    test("removes filter and highlights when the search is cleared", () => {
        const { input, rows } = makeView();
        search(input, "cache");
        search(input, "");
        assert.deepEqual(
            rows.map((row) => row.style.display),
            ["", "", ""],
        );
        assert.equal(rows[1].querySelector("mark"), null);
    });

    test("does nothing without the search field", () => {
        document.body.innerHTML = '<div data-view="logs"></div>';
        assert.doesNotThrow(() => initLogs(document.querySelector("[data-view=logs]") as HTMLElement));
    });
});
