export class Tooltip {
    constructor(text, options) {
        const defaults = {
            container: document.body,
            referenceElement: document.body,
            position: "top",
            offset: {
                x: 0,
                y: 0,
            },
            delay: 500,
            timeout: null,
            removeOnMouseout: true,
            removeOnClick: false,
        };

        this.text = text;
        this.options = Object.assign({}, defaults, options);
    }

    show() {
        const options = this.options;
        const container = options.container;

        this.delayTimer = setTimeout(() => {
            const tooltip = document.createElement("div");
            tooltip.className = "tooltip";
            tooltip.setAttribute("role", "tooltip");
            tooltip.style.display = "block";
            tooltip.innerHTML = this.text;

            const getTooltipPosition = (tooltip) => {
                const referenceElement = options.referenceElement;
                const offset = options.offset;
                const rect = referenceElement.getBoundingClientRect();

                const top = rect.top + window.scrollY;
                const left = rect.left + window.scrollX;

                const hw = (rect.width - tooltip.offsetWidth) / 2;
                const hh = (rect.height - tooltip.offsetHeight) / 2;

                switch (options.position) {
                    case "top":
                        return {
                            top: Math.round(top - tooltip.offsetHeight + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                    case "right":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left + referenceElement.offsetWidth + offset.x),
                        };
                    case "bottom":
                        return {
                            top: Math.round(top + referenceElement.offsetHeight + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                    case "left":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left - tooltip.offsetWidth + offset.x),
                        };
                    case "center":
                        return {
                            top: Math.round(top + hh + offset.y),
                            left: Math.round(left + hw + offset.x),
                        };
                }
            };

            container.appendChild(tooltip);

            const position = getTooltipPosition(tooltip);
            tooltip.style.top = `${position.top}px`;
            tooltip.style.left = `${position.left}px`;

            if (options.timeout !== null) {
                this.timeoutTimer = setTimeout(() => this.remove(), options.timeout);
            }

            this.tooltipElement = tooltip;
        }, options.delay);

        const referenceElement = options.referenceElement;

        if (referenceElement.tagName.toLowerCase() === "button" || referenceElement.classList.contains("button")) {
            referenceElement.addEventListener("click", () => this.remove());
            referenceElement.addEventListener("blur", () => this.remove());
        }

        if (options.removeOnMouseout) {
            referenceElement.addEventListener("mouseout", () => this.remove());
        }
        if (options.removeOnClick) {
            referenceElement.addEventListener("click", () => this.remove());
        }
    }

    remove() {
        clearTimeout(this.delayTimer);
        clearTimeout(this.timeoutTimer);

        const tooltip = this.tooltipElement;
        const container = this.options.container;

        if (tooltip !== undefined && container.contains(tooltip)) {
            container.removeChild(tooltip);
        }
    }
}
