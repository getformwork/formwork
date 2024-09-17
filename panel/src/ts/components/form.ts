import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { Inputs } from "./inputs";
import { serializeForm } from "../utils/forms";

export class Form {
    inputs: Inputs;
    originalData: string;
    element: HTMLFormElement;

    constructor(form: HTMLFormElement) {
        this.element = form;

        this.inputs = new Inputs(form);

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        const handleBeforeunload = (event: Event) => {
            if (this.hasChanged()) {
                event.preventDefault();
                event.returnValue = false;
            }
        };

        const removeBeforeUnload = () => {
            window.removeEventListener("beforeunload", handleBeforeunload);
        };

        window.addEventListener("beforeunload", handleBeforeunload);

        form.addEventListener("submit", removeBeforeUnload);

        $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element: HTMLAnchorElement) => {
            element.addEventListener("click", (event) => {
                if (this.hasChanged()) {
                    event.preventDefault();
                    app.modals["changesModal"].show(undefined, (modal) => {
                        const continueCommand = $("[data-command=continue]", modal.element);
                        if (continueCommand) {
                            continueCommand.dataset.href = element.href;
                        }
                    });
                }
            });
        });

        $$("input[type=file][data-auto-upload]", form).forEach((element) => {
            element.addEventListener("change", () => {
                if (!this.hasChanged(false)) {
                    form.requestSubmit($("[type=submit]", form));
                }
            });
        });

        registerModalExceptions();

        function registerModalExceptions() {
            const changesModal = document.getElementById("changesModal");
            const deletePageModal = document.getElementById("deletePageModal");
            const deleteUserModal = document.getElementById("deleteUserModal");

            if (changesModal) {
                const continueCommand = $("[data-command=continue]", changesModal);
                if (continueCommand) {
                    continueCommand.addEventListener("click", function () {
                        removeBeforeUnload();
                        if (this.dataset.href) {
                            window.location.href = this.dataset.href;
                        }
                    });
                }
            }

            if (deletePageModal) {
                const deleteCommand = $("[data-command=delete]", deletePageModal);
                if (deleteCommand) {
                    deleteCommand.addEventListener("click", removeBeforeUnload);
                }
            }

            if (deleteUserModal) {
                const deleteCommand = $("[data-command=delete]", deleteUserModal);
                if (deleteCommand) {
                    deleteCommand.addEventListener("click", removeBeforeUnload);
                }
            }
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
}
