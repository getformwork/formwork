import { $ } from "../utils/selectors";

export class Navigation {
    constructor() {
        if ($(".sidebar-toggle")) {
            $(".sidebar-toggle").addEventListener("click", () => {
                if ($(".sidebar").classList.toggle("show")) {
                    if (!$(".sidebar-backdrop")) {
                        const backdrop = document.createElement("div");
                        backdrop.className = "sidebar-backdrop hide-from-s";
                        document.body.appendChild(backdrop);
                    }
                } else {
                    const backdrop = $(".sidebar-backdrop");
                    if (backdrop) {
                        backdrop.parentNode.removeChild(backdrop);
                    }
                }
            });
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
