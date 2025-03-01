import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { Inputs } from "./inputs";
import { serializeForm } from "../utils/forms";

interface FormOptions {
    preventUnloadOnChanges?: boolean;
}
export class Form {
    inputs: Inputs;
    originalData: string;
    element: HTMLFormElement;
    options: FormOptions = {
        preventUnloadOnChanges: true,
    };

    constructor(form: HTMLFormElement, options: Partial<FormOptions> = {}) {
        this.element = form;

        this.inputs = new Inputs(this);

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        this.options = { ...this.options, ...options };

        if (this.options.preventUnloadOnChanges) {
            this.preventUnloadOnChanges();
        }
    }

    hasChanged(checkFileInputs: boolean = true) {
        const fileInputs = $$("input[type=file]", this.element) as NodeListOf<HTMLInputElement>;

        if (checkFileInputs === true && fileInputs.length > 0) {
            for (const fileInput of Array.from(fileInputs)) {
                if (fileInput.files && fileInput.files.length > 0) {
                    return true;
                }
            }
        }

        return serializeForm(this.element) !== this.originalData;
    }

    private preventUnloadOnChanges() {
        window.addEventListener("beforeunload", this.handleBeforeunload);

        this.element.addEventListener("submit", this.removeBeforeUnload);

        const changesModal = app.modals["changesModal"];

        if (changesModal) {
            changesModal.onCommand("continue", (_, button) => {
                this.removeBeforeUnload();
                if (button?.dataset.href) {
                    window.location.href = button.dataset.href;
                }
            });

            $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element: HTMLAnchorElement) => {
                if (element.closest(".editor-wrap")) {
                    return;
                }

                element.addEventListener("click", (event) => {
                    if (this.hasChanged()) {
                        event.preventDefault();

                        app.modals["changesModal"].onOpen((modal) => {
                            const continueCommand = $("[data-command=continue]", modal.element);
                            if (continueCommand) {
                                continueCommand.dataset.href = element.href;
                            }
                        });

                        app.modals["changesModal"].open();
                    }
                });
            });
        }
    }

    private handleBeforeunload(event: Event) {
        if (this.hasChanged()) {
            event.preventDefault();
            event.returnValue = false;
        }
    }

    private removeBeforeUnload() {
        window.removeEventListener("beforeunload", this.handleBeforeunload);
    }
}
