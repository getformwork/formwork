import { effect, signal } from "@preact/signals-core";
import type { SortableEvent } from "sortablejs";
import { app } from "../../services/app";
import { debounce } from "../../utils/events";
import { makeSearchRegExp } from "../../utils/search";
import { $$ } from "../../utils/selectors";
import { toCamelCase } from "../../utils/strings";
import * as icons from "../ui/icons";
import { DropdownList } from "./dropdown-list";
import { InputBase } from "./input";

/**
 * Holds the options of a tags input
 */
export interface TagsInputOptions {
    labels: { [key: string]: string };
    addKeyCodes: string[];
    limit: number;
    accept: "options" | "any";
    orderable: boolean;
}

/**
 * Describes a suggested tag
 */
interface TagsInputDropdownItem {
    label: string;
    value: string;
    icon?: keyof typeof icons;
    thumb?: string;
}

/**
 * Get the tags of a value, ignoring the empty ones
 */
function parseTags(value: string) {
    return value
        .split(", ")
        .map((tag) => tag.trim())
        .filter((tag) => tag !== "");
}

/**
 * Provides an input with a list of tags followed by a text field
 *
 * Tags are added with Enter or comma and removed with Backspace. With `data-options` the field
 * suggests tags while typing. The value is the list of tags separated by commas
 */
export class TagsInput extends InputBase<HTMLInputElement> {
    private options: TagsInputOptions;
    private readonly tags = signal<string[]>([]);
    private readonly tagValues = new WeakMap<HTMLElement, string>();
    private placeholder: string = "";
    private dropdown: DropdownList | undefined;

    private field: HTMLDivElement;
    private list: HTMLSpanElement;
    private innerInput: HTMLInputElement;

    constructor(element: HTMLInputElement, options: Partial<TagsInputOptions> = {}) {
        const defaults = {
            labels: {
                remove: app.translation.get("fields.tags.remove", "Remove"),
            },
            addKeyCodes: ["Comma"],
            limit: Infinity,
            accept: "options" as "options" | "any",
            orderable: true,
        };

        super(element);

        this.options = { ...defaults, ...options };

        this.field = document.createElement("div");
        this.list = document.createElement("span");
        this.innerInput = document.createElement("input");

        this.createField();
        this.createDropdown();

        this.registerInputEvents();
    }

    /**
     * Get the tags separated by commas
     */
    get value() {
        return this.element.value;
    }

    /**
     * Set the tags
     */
    set value(value: string) {
        this.element.value = value;
        this.list.replaceChildren();
        this.tags.value = parseTags(value);
        this.updatePlaceholder();
        this.updateDropdown();
    }

    /**
     * Create the field with the tags and the text field
     */
    private createField() {
        this.readDataAttributes();
        this.replaceElement();

        this.tags.value = parseTags(this.element.value);

        effect(() => this.renderTags(this.tags.value));

        if (this.innerInput.placeholder) {
            this.placeholder = this.innerInput.placeholder;
            this.updatePlaceholder();
        }

        this.field.addEventListener("mousedown", (event) => {
            this.innerInput.focus();
            event.preventDefault();
        });

        if (this.options.orderable) {
            void this.makeSortable();
        }
    }

    /**
     * Read the options set by the `data-*` attributes of the native input
     */
    private readDataAttributes() {
        const { dataset } = this.element;

        if ("limit" in dataset) {
            this.options.limit = parseInt(dataset.limit as string, 10);
        }

        if ("accept" in dataset) {
            this.options.accept = (dataset.accept ?? "options") as "options" | "any";
        }

        if (!("orderable" in dataset)) {
            this.options.orderable = false;
        }
    }

    /**
     * Replace the native input with the field, hiding it from users and assistive technologies
     */
    private replaceElement() {
        this.field.className = "form-input-tags-wrap";

        this.innerInput.className = "form-input";
        this.innerInput.type = "text";
        this.innerInput.id = this.element.id;
        this.innerInput.placeholder = this.element.placeholder;

        const describedBy = this.element.getAttribute("aria-describedby");

        if (describedBy) {
            this.innerInput.setAttribute("aria-describedby", describedBy);
        }

        this.element.classList.remove("form-input");
        this.element.removeAttribute("id");
        this.element.removeAttribute("placeholder");
        this.element.readOnly = true;
        this.element.hidden = true;
        this.element.tabIndex = -1;
        this.element.ariaHidden = "true";

        if (this.element.disabled) {
            this.innerInput.disabled = true;
        }

        (this.element.parentNode as ParentNode).replaceChild(this.field, this.element);
        this.field.appendChild(this.list);
        this.field.appendChild(this.innerInput);
        this.field.appendChild(this.element);
    }

