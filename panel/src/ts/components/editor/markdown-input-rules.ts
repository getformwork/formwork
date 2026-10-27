import { ellipsis, emDash, inputRules, smartQuotes, textblockTypeInputRule, wrappingInputRule } from "prosemirror-inputrules";
import type { NodeType, Schema } from "prosemirror-model";

/**
 * Convert the `> ` shortcut to a quote
 */
export function blockQuoteRule(nodeType: NodeType) {
    return wrappingInputRule(/^\s*>\s$/, nodeType);
}

/**
 * Convert the `1. ` shortcut to a numbered list
 */
export function orderedListRule(nodeType: NodeType) {
    return wrappingInputRule(
        /^(\d+)\.\s$/,
        nodeType,
        (match) => ({ order: +match[1] }),
        (match, node) => node.childCount + node.attrs.order === +match[1],
    );
}

/**
 * Convert the `- `, `+ ` and `* ` shortcuts to a bullet list
 */
export function bulletListRule(nodeType: NodeType) {
    return wrappingInputRule(/^\s*([-+*])\s$/, nodeType);
}

/**
 * Convert three backticks to a code block
 */
export function codeBlockRule(nodeType: NodeType) {
    return textblockTypeInputRule(/^```$/, nodeType);
}

/**
 * Convert `#` shortcuts to a heading
 *
 * @param maxLevel Maximum level of the heading
 */
export function headingRule(nodeType: NodeType, maxLevel: number) {
    return textblockTypeInputRule(new RegExp(`^(#{1,${maxLevel}})\\s$`), nodeType, (match) => ({ level: match[1].length }));
}

/**
 * Build the input rules of the editor
 */
export function buildInputRules(schema: Schema) {
    const rules = smartQuotes.concat(ellipsis, emDash);

    if (schema.nodes.blockquote) {
        rules.push(blockQuoteRule(schema.nodes.blockquote));
    }
    if (schema.nodes.ordered_list) {
        rules.push(orderedListRule(schema.nodes.ordered_list));
    }
    if (schema.nodes.bullet_list) {
        rules.push(bulletListRule(schema.nodes.bullet_list));
    }
    if (schema.nodes.code_block) {
        rules.push(codeBlockRule(schema.nodes.code_block));
    }
    if (schema.nodes.heading) {
        rules.push(headingRule(schema.nodes.heading, 6));
    }

    return inputRules({ rules });
}
