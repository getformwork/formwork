import { effect, signal } from "@preact/signals-core";
import { $ } from "../../utils/selectors";

/**
 * Handles the sidebar toggle for small screens and the save shortcut
 */
export class Navigation {
    /**
     * Initialize the sidebar and the save shortcut
     */
    constructor() {
        this.initSidebar();
        this.initSaveShortcut();
    }

    /**
     * Open and close the sidebar with its toggle, showing a backdrop while it is open
     */
    private initSidebar() {
        const toggle = $<HTMLElement>(".sidebar-toggle");
        const sidebar = $<HTMLElement>(".sidebar");

        if (!toggle || !sidebar) {
            return;
        }

        sidebar.id ||= "panel-sidebar";
        toggle.setAttribute("aria-controls", sidebar.id);

        const isOpen = signal(sidebar.classList.contains("show"));

        effect(() => {
            sidebar.classList.toggle("show", isOpen.value);
            toggle.setAttribute("aria-expanded", String(isOpen.value));

            if (isOpen.value) {
                this.showBackdrop();
            } else {
                this.hideBackdrop();
            }
        });

        toggle.addEventListener("click", () => {
            isOpen.value = !isOpen.value;
        });

        document.addEventListener("keydown", (event) => {
            if (event.key === "Escape" && isOpen.value) {
                isOpen.value = false;
                toggle.focus();
            }
        });
    }

    /**
     * Add the backdrop behind the sidebar, if missing
     */
    private showBackdrop() {
        if (!$(".sidebar-backdrop")) {
            const backdrop = document.createElement("div");
            backdrop.className = "sidebar-backdrop hide-from-md";
            document.body.appendChild(backdrop);
        }
    }

    /**
     * Remove the backdrop behind the sidebar
     */
    private hideBackdrop() {
        $(".sidebar-backdrop")?.remove();
    }

    /**
     * Click the save command with Ctrl/Cmd+S, if the page has one
     */
    private initSaveShortcut() {
        if (!$("[data-command=save]")) {
            return;
        }

        document.addEventListener("keydown", (event) => {
            if (!event.altKey && (event.ctrlKey || event.metaKey) && event.key === "s") {
                $("[data-command=save]")?.click();
                event.preventDefault();
            }
        });
    }
}
