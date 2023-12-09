import { $$ } from "../utils/selectors";
import { Form } from "./form";

export class Forms {
    constructor() {
        $$("[data-form]").forEach((element) => (this[element.dataset.form] = new Form(element)));
    }
}
