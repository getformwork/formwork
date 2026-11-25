import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { PagesTreeModel } from "../../../src/ts/models/pages-tree";

/**
 * Stub of an item of the pages tree with its classes and descendants
 */
class ItemStub {
    readonly classList: { contains: (name: string) => boolean };

    readonly children: ItemStub[];

    constructor(classes: string[] = [], children: ItemStub[] = []) {
        this.classList = { contains: (name) => classes.includes(name) };
        this.children = children;
    }

    contains(node: ItemStub): boolean {
        return node === this || this.children.some((child) => child.contains(node));
    }
}

// first (expanded) > second, third; fourth (expanded) > fifth; sixth
let second: ItemStub;
let third: ItemStub;
let first: ItemStub;
let fifth: ItemStub;
let fourth: ItemStub;
let sixth: ItemStub;

/**
 * Make a model of the tree, reading its items from a stub of the document
 */
function makeTree(reordering = false) {
    const items = [first, second, third, fourth, fifth, sixth];
    Object.assign(globalThis, { document: { querySelectorAll: () => items } });
    return new PagesTreeModel(reordering);
}

beforeEach(() => {
    second = new ItemStub(["is-orderable"]);
    third = new ItemStub(["is-orderable"]);
    first = new ItemStub(["has-children", "is-expanded", "is-orderable"], [second, third]);
    fifth = new ItemStub();
    fourth = new ItemStub(["has-children", "is-expanded"], [fifth]);
    sixth = new ItemStub();
});

describe("PagesTreeModel", () => {
    test("reads the state of the items from their classes", () => {
        const tree = makeTree();
        assert.deepEqual(tree.items.value, [first, second, third, fourth, fifth, sixth]);
        assert.deepEqual(tree.parents.value, new Set([first, fourth]));
        assert.deepEqual(tree.expanded.value, new Set([first, fourth]));
        assert.deepEqual(tree.expandable.value, [first, fourth]);
        assert.equal(tree.expandedCount.value, 2);
        assert.equal(tree.orderableCount.value, 3);
    });

    test("expands and collapses all items", () => {
        const tree = makeTree();
        tree.collapseAll();
        assert.equal(tree.expandedCount.value, 0);
        tree.expandAll();
        assert.equal(tree.expandedCount.value, 2);
    });

    test("expands only the items with children", () => {
        const tree = makeTree();
        tree.collapseAll();
        tree.expandAll();
        assert.deepEqual(tree.expanded.value, new Set([first, fourth]));
        assert.equal(tree.expandedCount.value, 2);
    });

    test("does not toggle an item without children", () => {
        const tree = makeTree();
        const expanded = tree.expanded.value;
        tree.toggle(sixth as unknown as HTMLElement);
        assert.equal(tree.expanded.value, expanded);
        assert.deepEqual(tree.expanded.value, new Set([first, fourth]));
    });

    test("toggles an item", () => {
        const tree = makeTree();
        tree.toggle(first as unknown as HTMLElement);
        assert.deepEqual(tree.expanded.value, new Set([fourth]));
        tree.toggle(first as unknown as HTMLElement);
        assert.deepEqual(tree.expanded.value, new Set([fourth, first]));
    });

    test("replaces the expanded items at each change", () => {
        const tree = makeTree();
        const expanded = tree.expanded.value;
        tree.toggle(first as unknown as HTMLElement);
        assert.notEqual(tree.expanded.value, expanded);
        assert.equal(expanded.size, 2);
    });

    test("toggles the reordering", () => {
        const tree = makeTree(true);
        assert.equal(tree.reordering.value, true);
        tree.toggleReordering();
        assert.equal(tree.reordering.value, false);
    });

    test("does not filter before searching", () => {
        const tree = makeTree();
        assert.equal(tree.query.value, null);
        assert.equal(tree.searchRegExp.value, null);
        assert.equal(tree.filtering.value, false);
    });

    test("filters by the searched text", () => {
        const tree = makeTree();
        tree.search("foo");
        assert.equal(tree.filtering.value, true);
        assert.ok(tree.searchRegExp.value?.test("Foo bar"));
        assert.equal(tree.searchRegExp.value?.global, true);
    });

    test("stops filtering when the search is cleared", () => {
        const tree = makeTree();
        tree.search("foo");
        tree.search("");
        assert.equal(tree.filtering.value, false);
        assert.equal(tree.searchRegExp.value, null);
    });

    test("preserves the expanded items while filtering", () => {
        const tree = makeTree();
        tree.toggle(first as unknown as HTMLElement);
        tree.search("foo");
        tree.search("");
        assert.deepEqual(tree.expanded.value, new Set([fourth]));
    });

    describe("remove", () => {
        test("removes an item", () => {
            const tree = makeTree();
            assert.equal(tree.remove(second as unknown as HTMLElement, first as unknown as HTMLElement), null);
            assert.deepEqual(tree.items.value, [first, third, fourth, fifth, sixth]);
            assert.equal(tree.orderableCount.value, 2);
        });

        test("preserves a parent with other children", () => {
            const tree = makeTree();
            tree.remove(second as unknown as HTMLElement, first as unknown as HTMLElement);
            assert.deepEqual(tree.parents.value, new Set([first, fourth]));
            assert.deepEqual(tree.expanded.value, new Set([first, fourth]));
        });

        test("returns and removes from the parents a parent without children left", () => {
            const tree = makeTree();
            assert.equal(tree.remove(fifth as unknown as HTMLElement, fourth as unknown as HTMLElement), fourth);
            assert.deepEqual(tree.items.value, [first, second, third, fourth, sixth]);
            assert.deepEqual(tree.parents.value, new Set([first]));
            assert.deepEqual(tree.expanded.value, new Set([first]));
            assert.deepEqual(tree.expandable.value, [first]);
        });

        test("returns a parent after its last child is removed", () => {
            const tree = makeTree();
            assert.equal(tree.remove(second as unknown as HTMLElement, first as unknown as HTMLElement), null);
            assert.equal(tree.remove(third as unknown as HTMLElement, first as unknown as HTMLElement), first);
        });

        test("removes the descendants of an item", () => {
            const tree = makeTree();
            assert.equal(tree.remove(first as unknown as HTMLElement, null), null);
            assert.deepEqual(tree.items.value, [fourth, fifth, sixth]);
            assert.deepEqual(tree.expandable.value, [fourth]);
            assert.equal(tree.expandedCount.value, 1);
            assert.equal(tree.orderableCount.value, 0);
        });

        test("removes a top-level item", () => {
            const tree = makeTree();
            assert.equal(tree.remove(sixth as unknown as HTMLElement, undefined), null);
            assert.deepEqual(tree.items.value, [first, second, third, fourth, fifth]);
        });
    });
});
