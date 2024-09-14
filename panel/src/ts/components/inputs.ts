import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { ArrayInput } from "./inputs/array-input";
import { DateInput } from "./inputs/date-input";
import { DurationInput } from "./inputs/duration-input";
import { EditorInput } from "./inputs/editor-input";
import { FileInput } from "./inputs/file-input";
import { ImageInput } from "./inputs/image-input";
import { ImagePicker } from "./inputs/image-picker";
import { RangeInput } from "./inputs/range-input";
import { SelectInput } from "./inputs/select-input";
import { TagInput } from "./inputs/tag-input";

export class Inputs {
    [name: string]: object;

    constructor(parent: HTMLElement) {
        $$(".form-input-date", parent).forEach((element: HTMLInputElement) => (this[element.name] = new DateInput(element, app.config.DateInput)));

        $$(".form-input-image", parent).forEach((element: HTMLInputElement) => (this[element.name] = new ImageInput(element)));

        $$(".image-picker", parent).forEach((element: HTMLSelectElement) => (this[element.name] = new ImagePicker(element)));

        $$(".editor-textarea", parent).forEach((element: HTMLTextAreaElement) => (this[element.name] = new EditorInput(element)));

        $$("input[type=file]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new FileInput(element)));

        $$("input[data-field=tags]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new TagInput(element, app.config.TagInput)));

        $$("input[data-field=duration]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new DurationInput(element, app.config.DurationInput)));

        $$("input[type=range]", parent).forEach((element: HTMLInputElement) => (this[element.name] = new RangeInput(element)));

        $$(".form-input-array", parent).forEach((element: HTMLInputElement) => (this[element.name] = new ArrayInput(element)));

        $$("select:not([hidden])", parent).forEach((element: HTMLSelectElement) => (this[element.name] = new SelectInput(element, app.config.SelectInput)));

        $$(".form-input-reset", parent).forEach((element) => {
            const targetId = element.dataset.reset;
            if (targetId) {
                element.addEventListener("click", () => {
                    const target = document.getElementById(targetId) as HTMLInputElement;
                    target.value = "";
                    target.dispatchEvent(new Event("change"));
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
