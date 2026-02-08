import "../polyfills/request-submit";
import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { ArrayInput } from "./inputs/array-input";
import { ColorInput } from "./inputs/color-input";
import { DateInput } from "./inputs/date-input";
import { DurationInput } from "./inputs/duration-input";
import { EditorInput } from "./inputs/editor-input";
import { ImagePicker } from "./inputs/image-picker";
import { Input } from "./inputs/input";
import { RangeInput } from "./inputs/range-input";
import { SelectInput } from "./inputs/select-input";
import { serializeForm } from "../utils/forms";
import { SlugInput } from "./inputs/slug-input";
import { TagsInput } from "./inputs/tags-input";
import { TogglegroupInput } from "./inputs/togglegroup-input";
import { UploadInput } from "./inputs/upload-input";

interface FormOptions {
    preventUnloadOnChanges?: boolean;
}

interface InputElement extends HTMLElement {
    name: string;
}

interface FormInput {
    element: InputElement;
    name: string;
    value: string;
}

export type HTMLInputLike = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

export class Form {
    readonly formInputs: FormInput[] = [];

    originalData: string;
    element: HTMLFormElement;
    options: FormOptions = {
        preventUnloadOnChanges: true,
    };

    private associations: { [key: string]: (element: HTMLElement) => void } = {
        ".editor-textarea": (element: HTMLTextAreaElement) => this.formInputs.push(new EditorInput(element)),

        ".form-input-color": (element: HTMLInputElement) => this.formInputs.push(new ColorInput(element)),

        ".form-input-array": (element: HTMLFieldSetElement) => this.formInputs.push(new ArrayInput(element, this)),

        ".form-input-date": (element: HTMLInputElement) => this.formInputs.push(new DateInput(element, app.config.DateInput)),

        ".form-input-duration": (element: HTMLInputElement) => this.formInputs.push(new DurationInput(element, app.config.DurationInput)),

        ".form-input-slug": (element: HTMLInputElement) => this.formInputs.push(new SlugInput(element)),

        ".form-input-tags": (element: HTMLInputElement) => this.formInputs.push(new TagsInput(element, app.config.TagsInput)),

        ".form-togglegroup": (element: HTMLFieldSetElement) => this.formInputs.push(new TogglegroupInput(element)),

        ".image-picker": (element: HTMLInputElement) => this.formInputs.push(new ImagePicker(element)),

        "input[type=file]": (element: HTMLInputElement) => this.formInputs.push(new UploadInput(element, this)),

        "input[type=range]": (element: HTMLInputElement) => this.formInputs.push(new RangeInput(element)),

        ".form-select": (element: HTMLSelectElement) => this.formInputs.push(new SelectInput(element, app.config.SelectInput)),

        ".form-input-action[data-reset]": (element: HTMLButtonElement) => {
            const targetId = element.dataset.reset;
            if (targetId) {
                element.addEventListener("click", () => {
                    const target = document.getElementById(targetId) as HTMLInputElement;
                    target.value = "";
                    target.dispatchEvent(new Event("input", { bubbles: true }));
                    target.dispatchEvent(new Event("change", { bubbles: true }));
                });
            }
        },

        "input[data-enable]": (element: HTMLInputElement) => {
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
        },

        "input, select, textarea": (element: HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement) => {
            if (!this.formInputs.find((input) => input.element === element)) {
                this.formInputs.push(new Input(element));
            }
        },
    };

    constructor(form: HTMLFormElement, options: Partial<FormOptions> = {}) {
        this.element = form;

        this.loadInputs();

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        this.options = { ...this.options, ...options };

        if (this.options.preventUnloadOnChanges) {
            this.preventUnloadOnChanges();
        }
    }

    get inputs(): { [name: string]: FormInput } {
        const inputs: { [name: string]: FormInput } = {};
        for (const input of this.formInputs) {
            inputs[input.name] = input;
        }
        return inputs;
    }

