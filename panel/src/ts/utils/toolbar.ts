import { getNextIndex } from "./keyboard";

/**
 * Make an element containing buttons an ARIA toolbar
 *
 * The toolbar is a single tab stop and its buttons are reached with the arrow keys
 *
 * @param labelledBy Id of the element labelling the toolbar
 */
export function initToolbar(toolbar: HTMLElement, labelledBy?: string) {
    toolbar.setAttribute("role", "toolbar");
    toolbar.setAttribute("aria-orientation", "horizontal");

    if (labelledBy) {
        toolbar.setAttribute("aria-labelledby", labelledBy);
    }

    let current: HTMLElement | null = null;

    // Until a button has been focused, the first enabled button is the tab stop (buttons are added after the toolbar is created)
    let chosen = false;

    const getButtons = () => Array.from(toolbar.querySelectorAll<HTMLButtonElement>("button")).filter((button) => !button.closest(".dropdown-menu"));

    const getEnabledButtons = () => getButtons().filter((button) => !button.disabled);

    const sync = () => {
        const enabled = getEnabledButtons();

        if (!chosen || !current || !enabled.includes(current as HTMLButtonElement)) {
            current = enabled[0] ?? null;
        }

        getButtons().forEach((button) => {
            button.tabIndex = button === current ? 0 : -1;
        });
    };

    toolbar.addEventListener("focusin", (event) => {
        const button = (event.target as HTMLElement).closest<HTMLButtonElement>("button");

        if (button && getEnabledButtons().includes(button)) {
            current = button;
            chosen = true;
            sync();
        }
    });

    toolbar.addEventListener("keydown", (event) => {
        const buttons = getEnabledButtons();
        const index = buttons.indexOf((event.target as HTMLElement).closest<HTMLButtonElement>("button") as HTMLButtonElement);

        if (index === -1) {
            return;
        }

        const nextIndex = getNextIndex(event.key, index, buttons.length - 1);

        if (nextIndex !== undefined) {
            event.preventDefault();
            buttons[nextIndex].focus();
        }
    });

    // Buttons are added and enabled or disabled while the editor is running
    new MutationObserver(sync).observe(toolbar, { subtree: true, childList: true, attributes: true, attributeFilter: ["disabled"] });

    sync();
}
