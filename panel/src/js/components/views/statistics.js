import { $ } from "../../utils/selectors";
import { StatisticsChart } from "../statistics-chart";

export class Statistics {
    constructor() {
        const chart = $(".statistics-chart");

        if (chart) {
            new StatisticsChart(chart, JSON.parse(chart.dataset.chartData));
        }
    }
}
