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

export class Modal {
    readonly element: HTMLElement;

    readonly form: Form | null;

    readonly data: { [key: string]: unknown } = {};

    private readonly callbacks: { [name: string]: ModalCallback } = {};

    private state: ModalState = "closed";

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

    open(options: ModalShowOptions = {}) {
        this.dispatchCallback("before-open", options.triggerElement);

        this.element.role = "dialog";
        this.element.ariaModal = "true";
        this.element.classList.add("open");

        if (options.action && this.form) {
            this.form.element.action = options.action;
        }

        (document.activeElement as HTMLElement | null)?.blur(); // Don't retain focus on any element

        this.getFirstFocusableElement(this.element)?.focus();

        $$(".tooltip").forEach((tooltip) => tooltip.remove());

        this.createBackdrop();

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

        this.dispatchCallback("close", options.triggerElement);
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
        $(".modal-backdrop")?.remove();
    }

    private dispatchCallback(name: string, triggerElement?: HTMLElement) {
        const callback = this.callbacks[name];
        if (callback) {
            callback(this, triggerElement);
        }
    }

    private registerEvents() {
        this.registerOpenTriggers();
        this.registerCommandTriggers();
        this.registerDismissTrigger();
        this.registerEscapeHandler();
        this.registerBackdropClickHandler();
        this.registerFocusHandler();
    }

    private registerOpenTriggers() {
        document.addEventListener("click", (event) => {
            const target = (event.target as HTMLElement).closest<HTMLElement>(`[data-modal="${this.element.id}"]`);
            if (!target) {
                return;
            }

            this.open({ action: target.dataset.modalAction, triggerElement: target });
        });
    }

    private registerCommandTriggers() {
        $$("[data-command]", this.element).forEach((commandButton) => {
            commandButton.addEventListener("click", () => {
                this.triggerCommand(commandButton.dataset.command ?? "", commandButton);
            });
        });
    }

    private registerDismissTrigger() {
        const dismissButton = $("[data-dismiss]", this.element);
        dismissButton?.addEventListener("click", () => this.close({ triggerElement: dismissButton }));
    }

    private registerEscapeHandler() {
        document.addEventListener("keyup", (event) => {
            if (event.key === "Escape") {
                this.close();
            }
        });
    }

    private registerBackdropClickHandler() {
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

    private registerFocusHandler() {
        window.addEventListener("focus", () => {
            if (this.isOpen) {
                this.getFirstFocusableElement(this.element)?.focus();
            }
        });
    }

    private getFirstFocusableElement(parent: HTMLElement = document.body): HTMLElement {
        return parent.querySelector("[autofocus], button, .button, input:not([type=hidden]), select, textarea") || parent;
    }
}
