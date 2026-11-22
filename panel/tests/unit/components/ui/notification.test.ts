import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, type TestContext, test } from "node:test";
import { Notification } from "../../../../src/ts/components/ui/notification";

beforeEach((t) => {
    (t as TestContext).mock.timers.enable({ apis: ["setTimeout"] });
});

/**
 * Get the notifications in the document, whose text is preceded by the whitespace of their icon
 */
const notifications = () => Array.from(document.querySelectorAll<HTMLElement>(".notification"));

/**
 * Show a notification and return its element
 */
function show(text: string, type: "info" | "success" | "warning" | "error" = "info", options = {}) {
    const notification = new Notification(text, type, options);
    notification.show();
    return notification.notificationElement;
}

describe("Notification", () => {
    test("shows the notification in the container of the page", () => {
        document.body.innerHTML = '<div class="notification-container" aria-live="polite"></div>';
        const element = show("Hello");
        assert.ok(element.parentElement === document.querySelector(".notification-container"));
        assert.equal(element.textContent?.trim(), "Hello");
        assert.equal(element.tabIndex, 0);
    });

    test("creates the container if missing", () => {
        show("Hello");
        assert.equal(document.querySelectorAll(".notification-container").length, 1);
    });

    test("styles the notification by type, with an icon", () => {
        for (const [type, className] of [
            ["info", "notification-info"],
            ["success", "notification-success"],
            ["warning", "notification-warning"],
            ["error", "notification-danger"],
        ] as const) {
            const element = show("Hello", type);
            assert.equal(element.classList.contains(className), true, type);
            assert.ok(element.querySelector("svg") !== null, type);
        }
    });

    test("announces errors and warnings as alerts", () => {
        assert.equal(show("Hello", "error").getAttribute("role"), "alert");
        assert.equal(show("Hello", "warning").getAttribute("role"), "alert");
        assert.equal(show("Hello", "success").hasAttribute("role"), false);
    });

    test("shows the newest notification on top", () => {
        show("first");
        show("second");
        assert.deepEqual(
            notifications().map((element) => element.textContent?.trim()),
            ["second", "first"],
        );
    });

    test("shows the newest notification at the bottom", () => {
        show("first", "info", { newestOnTop: false });
        show("second", "info", { newestOnTop: false });
        assert.deepEqual(
            notifications().map((element) => element.textContent?.trim()),
            ["first", "second"],
        );
    });

    test("removes the notification after its interval, fading it out", (t) => {
        const element = show("Hello");
        t.mock.timers.tick(4999);
        assert.equal(element.classList.contains("fadeout"), false);
        t.mock.timers.tick(1);
        assert.equal(element.classList.contains("fadeout"), true);
        assert.equal(element.isConnected, true);
        t.mock.timers.tick(300);
        assert.equal(element.isConnected, false);
    });

    test("removes the notification when clicked", (t) => {
        const element = show("Hello");
        element.click();
        t.mock.timers.tick(300);
        assert.equal(element.isConnected, false);
    });

    test("removes the notification with Escape, Enter and Space", (t) => {
        for (const key of ["Escape", "Enter", " "]) {
            const element = show("Hello");
            const event = new KeyboardEvent("keydown", { key, cancelable: true });
            element.dispatchEvent(event);
            assert.equal(event.defaultPrevented, true, key);
            t.mock.timers.tick(300);
            assert.equal(element.isConnected, false, key);
        }
    });

    test("keeps the notification while hovered, removing it shortly after", (t) => {
        const element = show("Hello");
        element.dispatchEvent(new MouseEvent("mouseenter"));
        t.mock.timers.tick(10000);
        assert.equal(element.classList.contains("fadeout"), false);
        element.dispatchEvent(new MouseEvent("mouseleave"));
        t.mock.timers.tick(1000);
        assert.equal(element.classList.contains("fadeout"), true);
    });

    test("keeps the notification while focused", (t) => {
        const element = show("Hello");
        element.focus();
        t.mock.timers.tick(10000);
        assert.equal(element.classList.contains("fadeout"), false);
        element.blur();
        t.mock.timers.tick(1000);
        assert.equal(element.classList.contains("fadeout"), true);
    });
});
