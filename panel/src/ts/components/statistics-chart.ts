import { LineChart, LineChartData } from "chartist";
import { passIcon } from "./icons";
import { Tooltip } from "./tooltip";

export class StatisticsChart {
    constructor(container: HTMLElement, data: LineChartData) {
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

        const chart = new LineChart(container, data, options);

        chart.on("draw", (event) => {
            if (event.type === "point") {
                event.element.attr({ "ct:index": event.index });
            }
        });

        container.addEventListener("mouseover", (event) => {
            const target = event.target as SVGElement;
            if (target.getAttribute("class") === "ct-point") {
                const strokeWidth = parseFloat(getComputedStyle(target).strokeWidth);
                const index = target.getAttribute("ct:index");
                if (index) {
                    passIcon("circle-small-fill", (icon) => {
                        // @ts-expect-error TODO
                        const text = `${data.labels[index]}<br><span class="text-color-blue">${icon}</span> ${data.series[0][index]} <span class="text-color-amber ml-2">${icon}</span>${data.series[1][index]}`;
                        const tooltip = new Tooltip(text, {
                            referenceElement: event.target as HTMLElement,
                            offset: { x: 0, y: -strokeWidth },
                        });
                        tooltip.show();
                    });
                }
            }
        });
    }
}
