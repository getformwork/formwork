import { EditorState, Plugin } from "prosemirror-state";
import { Mark, MarkType } from "prosemirror-model";
import { debounce } from "../../../../utils/events";
import { EditorView } from "prosemirror-view";
import { passIcon } from "../../../icons";
import { schema } from "prosemirror-markdown";
import { Tooltip } from "../../../tooltip";

class LinkTooltipView {
    editorView: EditorView;
    tooltip?: Tooltip;
    currentLink?: Mark;

    constructor(view: EditorView) {
        this.editorView = view;

        this.editorView.dom.addEventListener(
            "scroll",
            debounce(
                () => {
                    if (this.tooltip) {
                        this.tooltip.remove();
                    }
                    this.tooltip = undefined;
                    this.currentLink = undefined;
                },
                100,
                true,
            ),
        );
    }

    update(view: EditorView) {
        const state = view.state;

        if (isMarkActive(state, schema.marks.link)) {
            const link = state.selection.$head.marks().find((mark) => mark.type === schema.marks.link);

            if (link) {
                if (this.currentLink && link.eq(this.currentLink)) {
                    return;
                }

                this.currentLink = link;

                if (this.tooltip) {
                    this.tooltip.remove();
                }

                const domAtPos = view?.domAtPos(state.selection.$head.pos);

                passIcon("link", (icon) => {
                    this.tooltip = new Tooltip(`${icon}${link.attrs.href}`, {
                        referenceElement: domAtPos?.node.parentElement as HTMLElement,
                        container: view.dom.parentElement as HTMLElement,
                        removeOnMouseout: false,
                        delay: 0,
                    });

                    this.tooltip.show();
                });
            }
        } else {
            if (this.tooltip) {
                this.tooltip.remove();
            }
            this.tooltip = undefined;
            this.currentLink = undefined;
        }
    }
}

export function linkTooltip(): Plugin {
    return new Plugin({
        view(editorView: EditorView) {
            return new LinkTooltipView(editorView);
        },
    });
}

function isMarkActive(state: EditorState, type: MarkType) {
    const { from, $from, to, empty } = state.selection;
    if (empty) {
        return !!type.isInSet(state.storedMarks || $from.marks());
    }
    return state.doc.rangeHasMark(from, to, type);
}
