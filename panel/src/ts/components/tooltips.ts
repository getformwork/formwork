import { $$ } from "../utils/selectors";
import { debounce } from "../utils/events";
import { Tooltip } from "./tooltip";

export class Tooltips {
    constructor() {
        $$("[title]", document.body).forEach((element) => {
            element.dataset.tooltip = element.title;
            element.removeAttribute("title");
        });

        document.addEventListener(
            "mouseover",
            debounce((event: Event) => {
                const target = event.target as HTMLElement;

                const element = target.closest("[data-tooltip]") as HTMLElement;

                if (element) {
                    const tooltip = new Tooltip(element.dataset.tooltip as string, {
                        referenceElement: element,
                        position: "bottom",
                        offset: {
                            x: 0,
                            y: 4,
                        },
                        delay: 0,
                    });
                    tooltip.show();
                }

                const truncableElement = target.closest(".truncate") as HTMLElement;

                if (truncableElement && truncableElement.offsetWidth < truncableElement.scrollWidth) {
                    const tooltip = new Tooltip((truncableElement.textContent ?? "").trim(), {
                        referenceElement: truncableElement,
                        position: "bottom",
                        offset: {
                            x: 0,
                            y: 4 - parseFloat(getComputedStyle(truncableElement).paddingBottom),
                        },
                        delay: 0,
                    });
                    tooltip.show();
                }
            }, 500),
        );

        // Immediately show tooltip on focused buttons
        document.addEventListener("focusin", (event) => {
            const target = event.target as HTMLElement;

            const element = target.closest("[data-tooltip]") as HTMLElement;

            if (element && (element.tagName.toLowerCase() === "button" || element.classList.contains("button"))) {
                const tooltip = new Tooltip(element.dataset.tooltip as string, {
                    referenceElement: element,
                    position: "bottom",
                    offset: {
                        x: 0,
                        y: 4,
                    },
                    delay: 0,
                });
                tooltip.show();
            }
        });
    }
}
