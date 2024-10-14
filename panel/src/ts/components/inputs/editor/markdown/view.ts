import { defaultMarkdownParser, defaultMarkdownSerializer, schema } from "prosemirror-markdown";
import { EditorState, Plugin, Transaction } from "prosemirror-state";
import { baseKeymap } from "prosemirror-commands";
import { buildInputRules } from "./inputrules";
import { buildKeymap } from "./keymap";
import { EditorView } from "prosemirror-view";
import { history } from "prosemirror-history";
import { keymap } from "prosemirror-keymap";
import { menuPlugin } from "./menu";

export class MarkdownView {
    view: EditorView;

    constructor(target: Element, content: string, inputEventHandler: (content: string) => void, attributes: { [key: string]: string } = {}) {
        this.view = new EditorView(target, {
            state: EditorState.create({
                doc: defaultMarkdownParser.parse(content) as any,
                plugins: [
                    buildInputRules(schema),
                    keymap(buildKeymap(schema)),
                    keymap(baseKeymap),
                    history(),
                    menuPlugin(),
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
            attributes,
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

    focus() {
        this.view.focus();
    }

    destroy() {
        this.view.destroy();
    }
}
