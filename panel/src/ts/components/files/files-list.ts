import { effect, signal } from "@preact/signals-core";
import { app } from "../../services/app";
import type { Form } from "../../services/form";
import { post } from "../../services/request";
import { initSearchInput, makeSearchRegExp } from "../../utils/search";
import { $, $$ } from "../../utils/selectors";
import { escapeHtml } from "../../utils/validation";
import { Notification } from "../ui/notification";
import { removeFromFileInputs, replaceInFileInputs } from "./file-inputs";

/**
 * Describes the file data sent by the server after a rename
 */
interface RenamedFile {
    filename: string;
    type: string;
    thumbnail?: string;
    uri: string;
    actions: Record<"info" | "rename" | "replace" | "delete", string>;
}

/**
 * Describes the file data sent by the server after a replacement
 */
interface ReplacedFile {
    thumbnail?: string;
    lastModifiedTime: string;
    size: string;
}

/**
 * Handles a list of files with thumbnails or list view, search, multiple selection and file
 * commands
 */
export class FilesList {
    readonly element: HTMLElement;
    readonly form?: Form;
    private selectionAnchor?: HTMLElement;

    private readonly selected = signal(new Set<HTMLElement>());

    // `null` means that no search has been performed yet
    private readonly query = signal<string | null>(null);

    constructor(element: HTMLElement, form?: Form) {
        this.element = element;
        this.form = form;

        this.initThumbnails();
        this.initViewAs();
        this.initSelection();
        this.initReplaceCommand();
        this.initSearch();
        this.initRenameModal();
        this.initDeleteModal();
    }

    /**
     * Sort the items by the text of the elements matching a selector
     *
     * @param selector Selector of the element whose text is the sorting key
     */
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

    /**
     * Load the thumbnails of the files
     */
    private initThumbnails() {
        $$<HTMLImageElement | HTMLVideoElement>(".file-thumbnail[data-src]", this.element).forEach((thumbnail) => {
            thumbnail.addEventListener("error", () => thumbnail.removeAttribute("src"));
            thumbnail.src = thumbnail.dataset.src as string;
        });
    }

