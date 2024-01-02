import { $, $$ } from "../utils/selectors";
import { Inputs } from "./inputs";

function getFirstFocusableElement(parent = document.body) {
    return parent.querySelector("button, .button, input:not([type=hidden]), select, textarea") || parent;
}

export class Modal {
    constructor(element) {
        this.element = element;

        document.addEventListener("keyup", (event) => {
            if (event.key === "Escape") {
                this.hide();
            }
        });

        window.addEventListener("focus", () => getFirstFocusableElement(this.element).focus());

        this.inputs = new Inputs(this.element);

        $("[data-dismiss]", element).addEventListener("click", () => this.hide());

        let mousedownTriggered = false;
        element.addEventListener("mousedown", () => (mousedownTriggered = true));
        element.addEventListener("click", (event) => {
            if (mousedownTriggered && event.target === element) {
                this.hide();
            }
            mousedownTriggered = false;
        });

        document.addEventListener("click", (event) => {
            const target = event.target.closest("[data-modal]");
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

    show(action, callback) {
        const modal = this.element;
        modal.classList.add("show");
        if (action) {
            $("form", modal).action = action;
        }
        document.activeElement.blur(); // Don't retain focus on any element
        if ($("[autofocus]", modal)) {
            $("[autofocus]", modal).focus(); // Firefox bug
        } else {
            getFirstFocusableElement(modal).focus();
        }
        if (typeof callback === "function") {
            callback(this);
        }
        $$(".tooltip").forEach((element) => element.parentNode.removeChild(element));
        this.createBackdrop();
    }

    hide() {
        this.element.classList.remove("show");
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
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
    }
}
