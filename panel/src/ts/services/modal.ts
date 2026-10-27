import { $, $$ } from "../utils/selectors";
import { Form } from "./form";

/**
 * Holds the options to open a modal
 */
interface ModalShowOptions {
    action?: string;
    triggerElement?: HTMLElement;
}

/**
 * Holds the options to close a modal
 */
interface ModalHideOptions {
    triggerElement?: HTMLElement;
}

type ModalCallback = (modal: Modal, triggerElement?: HTMLElement) => void;

type ModalState = "open" | "closed";

const focusableSelector = ["a[href]", "button:not([disabled])", "input:not([disabled]):not([type=hidden])", "select:not([disabled])", "textarea:not([disabled])", "[contenteditable=true]", "[tabindex]:not([tabindex='-1'])"].join(",");

/**
 * Represents a dialog rendered by PHP, opened by the elements with `data-modal` set to its id
 *
 * Elements with `data-command` trigger the commands registered with `onCommand()`. While the modal
 * is the topmost one it keeps the focus inside and gives it back to the opener when closed
 */
export class Modal {
    private static readonly openModals: Modal[] = [];

    readonly element: HTMLElement;

    readonly form: Form | null;

    readonly data: { [key: string]: unknown } = {};

    private readonly callbacks: { [name: string]: ModalCallback } = {};

    private state: ModalState = "closed";

    private returnFocusElement: HTMLElement | null = null;

    constructor(element: HTMLElement) {
        this.element = element;

        const formElement = $<HTMLFormElement>("form", this.element);

        this.form = formElement
            ? new Form(formElement, {
                  preventUnloadOnChanges: false,
              })
            : null;

        this.registerEvents();

        if (element.classList.contains("open")) {
            this.open();
        }
    }

    /**
     * Return whether the modal is open
     */
    get isOpen() {
        return this.state === "open";
    }

    /**
     * Return whether the modal is closed
     */
    get isClosed() {
        return this.state === "closed";
    }

    /**
     * Set the text of the modal
     */
    setMessage(message: string) {
        const messageElement = $(".modal-text", this.element);
        if (messageElement) {
            messageElement.textContent = message;
        }
    }

    /**
     * Open the modal
     *
     * @param options Action of the form and element that opened the modal
     */
    open(options: ModalShowOptions = {}) {
        this.dispatchCallback("before-open", options.triggerElement);

        this.element.role = "dialog";
        this.element.ariaModal = "true";
        this.element.classList.add("open");

        if (options.action) {
            if (this.form) {
                this.form.element.action = options.action;
            }
        }

        if (this.isClosed) {
            this.returnFocusElement = this.resolveReturnFocusElement(options.triggerElement ?? (document.activeElement as HTMLElement | null));
        }

        (document.activeElement as HTMLElement | null)?.blur(); // Don't retain focus on any element

        this.getFirstFocusableElement(this.element)?.focus();

        $$(".tooltip").forEach((tooltip) => {
            tooltip.parentNode?.removeChild(tooltip);
        });

        this.createBackdrop();

        if (this.isClosed) {
            Modal.openModals.push(this);
        }

        this.state = "open";

        this.dispatchCallback("open", options.triggerElement);
    }

    /**
     * Close the modal
     *
     * @param options Element that closed the modal
     */
    close(options: ModalHideOptions = {}) {
        this.dispatchCallback("before-close", options.triggerElement);

        this.element.classList.remove("open");
        this.element.role = null;
        this.element.ariaModal = null;

        this.removeBackdrop();

        this.state = "closed";

        const index = Modal.openModals.indexOf(this);

        if (index !== -1) {
            Modal.openModals.splice(index, 1);
        }

        this.dispatchCallback("close", options.triggerElement);

        this.restoreFocus();
    }

    /**
     * Set the function called before the modal is opened
     */
    onBeforeOpen(callback: ModalCallback) {
        this.callbacks["before-open"] = callback;
    }

    /**
     * Set the function called when the modal is opened
     */
    onOpen(callback: ModalCallback) {
        this.callbacks.open = callback;
    }

    /**
     * Set the function called before the modal is closed
     */
    onBeforeClose(callback: ModalCallback) {
        this.callbacks["before-close"] = callback;
    }

    /**
     * Set the function called when the modal is closed
     */
    onClose(callback: ModalCallback) {
        this.callbacks.close = callback;
    }

    /**
     * Set the function called when the element with the `data-command` of the given name is clicked
     *
     * @param command  Value of the `data-command` attribute
     * @param callback Function called with the modal and the element that triggered it
     */
    onCommand(command: string, callback: ModalCallback) {
        this.callbacks[`command-${command}`] = callback;
    }

    /**
     * Call the function of a command
     *
     * @param command        Name of the command
     * @param triggerElement Element passed to the function
     */
    triggerCommand(command: string, triggerElement?: HTMLElement) {
        this.dispatchCallback(`command-${command}`, triggerElement);
    }

