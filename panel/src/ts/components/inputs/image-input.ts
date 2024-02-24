import { $ } from "../../utils/selectors";
import { app } from "../../app";

export class ImageInput {
    constructor(element: HTMLInputElement) {
        element.addEventListener("click", () => {
            app.modals["imagesModal"].show(undefined, (modal) => {
                const selected = $(".image-picker-thumbnail.selected", modal.element);
                if (selected) {
                    selected.classList.remove("selected");
                }
                if (element.value) {
                    const thumbnail = $(`.image-picker-thumbnail[data-filename="${element.value}"]`, modal.element);
                    if (thumbnail) {
                        thumbnail.classList.add("selected");
                    }
                }
                const confirm = $(".image-picker-confirm", modal.element) as HTMLElement;
                confirm.dataset.target = element.id;
                confirm.addEventListener("click", () => modal.hide());
            });
        });
    }
}
