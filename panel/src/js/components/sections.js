import { $$ } from "../utils/selectors";

export class Sections {
    constructor() {
        $$(".collapsible .section-header").forEach((element) => {
            element.addEventListener("click", () => {
                const section = element.parentNode;
                section.classList.toggle("collapsed");
            });
        });
    }
}