    /**
     * Let the user reorder the tags by dragging them (Sortable is loaded on demand)
     */
    private async makeSortable() {
        const { default: Sortable } = await import("sortablejs");

        Sortable.create(this.list, {
            forceFallback: true,
            animation: 150,
            filter: ".tag-remove",
            preventOnFilter: false,

            onChoose: () => {
                if (this.dropdown) {
                    this.dropdown.open.value = false;
                }
            },

            onStart: () => this.field.classList.add("is-dragging"),

            onEnd: ({ oldIndex, newIndex }: SortableEvent) => {
                this.field.classList.remove("is-dragging");
                this.innerInput.blur();
                this.innerInput.focus();

                if (oldIndex !== undefined && newIndex !== undefined && oldIndex !== newIndex) {
                    this.moveTag(oldIndex, newIndex);
                }
            },
        });
    }

    /**
     * Move a tag to another position
     */
    private moveTag(oldIndex: number, newIndex: number) {
        const tags = [...this.tags.value];
        tags.splice(newIndex, 0, tags.splice(oldIndex, 1)[0]);
        this.tags.value = tags;
        this.updateTags();
    }

    /**
     * Add a suggested tag
     */
    addDropdownItem(option: TagsInputDropdownItem) {
        if (!this.dropdown) {
            return;
        }

        const item = document.createElement("div");

        item.className = "dropdown-item";
        item.textContent = option.label;
        item.dataset.value = option.value;

        if (option.thumb) {
            const img = document.createElement("img");
            img.src = option.thumb;
            img.className = "dropdown-thumb";
            item.insertAdjacentElement("afterbegin", img);
        } else if (option.icon) {
            // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
            item.insertAdjacentHTML("afterbegin", icons[option.icon]);
        }

        item.addEventListener("click", () => {
            if (item.dataset.value) {
                this.addTag(item.dataset.value);
            }
        });

        this.dropdown.element.appendChild(item);
    }

    /**
     * Remove a suggested tag and the tag if added
     */
    removeDropdownItem(value: string) {
        this.dropdown?.find(value)?.remove();
        this.updateDropdown();
        this.removeTag(value);
    }

    /**
     * Sort the suggested tags by value
     */
    sortDropdownItems() {
        this.dropdown?.sort();
    }

    /**
     * Create the list of the suggested tags, if the native input has the `data-options` attribute
     */
    private createDropdown() {
        if (!("options" in this.element.dataset)) {
            return;
        }

        const suggestions: { [key: string | number]: string | TagsInputDropdownItem } = JSON.parse(this.element.dataset.options ?? "{}");
        const isAssociative = !Array.isArray(suggestions);

        this.dropdown = new DropdownList(document.createElement("div"), false);
        this.dropdown.element.className = "dropdown-list";

        for (const key in suggestions) {
            const suggestion = suggestions[key];
            const { value, icon, thumb } = typeof suggestion === "object" ? suggestion : { value: suggestion, icon: undefined, thumb: undefined };

            this.addDropdownItem({
                label: value,
                value: isAssociative ? key : value,
                icon: icon ? (toCamelCase(icon) as keyof typeof icons) : undefined,
                thumb,
            });
        }

        this.field.appendChild(this.dropdown.element);

        this.bindDropdownEvents();
    }

    /**
     * Open the list when the text field is focused, close it when it is blurred, and filter it while typing
     */
    private bindDropdownEvents() {
        this.innerInput.addEventListener("focus", () => {
            if (this.dropdown && !this.dropdown.open.value) {
                this.updateDropdown();
                this.dropdown.element.scrollTop = 0;
            }
        });

        this.innerInput.addEventListener("blur", () => {
            if (this.dropdown?.open.value) {
                this.updateDropdown();
                this.dropdown.open.value = false;
            }
        });

        this.innerInput.addEventListener(
            "keyup",
            debounce((event: KeyboardEvent) => this.handleDropdownKeyup(event), 100),
        );
    }

