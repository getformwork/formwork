/**
 * Notify that the value of an element has changed
 *
 * The native elements do not emit events when their value is changed by a script
 */
export function dispatchChange(element: HTMLElement) {
    element.dispatchEvent(new Event("input", { bubbles: true }));
    element.dispatchEvent(new Event("change", { bubbles: true }));
}

/**
 * Represents the native elements holding a value
 */
type NativeInputElement = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * Provides the base of the inputs of a form
 *
 * The input enhances the native element rendered by PHP, which is sent with the form
 */
export abstract class InputBase<Element extends HTMLElement & { name: string }> {
    /**
     * Native element of the input
     */
    readonly element: Element;

    constructor(element: Element) {
        this.element = element;
    }

    /**
     * Get the name of the input
     */
    get name(): string {
        return this.element.name;
    }

    /**
     * Set the name of the input
     */
    set name(value: string) {
        this.element.name = value;
    }

    /**
     * Get the value sent with the form
     */
    abstract get value(): string;

    /**
     * Notify that the value has changed
     */
    dispatchChange() {
        dispatchChange(this.element);
    }
}

/**
 * Represents an input without enhancements
 */
export class Input<Element extends NativeInputElement = NativeInputElement> extends InputBase<Element> {
    /**
     * Get the value of the native element
     */
    get value(): string {
        return this.element.value;
    }

    /**
     * Set the value of the native element
     */
    set value(value: string) {
        this.element.value = value;
    }
}
