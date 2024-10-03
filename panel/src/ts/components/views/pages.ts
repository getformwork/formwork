import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp, makeSlug, validateSlug } from "../../utils/validation";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import Sortable from "sortablejs";

export class Pages {
    constructor() {
        const commandExpandAllPages = $("[data-command=expand-all-pages]") as HTMLButtonElement;
        const commandCollapseAllPages = $("[data-command=collapse-all-pages]") as HTMLButtonElement;
        const commandReorderPages = $("[data-command=reorder-pages]") as HTMLButtonElement;
        const commandPreview = $("[data-command=preview]") as HTMLButtonElement;
        const commandChangeSlug = $("[data-command=change-slug]") as HTMLButtonElement;

        const searchInput = $(".page-search");

        const newPageModal = document.getElementById("newPageModal");
        const slugModal = document.getElementById("slugModal");

        $$(".pages-tree").forEach((element) => {
            if (element.dataset.orderableChildren === "true") {
                initSortable(element);
            }
        });

        $$(".page-details").forEach((element) => {
            if ($(".pages-tree-children-toggle", element)) {
                element.addEventListener("click", (event) => {
                    togglePageItem(element);
                    event.stopPropagation();
                });
            }
        });

        $$(".page-details a").forEach((element) => {
            element.addEventListener("click", (event) => {
                event.stopPropagation();
            });
        });

        $$(".pages-tree .sortable-handle").forEach((element) => {
            element.addEventListener("click", (event) => {
                event.stopPropagation();
            });
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
                ($(".pages-tree") as HTMLElement).classList.toggle("is-reordering");
                commandReorderPages.blur();
            });
        }

        if (searchInput) {
            searchInput.addEventListener("focus", () => {
                $$(".pages-tree-item").forEach((element) => {
                    element.dataset.expanded = element.classList.contains("expanded") ? "true" : "false";
                });
            });

            const handleSearch = (event: Event) => {
                const value = (event.target as HTMLInputElement).value;
                if (value.length === 0) {
                    ($(".pages-tree-root") as HTMLElement).classList.remove("is-filtered");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        title.innerHTML = title.textContent as string;
                        ($(".pages-tree-row", element) as HTMLElement).style.display = "";
                        element.classList.toggle("is-expanded", element.dataset.expanded === "true");
                    });
                } else {
                    ($(".pages-tree-root") as HTMLElement).classList.add("is-filtered");

                    const regexp = new RegExp(makeDiacriticsRegExp(escapeRegExp(value)), "gi");

                    $$(".pages-tree-item").forEach((element) => {
                        const title = $(".page-title a", element) as HTMLElement;
                        const text = title.textContent as string;
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
                if (event.ctrlKey || event.metaKey) {
                    if (event.key === "f" && document.activeElement !== searchInput) {
                        searchInput.focus();
                        event.preventDefault();
                    }
                }
            });
        }

        if (newPageModal) {
            ($("#title", newPageModal) as HTMLElement).addEventListener("keyup", (event) => {
                ($("#slug", newPageModal) as HTMLInputElement).value = makeSlug((event.target as HTMLInputElement).value);
            });

            const handleSlugChange = (event: Event) => {
                const target = event.target as HTMLInputElement;
                target.value = validateSlug(target.value);
            };

            ($("#slug", newPageModal) as HTMLElement).addEventListener("keyup", handleSlugChange);
            ($("#slug", newPageModal) as HTMLElement).addEventListener("blur", handleSlugChange);

            ($("#parent", newPageModal) as HTMLElement).addEventListener("change", () => {
                const option = $('.dropdown-list[data-for="parent"] .selected', newPageModal);

                if (!option) {
                    return;
                }

                const allowedTemplates = option.dataset.allowedTemplates ? option.dataset.allowedTemplates.split(" ") : [];

                const pageTemplate = $("#template", newPageModal) as HTMLInputElement;

                if (allowedTemplates.length > 0) {
                    pageTemplate.dataset.previousValue = pageTemplate.value;
                    pageTemplate.value = allowedTemplates[0];
                    ($('.form-select[data-for="template"', newPageModal) as HTMLInputElement).value = ($(`.dropdown-list[data-for="template"] .dropdown-item[data-value="${pageTemplate.value}"]`, newPageModal) as HTMLElement).innerText;

                    $$('.dropdown-list[data-for="template"] .dropdown-item').forEach((option) => {
                        if (!allowedTemplates.includes(option.dataset.value as string)) {
                            option.classList.add("disabled");
                        }
                    });
                } else {
                    if ("previousValue" in pageTemplate.dataset) {
                        pageTemplate.value = pageTemplate.dataset.previousValue as string;
                        delete pageTemplate.dataset.previousValue;
                        ($('.form-select[data-for="template"', newPageModal) as HTMLInputElement).value = ($(`.dropdown-list[data-for="template"] .dropdown-item[data-value="${pageTemplate.value}"]`, newPageModal) as HTMLElement).innerText;
                    }

                    $$('.dropdown-list[data-for="template"] .dropdown-item', newPageModal).forEach((option) => {
                        option.classList.remove("disabled");
                    });
                }
            });
        }

