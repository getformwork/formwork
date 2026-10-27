import { Notification } from "../components/ui/notification";
import { initStatisticsChart } from "../components/ui/statistics-chart";
import { app } from "../services/app";
import { post } from "../services/request";
import { $ } from "../utils/selectors";

/**
 * Caches that can be cleared, by command
 */
const clearCacheCommands: Record<string, string | undefined> = {
    "clear-cache": undefined,
    "clear-pages-cache": "pages",
    "clear-images-cache": "images",
    "clear-config-cache": "config",
    "clear-all-cache": "all",
};

/**
 * Clear a cache of the site, or all of them if no type is given
 *
 * @param type Type of cache to clear: `pages`, `images`, `config` or `all`
 */
function clearCache(type?: string) {
    post(`${app.config.baseUri}cache/clear/${type ?? ""}/`.replace(/\/+$/, "/"), {}, (response) => {
        const icon = response.status === "error" ? "exclamationOctagon" : "checkCircle";
        const notification = new Notification(response.message, response.status, { icon });
        notification.show();
    });
}

/**
 * Initialize the dashboard
 */
export function initDashboard(view: HTMLElement) {
    for (const [command, type] of Object.entries(clearCacheCommands)) {
        $(`[data-command=${command}]`, view)?.addEventListener("click", () => clearCache(type));
    }

    const chart = $(".dashboard-chart");

    if (chart) {
        initStatisticsChart(chart);
    }
}
