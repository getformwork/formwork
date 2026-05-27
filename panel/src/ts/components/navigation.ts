import { $ } from "../utils/selectors";

export class Navigation {
    constructor() {
        const sidebarToggle = $(".sidebar-toggle");
        if (sidebarToggle) {
            sidebarToggle.addEventListener("click", () => {
                if ($(".sidebar")?.classList.toggle("show")) {
                    if (!$(".sidebar-backdrop")) {
                        const backdrop = document.createElement("div");
                        backdrop.className = "sidebar-backdrop hide-from-md";
                        document.body.appendChild(backdrop);
                    }
                } else {
                    $(".sidebar-backdrop")?.remove();
                }
            });
        }

        const saveCommand = $("[data-command=save]");
        if (saveCommand) {
            document.addEventListener("keydown", (event) => {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.key === "s") {
                        saveCommand.click();
                        event.preventDefault();
                    }
                }
            });
        }
    }
}
