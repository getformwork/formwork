import { defaultKeymap, history, historyKeymap } from "@codemirror/commands";
import { EditorView, keymap } from "@codemirror/view";
import { HighlightStyle, syntaxHighlighting } from "@codemirror/language";
import { markdown } from "@codemirror/lang-markdown";
import { MenuPlugin } from "./menu";
import { tags } from "@lezer/highlight";

const theme = EditorView.theme({
    "&": {
        color: "var(--color-base-100)",
        backgroundColor: "var(--color-base-900)",
    },
    ".cm-content": {
        padding: "1rem",
        fontSize: "0.875rem",
        fontFamily: '"SFMono-Regular", "SF Mono", "Cascadia Mono", "Liberation Mono", "Menlo", "Consolas", monospace',
        lineHeight: "1.5",
    },
    "&.cm-focused": {
        outline: "none",
    },
    "&.cm-selectionBackground": {
        backgroundColor: "var(--color-selection)",
    },
});

const myHighlightStyle = HighlightStyle.define([
    { tag: tags.comment, color: "#777" },
    { tag: tags.link, textDecoration: "underline" },
    { tag: tags.heading, textDecoration: "underline", fontWeight: "bold" },
    { tag: tags.emphasis, fontStyle: "italic" },
    { tag: tags.strong, fontWeight: "bold" },
    { tag: tags.strikethrough, textDecoration: "line-through" },
    { tag: tags.keyword, color: "#dd4a68" },
    { tag: tags.url, color: "#1d86e1" },
    { tag: [tags.atom, tags.bool, tags.contentSeparator, tags.labelName, tags.literal, tags.typeName, tags.namespace], color: "#047d65" },
    { tag: [tags.regexp, tags.escape, tags.string], color: "#b35e14" },
    { tag: [tags.typeName, tags.namespace], color: "#085" },
    { tag: [tags.variableName, tags.macroName, tags.propertyName, tags.className], color: "#1d75b3" },
]);

export class CodeView {
    view: EditorView;

    constructor(target: Element, content: string, inputEventHandler: (content: string) => void) {
        this.view = new EditorView({
            doc: content,
            extensions: [
                EditorView.lineWrapping,
                history(),
                syntaxHighlighting(myHighlightStyle, { fallback: true }),
                markdown(),
                MenuPlugin(),
                theme,
                keymap.of([...defaultKeymap, ...historyKeymap]),
                EditorView.domEventHandlers({
                    focus: () => target.classList.add("focused"),
                    blur: () => target.classList.remove("focused"),
                    input: () => inputEventHandler(this.content),
                }),
                EditorView.updateListener.of((update) => {
                    if (update.docChanged) {
                        inputEventHandler(this.content);
                    }
                }),
            ],
            parent: target,
        });
    }

    get content() {
        return this.view.state.doc.toString();
    }

    focus() {
        this.view.focus();
    }

    destroy() {
        this.view.destroy();
    }
}