        if (commandPreview) {
            const editorForm = app.forms["page-editor-form"];

            const pageParent = $("#page-parent", editorForm.element) as HTMLInputElement;
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

        if (slugModal && commandChangeSlug) {
            commandChangeSlug.addEventListener("click", () => {
                app.modals["slugModal"].show(undefined, (modal) => {
                    const slug = (document.getElementById("slug") as HTMLInputElement).value;
                    const slugInput = $("#newSlug", modal.element) as HTMLInputElement;
                    slugInput.value = slug;
                    slugInput.placeholder = slug;
                });
            });

            ($("#newSlug", slugModal) as HTMLElement).addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    ($("[data-command=continue]", slugModal) as HTMLElement).click();
                }
            });

            const handleSlugChange = (event: Event) => {
                const target = event.target as HTMLInputElement;
                target.value = validateSlug(target.value);
            };

            ($("#newSlug", slugModal) as HTMLElement).addEventListener("keyup", handleSlugChange);
            ($("#newSlug", slugModal) as HTMLElement).addEventListener("blur", handleSlugChange);

            ($("[data-command=generate-slug]", slugModal) as HTMLElement).addEventListener("click", () => {
                const slug = makeSlug((document.getElementById("title") as HTMLInputElement).value);
                ($("#newSlug", slugModal) as HTMLInputElement).value = slug;
                ($("#newSlug", slugModal) as HTMLElement).focus();
            });

            ($("[data-command=continue]", slugModal) as HTMLElement).addEventListener("click", () => {
                const slug = ($("#newSlug", slugModal) as HTMLInputElement).value.replace(/^-+|-+$/, "");

                if (slug.length > 0) {
                    const route = ($(".page-route-inner") as HTMLElement).innerHTML;
                    ($("#newSlug", slugModal) as HTMLInputElement).value = slug;
                    ($("#slug") as HTMLInputElement).value = slug;
                    ($(".page-route-inner") as HTMLElement).innerHTML = route.replace(/\/[a-z0-9-]+\/$/, `/${slug}/`);
                }

                app.modals["slugModal"].hide();
            });
        }

        $$("[data-modal=renameFileModal]").forEach((element) => {
            element.addEventListener("click", () => {
                const modal = document.getElementById("renameFileModal") as HTMLElement;
                const input = $("#filename", modal) as HTMLInputElement;
                input.value = element.dataset.filename as string;
                input.setSelectionRange(0, input.value.lastIndexOf("."));
            });
        });

        $$("[data-command=replaceFile]").forEach((element) => {
            element.addEventListener("click", () => {
                const form = document.createElement("form");
                form.hidden = true;
                form.action = element.dataset.action as string;
                form.method = "post";
                form.enctype = "multipart/form-data";

                const fileInput = document.createElement("input");
                fileInput.name = "file";
                fileInput.type = "file";
                fileInput.accept = element.dataset.extension as string;
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
        });

        function expandAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.add("is-expanded");
            });
        }

        function collapseAllPages() {
            $$(".pages-tree-item").forEach((element) => {
                element.classList.remove("is-expanded");
            });
        }

        function togglePageItem(list: HTMLElement) {
            const element = list.closest(".pages-tree-item");
            element?.classList.toggle("is-expanded");
        }

        function initSortable(element: HTMLElement) {
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

                onMove(event: Sortable.MoveEvent) {
                    if (event.related.classList.contains("is-not-orderable")) {
                        return false;
                    }
                },

                onEnd(event: Sortable.SortableEvent) {
                    element.classList.remove("is-dragging");

                    document.body.style.height = "";

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option("disabled", true);

                    const data = {
                        "csrf-token": ($("meta[name=csrf-token]") as HTMLMetaElement).content,
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
                                const notification = new Notification(response.message, response.status, { icon: "check-circle" });
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
