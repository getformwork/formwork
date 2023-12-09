import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { Inputs } from "./inputs";
import { serializeForm } from "../utils/forms";

export class Form {
    constructor(form) {
        this.originalData = serializeForm(form);

        this.inputs = new Inputs(form);

        window.addEventListener("beforeunload", handleBeforeunload);

        form.addEventListener("submit", removeBeforeUnload);

        const hasChanged = (checkFileInputs = true) => {
            const fileInputs = $$("input[type=file]", form);

            if (checkFileInputs === true && fileInputs.length > 0) {
                for (const fileInput of fileInputs) {
                    if (fileInput.files.length > 0) {
                        return true;
                    }
                }
            }

            return serializeForm(form) !== this.originalData;
        };

        $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element) => {
            element.addEventListener("click", (event) => {
                if (hasChanged()) {
                    event.preventDefault();
                    app.modals["changesModal"].show(null, (modal) => {
                        $("[data-command=continue]", modal).dataset.href = element.href;
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

        function handleBeforeunload(event) {
            if (hasChanged()) {
                event.preventDefault();
                event.returnValue = "";
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
                $("[data-command=continue]", changesModal).addEventListener("click", function () {
                    removeBeforeUnload();
                    window.location.href = this.dataset.href;
                });
            }

            if (deletePageModal) {
                $("[data-command=delete]", deletePageModal).addEventListener("click", removeBeforeUnload);
            }

            if (deleteUserModal) {
                $("[data-command=delete]", deleteUserModal).addEventListener("click", removeBeforeUnload);
            }
        }
    }
}
