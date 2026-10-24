import { computed, createModel, signal } from "@preact/signals-core";
import { makeSearchRegExp } from "../utils/search";
import { $$ } from "../utils/selectors";

/**
 * Holds the state of the pages tree rendered by the server
 *
 * The signals mirror the initial classes of the items, the DOM is updated by the effects of the
 * view
 */
export const PagesTreeModel = createModel((initialReordering: boolean) => {
    const items = signal(Array.from($$(".pages-tree-item")));
    const parents = signal(new Set(items.value.filter((item) => item.classList.contains("has-children"))));
    const expanded = signal(new Set(items.value.filter((item) => item.classList.contains("is-expanded"))));

    // `null` means that no search has been performed yet
    const query = signal<string | null>(null);

    const reordering = signal(initialReordering);

    const expandable = computed(() => items.value.filter((item) => parents.value.has(item)));
    const expandedCount = computed(() => expandable.value.filter((item) => expanded.value.has(item)).length);
    const orderableCount = computed(() => items.value.filter((item) => item.classList.contains("is-orderable")).length);

    const searchRegExp = computed(() => (query.value ? makeSearchRegExp(query.value, { global: true }) : null));

    // While searching every item is shown expanded, without losing the expanded state
    const filtering = computed(() => searchRegExp.value !== null);

    return {
        items,
        parents,
        expanded,
        query,
        reordering,
        expandable,
        expandedCount,
        orderableCount,
        searchRegExp,
        filtering,

        /**
         * Expand all items
         */
        expandAll() {
            expanded.value = new Set(items.value);
        },

        /**
         * Collapse all items
         */
        collapseAll() {
            expanded.value = new Set();
        },

        /**
         * Expand an item, or collapse it if expanded
         */
        toggle(item: HTMLElement) {
            const next = new Set(expanded.value);
            if (!next.delete(item)) {
                next.add(item);
            }
            expanded.value = next;
        },

        /**
         * Turn the reordering of the pages on or off
         */
        toggleReordering() {
            reordering.value = !reordering.value;
        },

        /**
         * Filter the tree by a text
         */
        search(value: string) {
            query.value = value;
        },

        /**
         * Remove an item and its descendants from the tree
         *
         * @param removed    Removed item
         * @param parentItem Parent of the removed item
         *
         * @returns Parent item if it has no children left, or `null`
         */
        remove(removed: HTMLElement, parentItem: HTMLElement | null | undefined) {
            items.value = items.value.filter((item) => !removed.contains(item));

            const emptiedParent = parentItem && !items.value.some((item) => item !== parentItem && parentItem.contains(item)) ? parentItem : null;

            if (emptiedParent) {
                parents.value = new Set([...parents.value].filter((item) => item !== emptiedParent));
                expanded.value = new Set([...expanded.value].filter((item) => item !== emptiedParent));
            }

            return emptiedParent;
        },
    };
});

/**
 * Represents an instance of the model
 */
export type PagesTree = InstanceType<typeof PagesTreeModel>;
