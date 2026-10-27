import { batch, effect, signal } from "@preact/signals-core";
import type { Form } from "../../services/form";
import { post } from "../../services/request";
import { $ } from "../../utils/selectors";
import { toCamelCase } from "../../utils/strings";
import { escapeHtml } from "../../utils/validation";
import { addToFileInputs } from "../files/file-inputs";
import { FilesList } from "../files/files-list";
import * as icons from "../ui/icons";
import { Notification } from "../ui/notification";
import { InputBase } from "./input";

/**
 * Describes a file sent by the server after it has been uploaded
 */
interface UploadedFile {
    name: string;
    type: string;
    thumbnail?: string;
    uri: string;
    hash: string;
    mimeType: string;
    lastModifiedTime: string;
    size: string;
    actions: Record<"info" | "rename" | "replace" | "delete", string>;
}

/**
 * Provides a file input with a drop target
 *
 * Files can be uploaded as soon as they are chosen (`data-auto-upload`) and are added to the list
 * of files and to the inputs of the form
 */
export class UploadInput extends InputBase<HTMLInputElement> {
    /**
     * Form containing the input
     */
    private readonly form: Form;
    /**
     * Whether the form has been submitted
     */
    private isSubmitted: boolean = false;

    /**
     * Files chosen
     */
    private readonly files = signal<File[]>([]);
    /**
     * Whether the files are being uploaded
     */
    private readonly uploading = signal(false);
    /**
     * Whether files are being dragged over the drop target
     */
    private readonly dragging = signal(false);

    private readonly label: HTMLElement;
    private readonly dropTarget: HTMLElement;
    private readonly dropTargetLabel: HTMLElement;
    private readonly defaultDropLabel: string;

    /**
     * List of files of the page
     */
    private readonly filesList!: FilesList;

    /**
     * Create the upload input
     */
    constructor(element: HTMLInputElement, form: Form) {
        super(element);

        this.form = form;

        this.label = $(`label[for="${this.element.id}"]`) as HTMLElement;
        this.dropTarget = this.element.closest(".form-upload-drop-target") as HTMLElement;
        this.dropTargetLabel = $("span", this.dropTarget) as HTMLElement;
        this.defaultDropLabel = this.dropTargetLabel.innerHTML ?? "";

        effect(() => {
            this.dropTarget.classList.toggle("drag", this.dragging.value);
        });

        effect(() => this.renderDropTargetLabel(this.files.value, this.uploading.value));

        this.bindFormEvents();
        this.bindDropTarget();

        const filesList = $(`.files-list[data-for="${this.element.id}"]`) as HTMLElement;
        const autoUpload = this.element.dataset.autoUpload === "true";

        if (filesList) {
            this.filesList = new FilesList(filesList, this.form);

            if (autoUpload) {
                this.uploadOnChange();
            }
        } else if (autoUpload) {
            this.submitOnChange();
        }
    }

    /**
     * Get the value of the native input
     */
    get value(): string {
        return this.element.value;
    }

    /**
     * Update the files shown when they change, and block the input once the form is submitted
     */
    private bindFormEvents() {
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
    }

