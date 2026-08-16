import { $$ } from "../utils/selectors";
import { Form } from "./form";

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
