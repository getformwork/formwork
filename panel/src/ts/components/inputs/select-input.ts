import * as icons from "../icons";
import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";
import { toCamelCase } from "../../utils/strings";

type SelectInputListItem = {
    label: string;
    value: string;
    selected: boolean;
    disabled: boolean;
    dataset: Record<string, string>;
};

interface SelectInputOptions {
    labels: {
        empty: string;
    };
}

export class SelectInput {
    readonly options: SelectInputOptions;

    readonly element: HTMLSelectElement;

    private dropdown: HTMLElement;

    private labelInput: HTMLInputElement;

    private emptyState: HTMLDivElement;

    constructor(element: HTMLSelectElement, options: Partial<SelectInputOptions>) {
        const defaults: SelectInputOptions = { labels: { empty: "No matching options" } };

        this.element = element;

        this.options = { ...defaults, ...options };

        this.labelInput = document.createElement("input");

        this.emptyState = document.createElement("div");

        this.createField();
    }

    get dropdownElement() {
        return this.dropdown;
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
        this.setCurrent();
    }

    get selectedDropdownItem() {
        return $(".dropdown-item.selected", this.dropdown);
    }

    get dropdownItems() {
        return $$(".dropdown-item", this.dropdown);
    }

    private createField() {
        const hasWrap = this.element.closest(".form-input-wrap");

        const wrap = hasWrap || document.createElement("div");

        const selectId = this.element.id;

        if (!hasWrap) {
            wrap.className = "form-input-wrap";
            (this.element.parentNode as ParentNode).insertBefore(wrap, this.element.nextSibling);
        }

        this.element.hidden = true;

        this.labelInput.type = "text";

        this.labelInput.classList.add("form-select");
        this.labelInput.id = selectId;

        this.element.removeAttribute("id");

        this.element.ariaHidden = "true";

        this.element.tabIndex = -1;

        if (this.element.hasAttribute("disabled")) {
            this.labelInput.disabled = true;
        }

        for (const key in this.element.dataset) {
            this.labelInput.dataset[key] = this.element.dataset[key];
        }

        const list: SelectInputListItem[] = [];

        $$("option", this.element).forEach((option: HTMLOptionElement) => {
            const dataset: Record<string, string> = {};

            for (const key in option.dataset) {
                dataset[key] = option.dataset[key] as string;
            }

            list.push({
                label: option.innerText,
                value: option.value,
                selected: option.selected,
                disabled: option.disabled,
                dataset,
            });

            if (option.selected) {
                this.labelInput.value = option.innerText;
            }
        });

        wrap.appendChild(this.labelInput);

        wrap.appendChild(this.element);

        this.createDropdown(list, wrap as HTMLElement);
    }

    addOption({ label, value, disabled, thumb, icon }: { label: string; value: string; disabled?: boolean; thumb?: string; icon?: string }) {
        const option = document.createElement("option");
        option.value = value;
        option.innerText = label;
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

    removeOption(value: string) {
        const option = $(`option[value="${value}"]`, this.element) as HTMLOptionElement;
        if (option) {
            option.remove();
        }

        const item = $(`.dropdown-item[data-value="${value}"]`, this.dropdown);
        if (item) {
            item.remove();
        }

        if (option?.selected) {
            this.selectFirstDropdownItem();
            this.setCurrent(this.getSelected());
        }
    }

    sortDropdownItems() {
        const items = $$(".dropdown-item", this.dropdown);
        const sorted = Array.from(items).sort((a, b) => (a.dataset.value as string).localeCompare(b.dataset.value as string));
        for (const item of sorted) {
            this.dropdown.appendChild(item);
        }
    }

    private addDropdownItem(option: SelectInputListItem) {
        const item = document.createElement("div");
        item.className = "dropdown-item";

        item.innerText = option.label;
        item.dataset.value = option.value;

        if (option.selected) {
            item.classList.add("selected");
        }

        if (option.disabled) {
            item.classList.add("disabled");
        }

        if (option.dataset.thumb) {
            const img = document.createElement("img");
            img.src = option.dataset.thumb;
            img.className = "dropdown-thumb";
            item.insertAdjacentElement("afterbegin", img);
        } else if (option.dataset.icon) {
            const icon = toCamelCase(option.dataset.icon) as keyof typeof icons;
            item.insertAdjacentHTML("afterbegin", icons[icon]);
        }

        for (const key in option.dataset) {
            if (["icon", "thumb"].includes(key)) {
                continue;
            }
            item.dataset[key] = option.dataset[key];
        }

        item.addEventListener("mousedown", (event) => {
            if (!item.classList.contains("disabled")) {
                this.selectDropdownItem(item);
                this.setCurrent(item);
            } else {
                event.preventDefault();
            }
            event.stopPropagation();
        });

        this.dropdown.appendChild(item);
    }

    private createDropdown(list: SelectInputListItem[], wrap: HTMLElement) {
        this.dropdown = document.createElement("div");
        this.dropdown.className = "dropdown-list";

        this.dropdown.dataset.for = this.labelInput.id;

        this.emptyState.className = "dropdown-empty";
        this.emptyState.style.display = "none";
        this.emptyState.innerText = this.options.labels.empty;

        this.dropdown.appendChild(this.emptyState);

        for (const option of list) {
            this.addDropdownItem(option);
        }

        wrap.appendChild(this.dropdown);

        let hasKeyboardInput = false;

        this.labelInput.addEventListener("focus", () => {
            this.selectCurrent();
            this.labelInput.setSelectionRange(0, 0);
            hasKeyboardInput = false;
        });

        this.labelInput.addEventListener("mousedown", (event) => {
            this.labelInput.focus();
            event.preventDefault();
        });

        this.labelInput.addEventListener("blur", () => {
            if (!this.validateDropdownItem(this.labelInput.value)) {
                this.labelInput.value = this.getCurrentLabel();
            }
            this.dropdown.style.display = "none";
        });

        this.labelInput.addEventListener("keydown", (event) => {
            const selectedItem = $(".dropdown-item.selected", this.dropdown);

            switch (event.key) {
                case "Backspace": // backspace
                    this.updateDropdown();
                    break;

                case "ArrowUp": // up arrow
                    if (getComputedStyle(this.dropdown).display !== "none") {
                        this.selectPrevDropdownItem();
                    } else {
                        this.selectCurrent();
                    }
                    event.preventDefault();
                    break;

                case "ArrowDown": // down arrow
                    if (getComputedStyle(this.dropdown).display !== "none") {
                        this.selectNextDropdownItem();
                    } else {
                        this.selectCurrent();
                    }
                    event.preventDefault();
                    break;

                case "Enter":
                    if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
                        this.setCurrent(selectedItem);
                    }

                    // dropdown.style.display = 'none';
                    this.labelInput.blur();
                    event.preventDefault();
                    break;

                case "Escape":
                case "ArrowLeft":
                case "ArrowRight":
                    break;

                default:
                    if (!hasKeyboardInput) {
                        this.labelInput.value = "";
                        hasKeyboardInput = true;
                    }
                    break;
            }
        });

        this.labelInput.addEventListener("keyup", (event) => {
            const value = this.labelInput.value.trim();
            switch (event.key) {
                case "Escape":
                    this.labelInput.blur();
                    event.stopPropagation();
                    break;
                case "ArrowUp":
                case "ArrowDown":
                case "ArrowLeft":
                case "ArrowRight":
                case "Tab":
                case "Enter":
                    return true;
                default:
                    this.dropdown.style.display = "block";
                    this.filterDropdown(value);
                    if (value.length > 0) {
                        this.selectFirstDropdownItem();
                    }
            }
        });
    }

