import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { Inputs } from "./inputs";
import { serializeForm } from "../utils/forms";

export class Form {
    inputs: Inputs;
    originalData: string;

    constructor(form: HTMLFormElement) {
        this.inputs = new Inputs(form);

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        window.addEventListener("beforeunload", handleBeforeunload);

        form.addEventListener("submit", removeBeforeUnload);

        const hasChanged = (checkFileInputs = true) => {
            const fileInputs = $$("input[type=file]", form) as NodeListOf<HTMLInputElement>;

            if (checkFileInputs === true && fileInputs.length > 0) {
                for (const fileInput of Array.from(fileInputs)) {
                    if (fileInput.files && fileInput.files.length > 0) {
                        return true;
                    }
                }
            }

            return serializeForm(form) !== this.originalData;
        };

        $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element: HTMLAnchorElement) => {
            element.addEventListener("click", (event) => {
                if (hasChanged()) {
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
                if (!hasChanged(false)) {
                    form.requestSubmit($("[type=submit]", form));
                }
            });
        });

        registerModalExceptions();

        function handleBeforeunload(event: Event) {
            if (hasChanged()) {
                event.preventDefault();
                event.returnValue = false;
            }
        }

        function removeBeforeUnload() {
            window.removeEventListener("beforeunload", handleBeforeunload);
        }

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
}
