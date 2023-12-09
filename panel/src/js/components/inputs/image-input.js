import { $ } from "../../utils/selectors";
import { app } from "../../app";

export class ImageInput {
    constructor(element) {
        element.addEventListener("click", () => {
            app.modals["imagesModal"].show(null, (modal) => {
                const selected = $(".image-picker-thumbnail.selected", modal);
                if (selected) {
                    selected.classList.remove("selected");
                }
                if (element.value) {
                    const thumbnail = $(`.image-picker-thumbnail[data-filename="${element.value}"]`, modal);
                    if (thumbnail) {
                        thumbnail.classList.add("selected");
                    }
                }
                $(".image-picker-confirm", modal).dataset.target = element.id;
            });
        });
    }
}
