import { effect, signal } from "@preact/signals-core";
import type { MoveEvent, SortableEvent } from "sortablejs";
import type { SelectInput } from "../components/inputs/select-input";
import { Notification } from "../components/ui/notification";
import { type PagesTree, PagesTreeModel } from "../models/pages-tree";
import { app } from "../services/app";
import type { Form } from "../services/form";
import type { Modal } from "../services/modal";
import { post } from "../services/request";
import { debounce } from "../utils/events";
import { initSearchInput } from "../utils/search";
import { $, $$ } from "../utils/selectors";
import { escapeHtml, makeSlug } from "../utils/validation";

/**
 * Initialize the tree of the pages
 */
export function initPagesTree() {
    const tree = new PagesTreeModel($("[data-command=reorder-pages]")?.classList.contains("active") ?? false);

    renderTree(tree);
    initTreeToggles(tree);
    initTreeCommands(tree);
    initSearch(tree);
    initDeletePageModal(tree);
}

/**
 * Update the classes of the tree items from the model
 */
function renderTree(tree: PagesTree) {
    effect(() => {
        for (const item of tree.items.value) {
            const isExpanded = tree.filtering.value || tree.expanded.value.has(item);

            item.classList.toggle("has-children", tree.parents.value.has(item));
            item.classList.toggle("is-expanded", isExpanded);

            $(".pages-tree-children-toggle button", item)?.setAttribute("aria-expanded", String(isExpanded));
        }
    });
}

/**
 * Expand or collapse a page when its details are clicked
 */
function initTreeToggles(tree: PagesTree) {
    $$(".page-details").forEach((element) => {
        if ($(".pages-tree-children-toggle", element)) {
            element.addEventListener("click", (event) => {
                const target = event.target as HTMLElement;
                if (target.closest("a, .sortable-handle")) {
                    return;
                }
                const item = element.closest<HTMLElement>(".pages-tree-item");
                if (item) {
                    tree.toggle(item);
                }
                event.preventDefault();
            });
        }
    });
}

/**
 * Initialize the commands to expand, collapse and reorder the pages
 */
function initTreeCommands(tree: PagesTree) {
    bindCommand(
        "expand-all-pages",
        () => tree.expandAll(),
        () => tree.expandable.value.length === tree.expandedCount.value,
    );

    bindCommand(
        "collapse-all-pages",
        () => tree.collapseAll(),
        () => tree.expandedCount.value === 0,
    );

    const reorderCommand = bindCommand(
        "reorder-pages",
        () => tree.toggleReordering(),
        () => tree.orderableCount.value <= 1,
    );

    if (reorderCommand) {
        effect(() => {
            reorderCommand.classList.toggle("active", tree.reordering.value);
            ($(".pages-tree") as HTMLElement).classList.toggle("is-reordering", tree.reordering.value);
        });
    }
}

/**
 * Bind a command of the tree, if the page has it: the button runs an action and is disabled
 * according to the state of the tree
 *
 * @param name       Value of the `data-command` attribute
 * @param action     Function called when the button is clicked
 * @param isDisabled Function returning whether the button is disabled
 */
function bindCommand(name: string, action: () => void, isDisabled: () => boolean) {
    const button = $<HTMLButtonElement>(`[data-command=${name}]`);

    if (!button) {
        return null;
    }

    button.addEventListener("click", () => {
        action();
        button.blur();
    });

    effect(() => {
        button.disabled = isDisabled();
    });

    return button;
}

/**
 * Filter the tree while typing in the search field
 */
function initSearch(tree: PagesTree) {
    const searchInput = $(".page-search") as HTMLInputElement;

    if (!searchInput) {
        return;
    }

    effect(() => {
        if (tree.query.value !== null) {
            filterPages(tree);
        }
    });

    initSearchInput(searchInput, () => tree.search(escapeHtml(searchInput.value)));
}

/**
 * Show only the pages matching the search of the tree, highlighting the matches
 */
