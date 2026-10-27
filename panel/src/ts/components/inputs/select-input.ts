import { effect } from "@preact/signals-core";
import { app } from "../../services/app";
import { makeSearchRegExp } from "../../utils/search";
import { $, $$ } from "../../utils/selectors";
import { toCamelCase } from "../../utils/strings";
import * as icons from "../ui/icons";
import { DropdownList } from "./dropdown-list";
import { InputBase } from "./input";

type SelectInputListItem = {
    label: string;
    value: string;
    selected: boolean;
    disabled: boolean;
    dataset: Record<string, string>;
};

/**
 * Holds the options of a select input
 */
export interface SelectInputOptions {
    labels: {
        empty: string;
    };
}

let instanceCount = 0;

/**
 * Provides a select input with a searchable list of options in place of a native select
 *
 * The native select is hidden and holds the value sent with the form
 */
export class SelectInput extends InputBase<HTMLSelectElement> {
    readonly options: SelectInputOptions;

    private list!: DropdownList;

    private labelInput: HTMLInputElement;

    private emptyState: HTMLDivElement;

    // Whether a key has been typed since the text field got the focus
    private isTyping = false;

    // Whether Escape has just closed the list, so that the key is not left to the page
    private isEscapeHandled = false;

    private readonly uid = ++instanceCount;

    private optionCount = 0;

    constructor(element: HTMLSelectElement, options: Partial<SelectInputOptions> = {}) {
        const defaults: SelectInputOptions = {
            labels: {
                empty: app.translation.get("fields.select.empty", "No matching options"),
            },
        };

        super(element);

        this.options = { ...defaults, ...options };

        this.labelInput = document.createElement("input");

        this.emptyState = document.createElement("div");

        this.createField();
    }

    /**
     * Get the element of the list
     */
    private get dropdown() {
        return this.list.element;
    }

    /**
     * Get the element of the list
     */
    get dropdownElement() {
        return this.dropdown;
    }

    // The getter is defined again because a class defining only the setter would lose it
    get value() {
        return this.element.value;
    }

    /**
     * Select the option with a value
     */
    set value(value: string) {
        this.element.value = value;
        this.setCurrent();
    }

    /**
     * Get the selected item of the list
     */
    get selectedDropdownItem() {
        return this.list.selected.value;
    }

    /**
     * Get the items of the list
     */
    get dropdownItems() {
        return $$(".dropdown-item", this.dropdown);
    }

    /**
     * Create the text field and the list from the options of the select
     */
    private createField() {
        const wrap = this.getWrap();

        this.createLabelInput();
        this.hideSelect();

        const items = this.readOptions();

        wrap.appendChild(this.labelInput);
        wrap.appendChild(this.element);

        this.createList(items, wrap);

        const labelId = this.labelInput.labels?.[0]?.id;

        if (labelId) {
            this.dropdown.setAttribute("aria-labelledby", labelId);
        }
    }

    /**
     * Get the element wrapping the input, creating it if the select has none
     */
    private getWrap() {
        const existingWrap = this.element.closest<HTMLElement>(".form-input-wrap");

        if (existingWrap) {
            return existingWrap;
        }

        const wrap = document.createElement("div");
        wrap.className = "form-input-wrap";
        (this.element.parentNode as ParentNode).insertBefore(wrap, this.element.nextSibling);

        return wrap;
    }

    /**
     * Set up the text field, which acts as the combobox of the list
     */
    private createLabelInput() {
        const input = this.labelInput;

        input.type = "text";
        input.className = this.element.className;
        input.classList.add("form-select");
        input.id = this.element.id;

        const describedBy = this.element.getAttribute("aria-describedby");

        if (describedBy) {
            input.setAttribute("aria-describedby", describedBy);
        }

        input.setAttribute("role", "combobox");
        input.setAttribute("aria-autocomplete", "list");
        input.setAttribute("aria-haspopup", "listbox");
        input.setAttribute("aria-expanded", "false");

        if (this.element.hasAttribute("disabled")) {
            input.disabled = true;
        }

        for (const key in this.element.dataset) {
            input.dataset[key] = this.element.dataset[key];
        }
    }

