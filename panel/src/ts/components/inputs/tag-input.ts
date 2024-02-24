import { $, $$ } from "../../utils/selectors";
import { escapeRegExp, makeDiacriticsRegExp } from "../../utils/validation";
import { debounce } from "../../utils/events";

export class TagInput {
    constructor(input: HTMLInputElement) {
        const options = { addKeyCodes: ["Space"] };
        let tags: string[] = [];
        let placeholder: string, dropdown: HTMLElement;

        const field = document.createElement("div");
        const innerInput = document.createElement("input");
        const hiddenInput = document.createElement("input");

        createField();
        createDropdown();

        registerInputEvents();

        function createField() {
            const isRequired = input.hasAttribute("required");
            const isDisabled = input.hasAttribute("disabled");

            field.className = "form-input-tag";

            innerInput.className = "form-input tag-inner-input";
            innerInput.type = "text";
            innerInput.placeholder = input.placeholder;

            hiddenInput.className = "form-input-tag-hidden";
            hiddenInput.name = input.name;
            hiddenInput.id = input.id;
            hiddenInput.type = "text";
            hiddenInput.value = input.value;
            hiddenInput.readOnly = true;
            hiddenInput.hidden = true;

            if (isRequired) {
                hiddenInput.required = true;
            }

            if (isDisabled) {
                field.setAttribute("disabled", "disabled");
                innerInput.disabled = true;
                hiddenInput.disabled = true;
            }

            (input.parentNode as ParentNode).replaceChild(field, input);
            field.appendChild(innerInput);
            field.appendChild(hiddenInput);

            if (hiddenInput.value) {
                tags = hiddenInput.value.split(", ");
                tags.forEach((value, index) => {
                    value = value.trim();
                    tags[index] = value;
                    insertTag(value);
                });
            }

            if (innerInput.placeholder) {
                placeholder = innerInput.placeholder;
                updatePlaceholder();
            } else {
                placeholder = "";
            }

            field.addEventListener("mousedown", (event) => {
                innerInput.focus();
                event.preventDefault();
            });
        }

        function createDropdown() {
            if ("options" in input.dataset) {
                const list = JSON.parse(input.dataset.options ?? "{}");

                dropdown = document.createElement("div");
                dropdown.className = "dropdown-list";

                for (const key in list) {
                    const item = document.createElement("div");
                    item.className = "dropdown-item";
                    item.innerHTML = list[key];
                    item.dataset.value = key;
                    item.addEventListener("click", function () {
                        this.dataset.value && addTag(this.dataset.value);
                    });
                    dropdown.appendChild(item);
                }

                field.appendChild(dropdown);

                innerInput.addEventListener("focus", () => {
                    if (getComputedStyle(dropdown).display === "none") {
                        updateDropdown();
                        dropdown.scrollTop = 0;
                        dropdown.style.display = "block";
                    }
                });

                innerInput.addEventListener("blur", () => {
                    if (getComputedStyle(dropdown).display !== "none") {
                        updateDropdown();
                        dropdown.style.display = "none";
                    }
                });

                innerInput.addEventListener("keydown", (event) => {
                    switch (event.key) {
                        case "Backspace":
                            updateDropdown();
                            break;
                        case "Enter":
                            if (getComputedStyle(dropdown).display !== "none") {
                                addTagFromSelectedDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        case "ArrowUp":
                            if (getComputedStyle(dropdown).display !== "none") {
                                selectPrevDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        case "ArrowDown":
                            if (getComputedStyle(dropdown).display !== "none") {
                                selectNextDropdownItem();
                                event.preventDefault();
                            }
                            break;
                        default:
                            if (options.addKeyCodes.includes(event.key)) {
                                addTagFromSelectedDropdownItem();
                                event.preventDefault();
                            }
                    }
                });

                innerInput.addEventListener(
                    "keyup",
                    debounce((event: KeyboardEvent) => {
                        const value = innerInput.value.trim();
                        switch (event.key) {
                            case "Escape":
                                dropdown.style.display = "none";
                                break;
                            case "ArrowUp":
                            case "ArrowDown":
                                return true;
                            default:
                                dropdown.style.display = "block";
                                filterDropdown(value);
                                if (value.length > 0) {
                                    selectFirstDropdownItem();
                                }
                        }
                    }, 100),
                );
            }
        }

        function registerInputEvents() {
            innerInput.addEventListener("focus", () => field.classList.add("focused"));

            innerInput.addEventListener("blur", () => {
                const value = innerInput.value.trim();
                if (value !== "") {
                    addTag(value);
                }
                field.classList.remove("focused");
            });

            innerInput.addEventListener("keydown", (event) => {
                const value = innerInput.value.trim();
                switch (event.key) {
                    case "Backspace":
                        if (value === "") {
                            removeTag(tags[tags.length - 1]);
                            if (innerInput.previousSibling) {
                                (innerInput.parentNode as ParentNode).removeChild(innerInput.previousSibling);
                            }
                            event.preventDefault();
                        } else {
                            innerInput.size = Math.max(innerInput.value.length, innerInput.placeholder.length, 1);
                        }
                        break;
                    case "Enter":
                    case "Comma":
                        if (value !== "") {
                            addTag(value);
                        }
                        event.preventDefault();
                        break;
                    case "Escape":
                        clearInput();
                        innerInput.blur();
                        event.preventDefault();
                        break;
                    default:
                        if (value !== "" && options.addKeyCodes.includes(event.key)) {
                            addTag(value);
                            event.preventDefault();
                            break;
                        }
                        if (value.length > 0) {
                            innerInput.size = innerInput.value.length + 2;
                        }
                        break;
                }
            });
        }

        function updateTags() {
            hiddenInput.value = tags.join(", ");
            updatePlaceholder();
        }

        function updatePlaceholder() {
            if (placeholder.length > 0) {
                if (tags.length === 0) {
                    innerInput.placeholder = placeholder;
                    innerInput.size = placeholder.length;
                } else {
                    innerInput.placeholder = "";
                    innerInput.size = 1;
                }
            }
        }

        function validateTag(value: string) {
            if (!tags.includes(value)) {
                if (dropdown) {
                    return $(`[data-value="${value}"]`, dropdown) !== null;
                }
                return true;
            }
            return false;
        }

        function insertTag(value: string) {
            const tag = document.createElement("span");
            const tagRemove = document.createElement("i");
            tag.className = "tag";
            tag.innerHTML = value;
            tag.style.marginRight = ".25rem";
            (innerInput.parentNode as ParentNode).insertBefore(tag, innerInput);

            tagRemove.className = "tag-remove";
            tagRemove.setAttribute("role", "button");
            tagRemove.addEventListener("mousedown", (event) => {
                removeTag(value);
                (tag.parentNode as ParentNode).removeChild(tag);
                event.preventDefault();
            });
            tag.appendChild(tagRemove);
        }

        function addTag(value: string) {
            if (validateTag(value)) {
                tags.push(value);
                insertTag(value);
                updateTags();
            } else {
                updatePlaceholder();
            }
            innerInput.value = "";
            if (dropdown) {
                updateDropdown();
            }
        }

        function removeTag(value: string) {
            const index = tags.indexOf(value);
            if (index > -1) {
                tags.splice(index, 1);
                updateTags();
            }
            if (dropdown) {
                updateDropdown();
            }
        }

        function clearInput() {
            innerInput.value = "";
            updatePlaceholder();
        }

        function updateDropdown() {
            let visibleItems = 0;
            $$(".dropdown-item", dropdown).forEach((element) => {
                if (getComputedStyle(element).display !== "none") {
                    visibleItems++;
                }
                if (!tags.includes(element.dataset.value as string)) {
                    element.style.display = "block";
                } else {
                    element.style.display = "none";
                }
                element.classList.remove("selected");
            });
            if (visibleItems > 0) {
                dropdown.style.display = "block";
            } else {
                dropdown.style.display = "none";
            }
        }

        function filterDropdown(value: string) {
            let visibleItems = 0;
            dropdown.style.display = "block";
            $$(".dropdown-item", dropdown).forEach((element) => {
                const text = `${element.textContent}`;
                const regexp = new RegExp(makeDiacriticsRegExp(escapeRegExp(value)), "i");
                if (text.match(regexp) !== null && element.style.display !== "none") {
                    element.style.display = "block";
                    visibleItems++;
                } else {
                    element.style.display = "none";
                }
            });
            if (visibleItems > 0) {
                dropdown.style.display = "block";
            } else {
                dropdown.style.display = "none";
            }
        }

        function scrollToDropdownItem(item: HTMLElement) {
            const dropdownScrollTop = dropdown.scrollTop;
            const dropdownHeight = dropdown.clientHeight;
            const dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
            const dropdownStyle = getComputedStyle(dropdown);
            const dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
            const dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
            const itemTop = item.offsetTop;
            const itemHeight = item.clientHeight;
            const itemBottom = itemTop + itemHeight;
            if (itemTop < dropdownScrollTop) {
                dropdown.scrollTop = itemTop - dropdownPaddingTop;
            } else if (itemBottom > dropdownScrollBottom) {
                dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
            }
        }

        function addTagFromSelectedDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
                innerInput.value = selectedItem.dataset.value as string;
            }
        }

        function selectDropdownItem(item: HTMLElement) {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                selectedItem.classList.remove("selected");
            }
            if (item) {
                item.classList.add("selected");
                scrollToDropdownItem(item);
            }
        }

