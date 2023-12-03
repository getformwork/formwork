import Modals from "./modals";
import Utils from "./utils";

export default function Form(form) {
    const originalData = Utils.serializeForm(form);

    window.addEventListener("beforeunload", handleBeforeunload);

    form.addEventListener("submit", removeBeforeUnload);

    $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element) => {
        element.addEventListener("click", (event) => {
            if (hasChanged()) {
                event.preventDefault();
                Modals.show("changesModal", null, (modal) => {
                    $("[data-command=continue]", modal).setAttribute("data-href", element.href);
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

    function hasChanged(checkFileInputs = true) {
        const fileInputs = $$("input[type=file]", form);

        if (checkFileInputs === true && fileInputs.length > 0) {
            for (const fileInput of fileInputs) {
                if (fileInput.files.length > 0) {
                    return true;
                }
            }
        }

        return Utils.serializeForm(form) !== originalData;
    }
}
