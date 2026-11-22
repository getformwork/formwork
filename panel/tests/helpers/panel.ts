import { app } from "../../src/ts/services/app";
import { Modal } from "../../src/ts/services/modal";
import { Translation } from "../../src/ts/services/translation";

/**
 * Use the given translation in the panel, English without strings by default
 *
 * The configuration and the service are both replaced, as the service reads the configuration when made
 */
export function setTranslation(data: Record<string, string | string[]> = {}, code = "en") {
    app.config.translation = { code, data };
    app.translation = new Translation(app);
}

/**
 * Forget the open modals, which are shared by the instances, also of the windows of the previous tests
 */
export function resetModals() {
    (Modal as unknown as { openModals: Modal[] }).openModals.length = 0;
}

/**
 * Get the markup of the element of an icon as the document serializes it, to tell the icons apart
 */
export function iconMarkup(svg: string) {
    const element = document.createElement("div");
    element.innerHTML = svg;
    return element.firstElementChild?.outerHTML;
}

/**
 * Get the notifications shown, each as its type followed by its text
 */
export function shownNotifications() {
    return Array.from(document.querySelectorAll(".notification"), (notification) => `${notification.className.replace("notification notification-", "")}: ${notification.textContent?.trim()}`);
}

/**
 * Dispatch a `beforeunload` event and return whether leaving the page was prevented
 */
export function leavePage() {
    const event = new Event("beforeunload", { cancelable: true });
    window.dispatchEvent(event);
    return event.defaultPrevented;
}
