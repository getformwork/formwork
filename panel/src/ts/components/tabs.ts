import { $, $$ } from "../utils/selectors";

export class Tabs {
    constructor() {
        $$(".tabs").forEach((tabs) => {
            const formName = tabs.closest("form")?.dataset.form;
            const tabButtons = Array.from($$(".tabs-tab[data-tab]", tabs));

            const selectTab = (name: string) => {
                tabButtons.forEach((button) => {
                    button.classList.toggle("active", button.dataset.tab === name);
                    button.ariaSelected = (button.dataset.tab === name).toString();
                    button.tabIndex = button.dataset.tab === name ? 0 : -1;
                    const tabPanel = $(`.tabs-panel[data-tab="${button.dataset.tab}"]`);
                    tabPanel?.classList.toggle("visible", button.dataset.tab === name);
                });
            };

            const selectedTab = window.localStorage.getItem(`formwork.tabStatus[${formName}]`);
            if (selectedTab) {
                if (!$(`.tabs-tab[data-tab="${selectedTab}"]`, tabs)) {
                    window.localStorage.removeItem(`formwork.tabStatus[${formName}]`);
                } else {
                    selectTab(selectedTab);
                }
            }

            tabButtons.forEach((tabButton) => {
                tabButton.addEventListener("click", () => {
                    selectTab(tabButton.dataset.tab as string);
                    window.localStorage.setItem(`formwork.tabStatus[${formName}]`, tabButton.dataset.tab as string);
                });

                tabButton.addEventListener("keydown", (event) => {
                    const index = tabButtons.indexOf(tabButton);
                    const lastIndex = tabButtons.length - 1;

                    const nextIndex = {
                        ArrowRight: index === lastIndex ? 0 : index + 1,
                        ArrowLeft: index === 0 ? lastIndex : index - 1,
                        Home: 0,
                        End: lastIndex,
                    }[event.key];

                    if (nextIndex !== undefined) {
                        event.preventDefault();
                        tabButtons[nextIndex].focus();
                        tabButtons[nextIndex].click();
                    }
                });
            });
        });
    }
}
