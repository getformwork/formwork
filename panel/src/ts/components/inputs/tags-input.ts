import * as icons from "../icons";
import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";
import { debounce } from "../../utils/events";
import type { SortableEvent } from "sortablejs";
import { toCamelCase } from "../../utils/strings";

interface TagsInputOptions {
    labels: { [key: string]: string };
    addKeyCodes: string[];
    limit: number;
    accept: "options" | "any";
    orderable: boolean;
}

interface TagsInputDropdownItem {
    label: string;
    value: string;
    icon?: keyof typeof icons;
    thumb?: string;
}

export class TagsInput {
    readonly element: HTMLInputElement;

    private options: TagsInputOptions;
    private tags: string[] = [];
    private placeholder: string;
    private dropdown: HTMLElement | undefined;

    private field: HTMLDivElement;
    private list: HTMLSpanElement;
    private innerInput: HTMLInputElement;

    constructor(element: HTMLInputElement, options: Partial<TagsInputOptions>) {
        const defaults = { labels: { remove: "Remove" }, addKeyCodes: ["Comma"], limit: Infinity, accept: "options" as "options" | "any", orderable: true };

        this.element = element;

        this.options = { ...defaults, ...options };

        this.field = document.createElement("div");
        this.list = document.createElement("span");
        this.innerInput = document.createElement("input");

        this.createField();
        this.createDropdown();

        this.registerInputEvents();
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value() {
        return this.element.value;
    }

    set value(value: string) {
        this.element.value = value;
        this.tags = value.split(", ").map((tag) => tag.trim());
        this.list.innerText = "";
        this.tags.forEach((tag) => this.insertTag(tag, this.list));
        this.updatePlaceholder();
        this.updateDropdown();
    }

    private async createField() {
        if ("limit" in this.element.dataset) {
            this.options.limit = parseInt(this.element.dataset.limit as string);
        }

        if (!("orderable" in this.element.dataset)) {
            this.options.orderable = false;
        }

        this.field.className = "form-input-tags-wrap";

        this.innerInput.className = "form-input";
        this.innerInput.type = "text";
        this.innerInput.id = this.element.id;
        this.innerInput.placeholder = this.element.placeholder;

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

        if (this.element.value) {
            this.tags = this.element.value.split(", ");
            this.tags.forEach((value, index) => {
                value = value.trim();
                this.tags[index] = value;
                this.insertTag(value, this.list);
            });
        }

        if (this.innerInput.placeholder) {
            this.placeholder = this.innerInput.placeholder;
            this.updatePlaceholder();
        } else {
            this.placeholder = "";
        }

        this.field.addEventListener("mousedown", (event) => {
            this.innerInput.focus();
            event.preventDefault();
        });

        if (this.options.orderable) {
            const { default: Sortable } = await import("sortablejs");

            Sortable.create(this.list, {
                forceFallback: true,
                animation: 150,
                filter: ".tag-remove",

                onChoose: () => {
                    if (this.dropdown) {
                        this.dropdown.style.display = "none";
                    }
                },

                onStart: () => {
                    this.field.classList.add("is-dragging");
                },

                onFilter: (event: SortableEvent) => {
                    if (event.target.matches(".tag-remove")) {
                        this.removeTag(event.item.innerText);
                        this.list.removeChild(event.item);
                    }
                },

                onEnd: (event: SortableEvent) => {
                    this.field.classList.remove("is-dragging");
                    const newIndex = event.newIndex;
                    const oldIndex = event.oldIndex;
                    this.innerInput.blur();
                    this.innerInput.focus();
                    if (newIndex === oldIndex || newIndex === undefined || oldIndex === undefined) {
                        return;
                    }
                    this.tags.splice(newIndex, 0, this.tags.splice(oldIndex, 1)[0]);
                    this.updateTags();
                },
            });
        }
    }

    addDropdownItem(option: TagsInputDropdownItem) {
        if (!this.dropdown) {
            this.createDropdown();
        }

        const item = document.createElement("div");

        item.className = "dropdown-item";
        item.innerText = option.label;
        item.dataset.value = option.value;

        if (option.thumb) {
            const img = document.createElement("img");
            img.src = option.thumb;
            img.className = "dropdown-thumb";
            item.insertAdjacentElement("afterbegin", img);
        } else if (option.icon) {
            item.insertAdjacentHTML("afterbegin", icons[option.icon]);
        }

        item.addEventListener("click", () => {
            if (item.dataset.value) {
                this.addTag(item.dataset.value);
            }
        });
        this.dropdown?.appendChild(item);
    }

    removeDropdownItem(value: string) {
        const item = $(`.dropdown-item[data-value="${value}"]`, this.dropdown);
        if (item) {
            this.dropdown?.removeChild(item);
        }
        this.updateDropdown();
        this.removeTag(value);
        $$(".tag", this.list).forEach((tag) => {
            if (tag.textContent === value) {
                this.list.removeChild(tag);
            }
        });
    }

    sortDropdownItems() {
        const items = $$(".dropdown-item", this.dropdown);
        const sorted = Array.from(items).sort((a, b) => (a.dataset.value as string).localeCompare(b.dataset.value as string));
        for (const item of sorted) {
            this.dropdown?.appendChild(item);
        }
    }

    private createDropdown() {
        if ("options" in this.element.dataset) {
            const list: { [key: string | number]: string | TagsInputDropdownItem } = JSON.parse(this.element.dataset.options ?? "{}");
            const isAssociative = !Array.isArray(list);

            if ("accept" in this.element.dataset) {
                this.options.accept = (this.element.dataset.accept ?? "options") as "options" | "any";
            }

            this.dropdown = document.createElement("div");
            this.dropdown.className = "dropdown-list";
            this.dropdown.style.display = "none";

            for (const key in list) {
                const { value, icon, thumb } = typeof list[key] === "object" ? list[key] : { value: list[key], icon: undefined, thumb: undefined };

                this.addDropdownItem({
                    label: value,
                    value: isAssociative ? key : value,
                    icon: icon ? (toCamelCase(icon) as keyof typeof icons) : undefined,
                    thumb,
                });
            }

            this.field.appendChild(this.dropdown);

            this.innerInput.addEventListener("focus", () => {
                if (this.dropdown && getComputedStyle(this.dropdown).display === "none") {
                    this.updateDropdown();
                    this.dropdown.scrollTop = 0;
                }
            });

            this.innerInput.addEventListener("blur", () => {
                if (this.dropdown && getComputedStyle(this.dropdown).display !== "none") {
                    this.updateDropdown();
                    this.dropdown.style.display = "none";
                }
            });

            this.innerInput.addEventListener("keydown", (event) => {
                switch (event.key) {
                    case "Backspace":
                        this.updateDropdown();
                        break;
                    case "Enter":
                        if (this.dropdown && getComputedStyle(this.dropdown).display !== "none") {
                            this.addTagFromSelectedDropdownItem();
                            if (this.innerInput.value !== "") {
                                event.preventDefault();
                            }
                        }
                        break;
                    case "ArrowUp":
                        if (this.dropdown && getComputedStyle(this.dropdown).display !== "none") {
                            this.selectPrevDropdownItem();
                            event.preventDefault();
                        }
                        break;
                    case "ArrowDown":
                        if (this.dropdown && getComputedStyle(this.dropdown).display !== "none") {
                            this.selectNextDropdownItem();
                            event.preventDefault();
                        }
                        break;
                    default:
                        if (this.options.addKeyCodes.includes(event.code)) {
                            if (this.dropdown && getComputedStyle(this.dropdown).display !== "none") {
                                this.addTagFromSelectedDropdownItem();
                                event.preventDefault();
                            }
                        }
                }
            });

            this.innerInput.addEventListener(
                "keyup",
                debounce((event: KeyboardEvent) => {
                    const value = this.innerInput.value.trim();
                    switch (event.key) {
                        case "Escape":
                            if (this.dropdown) {
                                this.dropdown.style.display = "none";
                            }
                            break;
                        case "ArrowUp":
                        case "ArrowDown":
                            return true;
                        default:
                            if (this.tags.length >= this.options.limit) {
                                event.preventDefault();
                            }
                            this.filterDropdown(value);
                            if (value.length > 0) {
                                this.selectFirstDropdownItem();
                            }
                    }
                }, 100),
            );
        }
    }

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

        this.innerInput.addEventListener("keydown", (event) => {
            this.innerInput.classList.remove("form-input-invalid");
            const value = this.innerInput.value.trim();
            switch (event.key) {
                case "Backspace":
                    if (value === "") {
                        this.removeTag(this.tags[this.tags.length - 1]);
                        const lastTag = this.list.childNodes[this.list.childNodes.length - 1];
                        if (lastTag) {
                            this.list.removeChild(lastTag);
                        }
                        event.preventDefault();
                    }
                    break;
                case "Enter":
                case "Comma":
                    if (value !== "") {
                        event.preventDefault();
                        if (this.tags.length >= this.options.limit || this.tags.includes(value)) {
                            this.innerInput.classList.add("form-input-invalid");
                        } else {
                            this.addTag(value);
                        }
                    }
                    break;
                case "Escape":
                    this.clearInput();
                    this.innerInput.blur();
                    event.preventDefault();
                    break;
                default:
                    if (this.options.addKeyCodes.includes(event.key)) {
                        event.preventDefault();
                        if (value !== "") {
                            if (this.tags.length >= this.options.limit || this.tags.includes(value)) {
                                this.innerInput.classList.add("form-input-invalid");
                            } else {
                                this.addTag(value);
                            }
                        }
                        break;
                    }
                    break;
            }
        });
    }

