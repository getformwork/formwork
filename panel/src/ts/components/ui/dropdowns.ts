import { getOuterHeight, getOuterWidth } from "../../utils/dimensions";
import { throttle } from "../../utils/events";
import { $, $$ } from "../../utils/selectors";

const itemSelector = ".dropdown-item:not([disabled]):not([aria-disabled=true])";

// Stops repositioning the open menu when the window is resized
let stopResizeHandler: (() => void) | undefined;

/**
 * Handles the dropdown menus opened by `.dropdown-button` elements
 *
 * The buttons and the menus follow the ARIA menu button pattern. Buttons created after the page is
 * loaded, like the one in the toolbar of the editor, are set up when they get the focus
 */
export class Dropdowns {
    /**
     * Initialize the buttons of the page and bind the events of all dropdowns
     */
    constructor() {
        $$(".dropdown-button").forEach(initButton);

        document.addEventListener("click", handleClick);
        document.addEventListener("keydown", handleKeydown);
        document.addEventListener("focusin", handleFocusin);
    }
}

/**
 * Toggle the menu of a button, and close the menus when something else is clicked
 *
 * A menu opened with the keyboard gets the focus on its first item, and an item chosen with the
 * keyboard gives the focus back to the button
 */
function handleClick(event: MouseEvent) {
    const target = event.target as HTMLElement;

    const button = target.closest<HTMLElement>(".dropdown-button");
    const dropdown = button ? getDropdown(button) : null;
    const wasVisible = dropdown ? isVisible(dropdown) : false;

    const menu = target.closest<HTMLElement>(".dropdown-menu");
    const hadFocus = menu?.contains(document.activeElement) ?? false;

    const isKeyboard = event.detail === 0;

    closeAll();

    if (button) {
        event.preventDefault();

        if (dropdown && !wasVisible) {
            open(button, dropdown);

            // Some browsers do not focus a button when it is clicked, but the arrows need it
            if (isKeyboard) {
                getItems(dropdown)[0]?.focus();
            } else {
                button.focus();
            }
        }
    } else if (menu && hadFocus && isKeyboard) {
        getButton(menu)?.focus();
    }
}

/**
 * Handle the keyboard on the buttons and on the open menus
 */
function handleKeydown(event: KeyboardEvent) {
    if (!(event.target instanceof HTMLElement)) {
        return;
    }

    const button = event.target.closest<HTMLElement>(".dropdown-button");

    if (button) {
        handleButtonKeydown(event, button);
        return;
    }

    const menu = event.target.closest<HTMLElement>(".dropdown-menu");

    if (menu) {
        handleMenuKeydown(event, menu);
    }
}

/**
 * Close the menus when the focus moves out of them, and set up the buttons created after the page
 * was loaded
 */
function handleFocusin(event: FocusEvent) {
    const target = event.target as HTMLElement;

    if (!target.closest(".dropdown")) {
        closeAll();
    }

    const button = target.closest<HTMLElement>(".dropdown-button");

    if (button) {
        initButton(button);
    }
}

/**
 * Get the menu of a button
 */
function getDropdown(button: HTMLElement): HTMLElement | null {
    return button.dataset.dropdown ? document.getElementById(button.dataset.dropdown) : null;
}

/**
 * Get the button of a menu, if it has one
 */
function getButton(dropdown: HTMLElement): HTMLElement | null {
    return dropdown.id ? $(`.dropdown-button[data-dropdown="${dropdown.id}"]`) : null;
}

/**
 * Return whether a menu is displayed
 */
function isVisible(dropdown: HTMLElement): boolean {
    return getComputedStyle(dropdown).display !== "none";
}

/**
 * Link a button to its menu for assistive technologies
 */
function initButton(button: HTMLElement) {
    const dropdown = getDropdown(button);

    if (dropdown) {
        button.setAttribute("aria-haspopup", "menu");
        button.setAttribute("aria-controls", dropdown.id);
        button.setAttribute("aria-expanded", String(isVisible(dropdown)));
    }
}

/**
 * Make the items of a menu reachable only with the arrow keys, like the ARIA menu pattern requires
 */
function initMenu(menu: HTMLElement) {
    menu.setAttribute("role", "menu");

    $$(".dropdown-item", menu).forEach((item) => {
        item.setAttribute("role", "menuitem");
        item.tabIndex = -1;
    });
}

/**
 * Open a menu, keeping it inside the viewport when the window is resized
 */
function open(button: HTMLElement, dropdown: HTMLElement) {
    // A tooltip of the button would cover the first items of the menu
    $$(".tooltip").forEach((tooltip) => {
        tooltip.remove();
    });

    const resizeHandler = throttle(() => setDropdownPosition(dropdown), 100);

    stopResizeHandler?.();

    initMenu(dropdown);

    dropdown.style.display = "block";
    setDropdownPosition(dropdown);
    window.addEventListener("resize", resizeHandler);

    stopResizeHandler = () => window.removeEventListener("resize", resizeHandler);

    initButton(button);
    button.setAttribute("aria-expanded", "true");
}

