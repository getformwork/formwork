import { $$ } from "../utils/selectors";
import { Form } from "./form";

/**
 * Collects the forms of the page, by name
 */
export class Forms {
    [name: string]: Form;

    constructor() {
        $$<HTMLFormElement>("[data-form]").forEach((element) => {
            if (element.dataset.form) {
                this[element.dataset.form] = new Form(element, {
                    preventUnloadOnChanges: element.dataset.ignoreChanges !== "true",
                });
            }
        });
    }
}
