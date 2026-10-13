import { baseKeymap } from "prosemirror-commands";
import { history } from "prosemirror-history";
import { keymap } from "prosemirror-keymap";
import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import type { Transaction } from "prosemirror-state";
import { EditorState, Plugin } from "prosemirror-state";
import { EditorView } from "prosemirror-view";
import { app } from "../../../../app";
import { buildInputRules } from "./inputrules";
import { buildKeymap } from "./keymap";
import { linkTooltip } from "./linktooltip";
import { menuPlugin } from "./menu";
import { placeholderPlugin } from "./placeholder";

export interface MarkdownViewOptions {
    editable?: boolean;
    placeholder?: string;
    attributes?: Record<string, string>;
}

export class MarkdownView {
    view: EditorView;

    constructor(id: string, target: Element, content: string, inputEventHandler: (content: string) => void, options: MarkdownViewOptions = {}) {
        this.view = new EditorView(target, {
            state: EditorState.create({
                doc: defaultMarkdownParser.parse(content),
                plugins: [
                    buildInputRules(schema),
                    keymap(buildKeymap(schema)),
                    keymap(baseKeymap),
                    history(),
                    menuPlugin(id),
                    placeholderPlugin(options.placeholder ?? ""),
                    linkTooltip(app.config.siteUri),
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
            dispatchTransaction(this: EditorView, tr: Transaction) {
                this.updateState(this.state.apply(tr));
                if (tr.docChanged) {
                    inputEventHandler(defaultMarkdownSerializer.serialize(tr.doc));
                }
            },
        });
    }

    get content() {
        return defaultMarkdownSerializer.serialize(this.view.state.doc);
    }

    set content(value: string) {
        this.view.updateState(
            EditorState.create({
                doc: defaultMarkdownParser.parse(value),
                plugins: this.view.state.plugins,
            }),
        );
    }

    get editable() {
        return this.view.props.editable ? this.view.props.editable(this.view.state) : true;
    }

    set editable(value: boolean) {
        this.view.setProps({ editable: () => value });
    }

    focus() {
        this.view.focus();
    }

    destroy() {
        this.view.destroy();
    }
}
