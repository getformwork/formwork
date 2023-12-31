import { LineChart } from "chartist";
import { passIcon } from "./icons";
import { Tooltip } from "./tooltip";

export class StatisticsChart {
    constructor(element, data) {
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
                labelInterpolationFnc: (value, index, labels) => (index % Math.floor(labels.length / (element.clientWidth / spacing)) ? null : value),
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

        const chart = new LineChart(element, data, options);

        chart.on("draw", (event) => {
            if (event.type === "point") {
                event.element.attr({ "ct:index": event.index });
            }
        });

        chart.container.addEventListener("mouseover", (event) => {
            if (event.target.getAttribute("class") === "ct-point") {
                const strokeWidth = parseFloat(getComputedStyle(event.target)["stroke-width"]);
                const index = event.target.getAttribute("ct:index");

                passIcon("circle-small-fill", (icon) => {
                    const text = `${data.labels[index]}<br><span class="text-color-blue">${icon}</span> ${data.series[0][index]} <span class="text-color-amber ml-2">${icon}</span>${data.series[1][index]}`;
                    const tooltip = new Tooltip(text, {
                        referenceElement: event.target,
                        offset: { x: 0, y: -strokeWidth },
                    });
                    tooltip.show();
                });
            }
        });
    }
}