    /**
     * Add the backdrop if missing
     */
    private createBackdrop() {
        if (!$(".modal-backdrop")) {
            const backdrop = document.createElement("div");
            backdrop.className = "modal-backdrop";
            document.body.appendChild(backdrop);
        }
    }

    /**
     * Remove the backdrop
     */
    private removeBackdrop() {
        const backdrop = $(".modal-backdrop");
        if (backdrop?.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
        }
    }

    /**
     * Call the function of a modal event
     *
     * @param name           Name of the event
     * @param triggerElement Element passed to the function
     */
    private dispatchCallback(name: string, triggerElement?: HTMLElement) {
        const callback = this.callbacks[name];
        if (callback) {
            callback(this, triggerElement);
        }
    }

    /**
     * Bind the events that open and close the modal, trigger its commands and keep the focus inside it
     */
    private registerEvents() {
        this.bindTriggers();
        this.bindKeyboard();
        this.bindFocus();
        this.bindBackdropClick();
    }

    /**
     * Open the modal with the elements pointing to it, run its commands and close it with the
     * dismiss button
     */
    private bindTriggers() {
        document.addEventListener("click", (event) => {
            const target = (event.target as HTMLElement).closest(`[data-modal="${this.element.id}"]`) as HTMLElement;
            if (target) {
                this.open({ action: target.dataset.modalAction, triggerElement: target });
            }
        });

        $$("[data-command]", this.element).forEach((commandButton) => {
            commandButton.addEventListener("click", () => {
                this.triggerCommand(commandButton.dataset.command as string, commandButton);
            });
        });

        const dismissButton = $("[data-dismiss]", this.element);
        dismissButton?.addEventListener("click", () => this.close({ triggerElement: dismissButton }));
    }

    /**
     * Close the modal with Escape and keep Tab inside it
     */
    private bindKeyboard() {
        document.addEventListener("keyup", (event) => {
            if (event.key === "Escape" && this.isOpen) {
                this.close();
            }
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Tab" && this.isTopmost) {
                this.trapFocus(event);
            }
        });
    }

    /**
     * Bring the focus back inside the modal when it moves out or the window gets it back
     */
    private bindFocus() {
        document.addEventListener("focusin", (event) => {
            if (this.isTopmost && !this.element.contains(event.target as Node)) {
                this.getFirstFocusableElement(this.element).focus();
            }
        });

        window.addEventListener("focus", () => {
            if (this.element.classList.contains("open")) {
                this.getFirstFocusableElement(this.element).focus();
            }
        });
    }

    /**
     * Close the modal when the backdrop is clicked, but not when a click that started inside the
     * modal ends on the backdrop
     */
    private bindBackdropClick() {
        let mousedownCaptured = false;

        this.element.addEventListener("mousedown", (event) => {
            mousedownCaptured = event.target === this.element;
        });

        this.element.addEventListener("mouseup", (event) => {
            if (mousedownCaptured && event.target === this.element) {
                this.close();
            }
            mousedownCaptured = false;
        });
    }

    /**
     * Return whether the modal is the last one opened
     */
    private get isTopmost() {
        return this.isOpen && Modal.openModals[Modal.openModals.length - 1] === this;
    }

    /**
     * Get the visible elements of the modal that can receive the focus
     */
    private getFocusableElements(): HTMLElement[] {
        return Array.from($$<HTMLElement>(focusableSelector, this.element)).filter((element) => element.getClientRects().length > 0);
    }

    /**
     * Keep the focus inside the modal when Tab is pressed
     */
    private trapFocus(event: KeyboardEvent) {
        const focusable = this.getFocusableElements();

        if (focusable.length === 0) {
            event.preventDefault();
            return;
        }

        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        const active = document.activeElement;

        if (event.shiftKey && (active === first || !this.element.contains(active))) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && (active === last || !this.element.contains(active))) {
            event.preventDefault();
            first.focus();
        }
    }

    /**
     * Get the element that receives the focus when the modal is closed
     *
     * If the modal was opened from a dropdown menu it is the button of the menu
     */
    private resolveReturnFocusElement(element: HTMLElement | null): HTMLElement | null {
        // When the trigger lives in a dropdown menu, return focus to the button that opens that menu
        const menu = element?.closest<HTMLElement>(".dropdown-menu");

        if (menu?.id) {
            return $<HTMLElement>(`[data-dropdown="${menu.id}"]`) ?? element;
        }

        return element;
    }

    /**
     * Give the focus back to the element that opened the modal
     */
    private restoreFocus() {
        const element = this.returnFocusElement;

        this.returnFocusElement = null;

        if (element?.isConnected && element.getClientRects().length > 0) {
            element.focus();
        }
    }

    /**
     * Get the element to focus when the modal is opened
     */
    private getFirstFocusableElement(parent: HTMLElement = document.body): HTMLElement {
        return parent.querySelector("[autofocus], button, .button, input:not([type=hidden]), select, textarea") || parent;
    }
}
