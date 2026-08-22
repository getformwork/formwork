import type * as icons from "./icons";
import { $, $$ } from "../utils/selectors";
import { escapeHtml, escapeRegExp, makeDiacriticsRegExp } from "../utils/validation";
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
    private selectionAnchor?: HTMLElement;

    constructor(element: HTMLElement, form?: Form) {
        this.element = element;
        this.form = form;

        this.initFileList();
        this.initModals();
    }

    sort(selector: string = ".file-name") {
        const filesItems = $$(".files-item", this.element);
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

    private initFileList() {
        const toggle = $(".form-togglegroup.files-list-view-as", this.element);
        const searchInput = $(".files-search", this.element) as HTMLInputElement;

        $$<HTMLImageElement | HTMLVideoElement>(".file-thumbnail[data-src]", this.element).forEach((thumbnail) => {
            thumbnail.addEventListener("error", () => thumbnail.removeAttribute("src"));
            thumbnail.src = thumbnail.dataset.src as string;
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
                this.element.classList.toggle("is-list", viewAs === "list");
            }

            $$<HTMLInputElement>("input", toggle).forEach((input) => {
                input.addEventListener("input", () => {
                    this.element.classList.toggle("is-thumbnails", input.value === "thumbnails");
                    this.element.classList.toggle("is-list", input.value === "list");
                    window.localStorage.setItem(`formwork.filesListViewAs[${key}]`, input.value);
                });
            });
        }

        document.addEventListener("click", (event) => {
            const target = event.target as HTMLElement;
            const filesItem = target.closest<HTMLElement>(".files-item");

            if (!filesItem || !this.element.contains(filesItem)) {
                if (!target.closest(".modal, .files-selection-actions")) {
                    this.clearSelection();
                }
                // Ignore clicks on file items that belong to a different FilesList instance
                return;
            }

            if (this.hasSelectionActions()) {
                if (target.closest(".dropdown")) {
                    this.clearSelection();
                    return;
                }

                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    filesItem.classList.toggle("is-selected");
                    this.selectionAnchor = filesItem;
                    this.updateSelectionActions();
                    return;
                }

                if (event.shiftKey) {
                    event.preventDefault();
                    const items = Array.from($$(".files-item", this.element));
                    const currentIndex = items.indexOf(filesItem);

                    if (!this.selectionAnchor || !items.includes(this.selectionAnchor)) {
                        this.selectionAnchor = filesItem;
                    }

                    const anchorIndex = items.indexOf(this.selectionAnchor);
                    const start = Math.min(anchorIndex, currentIndex);
                    const end = Math.max(anchorIndex, currentIndex);

                    items.forEach((element, index) => element.classList.toggle("is-selected", index >= start && index <= end));
                    this.updateSelectionActions();
                    return;
                }
            }

            const anchor = $(".file-name a", filesItem) as HTMLAnchorElement;
            if (this.element.classList.contains("is-thumbnails") && anchor.href) {
                this.clearSelection();
                location.href = anchor.href;
            }
        });

        this.element.addEventListener("click", (event) => {
            const element = (event.target as HTMLElement).closest("[data-command=replaceFile]") as HTMLElement;
            if (element) {
                const fileInput = document.createElement("input");
                fileInput.type = "file";
                fileInput.accept = element.dataset.mimetype as string;
                fileInput.click();

                fileInput.addEventListener("change", () => {
                    if (fileInput.files?.length) {
                        const formData = new FormData();
                        formData.append("csrf-token", app.config.csrfToken as string);
                        formData.append("file", fileInput.files[0]);

                        new Request(
                            {
                                method: "POST",
                                url: element.dataset.action,
                                data: formData,
                            },
                            (response) => {
                                const notification = new Notification(response.message, response.status);

                                if (response.status === "success") {
                                    if (element.closest("[data-form=file-form]")) {
                                        window.location.reload();
                                    } else if (response.data.thumbnail) {
                                        const thumbnail = $(".file-thumbnail", element.closest(".files-item") as HTMLElement) as HTMLImageElement | HTMLVideoElement;
                                        thumbnail.src = response.data.thumbnail;

                                        const fileDate = $(".file-date", element.closest(".files-item") as HTMLElement) as HTMLElement;
                                        fileDate.textContent = response.data.lastModifiedTime;

                                        const fileSize = $(".file-size", element.closest(".files-item") as HTMLElement) as HTMLElement;
                                        fileSize.textContent = response.data.size;
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

        if (searchInput) {
            const handleSearch = () => {
                const value = escapeHtml(searchInput.value);
                ($(".files-item") as HTMLElement).classList.toggle("is-filtered", value.length > 0);

                $$(".files-item").forEach((element) => {
                    let matches = 0;

                    for (const selector of [".file-name a", ".file-parent-title"]) {
                        const item = $(selector, element) as HTMLElement;

                        if (!item) {
                            continue;
                        }

                        const text = escapeHtml(item.textContent);

                        const regexp = value ? new RegExp(`${makeDiacriticsRegExp(escapeRegExp(value))}`, "gi") : null;

                        if (regexp && text.match(regexp) !== null) {
                            item.innerHTML = text.replace(regexp, "<mark>$&</mark>");
                            matches++;
                        } else {
                            item.innerHTML = text;
                        }
                    }

                    if (!value || matches > 0) {
                        element.style.display = "";
                    } else {
                        element.style.display = "none";
                    }
                });
            };

            searchInput.addEventListener("keyup", debounce(handleSearch, 100));
            searchInput.addEventListener("search", handleSearch);

            document.addEventListener("keydown", (event) => {
                if (event.ctrlKey || event.metaKey) {
                    if (event.key === "f" && document.activeElement !== searchInput) {
                        searchInput.focus();
                        event.preventDefault();
                    }
                }
            });
        }
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
                    input.value = (trigger.closest("[data-filename]") as HTMLElement)?.dataset.filename as string;
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
                const { action, item, filename, input } = modal.data;

                new Request(
                    {
                        method: "POST",
                        url: action as string,
                        data: {
                            filename,
                            "renameFileItemModal[filename]": (input as HTMLInputElement).value,
                            "csrf-token": app.config.csrfToken as string,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            const data = response.data;

                            (item as HTMLElement).dataset.filename = data.filename;

                            const anchor = $(".file-name a", item as HTMLElement) as HTMLAnchorElement;
                            anchor.innerText = data.filename;
                            anchor.href = data.uri;

                            ($("[data-command=infoFile]", item as HTMLElement) as HTMLAnchorElement).href = data.actions.info;
                            ($("[data-command=previewFile]", item as HTMLElement) as HTMLAnchorElement).href = data.uri;
                            ($("[data-command=renameFile]", item as HTMLElement) as HTMLElement).dataset.action = data.actions.rename;
                            ($("[data-command=replaceFile]", item as HTMLElement) as HTMLElement).dataset.action = data.actions.replace;
                            ($("[data-command=deleteFile]", item as HTMLElement) as HTMLElement).dataset.action = data.actions.delete;

                            if (data.thumbnail) {
                                const thumbnail = $(".file-thumbnail", item as HTMLElement) as HTMLImageElement | HTMLVideoElement;
                                thumbnail.src = data.thumbnail;
                            }

                            if (this.form) {
                                for (const name in this.form.inputs) {
                                    const input = this.form.inputs[name];

                                    if (input instanceof SelectInput && (input.element.classList.contains("form-file") || input.element.classList.contains("form-image"))) {
                                        input.removeOption(filename as string);
                                        input.addOption({
                                            label: data.filename,
                                            value: data.filename,
                                            thumb: data.type === "image" ? data.thumbnail : undefined,
                                            icon: `file-${data.type}`,
                                        });
                                        input.sortDropdownItems();
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                        input.removeDropdownItem(filename as string);
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
                if (!trigger) {
                    return;
                }

                const item = trigger.closest<HTMLElement>(".files-item");

                if (item) {
                    Object.assign(modal.data, {
                        action: trigger.dataset.action,
                        item,
                        filename: item.dataset.filename,
                        items: undefined,
                    });

                    modal.setMessage(app.translation.get("panel.pages.deleteFile.prompt"));
                } else if (trigger.closest(".files-selection-actions")) {
                    const selectedItems = $$(".files-item.is-selected", this.element);

                    Object.assign(modal.data, {
                        action: undefined,
                        item: undefined,
                        filename: undefined,
                        items: selectedItems,
                    });

                    if (selectedItems.length > 1) {
                        modal.setMessage(app.translation.get("panel.pages.deleteFile.multiple.prompt").replace("%d", String(selectedItems.length)));
                    } else {
                        modal.setMessage(app.translation.get("panel.pages.deleteFile.prompt"));
                    }
                }
            });

            deleteFileItemModal.onCommand("delete-file", (modal) => {
                const { action, item, filename, items } = modal.data;

                if (items) {
                    const selectedItems = items as HTMLElement[];

                    let pending = selectedItems.length;
                    let deletedCount = 0;
                    let lastMessage = "";

                    selectedItems.forEach((selectedItem) => {
                        const deleteCommand = $("[data-command=deleteFile]", selectedItem);
                        const itemAction = deleteCommand?.dataset.action;
                        const itemFilename = selectedItem.dataset.filename;

                        if (!itemAction) {
                            pending -= 1;
                            return;
                        }

                        new Request(
                            {
                                method: "POST",
                                url: itemAction,
                                data: {
                                    filename: itemFilename,
                                    "csrf-token": app.config.csrfToken as string,
                                },
                            },
                            (response) => {
                                pending -= 1;
                                lastMessage = response.message;

                                if (response.status === "success") {
                                    deletedCount += 1;
                                    selectedItem.remove();

                                    if (this.form) {
                                        for (const name in this.form.inputs) {
                                            const input = this.form.inputs[name];
                                            if (input instanceof SelectInput && (input.element.classList.contains("form-file") || input.element.classList.contains("form-image"))) {
                                                input.removeOption(itemFilename as string);
                                            }

                                            if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                                input.removeDropdownItem(itemFilename as string);
                                            }
                                        }
                                    }
                                }

                                if (pending === 0) {
                                    if (this.element.querySelectorAll(".files-item").length === 0) {
                                        this.element.hidden = true;
                                    }

                                    this.updateSelectionActions();

                                    const allDeleted = deletedCount === selectedItems.length;
                                    const message = allDeleted && deletedCount > 1 ? app.translation.get("panel.files.deleted.multiple").replace("%d", String(deletedCount)) : lastMessage;
                                    const notification = new Notification(message, allDeleted ? "success" : "error");
                                    notification.show();
                                }
                            },
                        );
                    });

                    modal.close();
                    return;
                }

                new Request(
                    {
                        method: "POST",
                        url: action as string,
                        data: {
                            filename,
                            "csrf-token": app.config.csrfToken as string,
                        },
                    },
                    (response) => {
                        if (response.status === "success") {
                            (item as HTMLElement).remove();

                            if (this.element.querySelectorAll(".files-item").length === 0) {
                                this.element.hidden = true;
                            }

                            if (this.form) {
                                for (const name in this.form.inputs) {
                                    const input = this.form.inputs[name];
                                    if (input instanceof SelectInput && (input.element.classList.contains("form-file") || input.element.classList.contains("form-image"))) {
                                        input.removeOption(filename as string);
                                    }

                                    if (input instanceof TagsInput && (input.element.classList.contains("form-files") || input.element.classList.contains("form-images"))) {
                                        input.removeDropdownItem(filename as string);
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

    private hasSelectionActions() {
        const selectionActions = $(".files-selection-actions", this.element);
        return !!selectionActions && selectionActions.children.length > 0;
    }

    private clearSelection() {
        const selectedItems = $$(".files-item.is-selected", this.element);
        if (selectedItems.length === 0) {
            return;
        }
        selectedItems.forEach((element) => element.classList.remove("is-selected"));
        this.selectionAnchor = undefined;
        this.updateSelectionActions();
    }

    private updateSelectionActions() {
        const selectionActions = $(".files-selection-actions", this.element);
        if (selectionActions && selectionActions.children.length > 0) {
            const selectedItems = $$(".files-item.is-selected", this.element);
            selectionActions.hidden = selectedItems.length === 0;
            const countElement = $(".files-selection-count", selectionActions) as HTMLElement;
            countElement.textContent = `(${selectedItems.length})`;
        }
    }
}
