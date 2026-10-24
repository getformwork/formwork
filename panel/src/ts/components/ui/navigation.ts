import { effect, signal } from "@preact/signals-core";
import { $, $$ } from "../../utils/selectors";

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
     *
     * While the sidebar is open the rest of the page is inert, so that the focus cannot reach what
     * the backdrop covers. The sidebar is closed with Escape, by clicking the backdrop or when the
     * window grows enough to show it always, which does not need the toggle
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

            $$(".panel-header, .panel-main").forEach((element) => {
                element.inert = isOpen.value;
            });

            if (isOpen.value) {
                this.showBackdrop(() => {
                    isOpen.value = false;
                });
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

        window.addEventListener("resize", () => {
            // The toggle is hidden when the sidebar is always visible
            if (isOpen.value && toggle.getClientRects().length === 0) {
                isOpen.value = false;
            }
        });
    }

    /**
     * Add the backdrop behind the sidebar, if missing
     *
     * @param onClick Function called when the backdrop is clicked
     */
    private showBackdrop(onClick: () => void) {
        if (!$(".sidebar-backdrop")) {
            const backdrop = document.createElement("div");
            backdrop.className = "sidebar-backdrop hide-from-md";
            backdrop.addEventListener("click", onClick);
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
