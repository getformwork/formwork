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
    constructor(parent) {
        $$(".form-input-date", parent).forEach((element) => (this[element.name] = new DateInput(element, app.config.DateInput)));

        $$(".form-input-image", parent).forEach((element) => (this[element.name] = new ImageInput(element)));

        $$(".image-picker", parent).forEach((element) => (this[element.name] = new ImagePicker(element)));

        $$(".editor-textarea", parent).forEach((element) => (this[element.name] = new EditorInput(element)));

        $$("input[type=file]", parent).forEach((element) => (this[element.name] = new FileInput(element)));

        $$("input[data-field=tags]", parent).forEach((element) => (this[element.name] = new TagInput(element)));

        $$("input[data-field=duration]", parent).forEach((element) => (this[element.name] = new DurationInput(element, app.config.DurationInput)));

        $$("input[type=range]", parent).forEach((element) => (this[element.name] = new RangeInput(element)));

        $$(".form-input-array", parent).forEach((element) => (this[element.name] = new ArrayInput(element)));

        $$("select:not([hidden])", parent).forEach((element) => (this[element.name] = new SelectInput(element, app.config.SelectInput)));

        $$(".form-input-reset", parent).forEach((element) => {
            element.addEventListener("click", () => {
                const target = document.getElementById(element.dataset.reset);
                target.value = "";
                target.dispatchEvent(new Event("change"));
            });
        });

        $$("input[data-enable]", parent).forEach((element) => {
            element.addEventListener("change", () => {
                const inputs = element.dataset.enable.split(",");
                for (const name of inputs) {
                    const input = $(`input[name="${name}"]`);
                    if (!element.checked) {
                        input.disabled = true;
                    } else {
                        input.disabled = false;
                    }
                }
            });
        });
    }
}
