import { $, $$ } from "../../utils/selectors";

export class ImagePicker {
    constructor(element: HTMLSelectElement) {
        const options = $$("option", element);
        const confirmCommand = $(".image-picker-confirm", (element.parentNode as ParentNode).parentNode ?? document);
        const uploadCommand = $("[data-command=upload]", (element.parentNode as ParentNode).parentNode ?? document);

        element.hidden = true;

        if (options.length > 0) {
            const container = document.createElement("div");
            container.className = "image-picker-thumbnails";

            for (const option of Array.from(options) as HTMLOptionElement[]) {
                const thumbnail = document.createElement("div");
                thumbnail.className = "image-picker-thumbnail";
                thumbnail.style.backgroundImage = `url(${option.value})`;
                thumbnail.dataset.uri = option.value;
                thumbnail.dataset.filename = option.text;
                thumbnail.addEventListener("click", handleThumbnailClick);
                thumbnail.addEventListener("dblclick", handleThumbnailDblclick);
                container.appendChild(thumbnail);
            }

            (element.parentNode as ParentNode).insertBefore(container, element);
            ($(".image-picker-empty-state") as HTMLElement).style.display = "none";
        }

        confirmCommand?.addEventListener("click", function () {
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

        uploadCommand?.addEventListener("click", function () {
            const uploadTargetId = this.dataset.uploadTarget;
            if (uploadTargetId) {
                const uploadTarget = document.getElementById(uploadTargetId);
                uploadTarget && uploadTarget.click();
            }
        });

        function handleThumbnailClick(this: HTMLElement) {
            const targetId = ($(".image-picker-confirm") as HTMLElement).dataset.target;
            if (targetId) {
                const target = document.getElementById(targetId) as HTMLSelectElement;
                if (target) {
                    target.value = this.dataset.filename as string;
                }
                $$(".image-picker-thumbnail").forEach((element) => {
                    element.classList.remove("selected");
                });
                this.classList.add("selected");
            }
        }

        function handleThumbnailDblclick(this: HTMLElement) {
            this.click();
            $(".image-picker-confirm")?.click();
        }
    }
}
