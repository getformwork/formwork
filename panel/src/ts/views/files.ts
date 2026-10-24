import { FilesList } from "../components/files/files-list";
import { app } from "../services/app";
import { $ } from "../utils/selectors";

/**
 * Initialize the list of the files of a page
 */
export function initFilesView(view: HTMLElement) {
    new FilesList($(".files-list", view) as HTMLElement);
}

/**
 * Initialize the form of a single file
 */
export function initFileForm() {
    initRenameFileModal();
    initReplaceFileCommand();
}

/**
 * Initialize the modal to rename the file
 */
function initRenameFileModal() {
    const renameFileModal = app.modals.renameFileModal;

    if (!renameFileModal) {
        return;
    }

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

/**
 * Initialize the command to replace the file
 */
function initReplaceFileCommand() {
    const replaceFileCommand = $("[data-command=replaceFile]");

    if (!replaceFileCommand) {
        return;
    }

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
        csrfInput.value = app.config.csrfToken as string;
        form.appendChild(csrfInput);

        fileInput.click();

        fileInput.addEventListener("change", () => {
            document.body.appendChild(form);
            form.submit();
        });
    });
}
