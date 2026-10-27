import { $$ } from "../utils/selectors";
import { Modal } from "./modal";

/**
 * Collects the modals of the page, by id
 */
export class Modals {
    [id: string]: Modal;
    constructor() {
        $$(".modal").forEach((element: HTMLElement) => {
            this[element.id] = new Modal(element);
        });
    }
}
