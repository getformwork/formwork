import { getNextIndex } from "../../utils/keyboard";
import * as icons from "../ui/icons";

/**
 * Handles an ARIA toolbar, to which the buttons are added after it is created
 *
 * The toolbar is a single tab stop and its buttons are reached with the arrow keys
 */
export class Toolbar {
    /**
     * Element of the toolbar
     */
    readonly element: HTMLElement;

    /**
     * Button that is the tab stop
     */
    private current: HTMLButtonElement | null = null;

    /**
     * Whether a button has been focused, until then the first enabled button is the tab stop
     */
    private chosen = false;

    /**
     * @param element    Element of the toolbar
     * @param labelledBy Id of the element labelling the toolbar
     */
    constructor(element: HTMLElement, labelledBy?: string) {
        this.element = element;

        element.setAttribute("role", "toolbar");
        element.setAttribute("aria-orientation", "horizontal");

        if (labelledBy) {
            element.setAttribute("aria-labelledby", labelledBy);
        }

        element.addEventListener("focusin", (event) => {
            const button = (event.target as HTMLElement).closest<HTMLButtonElement>("button");

            if (button && this.getEnabledButtons().includes(button)) {
                this.current = button;
                this.chosen = true;
                this.sync();
            }
        });

        element.addEventListener("keydown", (event) => {
            const buttons = this.getEnabledButtons();
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

        // The buttons are enabled or disabled while the editor is running
        new MutationObserver(() => this.sync()).observe(element, { subtree: true, attributes: true, attributeFilter: ["disabled"] });

        this.sync();
    }

    /**
     * Create a button with an icon, to add to the toolbar
     *
     * @param icon  Name of the icon
     * @param title Title and label of the button
     */
    createButton(icon: keyof typeof icons, title: string) {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "button toolbar-button";
        button.dataset.tooltip = title;
        button.ariaLabel = title;

        // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
        button.innerHTML = icons[icon] || "";

        return button;
    }

    /**
     * Add a button or a group of buttons
     *
     * @param position Where to add it, at the end by default
     */
    add(element: HTMLElement, position: "afterbegin" | "beforeend" = "beforeend") {
        this.element.insertAdjacentElement(position, element);
        this.sync();
    }

    /**
     * Remove a button or a group of buttons
     */
    remove(element: HTMLElement) {
        element.remove();
        this.sync();
    }

    /**
     * Get the buttons of the toolbar, leaving out the items of its menus
     */
    private getButtons() {
        return Array.from(this.element.querySelectorAll<HTMLButtonElement>("button")).filter((button) => !button.closest(".dropdown-menu"));
    }

    /**
     * Get the enabled buttons of the toolbar
     */
    private getEnabledButtons() {
        return this.getButtons().filter((button) => !button.disabled);
    }

    /**
     * Make a single enabled button the tab stop, keeping the focused one if possible
     */
    private sync() {
        const enabled = this.getEnabledButtons();

        if (!this.chosen || !this.current || !enabled.includes(this.current)) {
            this.current = enabled[0] ?? null;
        }

        this.getButtons().forEach((button) => {
            button.tabIndex = button === this.current ? 0 : -1;
        });
    }
}
