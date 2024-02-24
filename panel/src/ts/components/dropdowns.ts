import { $, $$ } from "../utils/selectors";
import { getOuterHeight, getOuterWidth } from "../utils/dimensions";
import { throttle } from "../utils/events";

export class Dropdowns {
    constructor() {
        if ($(".dropdown")) {
            document.addEventListener("click", (event) => {
                $$(".dropdown-menu").forEach((element) => (element.style.display = ""));

                const button = (event.target as HTMLDivElement).closest(".dropdown-button") as HTMLButtonElement;

                if (button) {
                    const dropdown = document.getElementById(button.dataset.dropdown as string) as HTMLElement;
                    const isVisible = getComputedStyle(dropdown as HTMLElement).display !== "none";
                    event.preventDefault();

                    const resizeHandler = throttle(() => setDropdownPosition(dropdown), 100);

                    if (dropdown && !isVisible) {
                        dropdown.style.display = "block";
                        setDropdownPosition(dropdown);
                        window.addEventListener("resize", resizeHandler);
                    } else {
                        window.removeEventListener("resize", resizeHandler);
                    }
                }
            });
        }
    }
}

function setDropdownPosition(dropdown: HTMLElement) {
    dropdown.style.left = "0";
    dropdown.style.right = "";

    const dropdownRect = dropdown.getBoundingClientRect();
    const dropdownTop = dropdownRect.top + window.scrollY;
    const dropdownLeft = dropdownRect.left + window.scrollX;
    const dropdownWidth = getOuterWidth(dropdown);
    const dropdownHeight = getOuterHeight(dropdown);

    const windowWidth = document.documentElement.clientWidth;
    const windowHeight = document.documentElement.clientHeight;

    if (dropdownLeft + dropdownWidth > windowWidth) {
        dropdown.style.left = "auto";
        dropdown.style.right = "0";
    }

    if (dropdownTop < window.scrollY || window.scrollY < dropdownTop + dropdownHeight - windowHeight) {
        window.scrollTo(window.scrollX, dropdownTop + dropdownHeight - windowHeight);
    }
}
