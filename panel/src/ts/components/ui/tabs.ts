import { effect, signal } from "@preact/signals-core";
import { getNextIndex } from "../../utils/keyboard";
import { $, $$ } from "../../utils/selectors";
import { readStorage, writeStorage } from "../../utils/storage";

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
        // The tab is remembered only for named forms, as the others would share it
        const storageKey = formName ? `formwork.tabStatus[${formName}]` : null;
        const tabButtons = Array.from($$(".tabs-tab[data-tab]", tabs));

        // `null` means that the tab defined by the markup is selected
        const selected = signal<string | null>(null);

        effect(() => {
            if (selected.value !== null) {
                this.showTab(tabButtons, selected.value);
            }
        });

        if (storageKey) {
            const savedTab = readStorage(storageKey);

            if (savedTab) {
                if ($(`.tabs-tab[data-tab="${savedTab}"]`, tabs)) {
                    selected.value = savedTab;
                } else {
                    writeStorage(storageKey, null);
                }
            }
        }

        tabButtons.forEach((tabButton) => {
            tabButton.addEventListener("click", () => {
                // Some browsers do not focus a button when it is clicked, but the arrows need it
                tabButton.focus();
                selected.value = tabButton.dataset.tab as string;
                if (storageKey) {
                    writeStorage(storageKey, tabButton.dataset.tab as string);
                }
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
