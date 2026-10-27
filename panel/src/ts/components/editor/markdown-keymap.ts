import { chainCommands, exitCode, joinDown, joinUp, lift, selectParentNode, setBlockType, toggleMark, wrapIn } from "prosemirror-commands";
import { redo, undo } from "prosemirror-history";
import { undoInputRule } from "prosemirror-inputrules";
import type { Schema } from "prosemirror-model";
import { liftListItem, sinkListItem, splitListItem, wrapInList } from "prosemirror-schema-list";
import type { Command } from "prosemirror-state";

const mac = typeof navigator !== "undefined" ? /Mac|iP(hone|[oa]d)/.test(navigator.platform) : false;

/**
 * Build the keymap of the editor
 *
 * @param mapKeys Keys to rename, or to disable with `false`
 */
export function buildKeymap(schema: Schema, mapKeys?: { [key: string]: false | string }) {
    const keys: { [key: string]: Command } = {};

    /**
     * Bind a command to a key
     */
    function bind(key: string, cmd: Command) {
        if (mapKeys) {
            const mapped = mapKeys[key];
            if (mapped === false) {
                return;
            }
            if (mapped) {
                key = mapped;
            }
        }
        keys[key] = cmd;
    }

    bind("Mod-z", undo);
    bind("Shift-Mod-z", redo);
    bind("Backspace", undoInputRule);
    if (!mac) {
        bind("Mod-y", redo);
    }

    bind("Alt-ArrowUp", joinUp);
    bind("Alt-ArrowDown", joinDown);
    bind("Mod-BracketLeft", lift);
    bind("Escape", selectParentNode);

    if (schema.marks.strong) {
        bind("Mod-b", toggleMark(schema.marks.strong));
        bind("Mod-B", toggleMark(schema.marks.strong));
    }
    if (schema.marks.em) {
        bind("Mod-i", toggleMark(schema.marks.em));
        bind("Mod-I", toggleMark(schema.marks.em));
    }
    if (schema.marks.code) {
        bind("Mod-`", toggleMark(schema.marks.code));
    }

    if (schema.nodes.bullet_list) {
        bind("Shift-Ctrl-8", wrapInList(schema.nodes.bullet_list));
    }
    if (schema.nodes.ordered_list) {
        bind("Shift-Ctrl-9", wrapInList(schema.nodes.ordered_list));
    }
    if (schema.nodes.blockquote) {
        bind("Ctrl->", wrapIn(schema.nodes.blockquote));
    }
    if (schema.nodes.hard_break) {
        const br = schema.nodes.hard_break;
        const cmd = chainCommands(exitCode, (state, dispatch) => {
            if (dispatch) {
                dispatch(state.tr.replaceSelectionWith(br.create()).scrollIntoView());
            }
            return true;
        });
        bind("Mod-Enter", cmd);
        bind("Shift-Enter", cmd);
        if (mac) {
            bind("Ctrl-Enter", cmd);
        }
    }
    if (schema.nodes.list_item) {
        bind("Enter", splitListItem(schema.nodes.list_item));
        bind("Mod-[", liftListItem(schema.nodes.list_item));
        bind("Mod-]", sinkListItem(schema.nodes.list_item));
    }
    if (schema.nodes.paragraph) {
        bind("Shift-Ctrl-0", setBlockType(schema.nodes.paragraph));
    }
    if (schema.nodes.code_block) {
        bind("Shift-Ctrl-\\", setBlockType(schema.nodes.code_block));
    }
    if (schema.nodes.heading) {
        for (let i = 1; i <= 6; i++) {
            bind(`Shift-Ctrl-${i}`, setBlockType(schema.nodes.heading, { level: i }));
        }
    }
    if (schema.nodes.horizontal_rule) {
        const hr = schema.nodes.horizontal_rule;
        bind("Mod-_", (state, dispatch) => {
            if (dispatch) {
                dispatch(state.tr.replaceSelectionWith(hr.create()).scrollIntoView());
            }
            return true;
        });
    }

    return keys;
}
