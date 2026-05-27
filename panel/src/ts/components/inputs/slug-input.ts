import { makeSlug, validateSlug } from "../../utils/validation";
import { $ } from "../../utils/selectors";

export class SlugInput {
    readonly element: HTMLInputElement;

    constructor(element: HTMLInputElement) {
        this.element = element;

        this.initInput();
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value() {
        return this.element.value;
    }

    set value(value: string) {
        this.element.value = validateSlug(value);
    }

    private initInput() {
        const source = $<HTMLInputElement>(`[id="${this.element.dataset.source}"]`);
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
                $(`[data-generate-slug="${this.element.id}"]`)?.addEventListener("click", generateSlug);
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
