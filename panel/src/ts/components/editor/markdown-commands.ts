import type { Mark, MarkType, Node, NodeType } from "prosemirror-model";
import type { Command, EditorState } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";

export { lift, setBlockType, toggleMark, wrapIn } from "prosemirror-commands";
export { sinkListItem, wrapInList } from "prosemirror-schema-list";

import { redo as historyRedo, undo as historyUndo, redoDepth, undoDepth } from "prosemirror-history";
import { app } from "../../services/app";
import { $ } from "../../utils/selectors";
import type { ImagePicker } from "../inputs/image-picker";
import { schema } from "./markdown-schema";

/**
 * Open the modal to insert or edit a link
 */
export const insertLink: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return true;
    }

    if (!view) {
        return false;
    }

    let { from, to } = state.selection;

    // The link is changed only if the selection is inside it, otherwise the selection is linked
    const linkRange = getMarkRange(state, schema.marks.link);
    const range = linkRange && from >= linkRange.from && to <= linkRange.to ? linkRange : null;

    if (range) {
        from = range.from;
        to = range.to;
    }

    const selectedText = state.doc.textBetween(from, to);

    const { linkModal } = app.modals;

    const textInput = $('[id="linkModal.text"]', linkModal.element) as HTMLInputElement;
    const uriInput = $('[id="linkModal.uri"]', linkModal.element) as HTMLInputElement;
    const removeCommand = $('[data-command="remove-link"]', linkModal.element) as HTMLButtonElement;

    removeCommand.disabled = !range;
    textInput.value = selectedText;

    if (range) {
        uriInput.value = range.mark.attrs.href;
        uriInput.setSelectionRange(0, uriInput.value.length);
    } else {
        uriInput.value = "https://";
        uriInput.setSelectionRange(8, 8);
    }

    linkModal.onOpen(() => {
        uriInput.focus();
    });

    linkModal.onCommand("insert-link", (modal) => {
        if (uriInput.value) {
            const text = textInput.value || uriInput.value;
            if (range && range.mark.attrs.href === uriInput.value && selectedText === text) {
                modal.close();
                return;
            }
            const linkMark = schema.marks.link.create({ href: uriInput.value });
            // The formatting of the text is kept unless the text changes
            const tr = text === selectedText ? view.state.tr.removeMark(from, to, schema.marks.link) : view.state.tr.insertText(text, from, to);
            dispatch(tr.addMark(from, from + text.length, linkMark));
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

/**
 * Remove the link at the selection
 */
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

/**
 * Open the modal to insert an image
 */
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
                // Files of the page are relative to its route
                const filename = selected.dataset.filename;
                view.dispatch(
                    state.tr.replaceSelectionWith(
                        schema.nodes.image.createAndFill({
                            src: filename,
                            alt: "",
                        }) as Node,
                    ),
                );
                view.focus();
            }

            modal.close();
        });
    }
    return true;
};

/**
 * Undo the last change
 */
export const undo: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return undoDepth(state) > 0;
    }
    return historyUndo(state, dispatch, view);
};

/**
 * Redo the last undone change
 */
export const redo: Command = (state, dispatch, view) => {
    if (!dispatch) {
        return redoDepth(state) > 0;
    }
    return historyRedo(state, dispatch, view);
};

/**
 * Scroll the selection into view without adding to the history
 */
export function updateView(view: EditorView) {
    const tr = view.state.tr.scrollIntoView();
    tr.setMeta("addToHistory", false);
    view.dispatch(tr);
}

/**
 * Return whether a node can be inserted at the selection
 */
export function canInsert(state: EditorState, nodeType: NodeType) {
    const $from = state.selection.$from;
    for (let d = $from.depth; d >= 0; d--) {
        const index = $from.index(d);
        if ($from.node(d).canReplaceWith(index, index, nodeType)) {
            return true;
        }
    }
    return false;
}

/**
 * Return whether a mark is active at the selection
 */
export function isMarkActive(state: EditorState, type: MarkType) {
    const { from, $from, to, empty } = state.selection;
    if (empty) {
        return !!type.isInSet(state.storedMarks || $from.marks());
    }
    return state.doc.rangeHasMark(from, to, type);
}

/**
 * Get the range of the text having a mark at the cursor
 *
 * The range spans the adjacent texts with the same mark, e.g. the parts of a link with different
 * formatting
 *
 * @returns Range and mark, or `null` if the cursor is not in a text having the mark
 */
export function getMarkRange(state: EditorState, markType: MarkType): { from: number; to: number; mark: Mark } | null {
    const { $from } = state.selection;
    const parent = $from.parent;

    let range: { from: number; to: number; mark: Mark } | null = null;
    let current: { from: number; to: number; mark: Mark } | null = null;
    let position = $from.start();

    for (let index = 0; index < parent.childCount; index++) {
        const node = parent.child(index);
        const mark = node.isText ? markType.isInSet(node.marks) : undefined;
        const nodeEnd = position + node.nodeSize;

        if (mark && current?.mark.eq(mark)) {
            current.to = nodeEnd;
        } else {
            current = mark ? { from: position, to: nodeEnd, mark } : null;
        }

        // A later range containing the cursor at its start is preferred
        if (current && current.from <= $from.pos && current.to >= $from.pos) {
            range = current;
        }

        position = nodeEnd;
    }

    return range;
}