    private updateDropdown() {
        let visibleItems = 0;
        $$(".dropdown-item", this.dropdown).forEach((element) => {
            if (getComputedStyle(element).display !== "none") {
                visibleItems++;
            }
            element.classList.remove("selected");
        });

        if (visibleItems > 0) {
            this.emptyState.style.display = "none";
        } else {
            this.emptyState.style.display = "block";
        }
    }

    private filterDropdown(value: string) {
        const filter = (element: HTMLElement) => {
            if (value === "") {
                return true;
            }
            const text = `${element.textContent}`;
            const regexp = new RegExp(`(^|\\b)${makeDiacriticsRegExp(escapeRegExp(value))}`, "i");
            return regexp.test(text);
        };

        let visibleItems = 0;
        $$(".dropdown-item", this.dropdown).forEach((element) => {
            if (value === null || filter(element)) {
                element.style.display = "block";
                visibleItems++;
            } else {
                element.style.display = "none";
            }
        });

        if (visibleItems > 0) {
            this.emptyState.style.display = "none";
        } else {
            this.emptyState.style.display = "block";
        }
    }

    private scrollToDropdownItem(item: HTMLElement) {
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

    private selectDropdownItem(item: HTMLElement) {
        const selectedItem = $(".dropdown-item.selected", this.dropdown);
        if (selectedItem) {
            selectedItem.classList.remove("selected");
        }
        if (item) {
            const isDisabled = item.classList.contains("disabled");
            if (!isDisabled) {
                item.classList.add("selected");
                this.scrollToDropdownItem(item);
            }
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
        const selectedItem = $(".dropdown-item.selected", this.dropdown) as HTMLElement;
        if (selectedItem) {
            let previousItem = selectedItem.previousSibling as HTMLElement;
            while (previousItem && (previousItem.style.display === "none" || previousItem.classList.contains("disabled"))) {
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
        const selectedItem = $(".dropdown-item.selected", this.dropdown) as HTMLElement;
        if (selectedItem) {
            let nextItem = selectedItem.nextSibling as HTMLElement;
            while (nextItem && (nextItem.style.display === "none" || nextItem.classList.contains("disabled"))) {
                nextItem = nextItem.nextSibling as HTMLElement;
            }
            if (nextItem) {
                return this.selectDropdownItem(nextItem);
            }
        }
        this.selectFirstDropdownItem();
    }

    private setCurrent(item: HTMLElement = this.getCurrent()) {
        this.element.value = item.dataset.value as string;
        this.labelInput.value = item.innerText.trim();
        this.element.dispatchEvent(new Event("input", { bubbles: true }));
        this.element.dispatchEvent(new Event("change", { bubbles: true }));
    }

    private getCurrent() {
        return $(`[data-value="${this.element.value}"]`, this.dropdown) as HTMLElement;
    }

    private getCurrentLabel() {
        return this.getCurrent().innerText.trim();
    }

    private getSelected() {
        return $(".dropdown-item.selected", this.dropdown) as HTMLElement;
    }

    private selectCurrent() {
        if (getComputedStyle(this.dropdown).display === "none") {
            this.filterDropdown("");
            this.updateDropdown();
            this.selectDropdownItem(this.getCurrent());
            this.dropdown.style.display = "block";
            this.scrollToDropdownItem(this.getCurrent());
        }
    }

    private validateDropdownItem(value: string) {
        const items = $$(".dropdown-item", this.dropdown);
        for (let i = 0; i < items.length; i++) {
            if (items[i].innerText === value) {
                return true;
            }
        }
        return false;
    }
}
