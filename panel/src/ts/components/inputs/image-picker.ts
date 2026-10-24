import { app } from "../../services/app";
import { post } from "../../services/request";
import { $, $$ } from "../../utils/selectors";
import { Input } from "./input";

/**
 * Describes a file of the page, as listed in the images modal
 */
interface ImagePickerFile {
    type?: string;
    thumbnail?: string;
    uri?: string;
    filename?: string;
}

/**
 * Provides an input holding the URI of an image chosen among the ones of a page
 */
export class ImagePicker extends Input<HTMLInputElement> {
    /**
     * Load the images and show their thumbnails
     *
     * A click selects an image, a double click picks it
     */
    update() {
        const parentElement = this.element.parentElement as HTMLElement;
        const emptyState = $(".image-picker-empty-state", parentElement) as HTMLElement;

        $(".image-picker-thumbnails", parentElement)?.remove();
        emptyState.style.display = "";

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

        post<Record<string, ImagePickerFile>>(this.element.dataset.src as string, {}, (response) => {
            const images = Object.values(response.data ?? {}).filter((file) => file.type === "image");

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
            }
        });
    }
}
