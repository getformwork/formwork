import markdownit, { type StateCore, type Token } from "markdown-it";
import { schema as baseSchema, defaultMarkdownParser, defaultMarkdownSerializer, MarkdownParser, MarkdownSerializer, type MarkdownSerializerState } from "prosemirror-markdown";
import type { Node, NodeSpec } from "prosemirror-model";
import { Schema } from "prosemirror-model";
import { tableNodes } from "prosemirror-tables";

/**
 * Alignment of a table column, as in the delimiter row of a GFM table
 */
export type Alignment = "left" | "center" | "right" | null;

/**
 * Make the lists created in the editor tight, without blank lines between their items, as the
 * parser does with all the lists
 */
function tightByDefault(spec: NodeSpec): NodeSpec {
    return { ...spec, attrs: { ...spec.attrs, tight: { default: true } } };
}

const nodes = baseSchema.spec.nodes.update("bullet_list", tightByDefault(baseSchema.spec.nodes.get("bullet_list") as NodeSpec)).update("ordered_list", tightByDefault(baseSchema.spec.nodes.get("ordered_list") as NodeSpec));

/**
 * Schema of the Markdown editor: CommonMark with GFM tables
 *
 * The cells hold a single paragraph, as GFM tables have only inline content
 */
export const schema = new Schema({
    nodes: nodes.append(
        tableNodes({
            tableGroup: "block",
            cellContent: "paragraph",
            cellAttributes: {
                align: {
                    default: null,
                    getFromDOM: (dom) => (dom.style.textAlign as Alignment) || null,
                    setDOMAttr: (value, attrs) => {
                        if (value) {
                            attrs.style = `${attrs.style ?? ""}text-align: ${value};`;
                        }
                    },
                },
            },
        }),
    ),
    marks: baseSchema.spec.marks,
});

const tokenizer = markdownit("commonmark", { html: false }).enable("table");

// The content of the cells is wrapped in a paragraph, as the schema requires
tokenizer.core.ruler.push("table_cell_paragraph", (state: StateCore) => {
    const tokens: Token[] = [];

    for (const token of state.tokens) {
        const inCell = tokens.length > 0 && /^t[hd]_open$/.test(tokens[tokens.length - 1].type);

        if (inCell && token.type === "inline") {
            tokens.push(new state.Token("paragraph_open", "p", 1), token, new state.Token("paragraph_close", "p", -1));
        } else if (/^t[hd]_close$/.test(token.type) && /^t[hd]_open$/.test(tokens[tokens.length - 1].type)) {
            // Empty cells get an empty paragraph too
            tokens.push(new state.Token("paragraph_open", "p", 1), new state.Token("paragraph_close", "p", -1), token);
        } else {
            tokens.push(token);
        }
    }

    state.tokens = tokens;
});

/**
 * Get the alignment of a cell from the style markdown-it gives it
 */
function getAlignment(token: Token) {
    return { align: (token.attrGet("style")?.match(/text-align:\s*(left|center|right)/)?.[1] as Alignment) ?? null };
}

export const markdownParser = new MarkdownParser(schema, tokenizer, {
    ...defaultMarkdownParser.tokens,
    table: { block: "table" },
    thead: { ignore: true },
    tbody: { ignore: true },
    tr: { block: "table_row" },
    th: { block: "table_header", getAttrs: getAlignment },
    td: { block: "table_cell", getAttrs: getAlignment },
    // The lists are always tight, also the loose ones of the Markdown
    bullet_list: { block: "bullet_list", getAttrs: () => ({ tight: true }) },
    ordered_list: { block: "ordered_list", getAttrs: (token) => ({ order: Number(token.attrGet("start")) || 1, tight: true }) },
});

/**
 * Characters taking two columns in monospaced fonts: wide East Asian characters and emoji
 */
const wideCharacters = /\p{Extended_Pictographic}|[\u1100-\u115F\u2E80-\uA4CF\uAC00-\uD7A3\uF900-\uFAFF\uFE30-\uFE4F\uFF00-\uFF60\uFFE0-\uFFE6]/u;

/**
 * Characters taking no columns: combining marks, joiners and variation selectors
 */
const zeroWidthCharacters = /\p{M}|\u200D|[\uFE00-\uFE0F]/u;