    private loadInputs(parent: HTMLElement = this.element) {
        for (const selector in this.associations) {
            $$(selector, parent).forEach((element: HTMLElement) => {
                this.associations[selector](element);
            });
        }
    }

    private loadInput(element: HTMLElement) {
        for (const selector in this.associations) {
            if (element.matches(selector)) {
                this.associations[selector](element);
            }
        }
    }

    hasChanged(checkFileInputs: boolean = true) {
        const fileInputs = $$("input[type=file]", this.element) as NodeListOf<HTMLInputElement>;

        if (checkFileInputs === true && fileInputs.length > 0) {
            for (const fileInput of Array.from(fileInputs)) {
                if (fileInput.files && fileInput.files.length > 0) {
                    return true;
                }
            }
        }

        return serializeForm(this.element) !== this.originalData;
    }

    duplicateInput(element: HTMLInputLike, targetElement: HTMLElement) {
        let newNode: HTMLElement;
        let newInput: HTMLInputLike | undefined = undefined;
        const wrap = element.closest(".form-input-wrap");

        if (wrap) {
            newNode = wrap.cloneNode() as HTMLElement;
            for (const child of Array.from(wrap.children)) {
                if (child === element) {
                    newInput = child.cloneNode(true) as HTMLInputLike;
                    if (newInput instanceof HTMLInputElement && (newInput.type === "checkbox" || newInput.type === "radio")) {
                        newInput.checked = false;
                    } else {
                        newInput.value = "";
                    }
                    newNode.appendChild(newInput);
                } else if (child.matches(`.form-input-action, .form-input-description, .form-input-icon`)) {
                    newNode.appendChild(child.cloneNode(true));
                }
            }
            if (newInput === undefined) {
                throw new Error("Could not replicate input: input element not found in wrapper.");
            }
        } else {
            newInput = newNode = element.cloneNode(true) as HTMLInputLike;
            if (newInput instanceof HTMLInputElement && (newInput.type === "checkbox" || newInput.type === "radio")) {
                newInput.checked = false;
            } else {
                newInput.value = "";
            }
        }

        // Generate a new unique ID for the duplicated input
        const previousId = (element as HTMLElement).id;
        const newId = `${element.tagName.toLowerCase()}-${Math.random().toString(36).slice(2)}`;
        newInput.id = newId;

        if (wrap && previousId) {
            $$(`label[for="${previousId}"]`).forEach((label: HTMLLabelElement) => {
                label.htmlFor = newId;
            });
        }

        targetElement.appendChild(newNode);

        if (wrap) {
            this.loadInputs(newNode);
        } else {
            this.loadInput(newInput);
        }
    }

    private preventUnloadOnChanges() {
        const handleBeforeunload = (event: Event) => {
            if (this.hasChanged()) {
                event.preventDefault();
                event.returnValue = false;
            }
        };

        const removeBeforeUnload = () => {
            window.removeEventListener("beforeunload", handleBeforeunload);
        };

        window.addEventListener("beforeunload", handleBeforeunload);

        this.element.addEventListener("submit", removeBeforeUnload);

        const changesModal = app.modals["changesModal"];

        if (changesModal) {
            changesModal.onCommand("continue", (_, button) => {
                removeBeforeUnload();
                if (button?.dataset.href) {
                    window.location.href = button.dataset.href;
                }
            });

            $$('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element: HTMLAnchorElement) => {
                if (element.closest(".editor-wrap")) {
                    return;
                }

                element.addEventListener("click", (event) => {
                    if (this.hasChanged()) {
                        event.preventDefault();

                        app.modals["changesModal"].onOpen((modal) => {
                            const continueCommand = $("[data-command=continue]", modal.element);
                            if (continueCommand) {
                                continueCommand.dataset.href = element.href;
                            }
                        });

                        app.modals["changesModal"].open();
                    }
                });
            });
        }
    }
}