    private updateTags() {
        this.element.value = this.tags.join(", ");
        this.element.dispatchEvent(new Event("input", { bubbles: true }));
        this.element.dispatchEvent(new Event("change", { bubbles: true }));
        this.updatePlaceholder();
    }

    private updatePlaceholder() {
        if (this.placeholder.length > 0) {
            if (this.tags.length === 0) {
                this.innerInput.placeholder = this.placeholder;
                this.innerInput.size = this.placeholder.length;
            } else {
                this.innerInput.placeholder = "";
                this.innerInput.size = 1;
            }
        }
    }

    private validateTag(value: string) {
        if (this.tags.length >= this.options.limit) {
            return false;
        }
        if (!this.tags.includes(value)) {
            if (this.dropdown && this.options.accept === "options") {
                return $(`[data-value="${value}"]`, this.dropdown) !== null;
            }
            return true;
        }
        return false;
    }

    private insertTag(value: string, parent: HTMLElement) {
        const tag = document.createElement("span");
        const tagRemove = document.createElement("i");
        tag.className = "tag";
        tag.innerText = value;
        tag.style.marginRight = ".25rem";
        parent.appendChild(tag);

        tagRemove.className = "tag-remove";
        tagRemove.title = this.options.labels.remove;
        tagRemove.role = "button";

        tagRemove.addEventListener("mousedown", (event) => {
            this.removeTag(value);
            parent.removeChild(tag);
            event.preventDefault();
        });
        tag.appendChild(tagRemove);
    }

