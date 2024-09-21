import { $, $$ } from "../utils/selectors";

export class Files {
    constructor() {
        $$(".files-list").forEach((filesList) => {
            const toggle = $(".form-togglegroup.files-list-view-as", filesList);

            if (toggle) {
                const fieldName = toggle.dataset.fieldName;
                const viewAs = window.localStorage.getItem(`formwork.filesListViewAs[${fieldName}]`);

                if (viewAs) {
                    $$("input", toggle).forEach((input: HTMLInputElement) => (input.checked = false));
                    ($(`input[value=${viewAs}]`, filesList) as HTMLInputElement).checked = true;
                    filesList.classList.toggle("is-thumbnails", viewAs === "thumbnails");
                }

                $$("input", toggle).forEach((input: HTMLInputElement) => {
                    input.addEventListener("input", () => {
                        filesList.classList.toggle("is-thumbnails", input.value === "thumbnails");
                        window.localStorage.setItem(`formwork.filesListViewAs[${fieldName}]`, input.value);
                    });
                });
            }

            $$(".files-item", filesList).forEach((item: HTMLElement) => {
                item.addEventListener("click", (event) => {
                    if (!(event.target as HTMLElement).closest(".dropdown") && typeof item.dataset.href === "string") {
                        location.href = item.dataset.href;
                    }
                });
            });
        });
    }
}
