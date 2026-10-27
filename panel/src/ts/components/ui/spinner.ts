import { check, exclamation, info } from "./icons";

/**
 * Represents the status of a spinner
 */
export type SpinnerStatus = "pending" | "info" | "success" | "danger";

/**
 * Icon of each status
 */
const statusIcons: Record<SpinnerStatus, string> = {
    pending: "",
    info,
    success: check,
    danger: exclamation,
};

/**
 * Show a status in a spinner
 */
export function renderSpinner(spinner: HTMLElement, status: SpinnerStatus) {
    spinner.className = status === "pending" ? "spinner" : `spinner spinner-${status}`;
    spinner.innerHTML = statusIcons[status];
}
