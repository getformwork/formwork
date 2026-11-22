import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { Notifications } from "../../../../src/ts/components/ui/notifications";
import { shownNotifications } from "../../../helpers/panel";

beforeEach((t) => {
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
});

/**
 * Add the notifications rendered by PHP to the document
 */
function addNotifications(...notifications: Record<string, unknown>[]) {
    document.head.innerHTML = notifications.map((notification) => `<meta name="notification" content='${JSON.stringify(notification)}'>`).join("");
    document.body.innerHTML = '<div class="notification-container" role="status" aria-live="polite"></div>';
}

describe("Notifications", () => {
    test("shows the notifications of the page one after the other, newest on top", (t) => {
        addNotifications({ text: "Page saved", type: "success", interval: 5000 }, { text: "Cannot save the file", type: "error", interval: 10000 });
        new Notifications();
        assert.equal(document.querySelectorAll("meta[name=notification]").length, 0);
        t.mock.timers.tick(0);
        assert.deepEqual(shownNotifications(), ["success: Page saved"]);
        t.mock.timers.tick(500);
        assert.deepEqual(shownNotifications(), ["danger: Cannot save the file", "success: Page saved"]);
    });

    test("removes the notifications after their interval", (t) => {
        addNotifications({ text: "Page saved", type: "success", interval: 1000 });
        new Notifications();
        t.mock.timers.tick(0);
        t.mock.timers.tick(1000);
        assert.equal(document.querySelector(".notification")?.classList.contains("fadeout"), true);
    });

    test("shows the text of the notifications as HTML, as rendered by PHP", (t) => {
        addNotifications({ text: "Page <em>saved</em>", type: "info", interval: 5000 });
        new Notifications();
        t.mock.timers.tick(0);
        assert.equal(document.querySelector(".notification em")?.textContent, "saved");
    });

    test("does nothing without notifications", (t) => {
        addNotifications();
        new Notifications();
        t.mock.timers.tick(1000);
        assert.deepEqual(shownNotifications(), []);
    });
});