    /**
     * Hide the select from users and assistive technologies, leaving the id to the text field
     */
    private hideSelect() {
        this.element.hidden = true;
        this.element.removeAttribute("id");
        this.element.ariaHidden = "true";
        this.element.tabIndex = -1;
    }

    /**
     * Read the options of the select, showing the label of the selected one in the text field
     */
    private readOptions() {
        return Array.from($$<HTMLOptionElement>("option", this.element), (option): SelectInputListItem => {
            if (option.selected) {
                this.labelInput.value = option.textContent;
            }

            return {
                label: option.textContent,
                value: option.value,
                selected: option.selected,
                disabled: option.disabled,
                dataset: { ...option.dataset } as Record<string, string>,
            };
        });
    }

    /**
     * Add an option
     *
     * @param option Label, value, disabled state, thumbnail URI and icon name of the option
     */
    addOption({ label, value, disabled, thumb, icon }: { label: string; value: string; disabled?: boolean; thumb?: string; icon?: string }) {
        const option = document.createElement("option");
        option.value = value;
        option.textContent = label;
        option.disabled = disabled ?? false;
        if (thumb) {
            option.dataset.thumb = thumb;
        }
        if (icon) {
            option.dataset.icon = icon;
        }
        this.element.appendChild(option);

        this.addDropdownItem({
            label,
            value,
            selected: false,
            disabled: disabled ?? false,
            dataset: {
                thumb: thumb ?? "",
                icon: icon ?? "",
            },
        });
    }

    /**
     * Remove an option, selecting the first available one if it was selected
     */
    removeOption(value: string) {
        const option = $(`option[value="${CSS.escape(value)}"]`, this.element) as HTMLOptionElement;
        if (option) {
            option.remove();
        }

        const item = this.list.find(value);
        if (item) {
            item.remove();
            if (this.list.selected.value === item) {
                this.list.selected.value = null;
            }
        }

        if (option?.selected) {
            this.list.selectFirst();

            const selected = this.list.selected.value;

            if (selected) {
                this.setCurrent(selected);
            }
        }
    }

    /**
     * Sort the items of the list by value
     */
    sortDropdownItems() {
        this.list.sort();
    }

    /**
     * Add an item to the list
     */
    private addDropdownItem(option: SelectInputListItem) {
        const item = this.createItem(option);

        this.bindItemEvents(item);

        this.dropdown.appendChild(item);
    }

    /**
     * Create the element of an option, with its thumbnail or icon
     */
    private createItem(option: SelectInputListItem) {
        const item = document.createElement("div");
        item.className = "dropdown-item";
        item.id = `${this.dropdown.id}-option-${++this.optionCount}`;
        item.setAttribute("role", "option");
        item.setAttribute("aria-selected", String(option.selected));

        item.textContent = option.label;
        item.dataset.value = option.value;

        if (option.selected) {
            item.classList.add("selected");
            this.list.selected.value = item;
        }

        if (option.disabled) {
            item.classList.add("disabled");
            item.setAttribute("aria-disabled", "true");
        }

        if (option.dataset.thumb) {
            const img = document.createElement("img");
            img.src = option.dataset.thumb;
            img.className = "dropdown-thumb";
            img.alt = "";
            item.insertAdjacentElement("afterbegin", img);
        } else if (option.dataset.icon) {
            const icon = toCamelCase(option.dataset.icon) as keyof typeof icons;

            // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
            item.insertAdjacentHTML("afterbegin", icons[icon]);
        }

        for (const key in option.dataset) {
            if (key !== "icon" && key !== "thumb") {
                item.dataset[key] = option.dataset[key];
            }
        }

        return item;
    }

