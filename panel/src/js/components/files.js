import { $, $$ } from "../utils/selectors";

export class Files {
    constructor() {
        $$(".files-list").forEach((filesList) => {
            const toggle = $(".input-togglegroup", filesList);

            const viewAs = window.localStorage.getItem("formwork.filesListViewAs");

            if (viewAs) {
                $$("input", toggle).forEach((input) => (input.checked = false));
                $(`input[value=${viewAs}]`, filesList).checked = true;
                filesList.classList.toggle("is-thumbnails", viewAs === "thumbnails");
            }

            $$("input", toggle).forEach((input) => {
                input.addEventListener("input", () => {
                    filesList.classList.toggle("is-thumbnails", input.value === "thumbnails");
                    window.localStorage.setItem("formwork.filesListViewAs", input.value);
                });
            });
        });
    }
}
