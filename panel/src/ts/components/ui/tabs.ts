import { effect, signal } from "@preact/signals-core";
import { getNextIndex } from "../../utils/keyboard";
import { $, $$ } from "../../utils/selectors";

/**
 * Handles the tabs of the page with keyboard navigation, whose selection is remembered
 */
export class Tabs {
    /**
     * Initialize the tabs of the page
     */
    constructor() {
        for (const tabs of $$(".tabs")) {
            this.initTabs(tabs);
        }
    }

    /**
     * Initialize a group of tabs
     */
    private initTabs(tabs: HTMLElement) {
        const formName = tabs.closest("form")?.dataset.form;
        const storageKey = `formwork.tabStatus[${formName}]`;
        const tabButtons = Array.from($$(".tabs-tab[data-tab]", tabs));

        // `null` means that the tab defined by the markup is selected
        const selected = signal<string | null>(null);

        effect(() => {
            if (selected.value !== null) {
                this.showTab(tabButtons, selected.value);
            }
        });

        const savedTab = window.localStorage.getItem(storageKey);

        if (savedTab) {
            if ($(`.tabs-tab[data-tab="${savedTab}"]`, tabs)) {
                selected.value = savedTab;
            } else {
                window.localStorage.removeItem(storageKey);
            }
        }

        tabButtons.forEach((tabButton) => {
            tabButton.addEventListener("click", () => {
                selected.value = tabButton.dataset.tab as string;
                window.localStorage.setItem(storageKey, tabButton.dataset.tab as string);
            });

            tabButton.addEventListener("keydown", (event) => {
                const nextIndex = getNextIndex(event.key, tabButtons.indexOf(tabButton), tabButtons.length - 1);

                if (nextIndex !== undefined) {
                    event.preventDefault();
                    tabButtons[nextIndex].focus();
                    tabButtons[nextIndex].click();
                }
            });
        });
    }

    /**
     * Select a tab and show its panel
     *
     * @param tabButtons Buttons of the tabs
     * @param name       Name of the tab to select
     */
    private showTab(tabButtons: HTMLElement[], name: string) {
        tabButtons.forEach((button) => {
            const isSelected = button.dataset.tab === name;

            button.classList.toggle("active", isSelected);
            button.ariaSelected = isSelected.toString();
            button.tabIndex = isSelected ? 0 : -1;

            $(`.tabs-panel[data-tab="${button.dataset.tab}"]`)?.classList.toggle("visible", isSelected);
        });
    }
}
