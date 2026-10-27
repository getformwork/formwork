import { effect, signal } from "@preact/signals-core";
import { $, $$ } from "../../utils/selectors";

/**
 * Handles the collapsible sections, whose state is remembered
 */
export class Sections {
    constructor() {
        $$(".collapsible .section-header").forEach((element) => {
            const section = element.parentNode as HTMLElement;

            const formName = section.closest("form")?.dataset.form;
            const key = formName ? `${formName}.${section.id}` : section.id;

            const state = section.id ? window.localStorage.getItem(`formwork.sectionStatus[${key}]`) : null;

            const collapsed = signal(state ? state === "collapsed" : section.classList.contains("collapsed"));

            const toggleButton = $(".section-toggle", element);

            effect(() => {
                section.classList.toggle("collapsed", collapsed.value);
                toggleButton?.setAttribute("aria-expanded", String(!collapsed.value));
            });

            element.addEventListener("click", () => {
                collapsed.value = !collapsed.value;
                if (section.id) {
                    window.localStorage.setItem(`formwork.sectionStatus[${key}]`, collapsed.value ? "collapsed" : "expanded");
                }
            });
        });
    }
}
