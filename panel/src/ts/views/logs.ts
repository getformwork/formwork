import { effect, signal } from "@preact/signals-core";
import { highlightMatches, initSearchInput, makeSearchRegExp } from "../utils/search";
import { $, $$ } from "../utils/selectors";
import { escapeHtml } from "../utils/validation";

/**
 * Filter the log entries while typing in the search field
 */
export function initLogs(view: HTMLElement) {
    const searchInput = $<HTMLInputElement>(".logs-search", view);

    if (!searchInput) {
        return;
    }

    const query = signal("");

    effect(() => {
        const regExp = query.value === "" ? null : makeSearchRegExp(query.value, { wordStart: false });

        for (const row of $$<HTMLElement>("tbody tr", view)) {
            const message = $(".log-message", row) as HTMLElement;
            const html = regExp === null ? null : highlightMatches(message.textContent, regExp);

            // Remove the highlights of the previous search
            message.innerHTML = html ?? escapeHtml(message.textContent);
            row.style.display = regExp === null || html !== null ? "" : "none";
        }
    });

    initSearchInput(searchInput, () => {
        query.value = searchInput.value.trim();
    });
}