/**
 * Get the width of a text in a monospaced font, to align the columns of the tables
 */
function textWidth(text: string) {
    let width = 0;
    for (const character of text) {
        width += zeroWidthCharacters.test(character) ? 0 : wideCharacters.test(character) ? 2 : 1;
    }
    return width;
}

/**
 * Pad the text of a cell to the width of its column, on the sides given by the alignment
 */
function padCell(text: string, width: number, align: Alignment) {
    const padding = width - textWidth(text);
    const before = align === "right" ? padding : align === "center" ? Math.floor(padding / 2) : 0;
    return `${" ".repeat(before)}${text}${" ".repeat(padding - before)}`;
}

/**
 * Get the delimiter of a column with its alignment
 */
function delimiter(width: number, align: Alignment) {
    const start = align === "left" || align === "center" ? ":" : "-";
    const end = align === "right" || align === "center" ? ":" : "-";
    return `${start}${"-".repeat(width - 2)}${end}`;
}

/**
 * Serialize the content of a cell on a single line, escaping the pipes
 */
function serializeCell(cell: Node) {
    return markdownSerializer.serialize(cell).replace(/\\?\n/g, " ").replace(/\|/g, "\\|").trim();
}

/**
 * Return whether a list follows an odd number of lists of its type, which it is told apart from by
 * an alternate marker, as Markdown would join consecutive lists with the same marker
 */
function followsSameList(node: Node, parent: Node, index: number) {
    let count = 0;
    for (let i = index - 1; i >= 0 && parent.child(i).type === node.type; i--) {
        count++;
    }
    return count % 2 === 1;
}

/**
 * Close the list before a list of the same type with a single blank line, instead of the two that
 * the default serializer leaves, as the alternate markers already tell the lists apart
 */
function separateFromSameList(state: MarkdownSerializerState, node: Node, parent: Node, index: number) {
    if (index > 0 && parent.child(index - 1).type === node.type) {
        // Writing closes the previous block
        state.write("");
    }
}

/**
 * Serializer of the nodes written by the serializer of the table
 */
function writtenByTable() {
    // Rows and cells are written with their table
}

export const markdownSerializer = new MarkdownSerializer(
    {
        ...defaultMarkdownSerializer.nodes,
        bullet_list(state, node, parent, index) {
            separateFromSameList(state, node, parent, index);
            const bullet = followsSameList(node, parent, index) ? "-" : "*";
            state.renderList(node, "  ", () => `${bullet} `);
        },
        ordered_list(state, node, parent, index) {
            separateFromSameList(state, node, parent, index);
            const delimiter = followsSameList(node, parent, index) ? ")" : ".";
            const start: number = node.attrs.order ?? 1;
            const width = String(start + node.childCount - 1).length;
            state.renderList(node, " ".repeat(width + 2), (i) => `${String(start + i).padStart(width)}${delimiter} `);
        },
        table(state, node) {
            const rows: string[][] = [];

            node.forEach((row) => {
                const cells: string[] = [];
                row.forEach((cell) => {
                    cells.push(serializeCell(cell));
                });
                rows.push(cells);
            });

            const header = node.firstChild as Node;
            const columns = Math.max(...rows.map((cells) => cells.length));
            const alignments: Alignment[] = [];
            const widths: number[] = [];

            for (let column = 0; column < columns; column++) {
                alignments.push(header.maybeChild(column)?.attrs.align ?? null);
                // Delimiters have at least three characters
                widths.push(Math.max(3, ...rows.map((cells) => textWidth(cells[column] ?? ""))));
            }

            // The columns are aligned also in the Markdown
            const line = (cells: string[]) => `| ${cells.join(" | ")} |`;
            const lines = rows.map((cells) => line(widths.map((width, column) => padCell(cells[column] ?? "", width, alignments[column]))));
            lines.splice(1, 0, line(widths.map((width, column) => delimiter(width, alignments[column]))));

            for (const [index, line] of lines.entries()) {
                if (index > 0) {
                    state.ensureNewLine();
                }
                state.write(line);
            }

            state.closeBlock(node);
        },
        table_row: writtenByTable,
        table_header: writtenByTable,
        table_cell: writtenByTable,
    },
    defaultMarkdownSerializer.marks,
);
