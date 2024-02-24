import { $$ } from "../utils/selectors";
import { Modal } from "./modal";

export class Modals {
    [id: string]: Modal;
    constructor() {
        $$(".modal").forEach((element: HTMLElement) => (this[element.id] = new Modal(element)));
    }
}
