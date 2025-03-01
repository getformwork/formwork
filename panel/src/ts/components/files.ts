import { $ } from "../utils/selectors";
import { app } from "../app";

export class Files {
    constructor() {
        const fileForm = $("[data-form=file-form]");

        if (fileForm) {
            const renameFileModal = app.modals["renameFileModal"];

            if (renameFileModal) {
                renameFileModal.form?.element.addEventListener("keydown", (event) => {
                    if (event.key === "Enter") {
                        renameFileModal.form?.element.submit();
                        event.preventDefault();
                    }
                });

                renameFileModal.onOpen((modal, trigger) => {
                    if (trigger) {
                        const input = $('[id="renameFileModal.filename"]', modal.element) as HTMLInputElement;
                        input.value = trigger.dataset.filename as string;
                        input.setSelectionRange(0, input.value.lastIndexOf("."));
                    }
                });
            }

            const replaceFileCommand = $("[data-command=replaceFile]");

            if (replaceFileCommand) {
                replaceFileCommand.addEventListener("click", () => {
                    const form = document.createElement("form");
                    form.hidden = true;
                    form.action = replaceFileCommand.dataset.action as string;
                    form.method = "post";
                    form.enctype = "multipart/form-data";

                    const fileInput = document.createElement("input");
                    fileInput.name = "file";
                    fileInput.type = "file";
                    fileInput.accept = replaceFileCommand.dataset.extension as string;
                    form.appendChild(fileInput);

                    const csrfInput = document.createElement("input");
                    csrfInput.name = "csrf-token";
                    csrfInput.value = ($("meta[name=csrf-token]") as HTMLMetaElement).content;
                    form.appendChild(csrfInput);

                    fileInput.click();

                    fileInput.addEventListener("change", () => {
                        document.body.appendChild(form);
                        form.submit();
                    });
                });
            }
        }
    }
}
