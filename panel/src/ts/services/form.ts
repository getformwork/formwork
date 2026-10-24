import { signal } from "@preact/signals-core";
import { ArrayInput } from "../components/inputs/array-input";
import { ColorInput } from "../components/inputs/color-input";
import { DateInput } from "../components/inputs/date-input";
import { DurationInput } from "../components/inputs/duration-input";
import { EditorInput } from "../components/inputs/editor-input";
import { ImagePicker } from "../components/inputs/image-picker";
import { dispatchChange, Input } from "../components/inputs/input";
import { RangeInput } from "../components/inputs/range-input";
import { SelectInput } from "../components/inputs/select-input";
import { SlugInput } from "../components/inputs/slug-input";
import { TagsInput } from "../components/inputs/tags-input";
import { TogglegroupInput } from "../components/inputs/togglegroup-input";
import { UploadInput } from "../components/inputs/upload-input";
import { debounce } from "../utils/events";
import { serializeForm } from "../utils/forms";
import { $, $$ } from "../utils/selectors";
import { app } from "./app";
import type { Modal } from "./modal";

/**
 * Holds the options of a form
 */
interface FormOptions {
    preventUnloadOnChanges?: boolean;
}

/**
 * Represents the native element of an input
 */
interface InputElement extends HTMLElement {
    name: string;
}

/**
 * Represents an input of a form
 */
interface FormInput {
    element: InputElement;
    name: string;
    value: string;
}

/**
 * Represents the native elements holding the value of a field
 */
export type HTMLInputLike = HTMLInputElement | HTMLSelectElement | HTMLTextAreaElement;

/**
 * Manages a form of the page with its inputs
 *
 * The form is found by its `data-form` attribute and available as `app.forms[name]`
 */
export class Form {
    /**
     * Inputs of the form
     */
    readonly formInputs: FormInput[] = [];

    /**
     * Serialization of the form when it was loaded
     */
    originalData: string;

    /**
     * Whether the form differs from its initial state, updated shortly after the last input event
     */
    readonly changed = signal(false);

    element: HTMLFormElement;
    options: FormOptions = {
        preventUnloadOnChanges: true,
    };

    /**
     * Inputs replacing native elements, by selector
     */
    private readonly inputFactories: { [selector: string]: (element: HTMLElement) => FormInput } = {
        ".editor-textarea": (element) => new EditorInput(element as HTMLTextAreaElement),
        ".form-input-color": (element) => new ColorInput(element as HTMLInputElement),
        ".form-input-array": (element) => new ArrayInput(element as HTMLFieldSetElement, this),
        ".form-input-date": (element) => new DateInput(element as HTMLInputElement, app.config.DateInput),
        ".form-input-duration": (element) => new DurationInput(element as HTMLInputElement, app.config.DurationInput),
        ".form-input-slug": (element) => new SlugInput(element as HTMLInputElement),
        ".form-input-tags": (element) => new TagsInput(element as HTMLInputElement, app.config.TagsInput),
        ".form-togglegroup": (element) => new TogglegroupInput(element as HTMLFieldSetElement),
        ".image-picker": (element) => new ImagePicker(element as HTMLInputElement),
        "input[type=file]": (element) => new UploadInput(element as HTMLInputElement, this),
        "input[type=range]": (element) => new RangeInput(element as HTMLInputElement),
        ".form-select": (element) => new SelectInput(element as HTMLSelectElement, app.config.SelectInput),
    };

    /**
     * Behaviors of elements, by selector
     */
    private readonly behaviors: { [selector: string]: (element: HTMLElement) => void } = {
        ".form-input-action[data-reset]": (element) => {
            const button = element as HTMLButtonElement;
            const targetId = button.dataset.reset;
            if (targetId) {
                button.addEventListener("click", () => {
                    const target = document.getElementById(targetId) as HTMLInputElement;
                    target.value = "";
                    dispatchChange(target);
                });
            }
        },

        "input[data-enable]": (element) => {
            const checkbox = element as HTMLInputElement;

            checkbox.addEventListener("change", () => {
                for (const name of (checkbox.dataset.enable ?? "").split(",")) {
                    const input = $<HTMLInputElement>(`input[name="${name}"]`);

                    if (input) {
                        input.disabled = !checkbox.checked;
                    }
                }
            });
        },
    };

    /**
     * Load the inputs of the form and detect its changes
     */
    constructor(form: HTMLFormElement, options: Partial<FormOptions> = {}) {
        this.element = form;

        this.loadInputs();

        // Serialize after inputs are loaded
        this.originalData = serializeForm(form);

        form.addEventListener(
            "input",
            debounce(() => {
                this.changed.value = this.hasChanged();
            }, 500),
        );

        this.options = { ...this.options, ...options };

        if (this.options.preventUnloadOnChanges) {
            this.preventUnloadOnChanges();
        }
    }

    /**
     * Get the inputs of the form by name
     *
     * If more inputs have the same name the last one is returned
     */
    get inputs(): { [name: string]: FormInput } {
        const inputs: { [name: string]: FormInput } = {};
        for (const input of this.formInputs) {
            inputs[input.name] = input;
        }
        return inputs;
    }

