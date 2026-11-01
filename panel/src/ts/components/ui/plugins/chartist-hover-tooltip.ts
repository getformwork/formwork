import type { ChartPlugin } from "chartist";
import { Tooltip } from "../tooltip";
import "./chartist-hover-line";

/**
 * Holds the options of the hover tooltip
 */
interface HoverTooltipOptions {
    renderTooltip: (index: number) => string;
}

/**
 * Show a tooltip on the point reported by the `hover-line` plugin
 */
export const hoverTooltip = (options: HoverTooltipOptions): ChartPlugin => ({
    name: "hover-tooltip",
    install(chart) {
        let tooltip: Tooltip | undefined;

        const hide = () => {
            tooltip?.remove();
            tooltip = undefined;
        };

        chart.on("hover-line:change", ({ index, element }) => {
            hide();
            const strokeWidth = parseFloat(getComputedStyle(element).strokeWidth);
            // Tooltip expects an HTMLElement as reference, but only uses the bounding rect of SVG points
            tooltip = new Tooltip(options.renderTooltip(index), { referenceElement: element as unknown as HTMLElement, offset: { x: 0, y: -strokeWidth }, delay: 0, removeOnMouseout: false });
            // Prevents the tooltip from stealing the pointer and causing flickering
            tooltip.element.style.pointerEvents = "none";
            tooltip.show();
        });

        chart.on("hover-line:leave", hide);

        return hide;
    },
});
