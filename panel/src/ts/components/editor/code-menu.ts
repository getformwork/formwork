import { redo, redoDepth, undo, undoDepth } from "@codemirror/commands";
import { EditorView, ViewPlugin } from "@codemirror/view";
import { app } from "../../services/app";
import { createToolbarButton } from "./toolbar-button";

/**
 * Represents a command of the code editor
 */
type Command = (view: EditorView) => boolean;

/**
 * Describes a button of the toolbar
 */
interface MenuItem {
    command: Command;
    enabler: Command;
    dom: HTMLButtonElement;
    name?: string;
}

/**
 * Manages the toolbar of the code editor
 */
class Menu {
    items: MenuItem[];
    dom: HTMLElement;
    view: EditorView;

    constructor(items: MenuItem[], editorView: EditorView) {
        this.items = items;
        this.view = editorView;

        this.dom = document.createElement("div");

        items.forEach(({ dom }) => {
            this.dom.appendChild(dom);
        });

        this.updateItems();

        const toolbar = this.view.dom.parentNode?.querySelector(".editor-toolbar") as HTMLElement;

        toolbar.insertBefore(this.dom, toolbar.firstChild);

        // The buttons do not take the focus from the editor when pressed with the pointer
        this.dom.addEventListener("mousedown", (e) => e.preventDefault());

        // The commands run on click, so that the buttons also work with the keyboard
        this.dom.addEventListener("click", (e) => {
            const item = items.find(({ dom }) => dom.contains(e.target as HTMLElement));
            if (item && !item.dom.disabled) {
                editorView.focus();
                item.command(editorView);
            }
        });
    }

    /**
     * Remove the toolbar
     */
    destroy() {
        this.dom.remove();
    }

    /**
     * Enable or disable the buttons when the editor changes, e.g. when it becomes read-only
     */
    update() {
        this.updateItems();
    }

    /**
     * Enable the buttons that can be used, none if the editor is read-only
     */
    private updateItems() {
        const editable = this.view.state.facet(EditorView.editable);
        this.items.forEach(({ dom, enabler }) => {
            dom.disabled = !(editable && enabler(this.view));
        });
    }
}

/**
 * Get the CodeMirror extension that adds the toolbar to the editor
 */
export function MenuPlugin() {
    return ViewPlugin.define(
        (view) =>
            new Menu(
                [
                    { dom: createToolbarButton("rotateLeft", app.translation.get("panel.editor.undo", "Undo")), command: (view) => undo(view), enabler: (view) => undoDepth(view.state) > 0 },
                    { dom: createToolbarButton("rotateRight", app.translation.get("panel.editor.redo", "Redo")), command: (view) => redo(view), enabler: (view) => redoDepth(view.state) > 0 },
                ],
                view,
            ),
    );
}
