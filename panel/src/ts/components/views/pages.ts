import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp, makeSlug, validateSlug } from "../../utils/validation";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import Sortable from "sortablejs";

export class Pages {
    constructor() {
        const commandExpandAllPages = $("[data-command=expand-all-pages]");
        const commandCollapseAllPages = $("[data-command=collapse-all-pages]");
        const commandReorderPages = $("[data-command=reorder-pages]");
        const commandChangeSlug = $("[data-command=change-slug]");

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
            ($("#page-title", newPageModal) as HTMLElement).addEventListener("keyup", (event) => {
                ($("#page-slug", newPageModal) as HTMLInputElement).value = makeSlug((event.target as HTMLInputElement).value);
            });

            const handleSlugChange = (event: Event) => {
                const target = event.target as HTMLInputElement;
                target.value = validateSlug(target.value);
            };

            ($("#page-slug", newPageModal) as HTMLElement).addEventListener("keyup", handleSlugChange);
            ($("#page-slug", newPageModal) as HTMLElement).addEventListener("blur", handleSlugChange);

            ($("#page-parent", newPageModal) as HTMLElement).addEventListener("change", () => {
                const option = $('.dropdown-list[data-for="page-parent"] .selected');

                if (!option) {
                    return;
                }

                const allowedTemplates = (option.dataset.allowedTemplates as string).split(", ");

                const pageTemplate = $("#page-template", newPageModal) as HTMLInputElement;

                if (allowedTemplates.length > 0) {
                    pageTemplate.dataset.previousValue = pageTemplate.value;
                    pageTemplate.value = allowedTemplates[0];
                    ($('.select[data-for="page-template"') as HTMLInputElement).value = ($(`.dropdown-list[data-for="page-template"] .dropdown-item[data-value="${pageTemplate.value}"]`) as HTMLElement).innerText;

                    $$('.dropdown-list[data-for="page-template"] .dropdown-item').forEach((option) => {
                        if (!allowedTemplates.includes(option.dataset.value as string)) {
                            option.classList.add("disabled");
                        }
                    });
                } else {
                    if ("previousValue" in pageTemplate.dataset) {
                        pageTemplate.value = pageTemplate.dataset.previousValue as string;
                        delete pageTemplate.dataset.previousValue;
                        ($('.select[data-for="page-template"') as HTMLInputElement).value = ($(`.dropdown-list[data-for="page-template"] .dropdown-item[data-value="${pageTemplate.value}"]`) as HTMLElement).innerText;
                    }

                    $$('.dropdown-list[data-for="page-template"] .dropdown-item').forEach((option) => {
                        option.classList.remove("disabled");
                    });
                }
            });
        }

        if (slugModal && commandChangeSlug) {
            commandChangeSlug.addEventListener("click", () => {
                app.modals["slugModal"].show(undefined, (modal) => {
                    const slug = (document.getElementById("slug") as HTMLInputElement).value;
                    const slugInput = $("#page-slug", modal.element) as HTMLInputElement;
                    slugInput.value = slug;
                    slugInput.placeholder = slug;
                });
            });

            ($("#page-slug", slugModal) as HTMLElement).addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    ($("[data-command=continue]", slugModal) as HTMLElement).click();
                }
            });

            const handleSlugChange = (event: Event) => {
                const target = event.target as HTMLInputElement;
                target.value = validateSlug(target.value);
            };

            ($("#page-slug", slugModal) as HTMLElement).addEventListener("keyup", handleSlugChange);
            ($("#page-slug", slugModal) as HTMLElement).addEventListener("blur", handleSlugChange);

            ($("[data-command=generate-slug]", slugModal) as HTMLElement).addEventListener("click", () => {
                const slug = makeSlug((document.getElementById("title") as HTMLInputElement).value);
                ($("#page-slug", slugModal) as HTMLInputElement).value = slug;
                ($("#page-slug", slugModal) as HTMLElement).focus();
            });

            ($("[data-command=continue]", slugModal) as HTMLElement).addEventListener("click", () => {
                const slug = ($("#page-slug", slugModal) as HTMLInputElement).value.replace(/^-+|-+$/, "");

                if (slug.length > 0) {
                    const route = ($(".page-route-inner") as HTMLElement).innerHTML;
                    $$("#page-slug, #slug").forEach((element: HTMLInputElement) => {
                        element.value = slug;
                    });
                    ($("#page-slug", slugModal) as HTMLInputElement).value = slug;
                    (document.getElementById("slug") as HTMLInputElement).value = slug;
                    ($(".page-route-inner") as HTMLElement).innerHTML = route.replace(/\/[a-z0-9-]+\/$/, `/${slug}/`);
                }

                app.modals["slugModal"].hide();
            });
        }

        $$("[data-modal=renameFileModal]").forEach((element) => {
            element.addEventListener("click", () => {
                const modal = document.getElementById("renameFileModal") as HTMLElement;
                const input = $("#file-name", modal) as HTMLInputElement;
                input.value = element.dataset.filename as string;
                input.setSelectionRange(0, input.value.lastIndexOf("."));
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