function filterPages(tree: PagesTree) {
    const regexp = tree.searchRegExp.value;

    ($(".pages-tree-root") as HTMLElement).classList.toggle("is-filtered", regexp !== null);

    for (const item of tree.items.value) {
        const title = $(".page-title a", item) as HTMLElement;
        const row = $(".pages-tree-row", item) as HTMLElement;

        if (regexp === null) {
            row.style.display = "";
            continue;
        }

        const text = escapeHtml(title.textContent);

        if (text.match(regexp) !== null) {
            title.innerHTML = text.replace(regexp, "<mark>$&</mark>");
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    }
}

/**
 * Allow only the templates accepted by the parent of the new page
 */
export function initNewPageModal(newPageModal: Modal) {
    const form = newPageModal.form as Form;

    removeCreateNewParameter();

    const parentSelect = form.inputs["newPageModal[parent]"] as SelectInput;
    const templateSelect = form.inputs["newPageModal[template]"] as SelectInput;

    parentSelect.element.addEventListener("change", () => limitTemplates(parentSelect, templateSelect));

    limitTemplates(parentSelect, templateSelect);
}

/**
 * Remove the `createNew` parameter, which opens the modal, from the address of the page
 */
function removeCreateNewParameter() {
    const url = new URL(window.location.href);

    if (url.searchParams.has("createNew")) {
        url.searchParams.delete("createNew");
        window.history.replaceState({}, document.title, url.toString());
    }
}

/**
 * Disable the templates that the selected parent does not accept, selecting the first allowed one
 *
 * When the parent accepts all templates the previous selection is restored.
 *
 * @param parentSelect   Select of the parent page
 * @param templateSelect Select of the template
 */
function limitTemplates(parentSelect: SelectInput, templateSelect: SelectInput) {
    const parent = parentSelect.selectedDropdownItem;

    if (!parent) {
        return;
    }

    const allowedTemplates = parent.dataset.allowedTemplates ? parent.dataset.allowedTemplates.split(" ") : [];

    if (allowedTemplates.length > 0) {
        templateSelect.element.dataset.previousValue = templateSelect.value;

        templateSelect.value = allowedTemplates[0];

        templateSelect.dropdownItems.forEach((item) => {
            if (!allowedTemplates.includes(item.dataset.value as string)) {
                item.classList.add("disabled");
            }
        });
    } else {
        if ("previousValue" in templateSelect.element.dataset) {
            templateSelect.value = templateSelect.element.dataset.previousValue as string;
            delete templateSelect.element.dataset.previousValue;
        }

        templateSelect.dropdownItems.forEach((item) => {
            item.classList.remove("disabled");
        });
    }
}

/**
 * Initialize the modal to delete a page
 */
function initDeletePageModal(tree: PagesTree) {
    const deletePageItemModal = app.modals.deletePageItemModal;

    if (!deletePageItemModal) {
        return;
    }

    deletePageItemModal.onOpen((modal, trigger) => {
        if (trigger) {
            Object.assign(modal.data, {
                action: trigger.dataset.action,
                pageItem: trigger.closest(".pages-tree-item"),
            });
        }
    });

    deletePageItemModal.onCommand("delete-page", (modal) => {
        const { action, pageItem } = modal.data as { action: string; pageItem: HTMLElement };

        post(action, {}, (response) => {
            if (response.status === "success" && pageItem) {
                removePage(tree, pageItem);
            }

            const notification = new Notification(response.message, response.status);
            notification.show();

            modal.close();
        });
    });
}

/**
 * Remove a deleted page from the tree, enabling the commands of its parent if it has no children
 * left
 */
function removePage(tree: PagesTree, pageItem: HTMLElement) {
    const parentItem = pageItem.parentElement?.closest<HTMLElement>(".pages-tree-item");
    pageItem.remove();

    const emptiedParent = tree.remove(pageItem, parentItem);

    if (emptiedParent) {
        for (const modalId of ["deletePageItemModal", "duplicatePageModal"]) {
            const button = $<HTMLButtonElement>(`[data-modal=${modalId}]`, emptiedParent);

            if (button) {
                button.disabled = false;
            }
        }
    }
}

/**
 * Fill the title and the slug of the page to duplicate
 */
export function initDuplicatePageModal(duplicatePageModal: Modal) {
    duplicatePageModal.onOpen((modal, trigger) => {
        if (trigger && modal.form) {
            const duplicateTitle = trigger.dataset.duplicateTitle;

            if (duplicateTitle) {
                const title = $('[name="duplicatePageModal[title]"]', modal.form.element) as HTMLInputElement;
                const slug = $('[name="duplicatePageModal[slug]"]', modal.form.element) as HTMLInputElement;
                title.value = duplicateTitle;
                slug.value = makeSlug(duplicateTitle);
                title.setSelectionRange(0, title.value.length);
            }
        }
    });
}

/**
 * Disable the preview if the parent page has changed, and mark it if the form has unsaved changes
 */
export function initPagePreview(editorFormElement: HTMLElement) {
    const commandPreview = $("[data-command=preview]") as HTMLButtonElement;
    const editorForm = app.forms[editorFormElement.dataset.form as string];

    if (!commandPreview) {
        return;
    }

    const parentInput = editorForm.inputs.parent;
    const previousParent = parentInput?.value;

    const parentChanged = signal(false);

    editorForm.element.addEventListener(
        "input",
        debounce(() => {
            parentChanged.value = parentInput?.value !== previousParent;
        }, 500),
    );

    effect(() => {
        commandPreview.disabled = parentChanged.value;
        commandPreview.classList.toggle("button-indicator", !parentChanged.value && editorForm.changed.value);
    });
}

/**
 * Make the pages of a tree level sortable
 */
export function initSortable(element: HTMLElement) {
    void createSortable(element);
}

/**
 * Make the pages of a tree level sortable and save the new order
 *
 * If the server refuses the new order the previous one is restored
 */
async function createSortable(element: HTMLElement) {
    const { default: Sortable } = await import("sortablejs");

    let originalOrder: string[] = [];

    const sortable = Sortable.create(element, {
        handle: ".sortable-handle",
        filter: ".is-not-orderable",
        forceFallback: true,
        swapThreshold: 0.75,
        invertSwap: true,
        animation: 150,
        preventOnFilter: false, // Workaround to allow touch events on iOS

        onChoose() {
            const height = document.body.offsetHeight;
            document.body.style.height = `${height}px`;

            const e = () => {
                window.document.body.style.height = "";
                window.removeEventListener("scroll", e);
            };
            window.addEventListener("scroll", e);
        },

        onStart() {
            element.classList.add("is-dragging");
        },

        onMove(event: MoveEvent) {
            if (event.related.classList.contains("is-not-orderable")) {
                return false;
            }
        },

        onEnd(event: SortableEvent) {
            element.classList.remove("is-dragging");

            document.body.style.height = "";

            if (event.newIndex === event.oldIndex) {
                return;
            }

            sortable.option("disabled", true);

            const data = {
                page: event.item.dataset.route,
                before: (event.item.nextElementSibling as HTMLElement).dataset.route,
                parent: element.dataset.parent,
            };

            post(`${app.config.baseUri}pages/reorder/`, data, (response) => {
                if (response.status) {
                    const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
                    notification.show();
                }
                if (!response.status || response.status === "error") {
                    sortable.sort(originalOrder);
                }
                sortable.option("disabled", false);
                originalOrder = sortable.toArray();
            });
        },
    });

    originalOrder = sortable.toArray();
}
