import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp, makeSlug, validateSlug } from "../../utils/validation";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import { Notification } from "../notification";
import { Request } from "../../utils/request";
import { Sortable } from "sortablejs";

export class Pages {
    constructor() {
        const commandExpandAllPages = $("[data-command=expand-all-pages]");
        const commandCollapseAllPages = $("[data-command=collapse-all-pages]");
        const commandReorderPages = $("[data-command=reorder-pages]");
        const commandChangeSlug = $("[data-command=change-slug]");

        const searchInput = $(".page-search");

        const newPageModal = document.getElementById("newPageModal");
        const slugModal = document.getElementById("slugModal");

        $$(".pages-list").forEach((element) => {
            if (element.dataset.orderableChildren === "true") {
                initSortable(element);
            }
        });

        $$(".page-details").forEach((element) => {
            if ($(".page-children-toggle", element)) {
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

        $$(".pages-list .sort-handle").forEach((element) => {
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
                $(".pages-list").classList.toggle("is-reordering");
                commandReorderPages.blur();
            });
        }

        if (searchInput) {
            searchInput.addEventListener("focus", () => {
                $$(".pages-item").forEach((element) => {
                    element.dataset.expanded = element.classList.contains("expanded") ? "true" : "false";
                });
            });

            const handleSearch = (event) => {
                const value = event.target.value;
                if (value.length === 0) {
                    $(".pages-list-root").classList.remove("is-filtered");

                    $$(".pages-item").forEach((element) => {
                        const title = $(".page-title a", element);
                        title.innerHTML = title.textContent;
                        $(".pages-item-row", element).style.display = "";
                        element.classList.toggle("is-expanded", element.dataset.expanded === "true");
                    });
                } else {
                    $(".pages-list-root").classList.add("is-filtered");

                    const regexp = new RegExp(makeDiacriticsRegExp(escapeRegExp(value)), "gi");

                    $$(".pages-item").forEach((element) => {
                        const title = $(".page-title a", element);
                        const text = title.textContent;
                        const pagesItem = $(".pages-item-row", element);

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
            $("#page-title", newPageModal).addEventListener("keyup", (event) => {
                $("#page-slug", newPageModal).value = makeSlug(event.target.value);
            });

            const handleSlugChange = (event) => {
                event.target.value = validateSlug(event.target.value);
            };

            $("#page-slug", newPageModal).addEventListener("keyup", handleSlugChange);
            $("#page-slug", newPageModal).addEventListener("blur", handleSlugChange);

            $("#page-parent", newPageModal).addEventListener("change", () => {
                const option = $('.dropdown-list[data-for="page-parent"] .selected');

                if (!option) {
                    return;
                }

                let allowedTemplates = option.dataset.allowedTemplates;

                const pageTemplate = $("#page-template", newPageModal);

                if (allowedTemplates) {
                    allowedTemplates = allowedTemplates.split(", ");

                    pageTemplate.dataset.previousValue = pageTemplate.value;
                    pageTemplate.value = allowedTemplates[0];
                    $('.select[data-for="page-template"').value = $(`.dropdown-list[data-for="page-template"] .dropdown-item[data-value="${pageTemplate.value}"]`).innerText;

                    $$('.dropdown-list[data-for="page-template"] .dropdown-item').forEach((option) => {
                        if (!allowedTemplates.includes(option.dataset.value)) {
                            option.classList.add("disabled");
                        }
                    });
                } else {
                    if ("previousValue" in pageTemplate.dataset) {
                        pageTemplate.value = pageTemplate.dataset.previousValue;
                        delete pageTemplate.dataset.previousValue;
                        $('.select[data-for="page-template"').value = $(`.dropdown-list[data-for="page-template"] .dropdown-item[data-value="${pageTemplate.value}"]`).innerText;
                    }

                    $$('.dropdown-list[data-for="page-template"] .dropdown-item').forEach((option) => {
                        option.classList.remove("disabled");
                    });
                }
            });
        }

        if (slugModal && commandChangeSlug) {
            commandChangeSlug.addEventListener("click", () => {
                app.modals["slugModal"].show(null, (modal) => {
                    const slug = document.getElementById("slug").value;
                    const slugInput = $("#page-slug", modal.element);
                    slugInput.value = slug;
                    slugInput.placeholder = slug;
                });
            });

            $("#page-slug", slugModal).addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    $("[data-command=continue]", slugModal).click();
                }
            });

            const handleSlugChange = (event) => {
                event.target.value = validateSlug(event.target.value);
            };

            $("#page-slug", slugModal).addEventListener("keyup", handleSlugChange);
            $("#page-slug", slugModal).addEventListener("blur", handleSlugChange);

            $("[data-command=generate-slug]", slugModal).addEventListener("click", () => {
                const slug = makeSlug(document.getElementById("title").value);
                $("#page-slug", slugModal).value = slug;
                $("#page-slug", slugModal).focus();
            });

            $("[data-command=continue]", slugModal).addEventListener("click", () => {
                const slug = $("#page-slug", slugModal).value.replace(/^-+|-+$/, "");

                if (slug.length > 0) {
                    const route = $(".page-route-inner").innerHTML;
                    $$("#page-slug, #slug").forEach((element) => {
                        element.value = slug;
                    });
                    $("#page-slug", slugModal).value = slug;
                    document.getElementById("slug").value = slug;
                    $(".page-route-inner").innerHTML = route.replace(/\/[a-z0-9-]+\/$/, `/${slug}/`);
                }

                app.modals["slugModal"].hide();
            });
        }

        $$(["[data-modal=renameFileModal]"]).forEach((element) => {
            element.addEventListener("click", () => {
                const modal = document.getElementById("renameFileModal");
                const input = $("#file-name", modal);
                input.value = element.dataset.filename;
                input.setSelectionRange(0, input.value.lastIndexOf("."));
            });
        });

        function expandAllPages() {
            $$(".pages-item").forEach((element) => {
                element.classList.add("is-expanded");
            });
        }

        function collapseAllPages() {
            $$(".pages-item").forEach((element) => {
                element.classList.remove("is-expanded");
            });
        }

        function togglePageItem(list) {
            const element = list.closest(".pages-item");
            element.classList.toggle("is-expanded");
        }

        function initSortable(element) {
            let originalOrder = [];

            const sortable = Sortable.create(element, {
                handle: ".sort-handle",
                filter: ".is-not-orderable",
                forceFallback: true,
                swapThreshold: 0.75,
                invertSwap: true,
                animation: 150,

                onChoose() {
                    const height = document.body.offsetHeight;
                    document.body.style.height = `${height}px`;

                    const e = window.addEventListener("scroll", () => {
                        window.document.body.style.height = "";
                        window.removeEventListener("scroll", e);
                    });
                },

                onStart() {
                    element.classList.add("is-dragging");
                },

                onMove(event) {
                    if (event.related.classList.contains("is-not-orderable")) {
                        return false;
                    }
                },

                onEnd(event) {
                    element.classList.remove("is-dragging");

                    document.body.style.height = "";

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option("disabled", true);

                    const data = {
                        "csrf-token": $("meta[name=csrf-token]").content,
                        page: element.children[event.newIndex].dataset.route,
                        before: element.children[event.oldIndex].dataset.route,
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
