import "../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, test } from "node:test";
import Sortable from "sortablejs";
import { checkCircle, exclamationOctagon } from "../../../src/ts/components/ui/icons";
import { app } from "../../../src/ts/services/app";
import { Form } from "../../../src/ts/services/form";
import { Modal } from "../../../src/ts/services/modal";
import { initDuplicatePageModal, initNewPageModal, initPagePreview, initPagesTree, initSortable } from "../../../src/ts/views/pages";
import { iconMarkup, resetModals, setTranslation, shownNotifications } from "../../helpers/panel";
import { installXMLHttpRequestStub, XMLHttpRequestStub } from "../../stubs/xhr";

beforeEach(() => {
    installXMLHttpRequestStub();
    app.config.baseUri = "/panel/";
    setTranslation();
    app.modals = {};
    app.forms = {};
});

afterEach(() => {
    resetModals();
});

interface PageFixture {
    route: string;
    title: string;
    children?: PageFixture[];
    expanded?: boolean;
    orderable?: boolean;
    deletable?: boolean;
}

/**
 * Get the HTML of a page of the tree as the panel renders it
 */
function pageItem({ route, title, children = [], expanded = false, orderable = true, deletable = children.length === 0 }: PageFixture): string {
    const classes = ["pages-tree-item", children.length > 0 ? "has-children" : "", expanded ? "is-expanded" : "", orderable ? "is-orderable" : "is-not-orderable"].filter(Boolean).join(" ");
    return `
        <li class="${classes}" data-route="${route}">
            <div class="pages-tree-row">
                <div class="pages-tree-item-cell page-details flex">
                    <div class="pages-tree-icon sortable-handle"></div>
                    <div class="pages-tree-icon pages-tree-children-toggle">${children.length > 0 ? '<button type="button" class="button"></button>' : ""}</div>
                    <div class="page-title"><a href="/panel/pages${route}edit/">${title}</a></div>
                </div>
                <div class="pages-tree-item-cell page-actions">
                    <button type="button" class="dropdown-item" data-modal="duplicatePageModal" data-duplicate-title="${title} (copy)" ${children.length > 0 ? "disabled" : ""}>Duplicate</button>
                    <button type="button" class="dropdown-item" data-modal="deletePageItemModal" data-action="/panel/pages${route}delete/" ${deletable ? "" : "disabled"}>Delete</button>
                </div>
            </div>
            ${children.length > 0 ? `<ul class="pages-tree pages-tree-children" data-orderable-children="true" data-parent="${route}">${children.map(pageItem).join("")}</ul>` : ""}
        </li>`;
}

const pages: PageFixture[] = [
    {
        route: "/blog/",
        title: "Blog",
        children: [
            { route: "/blog/first-post/", title: "First post" },
            { route: "/blog/second-post/", title: "Second post" },
        ],
    },
    { route: "/about/", title: "About" },
    { route: "/contact/", title: "Contact" },
];

/**
 * Add the commands, the search and the tree of the pages, as the panel renders them, and
 * initialize the tree
 */
function makeTree(fixture = pages, { reordering = false, deleteModal = false } = {}) {
    document.body.innerHTML = `
        <input class="form-input page-search" type="search">
        <button type="button" data-command="expand-all-pages" disabled>Expand all</button>
        <button type="button" data-command="collapse-all-pages" disabled>Collapse all</button>
        <button type="button" data-command="reorder-pages" class="${reordering ? "active" : ""}" disabled>Reorder</button>
        <ul class="pages-tree pages-tree-root" data-orderable-children="true" data-parent=".">${fixture.map(pageItem).join("")}</ul>`;
    if (deleteModal) {
        document.body.insertAdjacentHTML("beforeend", '<div class="modal" id="deletePageItemModal"><div class="modal-container"><p class="modal-text"></p><button type="button" id="confirm-delete" data-command="delete-page">Delete</button></div></div>');
        app.modals.deletePageItemModal = new Modal(document.getElementById("deletePageItemModal") as HTMLElement);
    }
    initPagesTree();
}

const page = (route: string) => document.querySelector(`[data-route="${route}"]`) as HTMLElement;

const command = (name: string) => document.querySelector(`[data-command=${name}]`) as HTMLButtonElement;

