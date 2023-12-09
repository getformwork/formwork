import { $$ } from "../utils/selectors";
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

        $$('[data-overflow-tooltip="true"]').forEach((element) => {
            element.addEventListener("mouseover", () => {
                if (element.offsetWidth < element.scrollWidth) {
                    const tooltip = new Tooltip(element.textContent.trim(), {
                        referenceElement: element,
                        position: "bottom",
                        offset: {
                            x: 0,
                            y: 4,
                        },
                    });
                    tooltip.show();
                }
            });
        });
    }
}
