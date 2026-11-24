import assert from "node:assert/strict";
import { describe, test } from "node:test";
import type { Node } from "prosemirror-model";
import { markdownParser, markdownSerializer, schema } from "../../../../src/ts/components/editor/markdown-schema";

/**
 * Parse Markdown and serialize it back
 */
const roundTrip = (markdown: string) => markdownSerializer.serialize(markdownParser.parse(markdown));

/**
 * Get the first table of a document
 */
function firstTable(doc: Node) {
    let table: Node | undefined;
    doc.descendants((node) => {
        table ??= node.type === schema.nodes.table ? node : undefined;
        return !table;
    });
    return table as Node;
}

describe("Markdown Schema", () => {
    describe("parser", () => {
        test("parses the first row of a table as its header", () => {
            const table = firstTable(markdownParser.parse("| foo | bar |\n| --- | --- |\n| baz | qux |"));
            assert.deepEqual(
                Array.from({ length: table.childCount }, (_, index) => {
                    const row = table.child(index);
                    return Array.from({ length: row.childCount }, (_, cell) => `${row.child(cell).type.name}:${row.child(cell).textContent}`);
                }),
                [
                    ["table_header:foo", "table_header:bar"],
                    ["table_cell:baz", "table_cell:qux"],
                ],
            );
        });

        test("wraps the content of the cells in a paragraph", () => {
            const table = firstTable(markdownParser.parse("| **foo** |  |\n| --- | --- |"));
            const [first, second] = [table.child(0).child(0), table.child(0).child(1)];
            assert.equal(first.childCount, 1);
            assert.equal(first.child(0).type, schema.nodes.paragraph);
            assert.equal(first.child(0).child(0).marks[0].type, schema.marks.strong);
            assert.equal(second.child(0).type, schema.nodes.paragraph);
            assert.equal(second.child(0).content.size, 0);
        });

        test("parses the alignment of the columns", () => {
            const table = firstTable(markdownParser.parse("| foo | bar | baz | qux |\n| :-- | :-: | --: | --- |\n| 1 | 2 | 3 | 4 |"));
            for (const row of [table.child(0), table.child(1)]) {
                assert.deepEqual(
                    Array.from({ length: row.childCount }, (_, index) => row.child(index).attrs.align),
                    ["left", "center", "right", null],
                );
            }
        });

        test("parses tables inside quotes", () => {
            const doc = markdownParser.parse("> | foo |\n> | --- |\n> | bar |");
            assert.equal(doc.child(0).type, schema.nodes.blockquote);
            assert.equal(doc.child(0).child(0).type, schema.nodes.table);
        });
    });

    describe("serializer", () => {
        test("preserves the Markdown of tables", () => {
            const markdown = "foo\n\n| foo | bar |\n| --- | --- |\n| baz | qux |\n\nbar";
            assert.equal(roundTrip(markdown), markdown);
        });

        test("aligns the columns to their widest cell", () => {
            assert.equal(roundTrip("| foo | bar |\n|-|-|\n| Hello world | baz |"), "| foo         | bar |\n| ----------- | --- |\n| Hello world | baz |");
        });

        test("writes delimiters of at least three characters", () => {
            assert.equal(roundTrip("| a | b |\n|-|-|\n| c | d |"), "| a   | b   |\n| --- | --- |\n| c   | d   |");
        });

        test("writes the alignment of the columns, padding the cells on its sides", () => {
            assert.equal(roundTrip("| foo | bar | baz | qux |\n| :-- | :-: | --: | --- |\n| Hello | Hello | Hello | Hello |"), "| foo   |  bar  |   baz | qux   |\n| :---- | :---: | ----: | ----- |\n| Hello | Hello | Hello | Hello |");
        });

        test("counts wide characters as two columns and combining marks as none", () => {
            assert.equal(roundTrip("| 東京 | 😀 | é |\n|-|-|-|\n| foo | bar | baz |"), "| 東京 | 😀  | é   |\n| ---- | --- | --- |\n| foo  | bar | baz |");
        });

        test("escapes the pipes in the cells", () => {
            assert.equal(roundTrip("| foo \\| bar | `baz\\|qux` |\n|-|-|"), "| foo \\| bar | `baz\\|qux` |\n| ---------- | ---------- |");
        });

        test("writes the line breaks of the cells as spaces", () => {
            const { table, table_row, table_header, paragraph, hard_break } = schema.nodes;
            const cell = table_header.create(null, paragraph.create(null, [schema.text("foo"), hard_break.create(), schema.text("bar")]));
            const doc = schema.node("doc", null, [table.create(null, table_row.create(null, cell))]);
            assert.equal(markdownSerializer.serialize(doc), "| foo bar |\n| ------- |");
        });

        test("writes empty cells", () => {
            assert.equal(roundTrip("| foo |  |\n|-|-|\n|  | bar |"), "| foo |     |\n| --- | --- |\n|     | bar |");
        });

        test("tells consecutive lists of the same type apart with alternate markers", () => {
            assert.equal(roundTrip("1. foo\n\n1) bar\n\n1. baz"), "1. foo\n\n1) bar\n\n1. baz");
            assert.equal(roundTrip("* foo\n\n- bar"), "* foo\n\n- bar");
            assert.equal(roundTrip("> 1. foo\n>\n> 1) bar"), "> 1. foo\n>\n> 1) bar");
            assert.equal(markdownParser.parse(roundTrip("1. foo\n\n1) bar")).childCount, 2);
        });

        test("aligns the numbers of the ordered lists", () => {
            const items = Array.from({ length: 10 }, (_, index) => `${index + 1}. foo`).join("\n");
            const markdown = roundTrip(items);
            assert.equal(markdown.split("\n")[0], " 1. foo");
            assert.equal(markdown.split("\n")[9], "10. foo");
        });

        test("writes tables inside quotes with the prefix of the quote", () => {
            assert.equal(roundTrip("> | foo |\n> |-|\n> | bar |"), "> | foo |\n> | --- |\n> | bar |");
        });
    });
});