    /**
     * Pick the option of an item when it is clicked, but not when the pointer was pressed elsewhere
     */
    private bindItemEvents(item: HTMLElement) {
        let mousedownCaptured = false;

        item.addEventListener("mousedown", (event) => {
            if (!item.classList.contains("disabled")) {
                mousedownCaptured = true;
                this.list.select(item);
            }
            event.preventDefault();
            event.stopPropagation();
        });

        item.addEventListener("click", (event) => {
            const keyboardActivation = event.detail === 0;
            if (mousedownCaptured || keyboardActivation) {
                this.setCurrent(item);
                this.close();
            }
            mousedownCaptured = false;
            event.preventDefault();
            event.stopPropagation();
        });
    }

    /**
     * Create the list, fill it with the items and bind the text field to it
     */
    private createList(items: SelectInputListItem[], wrap: HTMLElement) {
        this.list = new DropdownList(document.createElement("div"));
        this.dropdown.className = "dropdown-list";

        this.dropdown.dataset.for = this.labelInput.id;
        this.dropdown.id = `${this.labelInput.id || `select-${this.uid}`}-listbox`;
        this.dropdown.setAttribute("role", "listbox");
        this.labelInput.setAttribute("aria-controls", this.dropdown.id);

        this.emptyState.className = "dropdown-empty";
        this.emptyState.style.display = "none";
        this.emptyState.textContent = this.options.labels.empty;

        this.dropdown.appendChild(this.emptyState);

        for (const item of items) {
            this.addDropdownItem(item);
        }

        wrap.appendChild(this.dropdown);

        this.bindAriaState();
        this.bindLabelInput();
    }

    /**
     * Keep the ARIA attributes of the text field and of the items in sync with the list
     */
    private bindAriaState() {
        effect(() => {
            const expanded = this.list.open.value;

            if (expanded === null) {
                return;
            }

            this.labelInput.setAttribute("aria-expanded", String(expanded));

            if (!expanded) {
                this.labelInput.removeAttribute("aria-activedescendant");
            }
        });

        let isFirstSelection = true;

        effect(() => {
            const selected = this.list.selected.value;

            for (const element of this.list.items) {
                element.setAttribute("aria-selected", String(element === selected));
            }

            // The attribute is not set for the option selected by the markup
            if (selected && !isFirstSelection) {
                this.labelInput.setAttribute("aria-activedescendant", selected.id);
            } else if (!selected) {
                this.labelInput.removeAttribute("aria-activedescendant");
            }

            isFirstSelection = false;
        });
    }

    /**
     * Bind the focus, blur and keyboard events of the text field
     */
    private bindLabelInput() {
        this.labelInput.addEventListener("focus", () => {
            this.openWithCurrent();
            this.labelInput.setSelectionRange(0, 0);
            this.isTyping = false;
        });

        this.labelInput.addEventListener("mousedown", (event) => {
            if (document.activeElement === this.labelInput) {
                // The list is not opened by the focus, which the text field already has
                this.openWithCurrent();
            } else {
                this.labelInput.focus();
            }

            event.preventDefault();
        });

        this.labelInput.addEventListener("blur", () => this.commitLabel());
        this.labelInput.addEventListener("keydown", (event) => this.handleKeydown(event));
        this.labelInput.addEventListener("keyup", (event) => this.handleKeyup(event));
    }

    /**
     * Pick the option whose label has been typed, or restore the label of the current one
     */
    private commitLabel() {
        const item = this.findDropdownItem(this.labelInput.value);

        if (!item) {
            this.labelInput.value = this.getCurrentLabel();
        } else if (item.dataset.value !== this.element.value) {
            this.setCurrent(item);
        } else {
            this.labelInput.value = item.textContent.trim();
        }

        this.list.open.value = false;
    }

    /**
     * Move through the items with the arrows, pick the selected one with Enter
     */
    private handleKeydown(event: KeyboardEvent) {
        switch (event.key) {
            case "Backspace":
                this.resetSelection();
                break;

            case "ArrowUp":
                this.moveSelection(() => this.list.selectPrevious());
                event.preventDefault();
                break;

            case "ArrowDown":
                this.moveSelection(() => this.list.selectNext());
                event.preventDefault();
                break;

            case "Enter":
                this.pickSelected();
                event.preventDefault();
                break;

            case "Escape":
                // A list that is open is closed, otherwise Escape is left to the page (e.g. a modal)
                if (this.list.open.value === true) {
                    this.cancel();
                    this.isEscapeHandled = true;
                    event.preventDefault();
                }
                break;

            case "ArrowLeft":
            case "ArrowRight":
                break;

            default:
                // The first key typed replaces the label instead of editing it
                if (!this.isTyping) {
                    this.labelInput.value = "";
                    this.isTyping = true;
                }
                break;
        }
    }

