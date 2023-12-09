import { $, $$ } from "../../utils/selectors";

export class ImagePicker {
    constructor(element) {
        const options = $$("option", element);
        const confirmCommand = $(".image-picker-confirm", element.parentNode.parentNode);
        const uploadCommand = $("[data-command=upload]", element.parentNode.parentNode);

        element.hidden = true;

        if (options.length > 0) {
            const container = document.createElement("div");
            container.className = "image-picker-thumbnails";

            for (const option of options) {
                const thumbnail = document.createElement("div");
                thumbnail.className = "image-picker-thumbnail";
                thumbnail.style.backgroundImage = `url(${option.value})`;
                thumbnail.dataset.uri = option.value;
                thumbnail.dataset.filename = option.text;
                thumbnail.addEventListener("click", handleThumbnailClick);
                thumbnail.addEventListener("dblclick", handleThumbnailDblclick);
                container.appendChild(thumbnail);
            }

            element.parentNode.insertBefore(container, element);
            $(".image-picker-empty-state").style.display = "none";
        }

        confirmCommand.addEventListener("click", function () {
            const selectedThumbnail = $(".image-picker-thumbnail.selected");
            const target = document.getElementById(this.dataset.target);
            if (selectedThumbnail && target) {
                target.value = selectedThumbnail.dataset.filename;
            }
        });

        uploadCommand.addEventListener("click", function () {
            document.getElementById(this.dataset.uploadTarget).click();
        });

        function handleThumbnailClick() {
            const target = document.getElementById($(".image-picker-confirm").dataset.target);
            if (target) {
                target.value = this.dataset.filename;
            }
            $$(".image-picker-thumbnail").forEach((element) => {
                element.classList.remove("selected");
            });
            this.classList.add("selected");
        }

        function handleThumbnailDblclick() {
            this.click();
            $(".image-picker-confirm").click();
        }
    }
}