    private addTag(value: string) {
        if (this.validateTag(value)) {
            this.tags.push(value);
            this.insertTag(value, this.list);
            this.updateTags();
        } else {
            this.updatePlaceholder();
        }
        this.innerInput.value = "";
        this.updateDropdown();
    }

    private removeTag(value: string) {
        const index = this.tags.indexOf(value);
        if (index > -1) {
            this.tags.splice(index, 1);
            this.updateTags();
        }
        this.updateDropdown();
    }

    private clearInput() {
        this.innerInput.value = "";
        this.updatePlaceholder();
    }

    private updateDropdown() {
        if (!this.dropdown) {
            return;
        }
        if (this.tags.length >= this.options.limit) {
            this.dropdown.style.display = "none";
            return;
        }
        let visibleItems = 0;
        $$(".dropdown-item", this.dropdown).forEach((element) => {
            if (!this.tags.includes(element.dataset.value as string)) {
                element.style.display = "block";
                visibleItems++;
            } else {
                element.style.display = "none";
            }
            element.classList.remove("selected");
        });
        if (visibleItems > 0 && this.innerInput === document.activeElement) {
            this.dropdown.style.display = "block";
        } else {
            this.dropdown.style.display = "none";
        }
    }

    private filterDropdown(value: string) {
        if (!this.dropdown) {
            return;
        }
        if (value === "" && this.tags.length < this.options.limit) {
            this.dropdown.style.display = "block";
            return;
        }
        let visibleItems = 0;
        $$(".dropdown-item", this.dropdown).forEach((element) => {
            if (value === "") {
                return true;
            }
            const text = `${element.textContent}`;
            const regexp = new RegExp(`(^|\\b)${makeDiacriticsRegExp(escapeRegExp(value))}`, "i");
            if (text.match(regexp) !== null && element.style.display !== "none") {
                element.style.display = "block";
                visibleItems++;
            } else {
                element.style.display = "none";
            }
        });
        if (visibleItems > 0) {
            this.dropdown.style.display = "block";
        } else {
            this.dropdown.style.display = "none";
        }
    }

