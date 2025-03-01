import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { ArrayInput } from "./inputs/array-input";
import { DateInput } from "./inputs/date-input";
import { DurationInput } from "./inputs/duration-input";
import { EditorInput } from "./inputs/editor-input";
import { FileInput } from "./inputs/file-input";
import { Form } from "./form";
import { ImagePicker } from "./inputs/image-picker";
import { RangeInput } from "./inputs/range-input";
import { SelectInput } from "./inputs/select-input";
import { SlugInput } from "./inputs/slug-input";
import { TagsInput } from "./inputs/tags-input";
import { TogglegroupInput } from "./inputs/togglegroup-input";

export class Inputs {
    [name: string]: object;

    constructor(form: Form) {
        const parent = form.element;

        $$(".editor-textarea", parent).forEach((element: HTMLTextAreaElement) => (this[element.name] = new EditorInput(element)));

        $$(".form-input-array", parent).forEach((element: HTMLInputElement) => (this[element.name] = new ArrayInput(element)));

        $$(".form-input-date", parent).forEach((element: HTMLInputElement) => (this[element.name] = new DateInput(element, app.config.DateInput)));

        $$(".form-input-duration", parent).forEach((element: HTMLInputElement) => (this[element.name] = new DurationInput(element, app.config.DurationInput)));

        $$(".form-input-slug", parent).forEach((element: HTMLInputElement) => (this[element.name] = new SlugInput(element)));

        $$(".form-input-tags", parent).forEach((element: HTMLInputElement) => (this[element.name] = new TagsInput(element, app.config.TagsInput)));

        $$(".form-togglegroup", parent).forEach((element: HTMLFieldSetElement) => (this[element.id] = new TogglegroupInput(element)));

        $$(".image-picker", parent).forEach((element: HTMLSelectElement) => (this[element.name] = new ImagePicker(element)));

        $$("input[type=file]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new FileInput(element, form)));

        $$("input[type=range]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new RangeInput(element)));

        $$("select:not([hidden])", parent).forEach((element: HTMLSelectElement) => (this[element.name] = new SelectInput(element, app.config.SelectInput)));

        $$(".form-input-action[data-reset]", parent).forEach((element) => {
            const targetId = element.dataset.reset;
            if (targetId) {
                element.addEventListener("click", () => {
                    const target = document.getElementById(targetId) as HTMLInputElement;
                    target.value = "";
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                    target.dispatchEvent(new Event("change", { bubbles: true }));
                });
            }
        });

        $$("input[data-enable]", parent).forEach((element: HTMLInputElement) => {
            element.addEventListener("change", () => {
                const targetId = element.dataset.enable;
                if (targetId) {
                    const inputs = targetId.split(",");
                    for (const name of inputs) {
                        const input = $(`input[name="${name}"]`) as HTMLInputElement;
                        if (!element.checked) {
                            input.disabled = true;
                        } else {
                            input.disabled = false;
                        }
                    }
                }
            });
        });
    }
}
