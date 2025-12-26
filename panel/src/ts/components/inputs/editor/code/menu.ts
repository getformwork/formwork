import * as icons from "../../../icons";
import type { EditorView, ViewUpdate } from "@codemirror/view";
import { redo, redoDepth, undo, undoDepth } from "@codemirror/commands";
import { app } from "../../../../app";
import { ViewPlugin } from "@codemirror/view";

function createButton(icon: keyof typeof icons, title: string) {
    const btn = document.createElement("button");
    btn.type = "button";
    btn.className = `button toolbar-button`;
    btn.dataset.tooltip = title;
    btn.ariaLabel = title;
    btn.innerHTML = icons[icon] || "";
    return btn;
}

type Command = (view: EditorView) => boolean;

interface MenuItem {
    command: Command;
    enabler: Command;
    dom: HTMLButtonElement;
    name?: string;
}

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

        const toolbar = this.view.dom.parentNode!.querySelector(".editor-toolbar")!;

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

    destroy() {
        this.dom.remove();
    }

    update(update: ViewUpdate) {
        if (update.docChanged) {
            this.items.forEach(({ dom, enabler }) => (dom.disabled = !enabler(this.view)));
        }
    }
}

export function MenuPlugin() {
    return ViewPlugin.define(
        (view) =>
            new Menu(
                [
                    { dom: createButton("rotateLeft", app.config.EditorInput.labels.undo), command: (view) => undo(view), enabler: (view) => undoDepth(view.state) > 0 },
                    { dom: createButton("rotateRight", app.config.EditorInput.labels.redo), command: (view) => redo(view), enabler: (view) => redoDepth(view.state) > 0 },
                ],
                view,
            ),
    );
}