    /**
     * Filter the suggested tags by the typed text, close the list with Escape
     */
    private handleDropdownKeyup(event: KeyboardEvent) {
        switch (event.key) {
            case "Escape":
                if (this.dropdown) {
                    this.dropdown.open.value = false;
                }
                break;

            case "ArrowUp":
            case "ArrowDown":
                break;

            default: {
                const value = this.innerInput.value.trim();

                this.filterDropdown(value);

                if (value.length > 0) {
                    this.dropdown?.selectFirst();
                }
            }
        }
    }

    /**
     * Bind the keyboard and focus events of the text field
     */
    private registerInputEvents() {
        this.innerInput.addEventListener("focus", () => this.field.classList.add("focused"));

        this.innerInput.addEventListener("blur", () => {
            const value = this.innerInput.value.trim();
            if (value !== "") {
                this.addTag(value);
            }
            this.field.classList.remove("focused");
        });

        this.innerInput.addEventListener("input", () => {
            const value = this.innerInput.value.trim();
            if (value.length > 0) {
                this.innerInput.size = this.innerInput.value.length + 2;
            } else {
                this.innerInput.size = Math.max(this.innerInput.placeholder.length, 1);
            }
        });

        this.innerInput.addEventListener("keydown", (event) => this.handleKeydown(event));
    }

    /**
     * Add or remove tags and move through the suggestions with the keyboard
     */
    private handleKeydown(event: KeyboardEvent) {
        this.innerInput.classList.remove("form-input-invalid");

        const isDropdownOpen = this.dropdown?.open.value === true;
        const isAddKey = this.options.addKeyCodes.includes(event.code);

        // A typed tag is replaced by the selected suggestion
        if (isDropdownOpen && (isAddKey || event.key === "Enter")) {
            this.addTagFromSelectedDropdownItem();
        }

        const value = this.innerInput.value.trim();

        if (event.key === "Backspace") {
            this.updateDropdown();
            if (value === "") {
                this.removeTag(this.tags.value[this.tags.value.length - 1]);
                event.preventDefault();
            }
        } else if (event.key === "Escape") {
            this.clearInput();
            this.innerInput.blur();
            event.preventDefault();
        } else if (isDropdownOpen && event.key === "ArrowUp") {
            this.dropdown?.selectPrevious();
            event.preventDefault();
        } else if (isDropdownOpen && event.key === "ArrowDown") {
            this.dropdown?.selectNext();
            event.preventDefault();
        } else if (isAddKey || (event.key === "Enter" && value !== "")) {
            event.preventDefault();
            if (value !== "") {
                this.addTypedTag(value);
            }
        }
    }

    /**
     * Add a tag typed by the user, marking the text field as invalid if the tag cannot be added
     */
    private addTypedTag(value: string) {
        if (this.tags.value.length >= this.options.limit || this.tags.value.includes(value)) {
            this.innerInput.classList.add("form-input-invalid");
        } else {
            this.addTag(value);
        }
    }

    /**
     * Update the native input and notify the form
     */
    private updateTags() {
        this.element.value = this.tags.value.join(", ");
        this.dispatchChange();
        this.updatePlaceholder();
    }

    /**
     * Show the placeholder if there are no tags
     */
    private updatePlaceholder() {
        if (this.placeholder.length > 0) {
            if (this.tags.value.length === 0) {
                this.innerInput.placeholder = this.placeholder;
                this.innerInput.size = this.placeholder.length;
            } else {
                this.innerInput.placeholder = "";
                this.innerInput.size = 1;
            }
        }
    }

    /**
     * Return whether a tag can be added
     *
     * A tag cannot be added if the limit is reached or if it is a duplicate, and, when only the
     * suggested tags are accepted, if it is not one of them
     */
    private validateTag(value: string) {
        if (this.tags.value.length >= this.options.limit) {
            return false;
        }
        if (!this.tags.value.includes(value)) {
            if (this.dropdown && this.options.accept === "options") {
                return this.dropdown.find(value) !== null;
            }
            return true;
        }
        return false;
    }

