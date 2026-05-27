import { $, $$ } from "../utils/selectors";

export class Tabs {
    constructor() {
        $$(".tabs").forEach((tabs) => {
            const formName = tabs.closest("form")?.dataset.form;
            const tabButtons = $$(".tabs-tab[data-tab]", tabs);
            const tabStatusKey = this.getTabStatusKey(formName);

            const selectTab = (name: string) => {
                tabButtons.forEach((button) => {
                    button.classList.toggle("active", button.dataset.tab === name);
                    button.ariaSelected = (button.dataset.tab === name).toString();
                    const tabPanel = $(`.tabs-panel[data-tab="${button.dataset.tab}"]`);
                    tabPanel?.classList.toggle("visible", button.dataset.tab === name);
                });
            };

            this.restoreSelectedTab(tabs, tabStatusKey, selectTab);

            tabButtons.forEach((tabButton) => {
                tabButton.addEventListener("click", () => {
                    const selectedTab = tabButton.dataset.tab ?? "";
                    selectTab(selectedTab);
                    window.localStorage.setItem(tabStatusKey, selectedTab);
                });
            });
        });
    }

    private getTabStatusKey(formName: string | undefined) {
        return `formwork.tabStatus[${formName}]`;
    }

    private restoreSelectedTab(tabs: HTMLElement, tabStatusKey: string, selectTab: (name: string) => void) {
        const selectedTab = window.localStorage.getItem(tabStatusKey);
        if (!selectedTab) {
            return;
        }

        if (!$(`.tabs-tab[data-tab="${selectedTab}"]`, tabs)) {
            window.localStorage.removeItem(tabStatusKey);
            return;
        }

        selectTab(selectedTab);
    }
}