    /**
     * Let the user choose the files by dropping them on the drop target or by pressing Enter
     */
    private bindDropTarget() {
        for (const type of ["drag", "dragstart", "dragend"]) {
            this.dropTarget.addEventListener(type, (event) => event.preventDefault());
        }

        for (const type of ["dragover", "dragenter"]) {
            this.dropTarget.addEventListener(type, (event) => {
                this.dragging.value = true;
                event.preventDefault();
            });
        }

        this.dropTarget.addEventListener("dragleave", (event) => {
            this.dragging.value = false;
            event.preventDefault();
        });

        this.dropTarget.addEventListener("drop", (event) => {
            event.preventDefault();
            this.dragging.value = false;

            if (!this.isSubmitted && event.dataTransfer) {
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

    /**
     * Submit the form as soon as a file is chosen, if the form has no other changes
     */
    private submitOnChange() {
        this.element.addEventListener("change", () => {
            if (!this.form.hasChanged(false)) {
                this.form.element.requestSubmit($("[type=submit]", this.form.element));
            }
        });
    }

    /**
     * Upload the files as soon as they are chosen
     */
    private uploadOnChange() {
        this.element.addEventListener("change", () => {
            if (!this.element.files) {
                return;
            }

            const files = Array.from(this.element.files);

            // Files still being uploaded
            const pending = new Set(files);

            this.updateDropTargetLabel(files, true);

            for (const file of files) {
                this.upload(file, () => {
                    this.element.value = "";

                    pending.delete(file);
                    this.updateDropTargetLabel(Array.from(pending), true);
                });
            }
        });
    }

    /**
     * Upload a file and add it to the list of files and to the inputs of the form
     *
     * @param file   File to upload
     * @param finish Function called when the upload is done, before the notification is shown
     */
    private upload(file: File, finish: () => void) {
        const formData = new FormData();
        formData.append(this.element.name, file);

        post<UploadedFile[]>(window.location.href, formData, (response) => {
            const notification = new Notification(response.message, response.status);

            if (response.status === "success") {
                const data = response.data[0];

                this.addFilesItem(data, $("template[id=files-item]") as HTMLTemplateElement);
                this.filesList.sort(".file-name");
                this.filesList.element.hidden = false;

                addToFileInputs(this.form, data);
            }

            finish();

            notification.show();
        });
    }

    /**
     * Format a file size with the most suitable unit
     *
     * @param size Size in bytes
     */
    private formatFileSize(size: number) {
        const units = ["B", "KB", "MB", "GB", "TB"];
        const exp = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);
        return `${(size / 1024 ** exp).toFixed(2)} ${units[exp]}`;
    }

    /**
     * Update the files shown in the drop target
     *
     * @param files     Files to show
     * @param uploading Whether the files are being uploaded
     */
    private updateDropTargetLabel(files: File[] = Array.from(this.element.files ?? []), uploading: boolean = this.isSubmitted) {
        batch(() => {
            this.files.value = files;
            this.uploading.value = uploading;
        });
    }

    /**
     * Render the files in the drop target, or its default text if there are none
     */
    private renderDropTargetLabel(files: File[], uploading: boolean) {
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

    /**
     * Add the item of an uploaded file to the list of files
     *
     * @param info     File data sent by the server
     * @param template Template of the item
     */
    private addFilesItem(info: UploadedFile, template: HTMLTemplateElement) {
        const node = template.content.cloneNode(true) as HTMLElement;
        const filesItem = $(".files-item", node) as HTMLElement;

        filesItem.dataset.filename = info.name;

        this.setThumbnail(filesItem, info);

        // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
        const icon = icons[toCamelCase(`file-${info.type}`) as keyof typeof icons] || icons.file;

        ($(".file-icon", filesItem) as HTMLElement).innerHTML = icon;

        const anchor = $(".file-name a", filesItem) as HTMLAnchorElement;
        anchor.href = info.actions.info;
        anchor.textContent = info.name;

        ($(".file-date", filesItem) as HTMLElement).textContent = info.lastModifiedTime;
        ($(".file-size", filesItem) as HTMLElement).textContent = info.size;

        ($(".dropdown-button", filesItem) as HTMLElement).dataset.dropdown = `dropdown-${info.hash}`;
        ($(".dropdown-menu", filesItem) as HTMLElement).id = `dropdown-${info.hash}`;

        this.setCommands(filesItem, info);

        $(".files-items", this.filesList.element)?.appendChild(node);
    }

    /**
     * Show the thumbnail of an image or a video in an item, and remove it for the other files
     */
    private setThumbnail(filesItem: HTMLElement, info: UploadedFile) {
        if (info.type === "image") {
            const img = document.createElement("img");
            img.className = "file-thumbnail";
            img.alt = "";
            img.src = info.thumbnail ?? "";
            img.loading = "lazy";
            $(".file-thumbnail", filesItem)?.replaceWith(img);
        } else if (info.type === "video") {
            const video = document.createElement("video");
            video.classList.add("file-thumbnail");
            video.ariaHidden = "true";
            video.src = info.thumbnail ?? "";
            video.preload = "metadata";
            $(".file-thumbnail", filesItem)?.replaceWith(video);
        } else {
            $(".file-thumbnail", filesItem)?.remove();
        }
    }

    /**
     * Set the links and actions of the commands of an item
     */
    private setCommands(filesItem: HTMLElement, info: UploadedFile) {
        const previewFileCommand = $("[data-command=previewFile]", filesItem) as HTMLAnchorElement;
        const replaceFileCommand = $("[data-command=replaceFile]", filesItem) as HTMLElement;

        ($("[data-command=infoFile]", filesItem) as HTMLAnchorElement).href = info.actions.info;

        previewFileCommand.href = info.uri;
        previewFileCommand.target = `formwork-preview-file-${info.hash}`;

        ($("[data-command=renameFile]", filesItem) as HTMLElement).dataset.action = info.actions.rename;

        replaceFileCommand.dataset.action = info.actions.replace;
        replaceFileCommand.dataset.mimetype = `${info.mimeType}`;

        ($("[data-command=deleteFile]", filesItem) as HTMLElement).dataset.action = info.actions.delete;
    }
}
