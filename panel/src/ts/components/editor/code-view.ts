import { defaultKeymap, history, historyKeymap } from "@codemirror/commands";
import { markdown } from "@codemirror/lang-markdown";
import { HighlightStyle, syntaxHighlighting } from "@codemirror/language";
import { Compartment } from "@codemirror/state";
import { EditorView, keymap, placeholder } from "@codemirror/view";
import { tags } from "@lezer/highlight";
import { MenuPlugin } from "./code-menu";

/**
 * Holds the options of the code editor
 */
interface CodeViewOptions {
    editable?: boolean;
    placeholder?: string;
}

/**
 * Theme of the editor
 */
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

/**
 * Syntax highlighting of the editor
 */
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

/**
 * Configuration that makes the editor read-only
 */
const editable = new Compartment();

/**
 * Wraps the code editor (CodeMirror)
 *
 * The handler is called with the content every time it changes
 */
export class CodeView {
    /**
     * CodeMirror view
     */
    view: EditorView;

    constructor(target: Element, content: string, inputEventHandler: (content: string) => void, options: CodeViewOptions = {}) {
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
                editable.of(EditorView.editable.of(options.editable ?? true)),
                placeholder(options.placeholder ?? ""),
            ],
            parent: target,
        });
    }

    /**
     * Get the content of the editor
     */
    get content() {
        return this.view.state.doc.toString();
    }

    /**
     * Set the content of the editor
     */
    set content(value: string) {
        this.view.dispatch({
            changes: { from: 0, to: this.view.state.doc.length, insert: value },
        });
    }

    /**
     * Return whether the editor is editable
     */
    get editable() {
        return this.view.state.facet(EditorView.editable);
    }

    /**
     * Make the editor editable or read-only
     */
    set editable(value: boolean) {
        this.view.dispatch({
            effects: editable.reconfigure(EditorView.editable.of(value)),
        });
    }

    /**
     * Focus the editor
     */
    focus() {
        this.view.focus();
    }

    /**
     * Remove the editor
     */
    destroy() {
        this.view.destroy();
    }
}
