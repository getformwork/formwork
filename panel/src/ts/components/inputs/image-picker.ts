import { $, $$ } from "../../utils/selectors";
import { app } from "../../app";
import { Request } from "../../utils/request";

export class ImagePicker {
    readonly element: HTMLInputElement;

    constructor(element: HTMLInputElement) {
        this.element = element;
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
        this.element.value = value;
    }

    update() {
        const parentElement = this.element.parentElement as HTMLElement;

        $(".image-picker-thumbnails", parentElement)?.remove();

        const emptyState = $(".image-picker-empty-state", parentElement) as HTMLElement;

        const selectImage = (thumbnail: HTMLElement) => {
            $$(".image-picker-thumbnail", parentElement).forEach((element) => {
                element.classList.remove("selected");
            });
            thumbnail.classList.add("selected");
            this.element.value = thumbnail.dataset.uri ?? "";
        };

        const pickImage = (thumbnail: HTMLElement) => {
            selectImage(thumbnail);
            const modal = this.element.closest(".modal") as HTMLElement;
            if (modal) {
                app.modals[modal.id].triggerCommand("pick-image");
            }
        };

        new Request(
            {
                method: "POST",
                url: this.element.dataset.src as string,
                data: { "csrf-token": app.config.csrfToken as string },
            },
            ({ data }: { data: Record<string, any> }) => {
                const images = Object.values(data).filter((file) => file.type === "image");

                if (images.length > 0) {
                    const container = document.createElement("div");
                    container.className = "image-picker-thumbnails";

                    for (const image of images) {
                        const thumbnail = document.createElement("div");
                        thumbnail.className = "image-picker-thumbnail";
                        thumbnail.style.backgroundImage = `url(${image.thumbnail})`;
                        thumbnail.dataset.uri = image.uri;
                        thumbnail.dataset.filename = image.filename;
                        thumbnail.addEventListener("click", () => selectImage(thumbnail));
                        thumbnail.addEventListener("dblclick", () => pickImage(thumbnail));
                        container.appendChild(thumbnail);
                    }

                    parentElement.appendChild(container);

                    emptyState.style.display = "none";
                } else {
                    emptyState.style.display = "block";
                }
            },
        );
    }
}
