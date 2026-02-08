import type { Command, EditorState } from "prosemirror-state";
import type { Mark, MarkType, Node, NodeType } from "prosemirror-model";
import type { EditorView } from "prosemirror-view";
export { lift, setBlockType, toggleMark, wrapIn } from "prosemirror-commands";
export { sinkListItem, wrapInList } from "prosemirror-schema-list";
import { redo as historyRedo, undo as historyUndo, redoDepth, undoDepth } from "prosemirror-history";
import { $ } from "../../../../utils/selectors";
import { app } from "../../../../app";
import type { ImagePicker } from "../../image-picker";
import { schema } from "prosemirror-markdown";

export const insertLink: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return true;
    }

    if (!view) {
        return false;
    }

    let { from, to } = state.selection;

    const range = getMarkRange(state, schema.marks.link);
    if (range && from >= range.from && to <= range.to) {
        from = range.from;
        to = range.to;
    }

    const { linkModal } = app.modals;

    const textInput = $('[id="linkModal.text"]', linkModal.element) as HTMLInputElement;
    const uriInput = $('[id="linkModal.uri"]', linkModal.element) as HTMLInputElement;
    const removeCommand = $('[data-command="remove-link"]', linkModal.element) as HTMLButtonElement;

    removeCommand.disabled = !range;
    textInput.value = "";

    if (range) {
        textInput.value = range.node.textContent;
        uriInput.value = range.mark.attrs.href;
        uriInput.setSelectionRange(0, uriInput.value.length);
    } else {
        textInput.value = state.doc.textBetween(from, to);
        uriInput.value = "https://";
        uriInput.setSelectionRange(8, 8);
    }

    linkModal.onOpen(() => {
        uriInput.focus();
    });

    linkModal.onCommand("insert-link", (modal) => {
        if (uriInput.value) {
            const text = textInput.value || uriInput.value;
            if (range && range.mark.attrs.href === uriInput.value && range.node.text === text) {
                modal.close();
                return;
            }
            const linkMark = schema.marks.link.create({ href: uriInput.value });
            dispatch(view.state.tr.insertText(text, from, to).addMark(from, from + text.length, linkMark));
        } else {
            dispatch(view.state.tr.removeMark(from, to, schema.marks.link));
        }
        modal.close();
    });

    linkModal.onCommand("remove-link", (modal) => {
        dispatch(view.state.tr.removeMark(from, to, schema.marks.link));
        modal.close();
    });

    linkModal.onClose(() => {
        updateView(view);
        view.focus();
    });

    linkModal.open();

    return true;
};

export const removeLink: Command = (state, dispatch, view) => {
    let { from, to } = state.selection;

    const range = getMarkRange(state, schema.marks.link);

    if (!dispatch) {
        return !!range;
    }

    if (!view) {
        return false;
    }

    if (range && from >= range.from && to <= range.to) {
        from = range.from;
        to = range.to;
    }

    dispatch(view.state.tr.removeMark(from, to, schema.marks.link));

    return true;
};

export const insertImage: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return canInsert(state, schema.nodes.image);
    }
    if (view) {
        const { imagesModal } = app.modals;

        imagesModal.onBeforeOpen((modal) => {
            const imagePicker = modal.form?.inputs[`${imagesModal.element.id}[imagepicker]`] as ImagePicker;
            imagePicker.update();
        });

        imagesModal.open();

        imagesModal.onCommand("pick-image", (modal) => {
            const selected = $(".image-picker-thumbnail.selected", modal.element);

            if (selected && view) {
                const baseUri = $("textarea", view.dom.parentNode!)!.dataset.baseUri;
                const filename = selected.dataset.filename;
                view.dispatch(
                    state.tr.replaceSelectionWith(
                        schema.nodes.image.createAndFill({
                            src: `${baseUri}${filename}`,
                            alt: "",
                        })!,
                    ),
                );
                view.focus();
            }

            modal.close();
        });
    }
    return true;
};

export const undo: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return undoDepth(state) > 0;
    }
    return historyUndo(state, dispatch, view);
};

export const redo: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return redoDepth(state) > 0;
    }
    return historyRedo(state, dispatch, view);
};

export function updateView(view: EditorView) {
    const tr = view.state.tr.scrollIntoView();
    tr.setMeta("addToHistory", false);
    view.dispatch(tr);
}

export function canInsert(state: EditorState, nodeType: NodeType) {
    const $from = state.selection.$from;
    for (let d = $from.depth; d >= 0; d--) {
        const index = $from.index(d);
        if ($from.node(d).canReplaceWith(index, index, nodeType)) return true;
    }
    return false;
}

export function isMarkActive(state: EditorState, type: MarkType) {
    const { from, $from, to, empty } = state.selection;
    if (empty) {
        return !!type.isInSet(state.storedMarks || $from.marks());
    }
    return state.doc.rangeHasMark(from, to, type);
}

export function getMarkRange(state: EditorState, markType: MarkType): { from: number; to: number; mark: Mark; node: Node } | null {
    const { $from } = state.selection;
    let from = $from.pos;
    let to = $from.pos;

    const parent = $from.parent;
    let found = false;
    let foundMark: Mark;

    parent.forEach((node, offset) => {
        if (!node.isText) {
            return;
        }
        node.marks.forEach((mark) => {
            if (mark.type === markType) {
                const nodeStart = $from.start() + offset;
                const nodeEnd = nodeStart + node.nodeSize;
                if (nodeStart <= $from.pos && nodeEnd >= $from.pos) {
                    from = nodeStart;
                    to = nodeEnd;
                    found = true;
                    foundMark = mark;
                }
            }
        });
    });

    return found
        ? {
              from: from,
              to: to,
              mark: foundMark!,
              node: state.doc.nodeAt(from)!,
          }
        : null;
}
