import type * as icons from "./icons";
import { $, $$ } from "../utils/selectors";
import { escapeHtml, makeSearchRegExp } from "../utils/validation";
import { app } from "../app";
import { debounce } from "../utils/events";
import type { Form } from "./form";
import { Notification } from "./notification";
import { Request } from "../utils/request";
import { SelectInput } from "./inputs/select-input";
import { TagsInput } from "./inputs/tags-input";
import { toCamelCase } from "../utils/strings";

export class FilesList {
    readonly element: HTMLElement;
    readonly form?: Form;

    constructor(element: HTMLElement, form?: Form) {
        this.element = element;
        this.form = form;

        this.initFileList();
        this.initModals();
    }

    sort(selector: string = ".file-name") {
        const filesItems = $$(".files-item", this.element);
        [...filesItems]
            .sort((a, b) => {
                const keyA = $(selector, a)?.textContent;
                const keyB = $(selector, b)?.textContent;
                return keyA?.localeCompare(keyB ?? "") ?? 0;
            })
            .forEach((element) => {
                element.parentElement?.appendChild(element);
            });
    }

    private initFileList() {
        const toggle = $(".form-togglegroup.files-list-view-as", this.element);
        const searchInput = $(".files-search", this.element) as HTMLInputElement;

        $$<HTMLImageElement | HTMLVideoElement>(".file-thumbnail[data-src]", this.element).forEach((thumbnail) => {
            thumbnail.addEventListener("error", () => thumbnail.removeAttribute("src"));
            thumbnail.src = thumbnail.dataset.src ?? "";
        });

        if (toggle) {
            const formName = this.element.closest("form")?.dataset.form;

            const fieldName = toggle.dataset.for;

            const key = formName ? `${formName}.${fieldName}` : fieldName;
            const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${key}]`);

            if (viewAs) {
                $$<HTMLInputElement>("input", toggle).forEach((input) => (input.checked = false));
                ($(`input[value=${viewAs}]`, this.element) as HTMLInputElement).checked = true;
                this.element.classList.toggle("is-thumbnails", viewAs === "thumbnails");
            }

            $$<HTMLInputElement>("input", toggle).forEach((input) => {
                input.addEventListener("input", () => {
                    this.element.classList.toggle("is-thumbnails", input.value === "thumbnails");
                    window.localStorage.setItem(`formwork.filesListViewAs[${key}]`, input.value);
                });
            });
        }

        document.addEventListener("click", (event) => {
            const target = event.target as HTMLElement;
            if (!target.closest(".dropdown") && target.closest(".files-item")) {
                const item = target.closest(".files-item") as HTMLElement;
                const list = item.closest(".files-list") as HTMLElement;
                const anchor = $(".file-name a", item) as HTMLAnchorElement;
                if (list.classList.contains("is-thumbnails") && anchor.href) {
                    location.href = anchor.href;
                }
            }
        });

        this.element.addEventListener("click", (event) => {
            const element = (event.target as HTMLElement).closest("[data-command=replaceFile]") as HTMLElement;
            if (!element) {
                return;
            }

            this.openReplaceFilePicker(element);
        });

        if (searchInput) {
            const handleSearch = () => this.filterFiles(searchInput.value);

            searchInput.addEventListener("keyup", debounce(handleSearch, 100));
            searchInput.addEventListener("search", handleSearch);

            document.addEventListener("keydown", (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key === "f" && document.activeElement !== searchInput) {
                    searchInput.focus();
                    event.preventDefault();
                }
            });
        }
    }

    private openReplaceFilePicker(element: HTMLElement) {
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.accept = element.dataset.mimetype ?? "";
        fileInput.click();

        fileInput.addEventListener("change", () => {
            const file = fileInput.files?.[0];
            if (file) {
                this.replaceFile(element, file);
            }

            fileInput.remove();
        });
    }

    private replaceFile(element: HTMLElement, file: File) {
        const formData = new FormData();
        formData.append("csrf-token", app.config.csrfToken);
        formData.append("file", file);

        new Request(
            {
                method: "POST",
                url: element.dataset.action,
                data: formData,
            },
            (response) => {
                if (response.status === "success") {
                    this.handleFileReplaceSuccess(element, response.data);
                }

                new Notification(response.message, response.status).show();
            },
        );
    }

    private handleFileReplaceSuccess(element: HTMLElement, data: Record<string, string>) {
        if (element.closest("[data-form=file-form]")) {
            window.location.reload();
            return;
        }

        if (!data.thumbnail) {
            return;
        }

        const filesItem = element.closest(".files-item") as HTMLElement;
        const thumbnail = $(".file-thumbnail", filesItem) as HTMLImageElement | HTMLVideoElement;
        thumbnail.src = data.thumbnail;

        const fileDate = $(".file-date", filesItem) as HTMLElement;
        fileDate.textContent = data.lastModifiedTime;

        const fileSize = $(".file-size", filesItem) as HTMLElement;
        fileSize.textContent = data.size;
    }

    private filterFiles(rawValue: string) {
        const value = escapeHtml(rawValue);
        const regexp = value ? makeSearchRegExp(value, "gi") : null;

        $(".files-item")?.classList.toggle("is-filtered", value.length > 0);

        $$(".files-item").forEach((element) => {
            const matches = this.highlightFileSearchMatches(element, regexp);
            element.style.display = !value || matches > 0 ? "" : "none";
        });
    }

    private highlightFileSearchMatches(element: HTMLElement, regexp: RegExp | null) {
        let matches = 0;

        for (const selector of [".file-name a", ".file-parent-title"]) {
            const item = $(selector, element) as HTMLElement;
            if (!item) {
                continue;
            }

            const text = escapeHtml(item.textContent);
            const hasMatch = regexp ? text.match(regexp) !== null : false;
            item.innerHTML = hasMatch ? text.replace(regexp!, "<mark>$&</mark>") : text;

            if (hasMatch) {
                matches++;
            }
        }

        return matches;
    }

    private initModals() {
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
                    input.value = (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename ?? "";
                    input.setSelectionRange(0, input.value.lastIndexOf("."));

                    Object.assign(modal.data, {
                        action: trigger.dataset.action,
                        item: trigger.closest(".files-item"),
                        filename: (trigger.closest(".files-item") as HTMLElement)?.dataset.filename,
                        input,
                    });
                }
            });

            renameFileItemModal.onCommand("rename-file", (modal) => {
                const { action, item, filename, input } = modal.data as { action: string; item: HTMLElement; filename: string; input: HTMLInputElement };

                new Request(
                    {
                        method: "POST",
                        url: action,
                        data: {
                            filename,
                            "renameFileItemModal[filename]": input.value,
                            "csrf-token": app.config.csrfToken,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            const data = response.data;

                            item.dataset.filename = data.filename;

                            const anchor = $(".file-name a", item) as HTMLAnchorElement;
                            anchor.innerText = data.filename;
                            anchor.href = data.uri;

                            ($("[data-command=infoFile]", item) as HTMLAnchorElement).href = data.actions.info;
                            ($("[data-command=previewFile]", item) as HTMLAnchorElement).href = data.uri;
                            ($("[data-command=renameFile]", item) as HTMLElement).dataset.action = data.actions.rename;
                            ($("[data-command=replaceFile]", item) as HTMLElement).dataset.action = data.actions.replace;
                            ($("[data-command=deleteFile]", item) as HTMLElement).dataset.action = data.actions.delete;

                            if (data.thumbnail) {
                                const thumbnail = $(".file-thumbnail", item) as HTMLImageElement | HTMLVideoElement;
                                thumbnail.src = data.thumbnail;
                            }

                            if (this.form) {
                                for (const input of Object.values(this.form.inputs)) {
                                    if (input instanceof SelectInput && (input.element.classList.contains("form-file") || input.element.classList.contains("form-image"))) {
                                        input.removeOption(filename);
                                        input.addOption({
                                            label: data.filename,
                                            value: data.filename,
                                            thumb: data.type === "image" ? data.thumbnail : undefined,
                                            icon: `file-${data.type}`,
                                        });
                                        input.sortDropdownItems();
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                        input.removeDropdownItem(filename);
                                        input.addDropdownItem({
                                            label: data.filename,
                                            value: data.filename,
                                            thumb: data.type === "image" ? data.thumbnail : undefined,
                                            icon: toCamelCase(`file-${data.type}`) as keyof typeof icons,
                                        });
                                        input.sortDropdownItems();
                                    }
                                }
                            }

                            this.sort(".file-name");
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
                        filename: (trigger.closest(".files-item") as HTMLElement)?.dataset.filename,
                    });
                }
            });

            deleteFileItemModal.onCommand("delete-file", (modal) => {
                const { action, item, filename } = modal.data as { action: string; item: HTMLElement; filename: string };

                new Request(
                    {
                        method: "POST",
                        url: action,
                        data: {
                            filename,
                            "csrf-token": app.config.csrfToken,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            item.remove();

                            if (this.element.querySelectorAll(".files-item").length === 0) {
                                this.element.hidden = true;
                            }

                            if (this.form) {
                                for (const input of Object.values(this.form.inputs)) {
                                    if (input instanceof SelectInput && (input.element.classList.contains("form-file") || input.element.classList.contains("form-image"))) {
                                        input.removeOption(filename);
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                        input.removeDropdownItem(filename);
                                    }
                                }
                            }
                        }

                        const notification = new Notification(response.message, response.status);
                        notification.show();

                        modal.close();
                    },
                );

                modal.close();
            });
        }
    }
}
