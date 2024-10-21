import { debounce } from "../../../../utils/events";
import { EditorView } from "prosemirror-view";
import { Mark } from "prosemirror-model";
import { passIcon } from "../../../icons";
import { Plugin } from "prosemirror-state";
import { schema } from "prosemirror-markdown";
import { Tooltip } from "../../../tooltip";

function addBaseUri(text: string, baseUri: string) {
    return text.replace(/^\/?(?!https?:\/\/)(.+)/, `${baseUri}$1`);
}

class LinkTooltipView {
    editorView: EditorView;
    tooltip?: Tooltip;
    currentLink?: Mark;
    baseUri: string;

    constructor(view: EditorView, baseUri: string) {
        this.editorView = view;

        this.baseUri = baseUri;

        this.editorView.dom.addEventListener(
            "scroll",
            debounce(() => this.destroy(), 100, true),
        );
        this.editorView.dom.addEventListener("blur", () => this.destroy());
    }

    update(view: EditorView) {
        const state = view.state;

        const link = state.selection.$head.marks().find((mark) => mark.type === schema.marks.link);

        if (link) {
            if (this.tooltip) {
                this.tooltip.remove();
            }

            const domAtPos = view?.domAtPos(state.selection.$head.pos);
            const coordsAtPos = view?.coordsAtPos(state.selection.$head.pos);

            passIcon("link", (icon) => {
                this.tooltip = new Tooltip(`${icon} <a href="${addBaseUri(link.attrs.href, this.baseUri)}" target="_blank">${link.attrs.href}</a>`, {
                    referenceElement: domAtPos?.node.parentElement as HTMLElement,
                    position: {
                        x: coordsAtPos.left + window.scrollX,
                        y: coordsAtPos.top + window.scrollY,
                    },
                    offset: { x: 0, y: -24 },
                    removeOnMouseout: false,
                    delay: 0,
                    zIndex: 7,
                });

                this.tooltip.show();
            });
        } else {
            this.destroy();
        }
    }

    destroy() {
        if (this.tooltip) {
            const tooltip = this.tooltip;
            setTimeout(() => tooltip.remove(), 100);
        }
        this.tooltip = undefined;
    }
}

export function linkTooltip(baseUri: string): Plugin {
    return new Plugin({
        view(editorView: EditorView) {
            return new LinkTooltipView(editorView, baseUri);
        },
    });
}
