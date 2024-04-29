import { $, $$ } from "../utils/selectors";

export class Files {
    constructor() {
        $$(".files-list").forEach((filesList) => {
            const toggle = $(".form-togglegroup", filesList);

            if (toggle) {
                const viewAs = window.localStorage.getItem("formwork.filesListViewAs");

                if (viewAs) {
                    $$("input", toggle).forEach((input: HTMLInputElement) => (input.checked = false));
                    ($(`input[value=${viewAs}]`, filesList) as HTMLInputElement).checked = true;
                    filesList.classList.toggle("is-thumbnails", viewAs === "thumbnails");
                }

                $$("input", toggle).forEach((input: HTMLInputElement) => {
                    input.addEventListener("input", () => {
                        filesList.classList.toggle("is-thumbnails", input.value === "thumbnails");
                        window.localStorage.setItem("formwork.filesListViewAs", input.value);
                    });
                });
            }

            $$("video.file-thumbnail", filesList).forEach((video: HTMLVideoElement) => {
                video.addEventListener("click", () => {
                    if (!video.controls) {
                        video.controls = true;
                    }
                });
            });
        });
    }
}
