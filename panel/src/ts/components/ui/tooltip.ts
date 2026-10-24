import { clamp } from "../../utils/math";

/**
 * Holds the options of a tooltip
 */
export interface TooltipOptions {
    container: HTMLElement;
    referenceElement: HTMLElement;
    position: "top" | "right" | "bottom" | "left" | "center" | { x: number; y: number };
    offset: {
        x: number;
        y: number;
    };
    delay: number;
    timeout: number | null;
    removeOnMouseout: boolean;
    removeOnMousedown: boolean;
    zIndex: number | null;
}

/**
 * Shows a text next to an element
 */
export class Tooltip {
    /**
     * Text of the tooltip, as HTML
     */
    text: string;
    /**
     * Options of the tooltip
     */
    options: TooltipOptions;
    /**
     * Timer showing the tooltip after its delay
     */
    delayTimer?: number;
    /**
     * Timer removing the tooltip after its timeout
     */
    timeoutTimer?: number;
    /**
     * Element of the tooltip
     */
    element: HTMLElement;

    /**
     * Return whether the tooltip has been removed
     */
    get removed() {
        return this.element === undefined || !this.options.container.contains(this.element);
    }

    /**
     * Create a tooltip, which is shown with `show()`
     */
    constructor(text: string, options: Partial<TooltipOptions> = {}) {
        const defaults = {
            container: document.body,
            referenceElement: document.body,
            position: "top",
            offset: {
                x: 0,
                y: 0,
            },
            delay: 500,
            timeout: null,
            removeOnMouseout: true,
            removeOnMousedown: true,
            zIndex: null,
        };

        this.text = text;
        this.options = Object.assign({}, defaults, options);

        this.element = document.createElement("div");
        this.element.className = "tooltip";
        this.element.role = "tooltip";
        this.element.innerHTML = this.text;
    }

    /**
     * Show the tooltip after its delay
     */
    show() {
        const { delay, referenceElement } = this.options;

        this.delayTimer = window.setTimeout(() => this.place(), delay);

        if (referenceElement.tagName.toLowerCase() === "button" || referenceElement.classList.contains("button")) {
            referenceElement.addEventListener("mousedown", () => this.remove());
            referenceElement.addEventListener("blur", () => this.remove());
        }

        if (this.options.removeOnMouseout) {
            referenceElement.addEventListener("mouseout", (event: MouseEvent) => {
                if (event.relatedTarget !== this.element) {
                    this.remove();
                }
            });
        }

        if (this.options.removeOnMousedown) {
            referenceElement.addEventListener("mousedown", () => this.remove());
        }
    }

    /**
     * Add the tooltip to its container and set its position and lifetime
     *
     * Nothing is shown for a reference element that has been removed, or whose menu is open and
     * would be covered
     */
    private place() {
        const { container, referenceElement, zIndex, timeout, removeOnMouseout } = this.options;

        if (!referenceElement.isConnected || referenceElement.getAttribute("aria-expanded") === "true") {
            return;
        }

        container.appendChild(this.element);

        const position = this.getPosition();
        this.element.style.top = `${position.top}px`;
        this.element.style.left = `${position.left}px`;

        if (zIndex !== null) {
            this.element.style.zIndex = `${zIndex}`;
        }

        if (timeout !== null) {
            this.timeoutTimer = window.setTimeout(() => this.remove(), timeout);
        }

        if (removeOnMouseout) {
            this.element.addEventListener("mouseout", () => this.remove());
        }
    }

    /**
     * Get the position of the tooltip, kept inside the window
     */
    private getPosition() {
        const { position, offset } = this.options;

        const { top, left } = typeof position === "string" ? this.getPositionAroundReference(position) : { top: position.y + offset.y, left: position.x + offset.x };

        const minTop = window.scrollY + 4;
        const minLeft = window.scrollX + 4;
        const maxTop = window.innerHeight + window.scrollY - this.element.offsetHeight - 20;
        const maxLeft = window.innerWidth + window.scrollX - this.element.offsetWidth - 4;

        return {
            top: clamp(top, minTop, maxTop),
            left: clamp(left, minLeft, maxLeft),
        };
    }

    /**
     * Get the position of the tooltip on a side of the reference element, or at its center
     */
    private getPositionAroundReference(side: "top" | "right" | "bottom" | "left" | "center") {
        const { referenceElement, offset } = this.options;
        const { offsetWidth: width, offsetHeight: height } = this.element;

        const rect = referenceElement.getBoundingClientRect();

        const top = rect.top + window.scrollY;
        const left = rect.left + window.scrollX;

        const centeredLeft = left + (rect.width - width) / 2 + offset.x;
        const centeredTop = top + (rect.height - height) / 2 + offset.y;

        switch (side) {
            case "right":
                return { top: Math.round(centeredTop), left: Math.round(left + referenceElement.offsetWidth + offset.x) };
            case "bottom":
                return { top: Math.round(top + referenceElement.offsetHeight + offset.y), left: Math.round(centeredLeft) };
            case "left":
                return { top: Math.round(centeredTop), left: Math.round(left - width + offset.x) };
            case "center":
                return { top: Math.round(centeredTop), left: Math.round(centeredLeft) };
            default:
                return { top: Math.round(top - height + offset.y), left: Math.round(centeredLeft) };
        }
    }

    /**
     * Remove the tooltip
     */
    remove() {
        clearTimeout(this.delayTimer);
        clearTimeout(this.timeoutTimer);
        this.element?.remove();
    }
}
