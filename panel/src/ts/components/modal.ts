import { $, $$ } from "../utils/selectors";
import { Inputs } from "./inputs";

function getFirstFocusableElement(parent: HTMLElement = document.body): HTMLElement {
    return parent.querySelector("button, .button, input:not([type=hidden]), select, textarea") || parent;
}

export class Modal {
    element: HTMLElement;
    inputs: Inputs;

    constructor(element: HTMLElement) {
        this.element = element;

        document.addEventListener("keyup", (event) => {
            if (event.key === "Escape") {
                this.hide();
            }
        });

        window.addEventListener("focus", () => getFirstFocusableElement(this.element).focus());

        this.inputs = new Inputs(this.element);

        $("[data-dismiss]", element)?.addEventListener("click", () => this.hide());

        let mousedownTriggered = false;
        element.addEventListener("mousedown", () => (mousedownTriggered = true));
        element.addEventListener("click", (event) => {
            if (mousedownTriggered && event.target === element) {
                this.hide();
            }
            mousedownTriggered = false;
        });

        document.addEventListener("click", (event) => {
            const target = (event.target as HTMLElement).closest("[data-modal]") as HTMLDivElement;
            if (target && target.dataset.modal === element.id) {
                const modalAction = target.dataset.modalAction;
                if (modalAction) {
                    this.show(modalAction);
                } else {
                    this.show();
                }
            }
        });
    }

    show(action?: string, callback?: (modal: this) => void) {
        const modal = this.element;
        modal.setAttribute("role", "dialog");
        modal.setAttribute("aria-modal", "true");
        modal.classList.add("show");
        if (action) {
            ($("form", modal) as HTMLFormElement).action = action;
        }
        (document.activeElement as HTMLElement).blur(); // Don't retain focus on any element
        if ($("[autofocus]", modal)) {
            ($("[autofocus]", modal) as HTMLFormElement).focus(); // Firefox bug
        } else {
            getFirstFocusableElement(modal).focus();
        }
        if (typeof callback === "function") {
            callback(this);
        }
        $$(".tooltip").forEach((element) => element.parentNode && element.parentNode.removeChild(element));
        this.createBackdrop();
    }

    hide() {
        const modal = this.element;
        modal.classList.remove("show");
        modal.removeAttribute("role");
        modal.removeAttribute("aria-modal");
        this.removeBackdrop();
    }

    createBackdrop() {
        if (!$(".modal-backdrop")) {
            const backdrop = document.createElement("div");
            backdrop.className = "modal-backdrop";
            document.body.appendChild(backdrop);
        }
    }

    removeBackdrop() {
        const backdrop = $(".modal-backdrop");
        if (backdrop && backdrop.parentNode) {
            backdrop.parentNode.removeChild(backdrop);
        }
    }
}
