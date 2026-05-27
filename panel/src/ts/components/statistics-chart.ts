import { circleSmallFill } from "./icons";
import { Tooltip } from "./tooltip";

interface NumericChartistData {
    labels: (string | number)[];
    series: number[][];
}

export class StatisticsChart {
    constructor(container: HTMLElement, data: NumericChartistData) {
        container.classList.add("is-loading");
        this.init(container, data);
    }

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
                labelInterpolationFnc: (value: string | number, index: number, labels?: any) => (index % Math.floor(labels.length / (container.clientWidth / spacing)) ? null : value),
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

        container.addEventListener("mouseover", (event) => {
            const target = event.target as SVGElement;
            if (target.getAttribute("class") === "ct-point" && target.hasAttribute("ct:index")) {
                const strokeWidth = parseFloat(getComputedStyle(target).strokeWidth);
                const index = parseInt(target.getAttribute("ct:index") ?? "");
                const text = `<div>${data.labels[index]}<br><span class="text-color-blue">${circleSmallFill}</span> ${data.series[0][index]} <span class="text-color-amber ml-2">${circleSmallFill}</span>${data.series[1][index]}</div>`;
                const tooltip = new Tooltip(text, {
                    referenceElement: event.target as HTMLElement,
                    offset: { x: 0, y: -strokeWidth },
                });
                tooltip.show();
            }
        });
    }
}
