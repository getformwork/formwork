import * as icons from "../icons";
import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { escapeHtml } from "../../utils/validation";
import { FilesList } from "../fileslist";
import type { Form } from "../form";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { SelectInput } from "./select-input";
import { TagsInput } from "./tags-input";
import { toCamelCase } from "../../utils/strings";

export class UploadInput {
    readonly element: HTMLInputElement;

    private readonly form: Form;
    private isSubmitted: boolean = false;

    private readonly label: HTMLElement;
    private readonly dropTarget: HTMLElement;
    private readonly dropTargetLabel: HTMLElement;
    private readonly defaultDropLabel: string;

    private readonly filesList!: FilesList;

    constructor(element: HTMLInputElement, form: Form) {
        this.element = element;

        this.form = form;

        this.label = $(`label[for="${this.element.id}"]`) as HTMLElement;
        this.dropTarget = this.element.closest(".form-upload-drop-target") as HTMLElement;
        this.dropTargetLabel = $("span", this.dropTarget) as HTMLElement;
        this.defaultDropLabel = this.dropTargetLabel.innerHTML ?? "";

        this.initInput();

        const filesList = $(`.files-list[data-for="${this.element.id}"]`) as HTMLElement;

        if (filesList) {
            this.filesList = new FilesList(filesList, this.form);
            this.initFilesList();
        } else if (this.element.dataset.autoUpload === "true") {
            this.element.addEventListener("change", () => {
                if (!this.form.hasChanged(false)) {
                    this.form.element.requestSubmit($("[type=submit]", this.form.element));
                }
            });
        }
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value(): string {
        return this.element.value;
    }

    private initInput() {
        this.label?.addEventListener("click", (event) => {
            this.dropTarget.focus();
            event.preventDefault();
        });

        this.element.addEventListener("change", () => this.updateDropTargetLabel());
        this.element.addEventListener("input", () => this.updateDropTargetLabel());

        this.element.form?.addEventListener("submit", () => {
            this.isSubmitted = true;
            this.updateDropTargetLabel();
        });

        this.dropTarget.addEventListener("drag", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragstart", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragend", (event) => event.preventDefault());
        this.dropTarget.addEventListener("dragover", (event) => {
            this.dropTarget.classList.add("drag");
            event.preventDefault();
        });
        this.dropTarget.addEventListener("dragenter", (event) => {
            this.dropTarget.classList.add("drag");
            event.preventDefault();
        });
        this.dropTarget.addEventListener("dragleave", (event) => {
            this.dropTarget.classList.remove("drag");
            event.preventDefault();
        });

        this.dropTarget.addEventListener("drop", (event) => {
            event.preventDefault();
            this.dropTarget.classList.remove("drag");
            if (this.isSubmitted) {
                return;
            }
            if (event.dataTransfer) {
                this.element.files = event.dataTransfer.files;
                // Firefox won't trigger a change event, so we explicitly do that
                this.element.dispatchEvent(new Event("change"));
            }
        });

        this.dropTarget.addEventListener("click", (event) => {
            if (this.isSubmitted) {
                event.preventDefault();
            }
        });

        this.dropTarget.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                this.element.click();
            }
        });
    }

    private initFilesList() {
        if (this.element.dataset.autoUpload === "true") {
            this.element.addEventListener("change", () => {
                if (!this.element.files) {
                    return;
                }

                let files = Array.from(this.element.files);

                this.updateDropTargetLabel(files, true);

                for (const file of files) {
                    const formData = new FormData();
                    formData.append("csrf-token", app.config.csrfToken as string);
                    formData.append(this.element.name, file);

                    new Request(
                        {
                            method: "POST",
                            data: formData,
                        },
                        (response) => {
                            const notification = new Notification(response.message, response.status);

                            if (response.status === "success") {
                                const data = response.data[0];
                                const template = $("template[id=files-item]") as HTMLTemplateElement;
                                this.addFilesItem(data, template);
                                this.filesList.sort(".file-name");
                                this.filesList.element.hidden = false;

                                for (const name in this.form.inputs) {
                                    const input = this.form.inputs[name];
                                    if (input instanceof SelectInput && (input.element.classList.contains("form-file") || (input.element.classList.contains("form-image") && data.type === "image"))) {
                                        input.addOption({
                                            label: data.name,
                                            value: data.name,
                                            thumb: data.type === "image" ? data.thumbnail : undefined,
                                            icon: `file-${data.type}`,
                                        });
                                        input.sortDropdownItems();
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || (input.element.classList.contains("form-images") && data.type === "image"))) {
                                        input.addDropdownItem({
                                            label: data.name,
                                            value: data.name,
                                            thumb: data.type === "image" ? data.thumbnail : undefined,
                                            icon: toCamelCase(`file-${data.type}`) as keyof typeof icons,
                                        });
                                        input.sortDropdownItems();
                                    }
                                }
                            }

                            this.element.value = "";

                            files = files.filter((f) => f !== file);
                            this.updateDropTargetLabel(files, true);

                            notification.show();
                        },
                    );
                }
            });
        }
    }

    private formatFileSize(size: number) {
        const units = ["B", "KB", "MB", "GB", "TB"];
        const exp = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
        return `${(size / 1024 ** exp).toFixed(2)} ${units[exp]}`;
    }

    private updateDropTargetLabel(files: File[] = Array.from(this.element.files ?? []), uploading: boolean = this.isSubmitted) {
        if (files.length) {
            const filenames: string[] = [];
            for (const file of files) {
                filenames.push(`${escapeHtml(file.name)} <span class="file-size-inline">(${this.formatFileSize(file.size)})</span>`);
            }
            this.dropTargetLabel.innerHTML = filenames.join(", ");

            if (uploading && !$(".spinner", this.dropTargetLabel)) {
                const spinner = document.createElement("span");
                spinner.classList.add("spinner", "ml-3");
                this.dropTargetLabel.appendChild(spinner);
            }
        } else {
            this.dropTargetLabel.innerHTML = this.defaultDropLabel;
        }
    }

    private addFilesItem(info: { [key: string]: any }, template: HTMLTemplateElement) {
        const node = template.content.cloneNode(true) as HTMLElement;
        const filesItem = $(".files-item", node) as HTMLElement;

        filesItem.dataset.filename = info.name;

        if (info.type === "image") {
            const img = document.createElement("img");
            img.className = "file-thumbnail";
            img.src = info.thumbnail;
            img.loading = "lazy";
            $(".file-thumbnail", filesItem)?.replaceWith(img);
        } else if (info.type === "video") {
            const video = document.createElement("video");
            video.classList.add("file-thumbnail");
            video.src = info.thumbnail;
            video.preload = "metadata";
            $(".file-thumbnail", filesItem)?.replaceWith(video);
        } else {
            $(".file-thumbnail", filesItem)?.remove();
        }

        const icon = icons[toCamelCase(`file-${info.type}`) as keyof typeof icons] || icons["file"];
        ($(".file-icon", filesItem) as HTMLElement).innerHTML = icon;

        const anchor = $(".file-name a", filesItem) as HTMLAnchorElement;
        anchor.href = info.actions.info;
        anchor.textContent = info.name;

        ($(".file-date", filesItem) as HTMLElement).textContent = info.lastModifiedTime;
        ($(".file-size", filesItem) as HTMLElement).textContent = info.size;

        ($(".dropdown-button", filesItem) as HTMLElement).dataset.dropdown = `dropdown-${info.hash}`;
        ($(".dropdown-menu", filesItem) as HTMLElement).id = `dropdown-${info.hash}`;

        const infoFileCommand = $("[data-command=infoFile]", filesItem) as HTMLAnchorElement;
        const previewFileCommand = $("[data-command=previewFile]", filesItem) as HTMLAnchorElement;
        const renameFileCommand = $("[data-command=renameFile]", filesItem) as HTMLElement;
        const replaceFileCommand = $("[data-command=replaceFile]", filesItem) as HTMLElement;
        const deleteFileCommand = $("[data-command=deleteFile]", filesItem) as HTMLElement;

        infoFileCommand.href = info.actions.info;

        previewFileCommand.href = info.uri;
        previewFileCommand.target = `formwork-preview-file-${info.hash}`;

        renameFileCommand.dataset.action = info.actions.rename;

        replaceFileCommand.dataset.action = info.actions.replace;
        replaceFileCommand.dataset.mimetype = `${info.mimeType}`;

        deleteFileCommand.dataset.action = info.actions.delete;

        $(".files-items", this.filesList.element)?.appendChild(node);
    }
}
