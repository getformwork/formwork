import Utils from "../utils";

export default function SelectInput(select, options) {
    const defaults = { labels: { empty: "No matching options" } };

    options = Utils.extendObject({}, defaults, options);

    let dropdown;

    const labelInput = document.createElement("input");

    const emptyState = document.createElement("div");

    createField();

    function createField() {
        const wrap = document.createElement("div");
        wrap.className = "input-wrap";

        select.hidden = true;

        labelInput.type = "text";

        labelInput.classList.add("select");
        labelInput.setAttribute("data-for", select.id);

        if (select.hasAttribute("disabled")) {
            labelInput.disabled = true;
        }

        select.getAttributeNames().forEach((attr) => {
            if (attr.indexOf("data-") === 0) {
                labelInput.setAttribute(attr, select.getAttribute(attr));
            }
        });

        const list = [];

        $$("option", select).forEach((option) => {
            const attributes = {};

            option.getAttributeNames().forEach((attr) => {
                if (attr.indexOf("data-") === 0) {
                    attributes[attr] = option.getAttribute(attr);
                }
            });

            list.push({
                label: option.innerText,
                value: option.value,
                selected: option.selected,
                disabled: option.disabled,
                attributes: attributes,
            });

            if (option.selected) {
                labelInput.value = option.innerText;
            }
        });

        select.parentNode.insertBefore(wrap, select.nextSibling);

        wrap.appendChild(select);

        wrap.appendChild(labelInput);

        createDropdown(list, wrap);
    }

    function createDropdown(list, wrap) {
        dropdown = document.createElement("div");
        dropdown.className = "dropdown-list";

        dropdown.setAttribute("data-for", select.id);

        emptyState.className = "dropdown-empty";
        emptyState.style.display = "none";
        emptyState.innerText = options.labels.empty;

        dropdown.appendChild(emptyState);

        for (const option of list) {
            const item = document.createElement("div");
            item.className = "dropdown-item";

            item.innerText = option.label;
            item.setAttribute("data-value", option.value);

            if (option.selected) {
                item.classList.add("selected");
            }

            if (option.disabled) {
                item.classList.add("disabled");
            }

            for (const attr in option.attributes) {
                item.setAttribute(attr, option.attributes[attr]);
            }

            item.addEventListener("mousedown", (event) => {
                if (!item.classList.contains("disabled")) {
                    selectDropdownItem(item);
                    setCurrent(item);
                } else {
                    event.preventDefault();
                }
                event.stopPropagation();
            });

            dropdown.appendChild(item);
        }

        wrap.appendChild(dropdown);

        let hasKeyboardInput = false;

        labelInput.addEventListener("focus", () => {
            selectCurrent();
            labelInput.setSelectionRange(0, 0);
            hasKeyboardInput = false;
        });

        labelInput.addEventListener("mousedown", (event) => {
            labelInput.focus();
            event.preventDefault();
        });

        labelInput.addEventListener("blur", () => {
            if (!validateDropdownItem(labelInput.value)) {
                labelInput.value = getCurrentLabel();
            }
            dropdown.style.display = "none";
        });

        labelInput.addEventListener("keydown", (event) => {
            const selectedItem = $(".dropdown-item.selected", dropdown);

            switch (event.key) {
                case "Backspace": // backspace
                    updateDropdown();
                    break;

                case "ArrowUp": // up arrow
                    if (getComputedStyle(dropdown).display !== "none") {
                        selectPrevDropdownItem();
                    } else {
                        selectCurrent();
                    }
                    event.preventDefault();
                    break;

                case "ArrowDown": // down arrow
                    if (getComputedStyle(dropdown).display !== "none") {
                        selectNextDropdownItem();
                    } else {
                        selectCurrent();
                    }
                    event.preventDefault();
                    break;

                case "Enter":
                    if (selectedItem && getComputedStyle(selectedItem).display !== "none") {
                        setCurrent(selectedItem);
                    }

                    // dropdown.style.display = 'none';
                    labelInput.blur();
                    event.preventDefault();
                    break;

                case "Escape":
                case "ArrowLeft":
                case "ArrowRight":
                    break;

                default:
                    if (!hasKeyboardInput) {
                        labelInput.value = "";
                        hasKeyboardInput = true;
                    }
                    break;
            }
        });

        labelInput.addEventListener("keyup", (event) => {
            const value = labelInput.value.trim();
            switch (event.key) {
                case "Escape":
                    labelInput.blur();
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
                    dropdown.style.display = "block";
                    filterDropdown(value);
                    if (value.length > 0) {
                        selectFirstDropdownItem();
                    }
            }
        });
    }

    function updateDropdown() {
        let visibleItems = 0;
        $$(".dropdown-item", dropdown).forEach((element) => {
            if (getComputedStyle(element).display !== "none") {
                visibleItems++;
            }
            element.classList.remove("selected");
        });

        if (visibleItems > 0) {
            emptyState.style.display = "none";
        } else {
            emptyState.style.display = "block";
        }
    }

    function filterDropdown(value) {
        const filter = (element) => {
            const text = element.textContent;
            const regexp = new RegExp(Utils.makeDiacriticsRegExp(Utils.escapeRegExp(value)), "i");
            return regexp.test(text);
        };

        let visibleItems = 0;
        $$(".dropdown-item", dropdown).forEach((element) => {
            if (value === null || filter(element)) {
                element.style.display = "block";
                visibleItems++;
            } else {
                element.style.display = "none";
            }
        });

        if (visibleItems > 0) {
            emptyState.style.display = "none";
        } else {
            emptyState.style.display = "block";
        }
    }

    function scrollToDropdownItem(item) {
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

    function selectDropdownItem(item) {
        const selectedItem = $(".dropdown-item.selected", dropdown);
        if (selectedItem) {
            selectedItem.classList.remove("selected");
        }
        if (item) {
            const isDisabled = item.classList.contains("disabled");
            if (!isDisabled) {
                item.classList.add("selected");
                scrollToDropdownItem(item);
            }
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
            let previousItem = selectedItem.previousSibling;
            while (previousItem && (previousItem.style.display === "none" || previousItem.classList.contains("disabled"))) {
                previousItem = previousItem.previousSibling;
            }
            if (previousItem) {
                return selectDropdownItem(previousItem);
            }
            selectDropdownItem(selectedItem.previousSibling);
        }
        selectLastDropdownItem();
    }

    function selectNextDropdownItem() {
        const selectedItem = $(".dropdown-item.selected", dropdown);
        if (selectedItem) {
            let nextItem = selectedItem.nextSibling;
            while (nextItem && (nextItem.style.display === "none" || nextItem.classList.contains("disabled"))) {
                nextItem = nextItem.nextSibling;
            }
            if (nextItem) {
                return selectDropdownItem(nextItem);
            }
        }
        selectFirstDropdownItem();
    }

    function setCurrent(item) {
        select.value = item.dataset.value;
        labelInput.value = item.innerText;
        Utils.triggerEvent(select, "change");
    }

    function getCurrent() {
        return $(`[data-value="${select.value}"]`, dropdown);
    }

    function getCurrentLabel() {
        return getCurrent().innerText;
    }

    function selectCurrent() {
        if (getComputedStyle(dropdown).display === "none") {
            filterDropdown(null);
            updateDropdown();
            selectDropdownItem(getCurrent());
            dropdown.style.display = "block";
            scrollToDropdownItem(getCurrent());
        }
    }

    function validateDropdownItem(value) {
        const items = $$(".dropdown-item", dropdown);
        for (let i = 0; i < items.length; i++) {
            if (items[i].innerText === value) {
                return true;
            }
        }
        return false;
    }
}
