import { circleSmallFill } from "./icons";
import { hoverLine } from "./plugins/chartist-hover-line";
import { hoverTooltip } from "./plugins/chartist-hover-tooltip";

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

        const { lineChart } = await import("chartist");

        lineChart(container, data, {
            showArea: true,
            fullWidth: true,
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
                scaleMinSpace: 20,
                divisor: 5,
            },
            plugins: [hoverLine(), hoverTooltip({ renderTooltip: (index) => this.renderTooltip(data, index) })],
        });

        container.classList.remove("is-loading");
    }

    /**
     * Render the content of the tooltip of a point
     */
    private renderTooltip(data: NumericChartistData, index: number) {
        return `<div>${data.labels[index]}<br><span class="text-color-blue">${circleSmallFill}</span> ${data.series[0][index]} <span class="text-color-amber ml-2">${circleSmallFill}</span>${data.series[1][index]}</div>`;
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
