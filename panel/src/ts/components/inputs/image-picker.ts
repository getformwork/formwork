import { $, $$ } from "../../utils/selectors";

export class ImagePicker {
    constructor(element: HTMLSelectElement) {
        const options = $$("option", element);
        const pickCommand = $("[data-command=pick-image]", (element.parentNode as ParentNode).parentNode ?? document);

        element.hidden = true;

        if (options.length > 0) {
            const container = document.createElement("div");
            container.className = "image-picker-thumbnails";

            for (const option of Array.from(options) as HTMLOptionElement[]) {
                const thumbnail = document.createElement("div");
                thumbnail.className = "image-picker-thumbnail";
                thumbnail.style.backgroundImage = `url(${option.dataset.thumbnail ?? option.value})`;
                thumbnail.dataset.uri = option.value;
                thumbnail.dataset.filename = option.text;
                thumbnail.addEventListener("click", handleThumbnailClick);
                thumbnail.addEventListener("dblclick", handleThumbnailDblclick);
                container.appendChild(thumbnail);
            }

            (element.parentNode as ParentNode).insertBefore(container, element);
            ($(".image-picker-empty-state") as HTMLElement).style.display = "none";
        }

        pickCommand?.addEventListener("click", function () {
            const selectedThumbnail = $(".image-picker-thumbnail.selected");
            const targetId = this.dataset.target;
            if (selectedThumbnail && targetId) {
                const target = document.getElementById(targetId) as HTMLSelectElement;
                const selectedThumbnailFilename = selectedThumbnail.dataset.filename;
                if (target && selectedThumbnailFilename) {
                    target.value = selectedThumbnailFilename;
                }
            }
        });

        function handleThumbnailClick(this: HTMLElement) {
            $$(".image-picker-thumbnail").forEach((element) => {
                element.classList.remove("selected");
            });
            this.classList.add("selected");
            const targetId = ($("[data-command=pick-image]") as HTMLElement).dataset.target;
            if (targetId) {
                const target = document.getElementById(targetId) as HTMLSelectElement;
                if (target) {
                    target.value = this.dataset.filename as string;
                }
            }
        }

        function handleThumbnailDblclick(this: HTMLElement) {
            this.click();
            $("[data-command=pick-image]")?.click();
        }
    }
}