        function selectFirstDropdownItem() {
            const items = $$(".dropdown-item", dropdown);
            for (let i = 0; i < items.length; i++) {
                if (getComputedStyle(items[i]).display !== "none") {
                    selectDropdownItem(items[i]);
                    return;
                }
            }
        }

        function selectLastDropdownItem() {
            const items = $$(".dropdown-item", dropdown);
            for (let i = items.length - 1; i >= 0; i--) {
                if (getComputedStyle(items[i]).display !== "none") {
                    selectDropdownItem(items[i]);
                    return;
                }
            }
        }

        function selectPrevDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                let previousItem = selectedItem.previousSibling as HTMLElement;
                while (previousItem && previousItem.style.display === "none") {
                    previousItem = previousItem.previousSibling as HTMLElement;
                }
                if (previousItem) {
                    return selectDropdownItem(previousItem);
                }
                selectDropdownItem(selectedItem.previousSibling as HTMLElement);
            }
            selectLastDropdownItem();
        }

        function selectNextDropdownItem() {
            const selectedItem = $(".dropdown-item.selected", dropdown);
            if (selectedItem) {
                let nextItem = selectedItem.nextSibling as HTMLElement;
                while (nextItem && nextItem.style.display === "none") {
                    nextItem = nextItem.nextSibling as HTMLElement;
                }
                if (nextItem) {
                    return selectDropdownItem(nextItem as HTMLElement);
                }
            }
            selectFirstDropdownItem();
        }
    }
}
