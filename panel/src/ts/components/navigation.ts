import { $ } from "../utils/selectors";

export class Navigation {
    constructor() {
        const toggle = $<HTMLElement>(".sidebar-toggle");
        const sidebar = $<HTMLElement>(".sidebar");

        if (toggle && sidebar) {
            sidebar.id ||= "panel-sidebar";
            toggle.setAttribute("aria-controls", sidebar.id);
            toggle.setAttribute("aria-expanded", "false");

            const setOpen = (open: boolean) => {
                sidebar.classList.toggle("show", open);
                toggle.setAttribute("aria-expanded", String(open));

                if (open) {
                    if (!$(".sidebar-backdrop")) {
                        const backdrop = document.createElement("div");
                        backdrop.className = "sidebar-backdrop hide-from-md";
                        document.body.appendChild(backdrop);
                    }
                } else {
                    const backdrop = $(".sidebar-backdrop");
                    if (backdrop) {
                        (backdrop.parentNode as ParentNode).removeChild(backdrop);
                    }
                }
            };

            toggle.addEventListener("click", () => setOpen(!sidebar.classList.contains("show")));

            document.addEventListener("keydown", (event) => {
                if (event.key === "Escape" && sidebar.classList.contains("show")) {
                    setOpen(false);
                    toggle.focus();
                }
            });
        }

        if ($("[data-command=save]")) {
            document.addEventListener("keydown", (event) => {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.key === "s") {
                        $("[data-command=save]")?.click();
                        event.preventDefault();
                    }
                }
            });
        }
    }
}