    /**
     * Load inputs and behaviors of the elements found for each selector
     */
    private load(find: (selector: string) => Iterable<HTMLElement>) {
        for (const selector in this.inputFactories) {
            for (const element of find(selector)) {
                this.formInputs.push(this.inputFactories[selector](element));
            }
        }

        for (const selector in this.behaviors) {
            for (const element of find(selector)) {
                this.behaviors[selector](element);
            }
        }

        // The named native elements that no input has replaced
        for (const element of find("input, select, textarea")) {
            const inputElement = element as HTMLInputLike;
            if (inputElement.name && !this.formInputs.some((input) => input.element === inputElement)) {
                this.formInputs.push(new Input(inputElement));
            }
        }
    }

    /**
     * Load inputs and behaviors of the elements inside a parent
     */
    private loadInputs(parent: HTMLElement = this.element) {
        this.load((selector) => $$(selector, parent));
    }

    /**
     * Load input and behaviors of a single element
     */
    private loadInput(element: HTMLElement) {
        this.load((selector) => (element.matches(selector) ? [element] : []));
    }

    /**
     * Return whether the form has changed since it was loaded
     *
     * @param checkFileInputs Whether file inputs with selected files are considered changes
     */
    hasChanged(checkFileInputs: boolean = true) {
        return (checkFileInputs && this.hasSelectedFiles()) || serializeForm(this.element) !== this.originalData;
    }

    /**
     * Return whether any file input has selected files
     */
    private hasSelectedFiles() {
        return Array.from($$<HTMLInputElement>("input[type=file]", this.element)).some((input) => input.files && input.files.length > 0);
    }

    /**
     * Copy an input at the end of a target element, with a new id and an empty value
     *
     * @param element       Input to copy
     * @param targetElement Element the copy is appended to
     */
    duplicateInput(element: HTMLInputLike, targetElement: HTMLElement) {
        const wrap = element.closest<HTMLElement>(".form-input-wrap");

        const { node, input } = wrap ? this.cloneWithWrap(wrap, element) : this.cloneAlone(element);

        // Generate a new unique ID for the duplicated input
        const previousId = element.id;
        const newId = `${element.tagName.toLowerCase()}-${Math.random().toString(36).slice(2)}`;
        input.id = newId;

        if (wrap && previousId) {
            $$<HTMLLabelElement>(`label[for="${previousId}"]`).forEach((label) => {
                label.htmlFor = newId;
            });
        }

        targetElement.appendChild(node);

        if (wrap) {
            this.loadInputs(node);
        } else {
            this.loadInput(input);
        }
    }

    /**
     * Copy an input with an empty value
     */
    private cloneEmpty(element: HTMLInputLike) {
        const copy = element.cloneNode(true) as HTMLInputLike;

        if (copy instanceof HTMLInputElement && (copy.type === "checkbox" || copy.type === "radio")) {
            copy.checked = false;
        } else {
            copy.value = "";
        }

        return copy;
    }

    /**
     * Copy an input that is not in a wrapper
     */
    private cloneAlone(element: HTMLInputLike) {
        const input = this.cloneEmpty(element);

        return { node: input, input };
    }

    /**
     * Copy the wrapper of an input with the input, its actions, description and icon
     */
    private cloneWithWrap(wrap: HTMLElement, element: HTMLInputLike) {
        const node = wrap.cloneNode() as HTMLElement;

        let input: HTMLInputLike | undefined;

        for (const child of Array.from(wrap.children)) {
            if (child === element) {
                input = this.cloneEmpty(element);
                node.appendChild(input);
            } else if (child.matches(".form-input-action, .form-input-description, .form-input-icon")) {
                node.appendChild(child.cloneNode(true));
            }
        }

        if (input === undefined) {
            throw new Error("Could not replicate input: input element not found in wrapper.");
        }

        return { node, input };
    }

    /**
     * Ask for confirmation before leaving the page if the form has unsaved changes
     */
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

        const changesModal = app.modals.changesModal;

        if (changesModal) {
            this.confirmLinks(changesModal, removeBeforeUnload);
        }
    }

    /**
     * Ask for confirmation with a modal when a link of the page is followed
     *
     * @param modal        Modal asking for confirmation
     * @param allowLeaving Function called when the user confirms, before the link is followed
     */
    private confirmLinks(modal: Modal, allowLeaving: () => void) {
        let nextHref: string | undefined;

        modal.onCommand("continue", (_, button) => {
            allowLeaving();

            if (button?.dataset.href) {
                window.location.href = button.dataset.href;
            }
        });

        modal.onOpen((openedModal) => {
            const continueCommand = $("[data-command=continue]", openedModal.element);

            if (continueCommand && nextHref) {
                continueCommand.dataset.href = nextHref;
            }
        });

        const links = Array.from($$<HTMLAnchorElement>('a[href]:not([href^="#"]):not([target="_blank"]):not([target^="formwork-"])'));

        links
            .filter((link) => !link.closest(".editor-wrap"))
            .forEach((link) => {
                link.addEventListener("click", (event) => {
                    if (this.hasChanged()) {
                        event.preventDefault();

                        nextHref = link.href;
                        modal.open();
                    }
                });
            });
    }
}