    private scrollToDropdownItem(item: HTMLElement) {
        if (!this.dropdown) {
            return;
        }
        const dropdownScrollTop = this.dropdown.scrollTop;
        const dropdownHeight = this.dropdown.clientHeight;
        const dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
        const dropdownStyle = getComputedStyle(this.dropdown);
        const dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
        const dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
        const itemTop = item.offsetTop;
        const itemHeight = item.clientHeight;
        const itemBottom = itemTop + itemHeight;
        if (itemTop < dropdownScrollTop) {
            this.dropdown.scrollTop = itemTop - dropdownPaddingTop;
        } else if (itemBottom > dropdownScrollBottom) {
            this.dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
        }
    }

    private addTagFromSelectedDropdownItem() {
        const selectedItem = $(".dropdown-item.selected", this.dropdown);
        if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
            this.innerInput.value = selectedItem.dataset.value as string;
        }
    }

    private selectDropdownItem(item: HTMLElement) {
        const selectedItem = $(".dropdown-item.selected", this.dropdown);
        if (selectedItem) {
            selectedItem.classList.remove("selected");
        }
        if (item) {
            item.classList.add("selected");
            this.scrollToDropdownItem(item);
        }
    }

    private selectFirstDropdownItem() {
        const items = $$(".dropdown-item", this.dropdown);
        for (let i = 0; i < items.length; i++) {
            if (getComputedStyle(items[i]).display !== "none") {
                this.selectDropdownItem(items[i]);
                return;
            }
        }
    }

    private selectLastDropdownItem() {
        const items = $$(".dropdown-item", this.dropdown);
        for (let i = items.length - 1; i >= 0; i--) {
            if (getComputedStyle(items[i]).display !== "none") {
                this.selectDropdownItem(items[i]);
                return;
            }
        }
    }

    private selectPrevDropdownItem() {
        const selectedItem = $(".dropdown-item.selected", this.dropdown);
        if (selectedItem) {
            let previousItem = selectedItem.previousSibling as HTMLElement;
            while (previousItem && previousItem.style.display === "none") {
                previousItem = previousItem.previousSibling as HTMLElement;
            }
            if (previousItem) {
                return this.selectDropdownItem(previousItem);
            }
            this.selectDropdownItem(selectedItem.previousSibling as HTMLElement);
        }
        this.selectLastDropdownItem();
    }

    private selectNextDropdownItem() {
        const selectedItem = $(".dropdown-item.selected", this.dropdown);
        if (selectedItem) {
            let nextItem = selectedItem.nextSibling as HTMLElement;
            while (nextItem && nextItem.style.display === "none") {
                nextItem = nextItem.nextSibling as HTMLElement;
            }
            if (nextItem) {
                return this.selectDropdownItem(nextItem);
            }
        }
        this.selectFirstDropdownItem();
    }
}
