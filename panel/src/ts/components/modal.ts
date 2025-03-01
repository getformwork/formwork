import { $, $$ } from "../utils/selectors";
import { Form } from "./form";
import { Inputs } from "./inputs";

interface ModalShowOptions {
    action?: string;
    triggerElement?: HTMLElement;
}

interface ModalHideOptions {
    triggerElement?: HTMLElement;
}

type ModalCallback = (modal: Modal, triggerElement?: HTMLElement) => void;

type ModalState = "open" | "closed";

export class Modal {
    readonly element: HTMLElement;

    readonly form: Form | null;

    readonly inputs: Inputs;

    readonly data: { [key: string]: unknown } = {};

    private readonly callbacks: { [name: string]: ModalCallback } = {};

    private state: ModalState = "closed";

    constructor(element: HTMLElement) {
        this.element = element;

        const formElement = $("form", this.element) as HTMLFormElement | null;

        this.form = formElement
            ? new Form(formElement, {
                  preventUnloadOnChanges: false,
              })
            : null;

        this.registerEvents();
    }

    get isOpen() {
        return this.state === "open";
    }

    get isClosed() {
        return this.state === "closed";
    }

    open(options: ModalShowOptions = {}) {
        this.element.role = "dialog";
        this.element.ariaModal = "true";
        this.element.classList.add("open");

        if (options.action) {
            if (this.form) {
                this.form.element.action = options.action;
            }
        }

        (document.activeElement as HTMLElement | null)?.blur(); // Don't retain focus on any element

        this.getFirstFocusableElement(this.element)?.focus();

        $$(".tooltip").forEach((tooltip) => tooltip.parentNode && tooltip.parentNode.removeChild(tooltip));

        this.createBackdrop();

        this.state = "open";

        this.dispatchCallback("open", options.triggerElement);
    }

    close(options: ModalHideOptions = {}) {
        const modal = this.element;

        modal.classList.remove("open");
        modal.role = null;
        modal.ariaModal = null;

        this.removeBackdrop();

        this.state = "closed";

        this.dispatchCallback("close", options.triggerElement);
    }

    onOpen(callback: ModalCallback) {
        this.callbacks["open"] = callback;
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
            const target = (event.target as HTMLElement).closest(`[data-modal="${this.element.id}"]`) as HTMLDivElement | null;
            if (target) {
                this.open({ action: target.dataset.modalAction, triggerElement: target });
            }
        });

        $$("[data-command]", this.element).forEach((commandButton) => commandButton.addEventListener("click", () => this.triggerCommand(commandButton.dataset.command as string, commandButton)));

        const dismissButton = $("[data-dismiss]", this.element);
        dismissButton?.addEventListener("click", () => this.close({ triggerElement: dismissButton }));

        document.addEventListener("keyup", (event) => {
            if (event.key === "Escape") {
                this.close();
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

    private getFirstFocusableElement(parent: HTMLElement = document.body): HTMLElement {
        return parent.querySelector("[autofocus], button, .button, input:not([type=hidden]), select, textarea") || parent;
    }
}
