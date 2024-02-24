import { $$ } from "../utils/selectors";
import { Form } from "./form";

export class Forms {
    [name: string]: Form;

    constructor() {
        $$("[data-form]").forEach((element: HTMLFormElement) => {
            if (element.dataset.form) {
                this[element.dataset.form] = new Form(element);
            }
        });
    }
}
