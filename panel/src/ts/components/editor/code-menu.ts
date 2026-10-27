import { redo, redoDepth, undo, undoDepth } from "@codemirror/commands";
import type { EditorView, ViewUpdate } from "@codemirror/view";
import { ViewPlugin } from "@codemirror/view";
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

        items.forEach(({ dom, enabler }) => {
            this.dom.appendChild(dom);
            dom.disabled = !enabler(this.view);
        });

        const toolbar = this.view.dom.parentNode?.querySelector(".editor-toolbar") as HTMLElement;

        toolbar.insertBefore(this.dom, toolbar.firstChild);

        this.dom.addEventListener("mousedown", (e) => {
            e.preventDefault();
            editorView.focus();
            items.forEach(({ command, dom }) => {
                if (dom.contains(e.target as HTMLElement)) {
                    command(editorView);
                }
            });
        });
    }

    /**
     * Remove the toolbar
     */
    destroy() {
        this.dom.remove();
    }

    /**
     * Enable or disable the buttons when the document changes
     */
    update(update: ViewUpdate) {
        if (update.docChanged) {
            this.items.forEach(({ dom, enabler }) => {
                dom.disabled = !enabler(this.view);
            });
        }
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
