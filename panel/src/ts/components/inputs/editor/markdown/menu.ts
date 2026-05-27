import * as icons from "../../../icons";
import { insertImage, insertLink, isMarkActive, lift, redo, setBlockType, sinkListItem, toggleMark, undo, wrapIn, wrapInList } from "./commands";
import type { MarkType, NodeType } from "prosemirror-model";
import { NodeSelection, Plugin } from "prosemirror-state";
import { $$ } from "../../../../utils/selectors";
import { app } from "../../../../app";
import type { Command } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { schema } from "prosemirror-markdown";

interface MenuItem {
    command: Command;
    dom: HTMLElement;
    mark?: MarkType;
    node?: NodeType;
    dropdown?: string;
    name?: string;
    group?: string;
}

class MenuView {
    items: MenuItem[];
    editorView: EditorView;
    dom: HTMLElement;
    dropdowns: { [name: string]: HTMLElement };

    constructor(id: string, items: MenuItem[], editorView: EditorView) {
        this.items = items;
        this.editorView = editorView;

        this.dom = document.createElement("div");

        this.dropdowns = {};

        let currentGroup: string | undefined;

        items.forEach(({ dom, dropdown, group }, index) => {
            let target = this.dom;

            if (index > 0 && group !== currentGroup) {
                const separator = document.createElement("div");
                separator.className = "separator";
                target.appendChild(separator);
            }

            currentGroup = group;

            if (dropdown) {
                if (!(dropdown in this.dropdowns)) {
                    const el = createDropdown(`${id}-${dropdown}`);
                    this.dropdowns[dropdown] = el;
                    target.appendChild(el);
                }
                target = this.dropdowns[dropdown].querySelector(".dropdown-menu")!;
            }

            target.appendChild(dom);
        });

        this.update();

        this.dom.addEventListener("click", (e) => {
            e.preventDefault();
            editorView.focus();
            items.forEach(({ command, dom }) => {
                if (dom.contains(e.target as HTMLElement)) {
                    command(editorView.state, editorView.dispatch, editorView);
                }
            });
        });
    }

    update() {
        this.items.forEach(({ command, dom, mark, dropdown, name, node }) => {
            const state = this.editorView.state;
            const editable = this.editorView.props.editable ? this.editorView.props.editable(state) : true;

            const applicable = editable && command(state, undefined, this.editorView);

            if (dom instanceof HTMLButtonElement) {
                dom.disabled = !applicable;
                dom.classList.remove("is-active");
            }

            if (dropdown && name) {
                const btn = this.dropdowns[dropdown].querySelector(".dropdown-button") as HTMLButtonElement;
                btn.disabled = !editable;
                if (!applicable) {
                    btn.textContent = name;
                }
                dom.classList.toggle("is-active", !applicable);
            }

            if (applicable && node) {
                dom.classList.toggle("is-active", state.selection instanceof NodeSelection && state.selection.node.type === node);
            }

            if (applicable && mark) {
                dom.classList.toggle("is-active", isMarkActive(state, mark));
            }
        });
    }

    destroy() {
        this.dom.remove();
    }
}

let modalsInitialized = false;

