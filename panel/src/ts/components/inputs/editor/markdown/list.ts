import { Command, EditorState, TextSelection } from "prosemirror-state";
import { Fragment, Node, NodeType } from "prosemirror-model";
import { wrapInList as baseWrapInList } from "prosemirror-schema-list";
import { lift } from "prosemirror-commands";
import { schema } from "prosemirror-markdown";

const LIST_TYPES = [schema.nodes.bullet_list, schema.nodes.ordered_list];
const LIST_ITEM_TYPES = [schema.nodes.list_item];

function findCommonListNode(state: EditorState): { node: Node; from: number; to: number } | null {
    const range = state.selection.$from.blockRange(state.selection.$to);

    if (!range) {
        return null;
    }

    const node = range.$from.node(-2);

    if (!node || !LIST_TYPES.find((item) => item === node.type)) {
        return null;
    }

    const from = range.$from.posAtIndex(0, -2);

    return { node, from, to: from + node.nodeSize - 1 };
}

function updateContent(content: Fragment, targetListType: NodeType, targetListItemType: NodeType): Fragment {
    let newContent = content;

    for (let i = 0; i < content.childCount; i++) {
        newContent = newContent.replaceChild(i, updateListNode(newContent.child(i), targetListType, targetListItemType));
    }

    return newContent;
}

function getReplacementType(node: Node, target: NodeType, types: Array<NodeType>): NodeType | null {
    return types.find((item) => item === node.type) ? target : null;
}

function updateListNode(node: Node, targetListType: NodeType, targetListItemType: NodeType): Node {
    const newContent = updateContent(node.content, targetListType, targetListItemType);

    const replacementType = getReplacementType(node, targetListType, LIST_TYPES) || getReplacementType(node, targetListItemType, LIST_ITEM_TYPES);

    if (replacementType) {
        return replacementType.create(node.attrs, newContent, node.marks);
    }
    return node.copy(newContent);
}

export function wrapInList(targetListType: NodeType, targetListItemType: NodeType, attrs?: { [key: string]: string | number | boolean }): Command {
    const command = baseWrapInList(targetListType, attrs);

    return (state, dispatch) => {
        if (command(state)) {
            return command(state, dispatch);
        }

        const commonListNode = findCommonListNode(state);

        if (!commonListNode) {
            return false;
        }

        if (dispatch) {
            if (commonListNode.node.type === targetListType) {
                lift(state, dispatch);
                return true;
            }

            const updatedNode = updateListNode(commonListNode.node, targetListType, targetListItemType);

            let tr = state.tr;

            tr = tr.replaceRangeWith(commonListNode.from, commonListNode.to, updatedNode);

            tr = tr.setSelection(new TextSelection(tr.doc.resolve(state.selection.from), tr.doc.resolve(state.selection.to)));

            dispatch(tr);
            return true;
        }

        return commonListNode.node.type !== targetListType;
    };
}