/**
 * Search the tree as the user does
 */
function search(text: string) {
    const input = document.querySelector(".page-search") as HTMLInputElement;
    input.value = text;
    input.dispatchEvent(new Event("search"));
}

const visible = () =>
    Array.from(document.querySelectorAll<HTMLElement>(".pages-tree-item"))
        .filter((item) => (item.querySelector(".pages-tree-row") as HTMLElement).style.display !== "none")
        .map((item) => item.dataset.route);

describe("Pages View", () => {
    describe("initPagesTree", () => {
        describe("toggles", () => {
            test("expands and collapses a page when its details are clicked", () => {
                makeTree();
                const details = page("/blog/").querySelector(".page-details") as HTMLElement;
                details.click();
                assert.equal(page("/blog/").classList.contains("is-expanded"), true);
                assert.equal(page("/blog/").querySelector(".pages-tree-children-toggle button")?.getAttribute("aria-expanded"), "true");
                details.click();
                assert.equal(page("/blog/").classList.contains("is-expanded"), false);
                assert.equal(page("/blog/").querySelector(".pages-tree-children-toggle button")?.getAttribute("aria-expanded"), "false");
            });

            test("does not expand a page without children", () => {
                makeTree();
                (page("/about/").querySelector(".page-details") as HTMLElement).click();
                assert.equal(page("/about/").classList.contains("is-expanded"), false);
                assert.equal(page("/about/").querySelector(".pages-tree-children-toggle button"), null);
            });

            test("does not show as expanded a page without children rendered as expanded", () => {
                makeTree([{ route: "/about/", title: "About", expanded: true }]);
                assert.equal(page("/about/").classList.contains("is-expanded"), false);
            });

            test("does not toggle a page when its link or handle are clicked", () => {
                makeTree();
                (page("/blog/").querySelector(".page-title a") as HTMLElement).click();
                (page("/blog/").querySelector(".sortable-handle") as HTMLElement).click();
                assert.equal(page("/blog/").classList.contains("is-expanded"), false);
            });
        });

        describe("commands", () => {
            test("expands and collapses all pages, disabling the command with no effect", () => {
                makeTree();
                assert.equal(command("expand-all-pages").disabled, false);
                assert.equal(command("collapse-all-pages").disabled, true);
                command("expand-all-pages").click();
                assert.equal(page("/blog/").classList.contains("is-expanded"), true);
                assert.equal(command("expand-all-pages").disabled, true);
                assert.equal(command("collapse-all-pages").disabled, false);
                command("collapse-all-pages").click();
                assert.equal(page("/blog/").classList.contains("is-expanded"), false);
                assert.equal(command("collapse-all-pages").disabled, true);
            });

            test("turns the reordering on and off", () => {
                makeTree();
                command("reorder-pages").click();
                assert.equal(command("reorder-pages").classList.contains("active"), true);
                assert.equal(document.querySelector(".pages-tree")?.classList.contains("is-reordering"), true);
                command("reorder-pages").click();
                assert.equal(document.querySelector(".pages-tree")?.classList.contains("is-reordering"), false);
            });

            test("disables the reordering with less than two orderable pages", () => {
                makeTree([
                    { route: "/about/", title: "About" },
                    { route: "/contact/", title: "Contact", orderable: false },
                ]);
                assert.equal(command("reorder-pages").disabled, true);
            });

            test("starts reordering if the command is active", () => {
                makeTree(pages, { reordering: true });
                assert.equal(document.querySelector(".pages-tree")?.classList.contains("is-reordering"), true);
            });
        });

        describe("search", () => {
            test("shows only the matching pages, highlighting the matches", () => {
                makeTree();
                search("post");
                assert.deepEqual(visible(), ["/blog/first-post/", "/blog/second-post/"]);
                assert.equal(page("/blog/first-post/").querySelector(".page-title a")?.innerHTML, "First <mark>post</mark>");
                assert.equal(document.querySelector(".pages-tree-root")?.classList.contains("is-filtered"), true);
            });

            test("shows the pages expanded while searching, preserving their state", () => {
                makeTree();
                search("post");
                assert.equal(page("/blog/").classList.contains("is-expanded"), true);
                search("");
                assert.equal(page("/blog/").classList.contains("is-expanded"), false);
            });

            test("does not show as expanded the pages without children while searching", () => {
                makeTree();
                search("about");
                assert.equal(page("/about/").classList.contains("is-expanded"), false);
            });

            test("shows all pages and removes the highlights when the search is cleared", () => {
                makeTree();
                search("post");
                search("");
                assert.deepEqual(visible(), ["/blog/", "/blog/first-post/", "/blog/second-post/", "/about/", "/contact/"]);
                assert.equal(document.querySelectorAll("mark").length, 0);
                assert.equal(page("/blog/first-post/").querySelector(".page-title a")?.textContent, "First post");
                assert.equal(document.querySelector(".pages-tree-root")?.classList.contains("is-filtered"), false);
            });

            test("escapes the titles", () => {
                makeTree([{ route: "/foo/", title: "&lt;b&gt;Foo&lt;/b&gt;" }]);
                search("foo");
                assert.equal(page("/foo/").querySelector(".page-title b"), null);
                assert.equal(page("/foo/").querySelector(".page-title a")?.textContent, "<b>Foo</b>");
            });
        });

        describe("delete", () => {
            /**
             * Delete a page with the modal and answer the request
             */
            function deletePage(route: string, status = "success", message = "Page deleted") {
                (page(route).querySelector("[data-modal=deletePageItemModal]") as HTMLElement).click();
                (document.getElementById("confirm-delete") as HTMLElement).click();
                XMLHttpRequestStub.last.respondJson(status, message);
            }

            test("deletes a page, notifying the result", () => {
                makeTree(pages, { deleteModal: true });
                deletePage("/about/");
                assert.equal(XMLHttpRequestStub.last.url, "/panel/pages/about/delete/");
                assert.equal(page("/about/"), null);
                assert.deepEqual(shownNotifications(), ["success: Page deleted"]);
                assert.equal(app.modals.deletePageItemModal.isClosed, true);
            });

            test("keeps the page when the deletion fails", () => {
                makeTree(pages, { deleteModal: true });
                deletePage("/about/", "error", "Cannot delete the page");
                assert.notEqual(page("/about/"), null);
                assert.deepEqual(shownNotifications(), ["danger: Cannot delete the page"]);
            });

            test("enables the commands of a parent whose last child is deleted", () => {
                makeTree([{ route: "/blog/", title: "Blog", children: [{ route: "/blog/first-post/", title: "First post" }] }], { deleteModal: true });
                deletePage("/blog/first-post/");
                const blog = page("/blog/");
                assert.equal(blog.classList.contains("has-children"), false);
                assert.equal((blog.querySelector("[data-modal=deletePageItemModal]") as HTMLButtonElement).disabled, false);
                assert.equal((blog.querySelector("[data-modal=duplicatePageModal]") as HTMLButtonElement).disabled, false);
            });

            test("keeps the commands of a parent with other children disabled", () => {
                makeTree(pages, { deleteModal: true });
                deletePage("/blog/first-post/");
                assert.equal(page("/blog/").classList.contains("has-children"), true);
                assert.equal((page("/blog/").querySelector("[data-modal=deletePageItemModal]") as HTMLButtonElement).disabled, true);
            });
        });
    });

    describe("initNewPageModal", () => {
        /**
         * Add the modal to create a page with the given parents, as the panel renders it, and
         * initialize it
         */
        function makeNewPageModal(parents = "") {
            document.body.innerHTML = `
                <div class="modal" id="newPageModal"><div class="modal-container"><form>
                    <div class="form-input-wrap"><select class="form-select" id="newPageModal.parent" name="newPageModal[parent]">
                        <option value=".">Site (/)</option>
                        ${parents}
                    </select></div>
                    <div class="form-input-wrap"><select class="form-select" id="newPageModal.template" name="newPageModal[template]">
                        <option value="page">Page</option>
                        <option value="post">Post</option>
                        <option value="gallery" selected>Gallery</option>
                    </select></div>
                </form></div></div>`;
            // happy-dom selects the option before the one with `selected` when parsing, unlike browsers
            (document.querySelector('[name="newPageModal[template]"]') as HTMLSelectElement).value = "gallery";
            const modal = new Modal(document.getElementById("newPageModal") as HTMLElement);
            initNewPageModal(modal);
            const form = modal.form as Form;
            return { parent: form.inputs["newPageModal[parent]"], template: form.inputs["newPageModal[template]"] };
        }

        const parents = '<option value="/blog/" data-allowed-templates="post">Blog</option><option value="/about/">About</option><option value="/portfolio/" data-allowed-templates="page gallery">Portfolio</option>';

        const disabledTemplates = () => Array.from(document.querySelectorAll<HTMLElement>('.dropdown-list[data-for="newPageModal.template"] .dropdown-item.disabled'), (item) => item.dataset.value);

        /**
         * Choose a parent as the user does
         */
        function chooseParent(value: string) {
            const field = document.getElementById("newPageModal.parent") as HTMLInputElement;
            field.focus();
            const item = document.querySelector(`.dropdown-list[data-for="newPageModal.parent"] [data-value="${value}"]`) as HTMLElement;
            item.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true }));
            item.dispatchEvent(new MouseEvent("click", { bubbles: true, cancelable: true, detail: 1 }));
        }

        test("removes the parameter opening the modal from the address", () => {
            window.history.replaceState({}, "", "/panel/pages/?createNew=true&foo=bar");
            makeNewPageModal();
            assert.equal(window.location.href, "http://example.test/panel/pages/?foo=bar");
        });

        test("allows only the templates accepted by the parent, selecting the first one", () => {
            const { template } = makeNewPageModal(parents);
            chooseParent("/blog/");
            assert.equal(template.value, "post");
            assert.deepEqual(disabledTemplates(), ["page", "gallery"]);
        });

        test("restores the templates and the previous one for a parent accepting all", () => {
            const { template } = makeNewPageModal(parents);
            chooseParent("/blog/");
            chooseParent("/about/");
            assert.equal(template.value, "gallery");
            assert.deepEqual(disabledTemplates(), []);
        });

        test("allows the templates accepted by a parent chosen after another", () => {
            makeNewPageModal(parents);
            chooseParent("/blog/");
            chooseParent("/portfolio/");
            assert.deepEqual(disabledTemplates(), ["post"]);
        });

        test("restores the template chosen by the user after changing parent twice", () => {
            const { template } = makeNewPageModal(parents);
            chooseParent("/blog/");
            chooseParent("/portfolio/");
            chooseParent("/about/");
            assert.equal(template.value, "gallery");
        });
    });

    describe("initDuplicatePageModal", () => {
        test("fills the title and the slug of the copy", () => {
            document.body.innerHTML = `
                <button type="button" id="trigger" data-modal="duplicatePageModal" data-duplicate-title="Foo Bar (copy)">Duplicate</button>
                <div class="modal" id="duplicatePageModal"><div class="modal-container"><form>
                    <input name="duplicatePageModal[title]"><input name="duplicatePageModal[slug]">
                </form></div></div>`;
            const modal = new Modal(document.getElementById("duplicatePageModal") as HTMLElement);
            initDuplicatePageModal(modal);
            (document.getElementById("trigger") as HTMLElement).click();
            assert.equal((document.querySelector('[name="duplicatePageModal[title]"]') as HTMLInputElement).value, "Foo Bar (copy)");
            assert.equal((document.querySelector('[name="duplicatePageModal[slug]"]') as HTMLInputElement).value, "foo-bar-copy");
        });
    });

    describe("initPagePreview", () => {
        /**
         * Add the editor of a page with the preview command and initialize it
         */
        function makeEditor() {
            document.body.innerHTML = `
                <form data-form="page-editor-form">
                    <input name="title" value="Foo">
                    <div class="form-input-wrap"><select class="form-select" id="parent" name="parent"><option value="." selected>Site</option><option value="/blog/">Blog</option></select></div>
                </form>
                <button type="button" data-command="preview">Preview</button>`;
            const element = document.querySelector("form") as HTMLFormElement;
            app.forms["page-editor-form"] = new Form(element, { preventUnloadOnChanges: false });
            initPagePreview(element);
            return { form: app.forms["page-editor-form"], preview: command("preview") };
        }

        test("disables the preview when the parent changes", (t) => {
            t.mock.timers.enable({ apis: ["setTimeout"] });
            const { form, preview } = makeEditor();
            form.inputs.parent.value = "/blog/";
            form.element.dispatchEvent(new Event("input"));
            t.mock.timers.tick(500);
            assert.equal(preview.disabled, true);
            form.inputs.parent.value = ".";
            form.element.dispatchEvent(new Event("input"));
            t.mock.timers.tick(500);
            assert.equal(preview.disabled, false);
        });

        test("marks the preview when the form has unsaved changes", (t) => {
            t.mock.timers.enable({ apis: ["setTimeout"] });
            const { form, preview } = makeEditor();
            assert.equal(preview.classList.contains("button-indicator"), false);
            form.changed.value = true;
            assert.equal(preview.classList.contains("button-indicator"), true);
        });
    });

    describe("initSortable", () => {
        /**
         * Make the top level of the tree sortable, waiting for Sortable to load
         */
        async function makeSortable() {
            makeTree();
            const element = document.querySelector(".pages-tree-root") as HTMLElement;
            initSortable(element);
            await new Promise((resolve) => setTimeout(resolve));
            return { element, sortable: Sortable.get(element) as Sortable };
        }

        const order = (element: HTMLElement) => Array.from(element.children, (item) => (item as HTMLElement).dataset.route);

        /**
         * Move the first page after the second, as dragging it does
         */
        function moveFirstPage(element: HTMLElement, sortable: Sortable) {
            element.insertBefore(element.children[1], element.children[0]);
            sortable.options.onEnd?.({ oldIndex: 0, newIndex: 1 } as Sortable.SortableEvent);
        }

        test("saves the new order of the pages", async () => {
            const { element, sortable } = await makeSortable();
            moveFirstPage(element, sortable);
            assert.equal(XMLHttpRequestStub.last.url, "/panel/pages/reorder/");
            const body = new URLSearchParams(XMLHttpRequestStub.last.body as string);
            assert.deepEqual(JSON.parse(body.get("order") as string), ["/about/", "/blog/", "/contact/"]);
            assert.equal(body.get("parent"), ".");
            assert.equal(sortable.option("disabled"), true);
            XMLHttpRequestStub.last.respondJson("success", "Pages reordered");
            assert.equal(sortable.option("disabled"), false);
            assert.deepEqual(order(element), ["/about/", "/blog/", "/contact/"]);
            assert.deepEqual(shownNotifications(), ["success: Pages reordered"]);
        });

        test("does not save when the order is unchanged", async () => {
            const { sortable } = await makeSortable();
            sortable.options.onEnd?.({ oldIndex: 1, newIndex: 1 } as Sortable.SortableEvent);
            assert.equal(XMLHttpRequestStub.requests.length, 0);
        });

        test("restores the previous order when the server refuses the new one", async () => {
            const { element, sortable } = await makeSortable();
            moveFirstPage(element, sortable);
            XMLHttpRequestStub.last.respondJson("error", "Cannot reorder the pages");
            assert.deepEqual(order(element), ["/blog/", "/about/", "/contact/"]);
            assert.equal(sortable.option("disabled"), false);
        });

        test("restores the order saved last when the server refuses a later one", async () => {
            const { element, sortable } = await makeSortable();
            moveFirstPage(element, sortable);
            XMLHttpRequestStub.last.respondJson("success", "Pages reordered");
            moveFirstPage(element, sortable);
            XMLHttpRequestStub.last.respondJson("error", "Cannot reorder the pages");
            assert.deepEqual(order(element), ["/about/", "/blog/", "/contact/"]);
        });

        test("notifies the failure with the icon of the errors", async () => {
            const { element, sortable } = await makeSortable();
            moveFirstPage(element, sortable);
            XMLHttpRequestStub.last.respondJson("error", "Cannot reorder the pages");
            const notification = document.querySelector(".notification") as HTMLElement;
            assert.equal(notification.classList.contains("notification-danger"), true);
            assert.equal(notification.firstElementChild?.outerHTML, iconMarkup(exclamationOctagon));
            assert.notEqual(iconMarkup(exclamationOctagon), iconMarkup(checkCircle));
        });
    });
});
