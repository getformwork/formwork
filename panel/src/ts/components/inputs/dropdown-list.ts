import { effect, type Signal, signal } from "@preact/signals-core";
import { $, $$ } from "../../utils/selectors";

const isDisplayed = (element: HTMLElement) => getComputedStyle(element).display !== "none";

/**
 * Represents the list of options of the select and tags inputs
 *
 * Items are hidden by the filter and skipped by the selection if they are hidden or disabled
 */
export class DropdownList {
    readonly element: HTMLElement;

    /**
     * Selected item
     */
    readonly selected = signal<HTMLElement | null>(null);

    /**
     * Whether the list is open, or `null` if it has not been opened or closed yet
     */
    readonly open: Signal<boolean | null>;

    constructor(element: HTMLElement, open: boolean | null = null) {
        this.element = element;

        this.open = signal(open);

        effect(() => {
            if (this.open.value !== null) {
                this.element.style.display = this.open.value ? "block" : "none";
            }
        });

        effect(() => {
            const selected = this.selected.value;

            for (const item of this.items) {
                item.classList.toggle("selected", item === selected);
            }
        });
    }

    /**
     * Get the items of the list
     */
    get items() {
        return Array.from($$(".dropdown-item", this.element));
    }

    /**
     * Get the item with a value
     *
     * @returns Item, or `null` if there is none
     */
    find(value: string) {
        return $<HTMLElement>(`.dropdown-item[data-value="${CSS.escape(value)}"]`, this.element);
    }

    /**
     * Select an item and scroll the list to show it
     *
     * A disabled item cannot be selected, so the selection is cleared
     *
     * @param item Item to select, or `null` to clear the selection
     */
    select(item: HTMLElement | null) {
        const isSelectable = item !== null && !item.classList.contains("disabled");

        this.selected.value = isSelectable ? item : null;

        if (isSelectable) {
            this.scrollTo(item);
        }
    }

    /**
     * Select the first selectable item
     */
    selectFirst() {
        const item = this.items.find((element) => this.isSelectable(element));

        if (item) {
            this.select(item);
        }
    }

    /**
     * Select the last selectable item
     */
    selectLast() {
        const item = this.items.reverse().find((element) => this.isSelectable(element));

        if (item) {
            this.select(item);
        }
    }

    /**
     * Select the previous item, or the last one if there is none
     */
    selectPrevious() {
        const items = this.items;
        const index = this.selected.value ? items.indexOf(this.selected.value) : -1;
        const previous = items
            .slice(0, Math.max(index, 0))
            .reverse()
            .find((item) => this.isSelectable(item));

        if (previous) {
            this.select(previous);
        } else {
            this.selectLast();
        }
    }

    /**
     * Select the next item, or the first one if there is none
     */
    selectNext() {
        const items = this.items;
        const index = this.selected.value ? items.indexOf(this.selected.value) : -1;
        const next = index === -1 ? undefined : items.slice(index + 1).find((item) => this.isSelectable(item));

        if (next) {
            this.select(next);
        } else {
            this.selectFirst();
        }
    }

    /**
     * Show only the items matching a condition
     *
     * @param isVisible Function returning whether an item is shown
     *
     * @returns Number of items shown
     */
    filter(isVisible: (item: HTMLElement) => boolean) {
        let visibleItems = 0;

        for (const item of this.items) {
            const visible = isVisible(item);
            item.style.display = visible ? "block" : "none";
            if (visible) {
                visibleItems++;
            }
        }

        return visibleItems;
    }

    /**
     * Return the number of displayed items
     */
    countDisplayed() {
        return this.items.filter(isDisplayed).length;
    }

    /**
     * Sort the items by value
     */
    sort() {
        const sorted = this.items.sort((a, b) => (a.dataset.value as string).localeCompare(b.dataset.value as string));

        for (const item of sorted) {
            this.element.appendChild(item);
        }
    }

    /**
     * Scroll the list to show an item
     */
    scrollTo(item: HTMLElement) {
        const scrollTop = this.element.scrollTop;
        const height = this.element.clientHeight;
        const scrollBottom = scrollTop + height;
        const style = getComputedStyle(this.element);
        const paddingTop = parseInt(style.paddingTop, 10);
        const paddingBottom = parseInt(style.paddingBottom, 10);
        const itemTop = item.offsetTop;
        const itemBottom = itemTop + item.clientHeight;

        if (itemTop < scrollTop) {
            this.element.scrollTop = itemTop - paddingTop;
        } else if (itemBottom > scrollBottom) {
            this.element.scrollTop = itemBottom - height + paddingBottom;
        }
    }

    /**
     * Return whether an item is displayed and not disabled
     */
    private isSelectable(item: HTMLElement) {
        return isDisplayed(item) && !item.classList.contains("disabled");
    }
}