    /**
     * Filter the items by the typed text, keep an Escape that closed the list from reaching other listeners
     */
    private handleKeyup(event: KeyboardEvent) {
        switch (event.key) {
            case "Escape":
                if (this.isEscapeHandled) {
                    this.isEscapeHandled = false;
                    event.stopPropagation();
                }
                break;

            case "ArrowUp":
            case "ArrowDown":
            case "ArrowLeft":
            case "ArrowRight":
            case "Tab":
            case "Enter":
                break;

            default: {
                const value = this.labelInput.value.trim();

                this.list.open.value = true;
                this.filterItems(value);

                if (value.length > 0) {
                    this.list.selectFirst();
                }
            }
        }
    }

    /**
     * Move the selection if the list is open, otherwise open it
     */
    private moveSelection(move: () => void) {
        if (this.list.open.value === true) {
            move();
        } else {
            this.openWithCurrent();
        }
    }

    /**
     * Pick the selected item, if it is displayed, and leave the text field
     */
    private pickSelected() {
        const selected = this.list.selected.value;

        if (selected && getComputedStyle(selected).display !== "none") {
            this.setCurrent(selected);
        }

        this.close();
    }

    /**
     * Close the list picking the option whose label has been typed, keeping the focus
     */
    private close() {
        this.commitLabel();
        this.isTyping = false;
    }

    /**
     * Close the list discarding the typed text, keeping the focus
     */
    private cancel() {
        this.labelInput.value = this.getCurrentLabel();
        this.list.open.value = false;
        this.isTyping = false;
    }

    /**
     * Clear the selection and update the empty state
     */
    private resetSelection() {
        this.list.selected.value = null;

        this.updateEmptyState();
    }

    /**
     * Show the empty state if no item is displayed
     */
    private updateEmptyState() {
        this.emptyState.style.display = this.list.countDisplayed() > 0 ? "none" : "block";
    }

    /**
     * Show only the items matching the typed text
     */
    private filterItems(value: string) {
        const regexp = value === "" ? null : makeSearchRegExp(value);

        this.list.filter((item) => regexp === null || regexp.test(`${item.textContent}`));

        this.updateEmptyState();
    }

    /**
     * Pick an option and update the select and the text field
     *
     * @param item Item of the option to pick, or the current one if missing
     */
    private setCurrent(item: HTMLElement | null = this.getCurrent()) {
        if (item) {
            this.element.value = item.dataset.value as string;
            this.labelInput.value = item.textContent.trim();
        } else {
            // The value matches no option
            this.labelInput.value = "";
        }
        this.dispatchChange();
    }

    /**
     * Get the item of the current option
     */
    private getCurrent() {
        return this.list.find(this.element.value);
    }

    /**
     * Get the label of the current option
     */
    private getCurrentLabel() {
        return this.getCurrent()?.textContent.trim() ?? "";
    }

    /**
     * Open the list with the current option selected
     */
    private openWithCurrent() {
        if (this.list.open.value !== true) {
            const current = this.getCurrent();

            this.filterItems("");
            this.resetSelection();
            this.list.select(current);
            this.list.open.value = true;

            if (current) {
                this.list.scrollTo(current);
            }
        }
    }

    /**
     * Get the option with a label, ignoring surrounding spaces
     *
     * @returns Item, or `null` if there is none
     */
    private findDropdownItem(label: string) {
        const current = this.getCurrent();

        if (current?.textContent.trim() === label.trim()) {
            return current;
        }

        return Array.from(this.dropdownItems).find((item) => !item.classList.contains("disabled") && item.textContent.trim() === label.trim()) ?? null;
    }
}
