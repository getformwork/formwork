import "../../environment/dom";
import assert from "node:assert/strict";
import { describe, test } from "node:test";
import { Modal } from "../../../src/ts/services/modal";
import { Modals } from "../../../src/ts/services/modals";

describe("Modals", () => {
    test("collects the modals of the page by id", () => {
        document.body.innerHTML = '<div class="modal" id="fooModal"><div class="modal-container"></div></div><div class="modal" id="barModal"><div class="modal-container"></div></div>';
        const modals = new Modals();
        assert.deepEqual(Object.keys(modals), ["fooModal", "barModal"]);
        assert.ok(modals.fooModal instanceof Modal);
        assert.ok(modals.fooModal.element === document.getElementById("fooModal"));
    });

    test("is empty without modals", () => {
        document.body.innerHTML = "";
        assert.deepEqual(Object.keys(new Modals()), []);
    });
});
