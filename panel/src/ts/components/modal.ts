import { $, $$ } from "../utils/selectors";
import { Form } from "./form";

interface ModalShowOptions {
    action?: string;
    triggerElement?: HTMLElement;
}

interface ModalHideOptions {
    triggerElement?: HTMLElement;
}

type ModalCallback = (modal: Modal, triggerElement?: HTMLElement) => void;

type ModalState = "open" | "closed";

const focusableSelector = ["a[href]", "button:not([disabled])", "input:not([disabled]):not([type=hidden])", "select:not([disabled])", "textarea:not([disabled])", "[contenteditable=true]", "[tabindex]:not([tabindex='-1'])"].join(",");

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

    get isOpen() {
        return this.state === "open";
    }

    get isClosed() {
        return this.state === "closed";
    }

    setMessage(message: string) {
        const messageElement = $(".modal-text", this.element);
        if (messageElement) {
            messageElement.textContent = message;
        }
    }

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

        $$(".tooltip").forEach((tooltip) => tooltip.parentNode && tooltip.parentNode.removeChild(tooltip));

        this.createBackdrop();

        if (this.isClosed) {
            Modal.openModals.push(this);
        }

        this.state = "open";

        this.dispatchCallback("open", options.triggerElement);
    }

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

    onBeforeOpen(callback: ModalCallback) {
        this.callbacks["before-open"] = callback;
    }

    onOpen(callback: ModalCallback) {
        this.callbacks["open"] = callback;
    }

    onBeforeClose(callback: ModalCallback) {
        this.callbacks["before-close"] = callback;
    }

    onClose(callback: ModalCallback) {
        this.callbacks["close"] = callback;
    }

    onCommand(command: string, callback: ModalCallback) {
        this.callbacks[`command-${command}`] = callback;
    }

    triggerCommand(command: string, triggerElement?: HTMLElement) {
        this.dispatchCallback(`command-${command}`, triggerElement);
    }

    private createBackdrop() {
        if (!$(".modal-backdrop")) {
            const backdrop = document.createElement("div");
            backdrop.className = "modal-backdrop";
            document.body.appendChild(backdrop);
        }
    }

    private removeBackdrop() {
        const backdrop = $(".modal-backdrop");
        if (backdrop && backdrop.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
        }
    }

    private dispatchCallback(name: string, triggerElement?: HTMLElement) {
        const callback = this.callbacks[name];
        if (callback) {
            callback(this, triggerElement);
        }
    }

    private registerEvents() {
        document.addEventListener("click", (event) => {
            const target = (event.target as HTMLElement).closest(`[data-modal="${this.element.id}"]`) as HTMLElement;
            if (target) {
                this.open({ action: target.dataset.modalAction, triggerElement: target });
            }
        });

        $$("[data-command]", this.element).forEach((commandButton) => commandButton.addEventListener("click", () => this.triggerCommand(commandButton.dataset.command as string, commandButton)));

        const dismissButton = $("[data-dismiss]", this.element);
        dismissButton?.addEventListener("click", () => this.close({ triggerElement: dismissButton }));

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

        document.addEventListener("focusin", (event) => {
            if (this.isTopmost && !this.element.contains(event.target as Node)) {
                this.getFirstFocusableElement(this.element).focus();
            }
        });

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

        window.addEventListener("focus", () => {
            if (this.element.classList.contains("open")) {
                this.getFirstFocusableElement(this.element).focus();
            }
        });
    }

    private get isTopmost() {
        return this.isOpen && Modal.openModals[Modal.openModals.length - 1] === this;
    }

    private getFocusableElements(): HTMLElement[] {
        return Array.from($$<HTMLElement>(focusableSelector, this.element)).filter((element) => element.getClientRects().length > 0);
    }

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

    private resolveReturnFocusElement(element: HTMLElement | null): HTMLElement | null {
        // When the trigger lives in a dropdown menu, return focus to the button that opens that menu
        const menu = element?.closest<HTMLElement>(".dropdown-menu");

        if (menu?.id) {
            return $<HTMLElement>(`[data-dropdown="${menu.id}"]`) ?? element;
        }

        return element;
    }

    private restoreFocus() {
        const element = this.returnFocusElement;

        this.returnFocusElement = null;

        if (element?.isConnected && element.getClientRects().length > 0) {
            element.focus();
        }
    }

    private getFirstFocusableElement(parent: HTMLElement = document.body): HTMLElement {
        return parent.querySelector("[autofocus], button, .button, input:not([type=hidden]), select, textarea") || parent;
    }
}
