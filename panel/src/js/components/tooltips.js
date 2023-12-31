import { $$ } from "../utils/selectors";
import { debounce } from "../utils/events";
import { Tooltip } from "./tooltip";

export class Tooltips {
    constructor() {
        $$("[title]", document.body).forEach((element) => {
            element.dataset.tooltip = element.title;
            element.title = "";
        });

        $$("[data-tooltip]").forEach((element) => {
            element.addEventListener("mouseover", () => {
                const tooltip = new Tooltip(element.dataset.tooltip, {
                    referenceElement: element,
                    position: "bottom",
                    offset: {
                        x: 0,
                        y: 4,
                    },
                });
                tooltip.show();
            });

            // Immediately show tooltip on focused buttons
            if (element.tagName.toLowerCase() === "button" || element.classList.contains("button")) {
                element.addEventListener("focus", () => {
                    const tooltip = new Tooltip(element.dataset.tooltip, {
                        referenceElement: element,
                        position: "bottom",
                        offset: {
                            x: 0,
                            y: 4,
                        },
                        delay: 0,
                    });
                    tooltip.show();
                });
            }
        });

        document.addEventListener(
            "mouseover",
            debounce((event) => {
                const element = event.target.closest(".truncate");
                if (element && element.offsetWidth < element.scrollWidth) {
                    const tooltip = new Tooltip(element.textContent.trim(), {
                        referenceElement: element,
                        position: "bottom",
                        offset: {
                            x: 0,
                            y: 4 - parseFloat(getComputedStyle(element).paddingBottom),
                        },
                    });
                    tooltip.show();
                }
            }, 500),
        );
    }
}
