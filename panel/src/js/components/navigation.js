import { $ } from "../utils/selectors";

export class Navigation {
    constructor() {
        if ($(".toggle-navigation")) {
            $(".toggle-navigation").addEventListener("click", () => $(".sidebar").classList.toggle("show"));
        }

        if ($("[data-command=save]")) {
            document.addEventListener("keydown", (event) => {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.key === "s") {
                        $("[data-command=save]").click();
                        event.preventDefault();
                    }
                }
            });
        }
    }
}
