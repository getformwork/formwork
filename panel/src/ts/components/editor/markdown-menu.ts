import { schema } from "prosemirror-markdown";
import type { MarkType, NodeType } from "prosemirror-model";
import type { Command } from "prosemirror-state";
import { NodeSelection, Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { app } from "../../services/app";
import { $$ } from "../../utils/selectors";
import { insertImage, insertLink, isMarkActive, lift, redo, setBlockType, sinkListItem, toggleMark, undo, wrapIn, wrapInList } from "./markdown-commands";
import { createToolbarButton } from "./toolbar-button";

/**
 * Describes an item of the toolbar
 */
interface MenuItem {
    command: Command;
    dom: HTMLElement;
    mark?: MarkType;
    node?: NodeType;
    dropdown?: string;
    name?: string;
    group?: string;
}

/**
 * Manages the toolbar of the Markdown editor
 */
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
                target = this.dropdowns[dropdown].querySelector(".dropdown-menu") as HTMLElement;
            }

            target.appendChild(dom);
        });

        this.update();

        // The focus goes back to the editor only for the items, the button of a menu keeps it
        // so that the menu can be used with the keyboard
        this.dom.addEventListener("click", (e) => {
            const item = items.find(({ dom }) => dom.contains(e.target as HTMLElement));

            if (item) {
                e.preventDefault();
                editorView.focus();
                item.command(editorView.state, editorView.dispatch, editorView);
            }
        });
    }

    /**
     * Update the state of the items
     */
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

    /**
     * Remove the toolbar
     */
    destroy() {
        this.dom.remove();
    }
}

let modalsInitialized = false;

/**
 * Get the plugin that adds the toolbar to the editor
 *
 * @param id Id of the editor
 */
export function menuPlugin(id: string) {
    if (!modalsInitialized) {
        initModals();
        modalsInitialized = true;
    }

    const items = createMenuItems();

    return new Plugin({
        view(editorView: EditorView) {
            const menuView = new MenuView(id, items, editorView);

            const toolbar = editorView.dom.parentNode?.querySelector(".editor-toolbar");
            toolbar?.prepend(menuView.dom);

            return menuView;
        },
    });
}

/**
 * Create the items of the toolbar: the block type menu, the formatting and block buttons, the
 * media buttons, undo and redo
 */
function createMenuItems(): MenuItem[] {
    const t = (key: string, defaultValue: string) => app.translation.get(key, defaultValue);

    const { nodes, marks } = schema;

    // Items of the menu to choose the type of the block
    const blockType = { dropdown: "editor-level", group: "style" };

    const headings = [1, 2, 3, 4, 5, 6].map((level): MenuItem => {
        const name = t(`panel.editor.heading${level}`, `Heading ${level}`);

        return {
            ...blockType,
            name,
            command: setBlockType(nodes.heading, { level }),
            dom: createMenuItem(`<span class="h${level}">${name}</span>`),
        };
    });

    return [
        {
            ...blockType,
            name: t("panel.editor.paragraph", "Paragraph"),
            command: setBlockType(nodes.paragraph),
            dom: createMenuItem(t("panel.editor.paragraph", "Paragraph")),
        },
        {
            ...blockType,
            name: t("panel.editor.code", "Code"),
            node: nodes.code_block,
            command: setBlockType(nodes.code_block),
            dom: createMenuItem(`<span class="text-monospace">${t("panel.editor.code", "Code")}</span>`),
        },
        ...headings,
        {
            mark: marks.strong,
            command: toggleMark(marks.strong),
            dom: createToolbarButton("bold", t("panel.editor.bold", "Bold")),
            group: "style",
        },
        {
            mark: marks.em,
            command: toggleMark(marks.em),
            dom: createToolbarButton("italic", t("panel.editor.italic", "Italic")),
            group: "style",
        },
        {
            mark: marks.code,
            command: toggleMark(marks.code),
            dom: createToolbarButton("code", t("panel.editor.code", "Code")),
            group: "style",
        },
        {
            command: wrapInList(nodes.bullet_list, nodes.list_item),
            dom: createToolbarButton("listUnordered", t("panel.editor.bulletList", "Bullet list")),
            group: "blocks",
        },
        {
            command: wrapInList(nodes.ordered_list, nodes.list_item),
            dom: createToolbarButton("listOrdered", t("panel.editor.numberedList", "Numbered list")),
            group: "blocks",
        },
        {
            node: nodes.blockquote,
            command: wrapIn(nodes.blockquote),
            dom: createToolbarButton("blockquote", t("panel.editor.quote", "Quote")),
            group: "blocks",
        },
        {
            command: sinkListItem(nodes.list_item),
            dom: createToolbarButton("indentIncrease", t("panel.editor.increaseIndent", "Increase indent")),
            group: "blocks",
        },
        {
            command: lift,
            dom: createToolbarButton("indentDecrease", t("panel.editor.decreaseIndent", "Decrease indent")),
            group: "blocks",
        },
        {
            node: nodes.image,
            command: insertImage,
            dom: createToolbarButton("image", t("panel.editor.image", "Image")),
            group: "media",
        },
        {
            mark: marks.link,
            command: insertLink,
            dom: createToolbarButton("link", t("panel.editor.link", "Link")),
            group: "media",
        },
        {
            command: undo,
            dom: createToolbarButton("rotateLeft", t("panel.editor.undo", "Undo")),
        },
        {
            command: redo,
            dom: createToolbarButton("rotateRight", t("panel.editor.redo", "Redo")),
        },
    ];
}

/**
 * Create an item of a toolbar menu
 *
 * @param text Content of the item, as HTML
 */
function createMenuItem(text: string) {
    const item = document.createElement("button");
    item.type = "button";
    item.className = "dropdown-item";
    item.innerHTML = text;
    return item;
}

/**
 * Create a toolbar menu
 *
 * @param id Id of the menu
 */
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

/**
 * Confirm the link modal with Enter
 */
function initModals() {
    const { linkModal } = app.modals;

    $$('[id="linkModal.text"], [id="linkModal.uri"]', linkModal.element).forEach((input) => {
        input.addEventListener("keydown", (event) => {
            if (event.key === "Enter") {
                linkModal.triggerCommand("insert-link");
                event.preventDefault();
            }
        });
    });
}
