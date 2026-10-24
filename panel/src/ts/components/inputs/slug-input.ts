import { $ } from "../../utils/selectors";
import { makeSlug, validateSlug } from "../../utils/validation";
import { Input } from "./input";

/**
 * Provides a text input whose value is always a valid slug
 *
 * The slug can be generated from another field (`data-source`), automatically (`data-auto-update`)
 * or with a button (`data-generate-slug`)
 */
export class SlugInput extends Input<HTMLInputElement> {
    constructor(element: HTMLInputElement) {
        super(element);

        this.initInput();
    }

    // The getter is defined again because a class defining only the setter would lose it
    get value() {
        return this.element.value;
    }

    /**
     * Set the value, making it a valid slug
     */
    set value(value: string) {
        this.element.value = validateSlug(value);
    }

    /**
     * Generate the slug from the source field and clean the value while typing
     */
    private initInput() {
        const source = $<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>(`[id="${this.element.dataset.source}"]`);
        const autoUpdate = "autoUpdate" in this.element.dataset && this.element.dataset.autoUpdate === "true";

        if (source) {
            const generateSlug = () => {
                if (this.element.disabled || this.element.readOnly) {
                    return;
                }
                this.element.value = makeSlug(source.value);
            };

            if (autoUpdate) {
                source.addEventListener("input", generateSlug);
                this.element.value = makeSlug(source.value);
            } else {
                const generateButton = $(`[data-generate-slug="${this.element.id}"]`);
                if (generateButton) {
                    generateButton.addEventListener("click", generateSlug);
                }
            }
        }

        const handleSlugChange = (event: Event) => {
            const target = event.target as HTMLInputElement;
            target.value = validateSlug(target.value);
        };

        this.element.addEventListener("keyup", handleSlugChange);
        this.element.addEventListener("blur", handleSlugChange);
    }
}