    /**
     * Render the tags, reusing the elements of the existing ones
     */
    private renderTags(tags: string[]) {
        const unused = new Map<string, HTMLElement[]>();

        for (const tag of Array.from(this.list.children) as HTMLElement[]) {
            const value = this.tagValues.get(tag) as string;
            unused.set(value, [...(unused.get(value) ?? []), tag]);
        }

        tags.forEach((value, index) => {
            const tag = unused.get(value)?.shift() ?? this.createTag(value);

            if (this.list.children[index] !== tag) {
                this.list.insertBefore(tag, this.list.children[index] ?? null);
            }
        });

        for (const tag of Array.from(unused.values()).flat()) {
            tag.remove();
        }
    }

    /**
     * Create the element of a tag
     */
    private createTag(value: string) {
        const tag = document.createElement("span");
        const tagRemove = document.createElement("button");
        tag.className = "tag";
        tag.textContent = value;
        this.tagValues.set(tag, value);
        tag.style.marginRight = ".25rem";

        tagRemove.type = "button";
        tagRemove.className = "tag-remove";
        tagRemove.title = this.options.labels.remove;
        tagRemove.ariaLabel = `${this.options.labels.remove}: ${value}`;

        let mousedownCaptured = false;

        tagRemove.addEventListener("mousedown", (event) => {
            mousedownCaptured = true;
            event.preventDefault();
            event.stopPropagation();
        });

        tagRemove.addEventListener("click", (event) => {
            const keyboardActivation = event.detail === 0;
            if (mousedownCaptured || keyboardActivation) {
                this.removeTag(value);

                $$(".tooltip").forEach((tooltip) => {
                    tooltip.remove();
                });

                if (keyboardActivation) {
                    // The focused button has been removed, keep keyboard focus in the field
                    this.innerInput.focus();
                }
            }
            mousedownCaptured = false;
            event.preventDefault();
            event.stopPropagation();
        });
        tag.appendChild(tagRemove);

        return tag;
    }

    /**
     * Add a tag if valid and clear the text field
     */
    private addTag(value: string) {
        if (this.validateTag(value)) {
            this.tags.value = [...this.tags.value, value];
            this.updateTags();
        } else {
            this.updatePlaceholder();
        }
        this.innerInput.value = "";
        this.updateDropdown();
    }

    /**
     * Remove a tag
     */
    private removeTag(value: string) {
        const index = this.tags.value.indexOf(value);
        if (index > -1) {
            this.tags.value = this.tags.value.filter((_, tagIndex) => tagIndex !== index);
            this.updateTags();
        }
        this.updateDropdown();
    }

    /**
     * Clear the text field
     */
    private clearInput() {
        this.innerInput.value = "";
        this.updatePlaceholder();
    }

    /**
     * Show the suggested tags that are not tags yet
     */
    private updateDropdown() {
        if (!this.dropdown) {
            return;
        }
        if (this.tags.value.length >= this.options.limit) {
            this.dropdown.open.value = false;
            return;
        }
        const visibleItems = this.dropdown.filter((item) => !this.tags.value.includes(item.dataset.value as string));
        this.dropdown.selected.value = null;
        this.dropdown.open.value = visibleItems > 0 && this.innerInput === document.activeElement;
    }

    /**
     * Show only the suggested tags that are not tags yet and match the typed text
     */
    private filterDropdown(value: string) {
        if (!this.dropdown) {
            return;
        }
        if (this.tags.value.length >= this.options.limit) {
            this.dropdown.open.value = false;
            return;
        }
        const regexp = value === "" ? null : makeSearchRegExp(value);
        const visibleItems = this.dropdown.filter((item) => !this.tags.value.includes(item.dataset.value as string) && (regexp === null || regexp.test(`${item.textContent}`)));
        this.dropdown.open.value = visibleItems > 0;
    }

    /**
     * Set the text field to the value of the selected suggestion
     */
    private addTagFromSelectedDropdownItem() {
        const selectedItem = this.dropdown?.selected.value;
        if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
            this.innerInput.value = selectedItem.dataset.value as string;
        }
    }
}
