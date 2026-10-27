import { circleSmallFill } from "./icons";
import { Tooltip } from "./tooltip";

/**
 * Describes the data of a chart
 */
interface NumericChartistData {
    labels: (string | number)[];
    series: number[][];
}

/**
 * Draws the line chart of the statistics
 */
export class StatisticsChart {
    /**
     * Draw the chart in a container
     */
    constructor(container: HTMLElement, data: NumericChartistData) {
        container.classList.add("is-loading");
        void this.init(container, data);
    }

    /**
     * Draw the chart
     */
    private async init(container: HTMLElement, data: NumericChartistData) {
        const spacing = 100;

        const options = {
            showArea: true,
            fullWidth: true,
            scaleMinSpace: 20,
            divisor: 5,
            chartPadding: 20,
            lineSmooth: false,
            low: 0,
            axisX: {
                showGrid: false,
                labelOffset: {
                    x: 0,
                    y: 10,
                },
                labelInterpolationFnc: (value: string | number, index: number, labels: string[] = []) => (index % Math.floor(labels.length / (container.clientWidth / spacing)) ? null : value),
            },
            axisY: {
                onlyInteger: true,
                offset: 15,
                labelOffset: {
                    x: 0,
                    y: 5,
                },
            },
        };

        const { LineChart } = await import("chartist");

        const chart = new LineChart(container, data, options);

        container.classList.remove("is-loading");

        chart.on("draw", (event) => {
            if (event.type === "point") {
                event.element.attr({ "ct:index": event.index });
            }
        });

        container.addEventListener("mouseover", (event) => this.showPointTooltip(event, data));
    }

    /**
     * Show a tooltip with the values of the point under the pointer
     */
    private showPointTooltip(event: MouseEvent, data: NumericChartistData) {
        const target = event.target as SVGElement;

        if (target.getAttribute("class") !== "ct-point" || !target.hasAttribute("ct:index")) {
            return;
        }

        const strokeWidth = parseFloat(getComputedStyle(target).strokeWidth);
        const index = parseInt(target.getAttribute("ct:index") as string, 10);

        const text = `<div>${data.labels[index]}<br><span class="text-color-blue">${circleSmallFill}</span> ${data.series[0][index]} <span class="text-color-amber ml-2">${circleSmallFill}</span>${data.series[1][index]}</div>`;

        const tooltip = new Tooltip(text, {
            referenceElement: target as unknown as HTMLElement,
            offset: { x: 0, y: -strokeWidth },
        });

        tooltip.show();
    }
}

/**
 * Draw the chart with the data rendered in the `data-chart-data` attribute
 */
export function initStatisticsChart(container: HTMLElement) {
    const chartData = container.dataset.chartData;

    if (chartData) {
        new StatisticsChart(container, JSON.parse(chartData));
    }
}