/**
 * Close all menus
 */
function closeAll() {
    stopResizeHandler?.();
    stopResizeHandler = undefined;

    $$(".dropdown-menu").forEach((element) => {
        element.style.display = "";
    });
    $$(".dropdown-button[aria-expanded=true]").forEach((button) => {
        button.setAttribute("aria-expanded", "false");
    });
}

/**
 * Get the visible and enabled items of a menu
 */
function getItems(dropdown: HTMLElement): HTMLElement[] {
    return Array.from($$<HTMLElement>(itemSelector, dropdown)).filter((item) => item.getClientRects().length > 0);
}

/**
 * Open and close a menu with the keyboard, and focus its first or last item
 */
function handleButtonKeydown(event: KeyboardEvent, button: HTMLElement) {
    const dropdown = getDropdown(button);

    if (!dropdown) {
        return;
    }

    if (event.key === "Escape" && isVisible(dropdown)) {
        closeAll();
    } else if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();

        if (!isVisible(dropdown)) {
            closeAll();
            open(button, dropdown);
        }

        const items = getItems(dropdown);

        (event.key === "ArrowDown" ? items[0] : items[items.length - 1])?.focus();
    }
}

/**
 * Move the focus between the items of an open menu
 *
 * The arrows move to the next or previous item (wrapping around), Home and End to the first and
 * last one, a printable character to the next item starting with it. Space chooses a link, Escape
 * closes the menu returning the focus to its button, and Tab closes it moving on from the button
 */
function handleMenuKeydown(event: KeyboardEvent, menu: HTMLElement) {
    const button = getButton(menu);

    // Menus without a button (e.g. select inputs) manage their own keyboard interaction
    if (!button || !isVisible(menu)) {
        return;
    }

    const items = getItems(menu);
    const index = items.indexOf(document.activeElement as HTMLElement);

    switch (event.key) {
        case "Escape":
            event.preventDefault();
            closeAll();
            button.focus();
            break;
        case "Tab":
            closeAll();
            button.focus();
            break;
        case "ArrowDown":
            event.preventDefault();
            items[(index + 1) % items.length]?.focus();
            break;
        case "ArrowUp":
            event.preventDefault();
            items[(index <= 0 ? items.length : index) - 1]?.focus();
            break;
        case "Home":
            event.preventDefault();
            items[0]?.focus();
            break;
        case "End":
            event.preventDefault();
            items[items.length - 1]?.focus();
            break;
        case " ":
            // Buttons are chosen natively, links need to be clicked
            if (document.activeElement instanceof HTMLAnchorElement) {
                event.preventDefault();
                document.activeElement.click();
            }
            break;
        default:
            if (event.key.length === 1 && !event.ctrlKey && !event.metaKey && !event.altKey) {
                event.preventDefault();
                focusByText(items, index, event.key);
            }
    }
}

// Characters typed in a menu to move to an item and the timer that forgets them
let typedText = "";
let typedTimer: number | undefined;

/**
 * Focus the next item whose text starts with the characters typed in the last half second
 *
 * Typing the same character repeatedly cycles through the items starting with it
 *
 * @param items Items of the menu
 * @param index Index of the focused item
 * @param key   Typed character
 */
function focusByText(items: HTMLElement[], index: number, key: string) {
    window.clearTimeout(typedTimer);
    typedTimer = window.setTimeout(() => {
        typedText = "";
    }, 500);

    typedText += key.toLowerCase();

    const search = [...typedText].every((character) => character === typedText[0]) ? typedText[0] : typedText;

    const startsWith = (item: HTMLElement) => (item.textContent ?? "").trim().toLowerCase().startsWith(search);

    // A longer text may still match the focused item, a repeated character looks for the next one
    const candidates = search.length > 1 ? [...items.slice(Math.max(index, 0)), ...items.slice(0, Math.max(index, 0))] : [...items.slice(index + 1), ...items.slice(0, index + 1)];

    candidates.find(startsWith)?.focus();
}

/**
 * Position a menu inside the viewport
 */
function setDropdownPosition(dropdown: HTMLElement) {
    dropdown.style.left = "0";
    dropdown.style.right = "";

    const dropdownRect = dropdown.getBoundingClientRect();
    const dropdownTop = dropdownRect.top + window.scrollY;
    const dropdownLeft = dropdownRect.left + window.scrollX;
    const dropdownWidth = getOuterWidth(dropdown);
    const dropdownHeight = getOuterHeight(dropdown);

    const windowWidth = document.documentElement.clientWidth;
    const windowHeight = document.documentElement.clientHeight;

    if (dropdownLeft + dropdownWidth > windowWidth) {
        dropdown.style.left = "auto";
        dropdown.style.right = "0";
    }

    if (dropdownTop < window.scrollY || window.scrollY < dropdownTop + dropdownHeight - windowHeight) {
        window.scrollTo(window.scrollX, dropdownTop + dropdownHeight - windowHeight);
    }
}
