import { getOuterHeight, getOuterWidth } from "../utils/dimensions";
import { throttle } from "../utils/events";
import { $, $$ } from "../utils/selectors";

const itemSelector = ".dropdown-item:not([disabled]):not([aria-disabled=true])";

export class Dropdowns {
    constructor() {
        if ($(".dropdown")) {
            $$(".dropdown-button").forEach(initButton);

            document.addEventListener("click", (event) => {
                const button = (event.target as HTMLElement).closest<HTMLElement>(".dropdown-button");
                const dropdown = button ? getDropdown(button) : null;
                const wasVisible = dropdown ? isVisible(dropdown) : false;

                closeAll();

                if (button) {
                    event.preventDefault();

                    if (dropdown && !wasVisible) {
                        open(button, dropdown);
                    }
                }
            });

            document.addEventListener("keydown", (event) => {
                const target = event.target as HTMLElement;

                const button = target.closest<HTMLElement>(".dropdown-button");

                if (button) {
                    handleButtonKeydown(event, button);
                    return;
                }

                const menu = target.closest<HTMLElement>(".dropdown-menu");

                if (menu) {
                    handleMenuKeydown(event, menu);
                }
            });

            document.addEventListener("focusin", (event) => {
                const target = event.target as HTMLElement;

                if (!target.closest(".dropdown")) {
                    closeAll();
                }
            });
        }
    }
}

function getDropdown(button: HTMLElement): HTMLElement | null {
    return button.dataset.dropdown ? document.getElementById(button.dataset.dropdown) : null;
}

function getButton(dropdown: HTMLElement): HTMLElement | null {
    return dropdown.id ? $(`.dropdown-button[data-dropdown="${dropdown.id}"]`) : null;
}

function isVisible(dropdown: HTMLElement): boolean {
    return getComputedStyle(dropdown).display !== "none";
}

function initButton(button: HTMLElement) {
    const dropdown = getDropdown(button);

    if (dropdown) {
        button.setAttribute("aria-controls", dropdown.id);
        button.setAttribute("aria-expanded", String(isVisible(dropdown)));
    }
}

function open(button: HTMLElement, dropdown: HTMLElement) {
    const resizeHandler = throttle(() => setDropdownPosition(dropdown), 100);

    dropdown.style.display = "block";
    setDropdownPosition(dropdown);
    window.addEventListener("resize", resizeHandler);

    initButton(button);
    button.setAttribute("aria-expanded", "true");
}

function closeAll() {
    $$(".dropdown-menu").forEach((element) => (element.style.display = ""));
    $$(".dropdown-button[aria-expanded=true]").forEach((button) => button.setAttribute("aria-expanded", "false"));
}

function getItems(dropdown: HTMLElement): HTMLElement[] {
    return Array.from($$<HTMLElement>(itemSelector, dropdown)).filter((item) => item.getClientRects().length > 0);
}

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
    }
}

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
