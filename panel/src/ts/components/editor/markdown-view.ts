import { baseKeymap } from "prosemirror-commands";
import { history } from "prosemirror-history";
import { keymap } from "prosemirror-keymap";
import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import type { Transaction } from "prosemirror-state";
import { EditorState, Plugin } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { app } from "../../services/app";
import { resolveUri } from "../../utils/uri";
import { buildInputRules } from "./markdown-input-rules";
import { buildKeymap } from "./markdown-keymap";
import { linkTooltip } from "./markdown-link-tooltip";
import { menuPlugin } from "./markdown-menu";
import { placeholderPlugin } from "./markdown-placeholder";
import type { Toolbar } from "./toolbar";

/**
 * Holds the options of the Markdown editor
 */
export interface MarkdownViewOptions {
    editable?: boolean;
    /**
     * URI of the page, against which the relative URIs of links and images are resolved
     */
    baseUri?: string;
    placeholder?: string;
    attributes?: Record<string, string>;
    /**
     * Toolbar to which the buttons of the editor are added
     */
    toolbar?: Toolbar;
}

/**
 * Wraps the Markdown editor (ProseMirror)
 *
 * The handler is called with the Markdown every time the document changes
 */
export class MarkdownView {
    /**
     * ProseMirror view
     */
    view: EditorView;

    constructor(id: string, target: Element, content: string, inputEventHandler: (content: string) => void, options: MarkdownViewOptions = {}) {
        const baseUri = options.baseUri ?? app.config.siteUri;

        this.view = new EditorView(target, {
            state: EditorState.create({
                doc: defaultMarkdownParser.parse(content),
                plugins: [
                    buildInputRules(schema),
                    keymap(buildKeymap(schema)),
                    keymap(baseKeymap),
                    history(),
                    ...(options.toolbar ? [menuPlugin(id, options.toolbar)] : []),
                    placeholderPlugin(options.placeholder ?? ""),
                    linkTooltip(baseUri),
                    new Plugin({
                        props: {
                            handleDOMEvents: {
                                focus: () => target.classList.add("focused"),
                                blur: () => target.classList.remove("focused"),
                            },
                        },
                    }),
                ],
            }),
            attributes: options.attributes,
            editable: () => options.editable ?? true,
            nodeViews: {
                // Images are shown resolving their source as the site does, without changing the content
                image(node) {
                    const image = document.createElement("img");
                    image.src = resolveUri(node.attrs.src, app.config.siteUri, baseUri);
                    image.alt = node.attrs.alt ?? "";
                    if (node.attrs.title) {
                        image.title = node.attrs.title;
                    }
                    return { dom: image };
                },
            },
            dispatchTransaction(this: EditorView, tr: Transaction) {
                this.updateState(this.state.apply(tr));
                if (tr.docChanged) {
                    inputEventHandler(defaultMarkdownSerializer.serialize(tr.doc));
                }
            },
        });
    }

    /**
     * Get the content of the editor as Markdown
     */
    get content() {
        return defaultMarkdownSerializer.serialize(this.view.state.doc);
    }

    /**
     * Set the content of the editor from Markdown
     */
    set content(value: string) {
        this.view.updateState(
            EditorState.create({
                doc: defaultMarkdownParser.parse(value),
                plugins: this.view.state.plugins,
            }),
        );
    }

    /**
     * Return whether the editor is editable
     */
    get editable() {
        return this.view.props.editable ? this.view.props.editable(this.view.state) : true;
    }

    /**
     * Make the editor editable or read-only
     */
    set editable(value: boolean) {
        this.view.setProps({ editable: () => value });
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