    /**
     * Switch between list and thumbnails view and remember the choice
     */
    private initViewAs() {
        const toggle = $(".form-togglegroup.files-list-view-as", this.element);

        if (!toggle) {
            return;
        }

        const formName = this.element.closest("form")?.dataset.form;

        const fieldName = toggle.dataset.for;

        const key = formName ? `${formName}.${fieldName}` : fieldName;
        const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${key}]`);

        if (viewAs) {
            $$<HTMLInputElement>("input", toggle).forEach((input) => {
                input.checked = false;
            });
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

    /**
     * Select files with Ctrl/Cmd and Shift, and open them in thumbnails view
     */
    private initSelection() {
        document.addEventListener("click", (event) => this.handleClick(event));

        effect(() => this.renderSelection());
    }

    /**
     * Handle a click on the page: select the clicked file, or clear the selection
     */
    private handleClick(event: MouseEvent) {
        const target = event.target as HTMLElement;
        const item = target.closest<HTMLElement>(".files-item");

        // Ignore clicks on the files of a different list
        if (!item || !this.element.contains(item)) {
            if (!target.closest(".modal, .files-selection-actions")) {
                this.clearSelection();
            }
            return;
        }

        if (this.hasSelectionActions()) {
            if (target.closest(".dropdown")) {
                this.clearSelection();
                return;
            }

            if (event.ctrlKey || event.metaKey) {
                event.preventDefault();
                this.toggleSelected(item);
                return;
            }

            if (event.shiftKey) {
                event.preventDefault();
                this.selectRange(item);
                return;
            }
        }

        const anchor = $(".file-name a", item) as HTMLAnchorElement;

        if (this.element.classList.contains("is-thumbnails") && anchor.href) {
            this.clearSelection();
            location.href = anchor.href;
        }
    }

    /**
     * Add an item to the selection, or remove it if selected
     */
    private toggleSelected(item: HTMLElement) {
        const selected = new Set(this.selected.value);

        if (!selected.delete(item)) {
            selected.add(item);
        }

        this.selected.value = selected;
        this.selectionAnchor = item;
    }

    /**
     * Select the items between the last one selected with Ctrl/Cmd and an item
     */
    private selectRange(item: HTMLElement) {
        const items = Array.from($$(".files-item", this.element));

        if (!this.selectionAnchor || !items.includes(this.selectionAnchor)) {
            this.selectionAnchor = item;
        }

        const anchorIndex = items.indexOf(this.selectionAnchor);
        const currentIndex = items.indexOf(item);

        const start = Math.min(anchorIndex, currentIndex);
        const end = Math.max(anchorIndex, currentIndex);

        this.selected.value = new Set(items.slice(start, end + 1));
    }

    /**
     * Mark the selected items and show the actions for the selection with the number of items
     */
    private renderSelection() {
        const selected = this.selected.value;
        const selectionActions = $(".files-selection-actions", this.element);

        // Items removed from the list are not counted as selected
        const selectedItems = Array.from($$(".files-item", this.element)).filter((item) => selected.has(item));

        $$(".files-item", this.element).forEach((item) => {
            item.classList.toggle("is-selected", selected.has(item));
        });

        if (selectionActions && selectionActions.children.length > 0) {
            const countElement = $(".files-selection-count", selectionActions) as HTMLElement;

            selectionActions.hidden = selectedItems.length === 0;

            // The counter is left empty until the selection changes
            if (selectedItems.length > 0 || countElement.textContent !== "") {
                countElement.textContent = `(${selectedItems.length})`;
            }
        }
    }

    /**
     * Replace a file by uploading a new one
     */
    private initReplaceCommand() {
        this.element.addEventListener("click", (event) => {
            const command = (event.target as HTMLElement).closest<HTMLElement>("[data-command=replaceFile]");

            if (command) {
                this.chooseReplacement(command);
            }
        });
    }

    /**
     * Ask for a file to replace the one of a command and upload it
     */
    private chooseReplacement(command: HTMLElement) {
        const fileInput = document.createElement("input");
        fileInput.type = "file";
        fileInput.accept = command.dataset.mimetype as string;
        fileInput.click();

        fileInput.addEventListener("change", () => {
            if (fileInput.files?.length) {
                const formData = new FormData();
                formData.append("file", fileInput.files[0]);

                post<ReplacedFile>(command.dataset.action as string, formData, (response) => {
                    const notification = new Notification(response.message, response.status);

                    if (response.status === "success") {
                        if (command.closest("[data-form=file-form]")) {
                            window.location.reload();
                        } else if (response.data.thumbnail) {
                            this.updateReplacedItem(command.closest(".files-item") as HTMLElement, { ...response.data, thumbnail: response.data.thumbnail });
                        }
                    }

                    notification.show();
                });
            }

            fileInput.remove();
        });
    }

    /**
     * Show the thumbnail, date and size of a replaced file
     */
    private updateReplacedItem(item: HTMLElement, data: ReplacedFile & { thumbnail: string }) {
        ($(".file-thumbnail", item) as HTMLImageElement | HTMLVideoElement).src = data.thumbnail;
        ($(".file-date", item) as HTMLElement).textContent = data.lastModifiedTime;
        ($(".file-size", item) as HTMLElement).textContent = data.size;
    }

    /**
     * Filter the files by name and parent, highlighting the matches
     */
    private initSearch() {
        const searchInput = $(".files-search", this.element) as HTMLInputElement;

        if (!searchInput) {
            return;
        }

        effect(() => {
            if (this.query.value !== null) {
                this.filterItems(this.query.value);
            }
        });

        initSearchInput(searchInput, () => {
            this.query.value = escapeHtml(searchInput.value);
        });
    }

    /**
     * Show only the files matching a search, highlighting the matches
     */
    private filterItems(query: string) {
        const regexp = query ? makeSearchRegExp(query, { wordStart: false, global: true }) : null;

        ($(".files-item") as HTMLElement).classList.toggle("is-filtered", query.length > 0);

        $$(".files-item").forEach((item) => {
            const matches = this.highlightMatches(item, regexp);

            item.style.display = !query || matches > 0 ? "" : "none";
        });
    }

    /**
     * Highlight the matches in the name and in the parent of a file and return how many there are
     */
    private highlightMatches(item: HTMLElement, regexp: RegExp | null) {
        let matches = 0;

        for (const selector of [".file-name a", ".file-parent-title"]) {
            const element = $(selector, item);

            if (!element) {
                continue;
            }

            const text = escapeHtml(element.textContent);

            if (regexp && text.match(regexp) !== null) {
                element.innerHTML = text.replace(regexp, "<mark>$&</mark>");
                matches++;
            } else {
                element.innerHTML = text;
            }
        }

        return matches;
    }

    /**
     * Initialize the modal to rename a file
     */
    private initRenameModal() {
        const renameFileItemModal = app.modals.renameFileItemModal;

        if (!renameFileItemModal) {
            return;
        }

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

            post<RenamedFile>(
                action as string,
                {
                    filename: filename as string,
                    "renameFileItemModal[filename]": (input as HTMLInputElement).value,
                },
                (response) => {
                    if (response.status === "success") {
                        const data = response.data;

                        this.updateItem(item as HTMLElement, data);
                        replaceInFileInputs(this.form, filename as string, { name: data.filename, type: data.type, thumbnail: data.thumbnail });
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

    /**
     * Initialize the modal to delete a file or the selected files
     */
    private initDeleteModal() {
        const deleteFileItemModal = app.modals.deleteFileItemModal;

        if (!deleteFileItemModal) {
            return;
        }

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
                const selectedItems = this.selectedItems();

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
                this.deleteItems(items as HTMLElement[]);

                modal.close();
                return;
            }

            post(
                action as string,
                {
                    filename: filename as string,
                },
                (response) => {
                    if (response.status === "success") {
                        (item as HTMLElement).remove();

                        if (this.element.querySelectorAll(".files-item").length === 0) {
                            this.element.hidden = true;
                        }

                        removeFromFileInputs(this.form, filename as string);
                    }

                    const notification = new Notification(response.message, response.status);
                    notification.show();

                    modal.close();
                },
            );

            modal.close();
        });
    }

    /**
     * Delete the selected files and show a single notification when all requests are done
     */
    private deleteItems(selectedItems: HTMLElement[]) {
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

            post(
                itemAction,
                {
                    filename: itemFilename as string,
                },
                (response) => {
                    pending -= 1;
                    lastMessage = response.message;

                    if (response.status === "success") {
                        deletedCount += 1;
                        selectedItem.remove();

                        removeFromFileInputs(this.form, itemFilename as string);
                    }

                    if (pending === 0) {
                        if (this.element.querySelectorAll(".files-item").length === 0) {
                            this.element.hidden = true;
                        }

                        this.selected.value = new Set(this.selectedItems());

                        const allDeleted = deletedCount === selectedItems.length;
                        const message = allDeleted && deletedCount > 1 ? app.translation.get("panel.files.deleted.multiple").replace("%d", String(deletedCount)) : lastMessage;
                        const notification = new Notification(message, allDeleted ? "success" : "error");
                        notification.show();
                    }
                },
            );
        });
    }

    /**
     * Update an item after the file has been renamed
     */
    private updateItem(item: HTMLElement, data: RenamedFile) {
        item.dataset.filename = data.filename;

        const anchor = $(".file-name a", item) as HTMLAnchorElement;
        anchor.textContent = data.filename;
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
    }

    /**
     * Return whether the list has actions for the selected files
     */
    private hasSelectionActions() {
        const selectionActions = $(".files-selection-actions", this.element);
        return !!selectionActions && selectionActions.children.length > 0;
    }

    /**
     * Get the selected items that are still in the list
     */
    private selectedItems() {
        return Array.from($$(".files-item", this.element)).filter((item) => this.selected.value.has(item));
    }

    /**
     * Deselect all items
     */
    private clearSelection() {
        if (this.selectedItems().length === 0) {
            return;
        }
        this.selected.value = new Set();
        this.selectionAnchor = undefined;
    }
}
