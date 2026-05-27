import { $, $$ } from "../../utils/selectors";
import { escapeHtml, makeSearchRegExp, makeSlug } from "../../utils/validation";
import type { MoveEvent, SortableEvent } from "sortablejs";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import type { Form } from "../form";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import type { SelectInput } from "../inputs/select-input";

export class Pages {
    constructor() {
        const commandExpandAllPages = $("[data-command=expand-all-pages]") as HTMLButtonElement;
        const commandCollapseAllPages = $("[data-command=collapse-all-pages]") as HTMLButtonElement;
        const commandReorderPages = $("[data-command=reorder-pages]") as HTMLButtonElement;
        const commandPreview = $("[data-command=preview]") as HTMLButtonElement;

        const searchInput = $(".page-search") as HTMLInputElement;

        const newPageModal = app.modals["newPageModal"];
        const deletePageItemModal = app.modals["deletePageItemModal"];
        const duplicatePageModal = app.modals["duplicatePageModal"];

        $$(".pages-tree").forEach((element) => {
            if (element.dataset.orderableChildren === "true") {
                initSortable(element);
            }
        });

        $$(".page-details").forEach((element) => {
            if ($(".pages-tree-children-toggle", element)) {
                element.addEventListener("click", (event) => {
                    const target = event.target as HTMLElement;
                    if (target.closest("a, .sortable-handle")) {
                        return;
                    }
                    togglePageItem(element);
                    event.preventDefault();
                });
            }
        });

        if (commandExpandAllPages) {
            commandExpandAllPages.addEventListener("click", () => {
                expandAllPages();
                commandExpandAllPages.blur();
            });
        }

        if (commandCollapseAllPages) {
            commandCollapseAllPages.addEventListener("click", () => {
                collapseAllPages();
                commandCollapseAllPages.blur();
            });
        }

        if (commandReorderPages) {
            commandReorderPages.addEventListener("click", () => {
                commandReorderPages.classList.toggle("active");
                $(".pages-tree")?.classList.toggle("is-reordering");
                commandReorderPages.blur();
            });
        }

        if (commandExpandAllPages || commandCollapseAllPages || commandReorderPages) {
            setCommandsState();
        }

        if (searchInput) {
            searchInput.addEventListener("focus", () => {
                $$(".pages-tree-item").forEach((element) => {
                    element.dataset.expanded = element.classList.contains("expanded") ? "true" : "false";
                });
            });

            const handleSearch = () => {
                const value = escapeHtml(searchInput.value);
                if (value.length === 0) {
                    $(".pages-tree-root")?.classList.remove("is-filtered");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        title.innerText = title.textContent;
                        ($(".pages-tree-row", element) as HTMLElement).style.display = "";
                        element.classList.toggle("is-expanded", element.dataset.expanded === "true");
                    });
                } else {
                    $(".pages-tree-root")?.classList.add("is-filtered");

                    const regexp = makeSearchRegExp(value, "gi");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        const text = escapeHtml(title.textContent);
                        const pagesItem = $(".pages-tree-row", element) as HTMLElement;

                        if (text.match(regexp) !== null) {
                            title.innerHTML = text.replace(regexp, "<mark>$&</mark>");
                            pagesItem.style.display = "";
                        } else {
                            pagesItem.style.display = "none";
                        }

                        element.classList.add("is-expanded");
                    });
                }
            };

            searchInput.addEventListener("keyup", debounce(handleSearch, 100));
            searchInput.addEventListener("search", handleSearch);

            document.addEventListener("keydown", (event) => {
                if ((event.ctrlKey || event.metaKey) && event.key === "f" && document.activeElement !== searchInput) {
                    searchInput.focus();
                    event.preventDefault();
                }
            });
        }

        if (newPageModal) {
            const form = newPageModal.form as Form;

            const url = new URL(window.location.href);

            if (url.searchParams.has("createNew")) {
                url.searchParams.delete("createNew");
                window.history.replaceState({}, document.title, url.toString());
            }

            const parentSelect = form.inputs["newPageModal[parent]"] as SelectInput;

            const filterAllowedTemplates = () => {
                const option = parentSelect.selectedDropdownItem;

                if (!option) {
                    return;
                }

                const allowedTemplates = option.dataset.allowedTemplates ? option.dataset.allowedTemplates.split(" ") : [];

                const templateSelect = form.inputs["newPageModal[template]"] as SelectInput;

                if (allowedTemplates.length > 0) {
                    templateSelect.element.dataset.previousValue = templateSelect.value;

                    templateSelect.value = allowedTemplates[0];

                    templateSelect.dropdownItems.forEach((item) => {
                        if (!allowedTemplates.includes(item.dataset.value ?? "")) {
                            item.classList.add("disabled");
                        }
                    });
                } else {
                    if ("previousValue" in templateSelect.element.dataset) {
                        templateSelect.value = templateSelect.element.dataset.previousValue ?? "";
                        delete templateSelect.element.dataset.previousValue;
                    }

                    templateSelect.dropdownItems.forEach((item) => {
                        item.classList.remove("disabled");
                    });
                }
            };

            parentSelect.element.addEventListener("change", () => filterAllowedTemplates());

            filterAllowedTemplates();
        }

        if (deletePageItemModal) {
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

                new Request(
                    {
                        method: "POST",
                        url: action,
                        data: {
                            "csrf-token": app.config.csrfToken,
                        },
                    },
                    (response) => {
                        if (response.status === "success" && pageItem) {
                            const parentItem = pageItem.parentElement?.closest(".pages-tree-item");
                            pageItem.remove();
                            setCommandsState();

                            if (parentItem && $$(".pages-tree-item", parentItem).length === 0) {
                                parentItem.classList.remove("has-children", "is-expanded");

                                const deleteButton = $("[data-modal=deletePageItemModal]", parentItem) as HTMLButtonElement;
                                if (deleteButton) {
                                    deleteButton.disabled = false;
                                }

                                const duplicateButton = $("[data-modal=duplicatePageModal]", parentItem) as HTMLButtonElement;
                                if (duplicateButton) {
                                    duplicateButton.disabled = false;
                                }
                            }
                        }

                        const notification = new Notification(response.message, response.status);
                        notification.show();

                        modal.close();
                    },
                );
            });
        }

        if (duplicatePageModal) {
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

        if (commandPreview) {
            const editorForm = app.forms["page-editor-form"];

            const pageParent = $("#parent", editorForm.element) as HTMLInputElement;
            const previousParent = pageParent.value;

            if (editorForm) {
                editorForm.element.addEventListener(
                    "input",
                    debounce(() => {
                        if (pageParent.value !== previousParent) {
                            // Prevent preview if the parent page has changed
                            commandPreview.disabled = true;
                            commandPreview.classList.remove("button-indicator");
                            return;
                        }
                        commandPreview.disabled = false;
                        commandPreview.classList.toggle("button-indicator", editorForm.hasChanged());
                    }, 500),
                );
            }
        }

        function expandAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.add("is-expanded");
            });
            setCommandsState();
        }

        function collapseAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.remove("is-expanded");
            });
            setCommandsState();
        }

        function togglePageItem(list: HTMLElement) {
            const element = list.closest(".pages-tree-item");
            element?.classList.toggle("is-expanded");
            setCommandsState();
        }

        function setCommandsState() {
            const allPages = $$(".pages-tree-item.has-children");
            const expandedPages = $$(".pages-tree-item.has-children.is-expanded");
            const orderablePages = $$(".pages-tree-item.is-orderable");

            if (commandExpandAllPages) {
                commandExpandAllPages.disabled = allPages.length === expandedPages.length;
            }

            if (commandCollapseAllPages) {
                commandCollapseAllPages.disabled = expandedPages.length === 0;
            }

            if (commandReorderPages) {
                commandReorderPages.disabled = orderablePages.length <= 1;
            }
        }

        async function initSortable(element: HTMLElement) {
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
                        "csrf-token": app.config.csrfToken,
                        page: event.item.dataset.route,
                        before: (event.item.nextElementSibling! as HTMLElement).dataset.route,
                        parent: element.dataset.parent,
                    };

                    new Request(
                        {
                            method: "POST",
                            url: `${app.config.baseUri}pages/reorder/`,
                            data: data,
                        },
                        (response) => {
                            if (response.status) {
                                const notification = new Notification(response.message, response.status, { icon: "checkCircle" });
                                notification.show();
                            }
                            if (!response.status || response.status === "error") {
                                sortable.sort(originalOrder);
                            }
                            sortable.option("disabled", false);
                            originalOrder = sortable.toArray();
                        },
                    );
                },
            });

            originalOrder = sortable.toArray();
        }
    }
}
