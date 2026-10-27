import { debounce } from "../../utils/events";
import { $, $$ } from "../../utils/selectors";
import { Tooltip, type TooltipOptions } from "./tooltip";

/**
 * Handles the tooltips of the page
 *
 * They are shown for the elements with `data-tooltip` (the `title` attributes are converted), for
 * truncated texts and for focused buttons
 */
export class Tooltips {
    /**
     * Tooltip currently shown
     */
    tooltip?: Tooltip;
    /**
     * Element of the tooltip currently shown
     */
    element?: HTMLElement;

    /**
     * Convert the titles and listen to the pointer and focus events
     */
    constructor() {
        $$("[title]", document.body).forEach((element) => {
            element.dataset.tooltip = element.title;
            element.removeAttribute("title");
        });

        document.addEventListener(
            "mousemove",
            debounce((event: MouseEvent) => this.handleMousemove(event), 500),
        );

        document.addEventListener("focusin", (event) => this.handleFocusin(event));
    }

    /**
     * Show the tooltip of the element under the pointer and the one of its truncated text
     */
    private handleMousemove(event: MouseEvent) {
        const target = event.target as HTMLElement;

        // The event is debounced, its target may have been removed in the meantime
        if (!target.isConnected) {
            return;
        }

        const pointer = {
            position: { x: event.pageX, y: event.pageY },
            offset: { x: 0, y: 16 },
        };

        const element = target.closest<HTMLElement>("[data-tooltip]");

        if (element) {
            // Do not cover an open menu with the tooltip of the button that opened it
            if (element.getAttribute("aria-expanded") === "true") {
                this.tooltip?.remove();
                return;
            }

            if (this.isShownFor(element)) {
                return;
            }

            this.track(element);

            const openModal = $(".modal.open");

            if (openModal && !openModal.contains(element)) {
                return;
            }

            this.display(element, element.dataset.tooltip as string, pointer);
        }

        const truncableElement = target.closest<HTMLElement>(".truncate");

        if (truncableElement && truncableElement.offsetWidth < truncableElement.scrollWidth) {
            if (this.isShownFor(truncableElement)) {
                return;
            }

            this.track(truncableElement);

            this.display(truncableElement, (truncableElement.textContent ?? "").trim(), pointer);
        }
    }

    /**
     * Show immediately the tooltip of a button that gets the focus
     */
    private handleFocusin(event: FocusEvent) {
        const target = event.target as HTMLElement;

        const element = target.closest<HTMLElement>("[data-tooltip]");

        if (!element || !(element.tagName.toLowerCase() === "button" || element.classList.contains("button"))) {
            return;
        }

        if (this.element === element || element.getAttribute("aria-expanded") === "true") {
            return;
        }

        this.track(element);

        this.display(element, element.dataset.tooltip as string, {
            position: "bottom",
            offset: { x: 0, y: 4 },
        });
    }

    /**
     * Return whether the tooltip shown belongs to an element
     */
    private isShownFor(element: HTMLElement) {
        return this.element === element && !this.tooltip?.removed;
    }

    /**
     * Make an element the owner of the tooltip, removing the previous one
     */
    private track(element: HTMLElement) {
        this.element = element;
        this.tooltip?.remove();
    }

    /**
     * Show a tooltip for an element
     *
     * @param element Element the tooltip refers to
     * @param text    Text of the tooltip
     * @param options Position and offset of the tooltip
     */
    private display(element: HTMLElement, text: string, options: Pick<TooltipOptions, "position" | "offset">) {
        this.tooltip = new Tooltip(text, { referenceElement: element, delay: 0, ...options });
        this.tooltip.show();
    }
}
