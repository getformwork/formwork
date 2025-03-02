import { $, $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Form } from "../form";
import { insertIcon } from "../icons";
import { Notification } from "../notification";
import { Request } from "../../utils/request";

export class FileInput {
    constructor(input: HTMLInputElement, form: Form) {
        const label = $(`label[for="${input.id}"]`) as HTMLElement;
        const dropTarget = input.closest(".form-file-drop-target") as HTMLElement;
        const dropTargetLabel = $("span", dropTarget) as HTMLElement;
        const defaultDropLabel = dropTargetLabel.innerHTML ?? "";

        let isSubmitted = false;

        label?.addEventListener("click", (event) => {
            dropTarget.focus();
            event.preventDefault();
        });

        input.addEventListener("change", updateDropTargetLabel);
        input.addEventListener("input", updateDropTargetLabel);

        input.form?.addEventListener("submit", () => {
            isSubmitted = true;
            updateDropTargetLabel();
        });

        dropTarget.addEventListener("drag", (event) => event.preventDefault());
        dropTarget.addEventListener("dragstart", (event) => event.preventDefault());
        dropTarget.addEventListener("dragend", (event) => event.preventDefault());
        dropTarget.addEventListener("dragover", handleDragenter);
        dropTarget.addEventListener("dragenter", handleDragenter);
        dropTarget.addEventListener("dragleave", handleDragleave);

        dropTarget.addEventListener("drop", (event) => {
            event.preventDefault();
            if (isSubmitted) {
                return;
            }
            if (event.dataTransfer) {
                input.files = event.dataTransfer.files;
                // Firefox won't trigger a change event, so we explicitly do that
                input.dispatchEvent(new Event("change"));
            }
        });

        dropTarget.addEventListener("click", (event) => {
            if (isSubmitted) {
                event.preventDefault();
            }
        });

        dropTarget.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                input.click();
            }
        });

        const filesList = $(`.files-list[data-for="${input.id}"]`) as HTMLElement;

        if (!filesList && input.dataset.autoUpload === "true") {
            input.addEventListener("change", () => {
                if (!form.hasChanged(false)) {
                    form.element.requestSubmit($("[type=submit]", form.element));
                }
            });
        }

        if (filesList) {
            const toggle = $(".form-togglegroup.files-list-view-as", filesList);

            if (toggle) {
                const fieldName = toggle.dataset.for;
                const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${fieldName}]`);

                if (viewAs) {
                    $$("input", toggle).forEach((input: HTMLInputElement) => (input.checked = false));
                    ($(`input[value=${viewAs}]`, filesList) as HTMLInputElement).checked = true;
                    filesList.classList.toggle("is-thumbnails", viewAs === "thumbnails");
                }

                $$("input", toggle).forEach((input: HTMLInputElement) => {
                    input.addEventListener("input", () => {
                        filesList.classList.toggle("is-thumbnails", input.value === "thumbnails");
                        window.localStorage.setItem(`formwork.filesListViewAs[${fieldName}]`, input.value);
                    });
                });
            }

            document.addEventListener("click", (event) => {
                const target = event.target as HTMLElement;
                if (!target.closest(".dropdown") && target.closest(".files-item")) {
                    const item = target.closest(".files-item") as HTMLElement;
                    if (typeof item.dataset.href === "string") {
                        location.href = item.dataset.href;
                    }
                }
            });

            if (input.dataset.autoUpload === "true") {
                input.addEventListener("change", () => {
                    if (input.files?.length) {
                        for (const file of Array.from(input.files)) {
                            const formData = new FormData();
                            formData.append("csrf-token", ($("meta[name=csrf-token]") as HTMLMetaElement).content);
                            formData.append("file", file);

                            dropTargetLabel.innerHTML += ' <span class="spinner"></span>';

                            new Request(
                                {
                                    method: "POST",
                                    data: formData,
                                },
                                (response) => {
                                    const notification = new Notification(response.message, response.status);

                                    if (response.status === "success") {
                                        const template = $("template[id=files-item]") as HTMLTemplateElement;

                                        addFilesItem(response.data[0], template);
                                        sortFilesList(filesList, ".file-name");

                                        input.value = "";
                                        updateDropTargetLabel();
                                    }

                                    notification.show();
                                },
                            );
                        }
                    }
                });
            }

            const renameFileItemModal = app.modals["renameFileItemModal"];

            if (renameFileItemModal) {
                $('[id="renameFileItemModal.filename"]', renameFileItemModal.element)?.addEventListener("keydown", (event) => {
                    if (event.key === "Enter") {
                        renameFileItemModal.triggerCommand("rename-file");
                        event.preventDefault();
                    }
                });

                renameFileItemModal.onOpen((modal, trigger) => {
                    if (trigger) {
                        const input = $('[id="renameFileItemModal.filename"]', modal.element) as HTMLInputElement;
                        input.value = (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename as string;
                        input.setSelectionRange(0, input.value.lastIndexOf("."));

                        Object.assign(modal.data, {
                            action: trigger.dataset.action,
                            item: trigger.closest(".files-item"),
                            filename: (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename,
                            input,
                        });
                    }
                });

                renameFileItemModal.onCommand("rename-file", (modal) => {
                    const { action, item, filename, input } = modal.data;

                    new Request(
                        {
                            method: "POST",
                            url: action as string,
                            data: {
                                filename,
                                "renameFileItemModal[filename]": (input as HTMLInputElement).value,
                                "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content,
                            },
                        },
                        (response) => {
                            if (response.status === "success") {
                                (item as HTMLElement).dataset.filename = response.data.filename;

                                ($(".file-name", item as HTMLElement) as HTMLElement).innerHTML = response.data.filename;

                                const template = $("template[id=files-item]") as HTMLTemplateElement;
                                const uri = ($(".files-item", template.content) as HTMLElement).dataset.href as string;
                                (item as HTMLElement).dataset.href = `${uri}${response.data.filename}`;

                                sortFilesList(filesList, ".file-name");
                            }

                            const notification = new Notification(response.message, response.status);
                            notification.show();

                            modal.close();
                        },
                    );

                    modal.close();
                });
            }

            const deleteFileItemModal = app.modals["deleteFileItemModal"];

            if (deleteFileItemModal) {
                deleteFileItemModal.onOpen((modal, trigger) => {
                    if (trigger) {
                        Object.assign(modal.data, {
                            action: trigger.dataset.action,
                            item: trigger.closest(".files-item"),
                            filename: (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename,
                        });
                    }
                });

                deleteFileItemModal.onCommand("delete-file", (modal) => {
                    const { action, item, filename } = modal.data;

                    new Request(
                        {
                            method: "POST",
                            url: action as string,
                            data: {
                                filename,
                                "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content,
                            },
                        },
                        (response) => {
                            if (response.status === "success") {
                                (item as HTMLElement).remove();
                            }

                            const notification = new Notification(response.message, response.status);
                            notification.show();

                            modal.close();
                        },
                    );

                    modal.close();
                });
            }

            filesList.addEventListener("click", (event) => {
                const element = (event.target as HTMLElement).closest("[data-command=replaceFile]") as HTMLElement;
                if (element) {
                    const fileInput = document.createElement("input");
                    fileInput.type = "file";
                    fileInput.accept = element.dataset.mimetype as string;
                    fileInput.click();

                    fileInput.addEventListener("change", () => {
                        if (fileInput.files?.length) {
                            const formData = new FormData();
                            formData.append("filename", (element.closest("[data-filename]") as HTMLElement).dataset.filename as string);
                            formData.append("csrf-token", ($("meta[name=csrf-token]") as HTMLMetaElement).content);
                            formData.append("file", fileInput.files[0]);

                            new Request(
                                {
                                    method: "POST",
                                    url: element.dataset.action as string,
                                    data: formData,
                                },
                                (response) => {
                                    const notification = new Notification(response.message, response.status);

                                    if (response.status === "success") {
                                        if (element.closest("[data-form=page-file-form]")) {
                                            window.location.reload();
                                        } else if (response.data.thumbnail) {
                                            const thumbnail = $(".file-thumbnail", element.closest(".files-item") as HTMLElement) as HTMLImageElement;
                                            const fileSize = $(".file-size", element.closest(".files-item") as HTMLElement) as HTMLImageElement;

                                            if (response.data.type === "image") {
                                                thumbnail.style.backgroundImage = `url(${response.data.thumbnail})`;
                                            } else if (response.data.type === "video") {
                                                thumbnail.src = response.data.thumbnail;
                                            }

                                            fileSize.textContent = `(${response.data.size})`;
                                        }
                                    }

                                    notification.show();
                                },
                            );
                        }

                        fileInput.remove();
                    });
                }
            });
        }

        function formatFileSize(size: number) {
            const units = ["B", "KB", "MB", "GB", "TB"];
            const exp = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
            return `${(size / 1024 ** exp).toFixed(2)} ${units[exp]}`;
        }

        function updateDropTargetLabel() {
            if (input.files && input.files.length > 0) {
                const filenames: string[] = [];
                for (const file of Array.from(input.files)) {
                    filenames.push(`${file.name} <span class="file-size">(${formatFileSize(file.size)})</span>`);
                }
                dropTargetLabel.innerHTML = filenames.join(", ");

                if (isSubmitted && !$(".spinner", dropTargetLabel)) {
                    const spinner = document.createElement("span");
                    spinner.classList.add("spinner", "ml-3");
                    dropTargetLabel.appendChild(spinner);
                }
            } else {
                dropTargetLabel.innerHTML = defaultDropLabel;
            }
        }

        function handleDragenter(this: HTMLInputElement, event: DragEvent) {
            this.classList.add("drag");
            event.preventDefault();
        }

        function handleDragleave(this: HTMLInputElement, event: DragEvent) {
            this.classList.remove("drag");
            event.preventDefault();
        }

        function sortFilesList(filesList: HTMLElement, selector: string = ".file-name") {
            const filesItems = $$(".files-item", filesList);
            Array.from(filesItems)
                .sort((a: HTMLElement, b: HTMLElement) => {
                    const keyA = $(selector, a)?.textContent;
                    const keyB = $(selector, b)?.textContent;
                    return keyA?.localeCompare(keyB ?? "") ?? 0;
                })
                .forEach((element: HTMLElement) => {
                    element.parentElement?.appendChild(element);
                });
        }

        function addFilesItem(info: { [key: string]: string }, template: HTMLTemplateElement) {
            const node = template.content.cloneNode(true) as HTMLElement;
            const filesItem = $(".files-item", node) as HTMLElement;

            filesItem.dataset.filename = info.name;
            filesItem.dataset.href += `${info.name}/`;

            if (info.type === "image") {
                ($(".file-thumbnail", filesItem) as HTMLElement).style.backgroundImage = `url(${info.thumbnail})`;
            } else if (info.type === "video") {
                const video = document.createElement("video");
                video.classList.add("file-thumbnail");
                video.src = info.thumbnail;
                video.preload = "metadata";
                $(".file-thumbnail", filesItem)?.replaceWith(video);
            } else {
                $(".file-thumbnail", filesItem)?.remove();
            }

            insertIcon(info.type ? `file-${info.type}` : "file", $(".file-icon", filesItem) as HTMLElement);

            ($(".file-name", filesItem) as HTMLElement).textContent = info.name;
            ($(".file-size", filesItem) as HTMLElement).textContent = `(${info.size})`;

            ($(".dropdown-button", filesItem) as HTMLElement).dataset.dropdown = `dropdown-${info.hash}`;
            ($(".dropdown-menu", filesItem) as HTMLElement).id = `dropdown-${info.hash}`;

            ($("[data-command=infoFile", filesItem) as HTMLAnchorElement).href += `${info.name}/`;

            ($("[data-command=previewFile"), filesItem as HTMLAnchorElement).href = info.uri;
            ($("[data-command=previewFile"), filesItem as HTMLAnchorElement).target = `formwork-preview-file-${info.hash}`;

            ($("[data-command=replaceFile", filesItem) as HTMLElement).dataset.extension = `${info.mimeType}`;

            $(".files-items", filesList)?.appendChild(node);
        }
    }
}