export function menuPlugin(id: string) {
    if (!modalsInitialized) {
        initModals();
        modalsInitialized = true;
    }

    const labels = app.config.EditorInput?.labels;

    const items = [
        {
            name: labels?.paragraph ?? "",
            command: setBlockType(schema.nodes.paragraph),
            dom: createMenuItem(labels?.paragraph ?? ""),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.code ?? "",
            node: schema.nodes.code_block,
            command: setBlockType(schema.nodes.code_block),
            dom: createMenuItem(`<span class="text-monospace">${labels?.code ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading1 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 1 }),
            dom: createMenuItem(`<span class="h1">${labels?.heading1 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading2 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 2 }),
            dom: createMenuItem(`<span class="h2">${labels?.heading2 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading3 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 3 }),
            dom: createMenuItem(`<span class="h3">${labels?.heading3 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading4 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 4 }),
            dom: createMenuItem(`<span class="h4">${labels?.heading4 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading5 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 5 }),
            dom: createMenuItem(`<span class="h5">${labels?.heading5 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            name: labels?.heading6 ?? "",
            command: setBlockType(schema.nodes.heading, { level: 6 }),
            dom: createMenuItem(`<span class="h6">${labels?.heading6 ?? ""}</span>`),
            dropdown: "editor-level",
            group: "style",
        },
        {
            mark: schema.marks.strong,
            command: toggleMark(schema.marks.strong),
            dom: createButton("bold", labels?.bold ?? ""),
            group: "style",
        },
        {
            mark: schema.marks.em,
            command: toggleMark(schema.marks.em),
            dom: createButton("italic", labels?.italic ?? ""),
            group: "style",
        },
        {
            mark: schema.marks.code,
            command: toggleMark(schema.marks.code),
            dom: createButton("code", labels?.code ?? ""),
            group: "style",
        },
        {
            command: wrapInList(schema.nodes.bullet_list, schema.nodes.list_item),
            dom: createButton("listUnordered", labels?.bulletList ?? ""),
            group: "blocks",
        },
        {
            command: wrapInList(schema.nodes.ordered_list, schema.nodes.list_item),
            dom: createButton("listOrdered", labels?.numberedList ?? ""),
            group: "blocks",
        },
        {
            node: schema.nodes.blockquote,
            command: wrapIn(schema.nodes.blockquote),
            dom: createButton("blockquote", labels?.quote ?? ""),
            group: "blocks",
        },
        {
            command: sinkListItem(schema.nodes.list_item),
            dom: createButton("indentIncrease", labels?.increaseIndent ?? ""),
            group: "blocks",
        },
        {
            command: lift,
            dom: createButton("indentDecrease", labels?.decreaseIndent ?? ""),
            group: "blocks",
        },
        {
            node: schema.nodes.image,
            command: insertImage,
            dom: createButton("image", labels?.image ?? ""),
            group: "media",
        },
        {
            mark: schema.marks.link,
            command: insertLink,
            dom: createButton("link", labels?.link ?? ""),
            group: "media",
        },
        {
            command: undo,
            dom: createButton("rotateLeft", labels?.undo ?? ""),
        },
        {
            command: redo,
            dom: createButton("rotateRight", labels?.redo ?? ""),
        },
    ];

    return new Plugin({
        view(editorView: EditorView) {
            const menuView = new MenuView(id, items, editorView);

            const toolbar = editorView.dom.parentNode!.querySelector(".editor-toolbar");
            toolbar!.prepend(menuView.dom);

            return menuView;
        },
    });
}

function createButton(icon: keyof typeof icons, title: string) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `button toolbar-button`;
    btn.dataset.tooltip = title;
    btn.ariaLabel = title;
    btn.innerHTML = icons[icon] || "";
    return btn;
}

function createMenuItem(text: string) {
    const item = document.createElement("button");
    item.type = "button";
    item.className = "dropdown-item";
    item.innerHTML = text;
    return item;
}

function createDropdown(id: string) {
    const dropdown = document.createElement("div");
    dropdown.className = "dropdown";

    const btn = document.createElement("button");
    btn.type = "button";
    btn.classList.add("button", "toolbar-button", "dropdown-button", "caret");
    btn.dataset.dropdown = `dropdown-${id}`;

    dropdown.appendChild(btn);

    const menu = document.createElement("div");
    menu.className = "dropdown-menu";
    menu.id = `dropdown-${id}`;

    dropdown.appendChild(menu);

    return dropdown;
}

function initModals() {
    const { linkModal } = app.modals;

    $$('[id="linkModal.text"], [id="linkModal.uri"]', linkModal.element).forEach((input) =>
        input.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                linkModal.triggerCommand("insert-link");
                event.preventDefault();
            }
        }),
    );
}
