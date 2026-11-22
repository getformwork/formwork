import "../../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { check, exclamation, info } from "../../../../src/ts/components/ui/icons";
import { renderSpinner } from "../../../../src/ts/components/ui/spinner";
import { iconMarkup } from "../../../helpers/panel";

describe("Spinner Component", () => {
    test("shows a pending spinner without an icon", () => {
        const spinner = document.createElement("div");
        spinner.innerHTML = "foo";
        renderSpinner(spinner, "pending");
        assert.equal(spinner.className, "spinner");
        assert.equal(spinner.innerHTML, "");
    });

    test("shows the status with its class and icon", () => {
        const icons = { info, success: check, danger: exclamation };
        for (const [status, icon] of Object.entries(icons) as [keyof typeof icons, string][]) {
            const spinner = document.createElement("div");
            renderSpinner(spinner, status);
            assert.equal(spinner.className, `spinner spinner-${status}`);
            assert.equal(spinner.firstElementChild?.outerHTML, iconMarkup(icon));
        }
    });

    test("replaces the previous status", () => {
        const spinner = document.createElement("div");
        renderSpinner(spinner, "success");
        renderSpinner(spinner, "pending");
        assert.equal(spinner.className, "spinner");
        assert.equal(spinner.innerHTML, "");
    });
});
