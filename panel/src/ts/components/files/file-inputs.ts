import type { Form } from "../../services/form";
import { toCamelCase } from "../../utils/strings";
import { SelectInput } from "../inputs/select-input";
import { TagsInput } from "../inputs/tags-input";
import type * as icons from "../ui/icons";

/**
 * Describes a file listed by the select and tags inputs
 */
export interface FileOption {
    name: string;
    type: string;
    thumbnail?: string;
}

/**
 * Get the select and tags inputs of a form that list files If a type is given, the inputs that list
 * only images are left out unless the type is `image`
 *
 * @param onlyForType Type of the file to offer to the inputs
 */
function fileInputs(form: Form | undefined, onlyForType?: string) {
    const inputs: (SelectInput | TagsInput)[] = [];

    if (form) {
        for (const name in form.inputs) {
            const input = form.inputs[name];
            const acceptsType = (images: boolean) => onlyForType === undefined || !images || onlyForType === "image";

            if (input instanceof SelectInput && (input.element.classList.contains("form-file") || (input.element.classList.contains("form-image") && acceptsType(true)))) {
                inputs.push(input);
            }

            if (input instanceof TagsInput && (input.element.classList.contains("form-files") || (input.element.classList.contains("form-images") && acceptsType(true)))) {
                inputs.push(input);
            }
        }
    }

    return inputs;
}

/**
 * Add a file to an input and keep its options sorted
 */
function addOption(input: SelectInput | TagsInput, file: FileOption) {
    const thumb = file.type === "image" ? file.thumbnail : undefined;

    if (input instanceof SelectInput) {
        input.addOption({ label: file.name, value: file.name, thumb, icon: `file-${file.type}` });
    } else {
        input.addDropdownItem({ label: file.name, value: file.name, thumb, icon: toCamelCase(`file-${file.type}`) as keyof typeof icons });
    }

    input.sortDropdownItems();
}

/**
 * Remove a file from an input
 */
function removeOption(input: SelectInput | TagsInput, filename: string) {
    if (input instanceof SelectInput) {
        input.removeOption(filename);
    } else {
        input.removeDropdownItem(filename);
    }
}

/**
 * Add a new file to the inputs of a form
 */
export function addToFileInputs(form: Form | undefined, file: FileOption) {
    for (const input of fileInputs(form, file.type)) {
        addOption(input, file);
    }
}

/**
 * Remove a deleted file from the inputs of a form
 */
export function removeFromFileInputs(form: Form | undefined, filename: string) {
    for (const input of fileInputs(form)) {
        removeOption(input, filename);
    }
}

/**
 * Replace a renamed file in the inputs of a form
 */
export function replaceInFileInputs(form: Form | undefined, filename: string, file: FileOption) {
    for (const input of fileInputs(form)) {
        removeOption(input, filename);
        addOption(input, file);
    }
}
