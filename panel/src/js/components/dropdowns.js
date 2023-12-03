import Utils from "./utils";

export default {
    init: function () {
        if ($(".dropdown")) {
            document.addEventListener("click", (event) => {
                $$(".dropdown-menu").forEach((element) => {
                    element.style.display = "";
                });

                const button = event.target.closest(".dropdown-button");

                if (button) {
                    const dropdown = document.getElementById(button.getAttribute("data-dropdown"));
                    const isVisible = getComputedStyle(dropdown).display !== "none";
                    event.preventDefault();

                    const resizeHandler = Utils.throttle(() => {
                        setDropdownPosition(dropdown);
                    }, 100);

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
    },
};

function setDropdownPosition(dropdown) {
    dropdown.style.left = 0;
    dropdown.style.right = "";

    const dropdownRect = dropdown.getBoundingClientRect();
    const dropdownTop = dropdownRect.top + window.pageYOffset;
    const dropdownLeft = dropdownRect.left + window.pageXOffset;
    const dropdownWidth = Utils.outerWidth(dropdown);
    const dropdownHeight = Utils.outerHeight(dropdown);

    const windowWidth = document.documentElement.clientWidth;
    const windowHeight = document.documentElement.clientHeight;

    if (dropdownLeft + dropdownWidth > windowWidth) {
        dropdown.style.left = "auto";
        dropdown.style.right = 0;
    }

    if (dropdownTop < window.pageYOffset || window.pageYOffset < dropdownTop + dropdownHeight - windowHeight) {
        window.scrollTo(window.pageXOffset, dropdownTop + dropdownHeight - windowHeight);
    }
}
