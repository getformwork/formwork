import { $ } from "../../utils/selectors";
import { StatisticsChart } from "../statistics-chart";

export class Statistics {
    constructor() {
        const chart = $(".statistics-chart");
        if (chart) {
            const chartData = chart.dataset.chartData;
            if (chartData) {
                new StatisticsChart(chart, JSON.parse(chartData));
            }
        }
    }
}
