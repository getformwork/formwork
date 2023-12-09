import { $$ } from "../utils/selectors";
import { Modal } from "./modal";

export class Modals {
    constructor() {
        $$(".modal").forEach((element) => (this[element.id] = new Modal(element)));
    }
}
