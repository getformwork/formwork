import { type ChartPlugin, findAll } from "chartist";

declare module "chartist" {
    interface ChartEvents {
        /** The pointer moved to another point (by index in the series) */
        "hover-line:change": { readonly index: number; readonly element: Element };
        /** The pointer left the grids, or the chart is about to render again */
        "hover-line:leave": Record<string, never>;
    }
}

/**
 * Describes a point that can be reached by the hover line
 */
interface HoverPoint {
    x: number;
    index: number;
    element: Element;
}

/**
 * Describes what the plugin adds to the rendered chart
 */
interface Mounted {
    svg: SVGSVGElement;
    line: SVGLineElement;
    points: HoverPoint[];
    // Half of the points' stroke: points on the edges of the grids extend beyond them
    margin: number;
}

/**
 * Find the point nearest to a position
 */
const findNearestPoint = (points: HoverPoint[], x: number) => points.reduce((a, b) => (Math.abs(b.x - x) < Math.abs(a.x - x) ? b : a));

/**
 * Convert the pointer position to the coordinate system of the SVG, which is the one of the points
 */
const getPointerPosition = (event: PointerEvent, svg: SVGSVGElement) => {
    const matrix = svg.getScreenCTM();
    return matrix ? new DOMPoint(event.clientX, event.clientY).matrixTransform(matrix.inverse()) : undefined;
};

/**
 * Show a vertical line on the point nearest to the pointer, while it is inside the grids,
 * and emit `hover-line:change` and `hover-line:leave` for other plugins to react to
 */
export const hoverLine = (): ChartPlugin => ({
    name: "hover-line",
    provides: ["hover-line:change", "hover-line:leave"],
    install(chart) {
        const container = chart.container;

        if (!(container instanceof HTMLElement)) {
            return;
        }

        let mounted: Mounted | undefined;
        let currentIndex = -1;

        const hide = () => {
            mounted?.line.setAttribute("visibility", "hidden");
            if (currentIndex !== -1) {
                currentIndex = -1;
                chart.emit("hover-line:leave", {});
            }
        };

        const onPointer = (event: PointerEvent) => {
            if (!mounted || mounted.points.length === 0) {
                return;
            }

            const { svg, line, points, margin } = mounted;
            const grids = svg.querySelector<SVGGElement>(".ct-grids");
            const position = getPointerPosition(event, svg);

            if (!grids || !position) {
                return hide();
            }

            const { x, y, width, height } = grids.getBBox();

            if (position.x < x - margin || position.x > x + width + margin || position.y < y - margin || position.y > y + height + margin) {
                return hide();
            }

            const nearest = findNearestPoint(points, position.x);

            line.setAttribute("x1", String(nearest.x));
            line.setAttribute("x2", String(nearest.x));
            line.setAttribute("y1", String(y));
            line.setAttribute("y2", String(y + height));
            line.removeAttribute("visibility");

            if (nearest.index !== currentIndex) {
                currentIndex = nearest.index;
                chart.emit("hover-line:change", { index: nearest.index, element: nearest.element });
            }
        };

        // The output is touched only once mounted, and what is added here is undone before the next render
        chart.on("mount", ({ scene, elementOf }) => {
            const svg = container.querySelector("svg");

            if (!svg) {
                return;
            }

            const points = findAll(scene, { role: "point" }).flatMap((node) => {
                const element = elementOf(node);
                return element && node.ctx.valueIndex !== undefined ? [{ x: node.ctx.x, index: node.ctx.valueIndex, element }] : [];
            });

            const line = document.createElementNS("http://www.w3.org/2000/svg", "line");
            line.classList.add("ct-point-line");
            line.setAttribute("stroke", "currentColor");
            line.setAttribute("stroke-opacity", "0.3");
            line.setAttribute("pointer-events", "none");
            line.setAttribute("visibility", "hidden");
            svg.prepend(line);

            const margin = points.length > 0 ? parseFloat(getComputedStyle(points[0].element).strokeWidth) / 2 : 0;

            mounted = { svg, line, points, margin };

            return () => {
                hide();
                line.remove();
                mounted = undefined;
            };
        });

        // A touch has no hover: the line stays after the finger is lifted, until the next touch elsewhere
        const onPointerLeave = (event: PointerEvent) => {
            if (event.pointerType !== "touch") {
                hide();
            }
        };

        const onDocumentPointerDown = (event: PointerEvent) => {
            if (!container.contains(event.target as Node)) {
                hide();
            }
        };

        // Lets horizontal touch moves reach the chart, while vertical ones still scroll the page
        const previousTouchAction = container.style.touchAction;
        container.style.touchAction = "pan-y";

        container.addEventListener("pointerdown", onPointer);
        container.addEventListener("pointermove", onPointer);
        container.addEventListener("pointerleave", onPointerLeave);
        container.addEventListener("pointercancel", hide);
        container.ownerDocument.addEventListener("pointerdown", onDocumentPointerDown);

        return () => {
            container.removeEventListener("pointerdown", onPointer);
            container.removeEventListener("pointermove", onPointer);
            container.removeEventListener("pointerleave", onPointerLeave);
            container.removeEventListener("pointercancel", hide);
            container.ownerDocument.removeEventListener("pointerdown", onDocumentPointerDown);
            container.style.touchAction = previousTouchAction;
            hide();
        };
    },
});
