import { $$ } from "../utils/selectors";
import { debounce } from "../utils/events";
import { Tooltip } from "./tooltip";

export class Tooltips {
    tooltip?: Tooltip;
    element?: HTMLElement;

    constructor() {
        $$("[title]", document.body).forEach((element) => {
            element.dataset.tooltip = element.title;
            element.removeAttribute("title");
        });

        document.addEventListener(
            "mousemove",
            debounce((event: MouseEvent) => {
                const target = event.target as HTMLElement;

                const element = target.closest("[data-tooltip]") as HTMLElement;

                const position = { x: event.pageX, y: event.pageY };
                const offset = { x: 0, y: 16 };

                if (element) {
                    if (this.element === element && !this.tooltip?.removed) {
                        return;
                    }

                    this.element = element;

                    this.tooltip?.remove();

                    this.tooltip = new Tooltip(element.dataset.tooltip as string, {
                        referenceElement: element,
                        position,
                        offset,
                        delay: 0,
                    });

                    this.tooltip.show();
                }

                const truncableElement = target.closest(".truncate") as HTMLElement;

                if (truncableElement && truncableElement.offsetWidth < truncableElement.scrollWidth) {
                    if (this.element === truncableElement && !this.tooltip?.removed) {
                        return;
                    }

                    this.element = truncableElement;

                    this.tooltip?.remove();

                    this.tooltip = new Tooltip((truncableElement.textContent ?? "").trim(), {
                        referenceElement: truncableElement,
                        position,
                        offset,
                        delay: 0,
                    });

                    this.tooltip.show();
                }
            }, 500),
        );

        // Immediately show tooltip on focused buttons
        document.addEventListener("focusin", (event) => {
            const target = event.target as HTMLElement;

            const element = target.closest("[data-tooltip]") as HTMLElement;

            if (element && (element.tagName.toLowerCase() === "button" || element.classList.contains("button"))) {
                if (this.element === element) {
                    return;
                }

                this.element = element;

                this.tooltip?.remove();

                this.tooltip = new Tooltip(element.dataset.tooltip as string, {
                    referenceElement: element,
                    position: "bottom",
                    offset: {
                        x: 0,
                        y: 4,
                    },
                    delay: 0,
                });

                this.tooltip.show();
            }
        });
    }
}
