import { ellipsis, emDash, inputRules, smartQuotes, textblockTypeInputRule, wrappingInputRule } from "prosemirror-inputrules";
import { NodeType, Schema } from "prosemirror-model";

export function blockQuoteRule(nodeType: NodeType) {
    return wrappingInputRule(/^\s*>\s$/, nodeType);
}

export function orderedListRule(nodeType: NodeType) {
    return wrappingInputRule(
        /^(\d+)\.\s$/,
        nodeType,
        (match) => ({ order: +match[1] }),
        (match, node) => node.childCount + node.attrs.order === +match[1],
    );
}

export function bulletListRule(nodeType: NodeType) {
    return wrappingInputRule(/^\s*([-+*])\s$/, nodeType);
}

export function codeBlockRule(nodeType: NodeType) {
    return textblockTypeInputRule(/^```$/, nodeType);
}

export function headingRule(nodeType: NodeType, maxLevel: number) {
    return textblockTypeInputRule(new RegExp(`^(#{1,${maxLevel}})\\s$`), nodeType, (match) => ({ level: match[1].length }));
}

export function buildInputRules(schema: Schema) {
    const rules = smartQuotes.concat(ellipsis, emDash);
    let type;
    if ((type = schema.nodes.blockquote)) rules.push(blockQuoteRule(type));
    if ((type = schema.nodes.ordered_list)) rules.push(orderedListRule(type));
    if ((type = schema.nodes.bullet_list)) rules.push(bulletListRule(type));
    if ((type = schema.nodes.code_block)) rules.push(codeBlockRule(type));
    if ((type = schema.nodes.heading)) rules.push(headingRule(type, 6));
    return inputRules({ rules });
}
