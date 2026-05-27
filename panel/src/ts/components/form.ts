import { $, $$ } from "../utils/selectors";
import { app } from "../app";
import { ArrayInput } from "./inputs/array-input";
import { ColorInput } from "./inputs/color-input";
import { DateInput } from "./inputs/date-input";
import { DurationInput } from "./inputs/duration-input";
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

    private associations: Record<string, (element: any) => void> = {
        ".editor-textarea": (element: HTMLTextAreaElement) => {
            import("./inputs/editor-input").then(({ EditorInput }) => {
                this.formInputs.push(new EditorInput(element));
            });
        },

        ".form-input-color": (element: HTMLInputElement) => this.formInputs.push(new ColorInput(element)),

        ".form-input-array": (element: HTMLFieldSetElement) => this.formInputs.push(new ArrayInput(element, this)),

        ".form-input-date": (element: HTMLInputElement) => this.formInputs.push(new DateInput(element, app.config.DateInput ?? {})),

        ".form-input-duration": (element: HTMLInputElement) => this.formInputs.push(new DurationInput(element, app.config.DurationInput ?? {})),

        ".form-input-slug": (element: HTMLInputElement) => this.formInputs.push(new SlugInput(element)),

        ".form-input-tags": (element: HTMLInputElement) => this.formInputs.push(new TagsInput(element, app.config.TagsInput ?? {})),

        ".form-togglegroup": (element: HTMLFieldSetElement) => this.formInputs.push(new TogglegroupInput(element)),

        ".image-picker": (element: HTMLInputElement) => this.formInputs.push(new ImagePicker(element)),

        "input[type=file]": (element: HTMLInputElement) => this.formInputs.push(new UploadInput(element, this)),

        "input[type=range]": (element: HTMLInputElement) => this.formInputs.push(new RangeInput(element)),

        ".form-select": (element: HTMLSelectElement) => this.formInputs.push(new SelectInput(element, app.config.SelectInput ?? {})),

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
                        input.disabled = !element.checked;
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

    get inputs(): Record<string, FormInput> {
        return Object.fromEntries(this.formInputs.map((input) => [input.name, input]));
    }

    private loadInputs(parent: HTMLElement = this.element) {
        for (const [selector, handler] of Object.entries(this.associations)) {
            $$(selector, parent).forEach((element: HTMLElement) => {
                handler(element);
            });
        }
    }

    private loadInput(element: HTMLElement) {
        for (const [selector, handler] of Object.entries(this.associations)) {
            if (element.matches(selector)) {
                handler(element);
            }
        }
    }

    hasChanged(checkFileInputs: boolean = true) {
        if (checkFileInputs) {
            for (const fileInput of $$<HTMLInputElement>("input[type=file]", this.element)) {
                if (fileInput.files?.length) {
                    return true;
                }
            }
        }

        return serializeForm(this.element) !== this.originalData;
    }

    duplicateInput(element: HTMLInputLike, targetElement: HTMLElement) {
        const wrap = element.closest(".form-input-wrap");
        const duplicated = wrap ? this.duplicateWrappedInput(element, wrap) : this.duplicateStandaloneInput(element);
        const { newNode, newInput } = duplicated;

        // Keep labels connected to the duplicated input by assigning a unique ID.
        const previousId = (element as HTMLElement).id;
        const newId = `${element.tagName.toLowerCase()}-${Math.random().toString(36).slice(2)}`;
        newInput.id = newId;

        if (wrap && previousId) {
            $$<HTMLLabelElement>(`label[for="${previousId}"]`).forEach((label) => {
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

    private duplicateWrappedInput(element: HTMLInputLike, wrap: Element) {
        const newNode = wrap.cloneNode() as HTMLElement;
        let newInput: HTMLInputLike | undefined;

        for (const child of wrap.children) {
            if (child === element) {
                newInput = this.cloneAndResetInput(element);
                newNode.appendChild(newInput);
                continue;
            }

            if (child.matches(`.form-input-action, .form-input-description, .form-input-icon`)) {
                newNode.appendChild(child.cloneNode(true));
            }
        }

        if (!newInput) {
            throw new Error("Could not replicate input: input element not found in wrapper.");
        }

        return { newNode, newInput };
    }

    private duplicateStandaloneInput(element: HTMLInputLike) {
        const newInput = this.cloneAndResetInput(element);
        return { newNode: newInput as HTMLElement, newInput };
    }

    private cloneAndResetInput(element: HTMLInputLike) {
        const newInput = element.cloneNode(true) as HTMLInputLike;
        if (newInput instanceof HTMLInputElement && (newInput.type === "checkbox" || newInput.type === "radio")) {
            newInput.checked = false;
        } else {
            newInput.value = "";
        }
        return newInput;
    }

    private openChangesModalForHref(href: string) {
        const changesModal = app.modals["changesModal"];

        changesModal.onOpen((modal) => {
            const continueCommand = $("[data-command=continue]", modal.element);
            if (continueCommand) {
                continueCommand.dataset.href = href;
            }
        });

        changesModal.open();
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

            $$<HTMLAnchorElement>('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])').forEach((element) => {
                if (element.closest(".editor-wrap")) {
                    return;
                }

                element.addEventListener("click", (event) => {
                    if (!this.hasChanged()) {
                        return;
                    }

                    event.preventDefault();
                    this.openChangesModalForHref(element.href);
                });
            });
        }
    }
}
