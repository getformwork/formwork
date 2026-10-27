import * as icons from "../ui/icons";

/**
 * Create a toolbar button with an icon
 *
 * @param icon  Name of the icon
 * @param title Title and label of the button
 */
export function createToolbarButton(icon: keyof typeof icons, title: string) {
    const button = document.createElement("button");
    button.type = "button";
    button.className = "button toolbar-button";
    button.dataset.tooltip = title;
    button.ariaLabel = title;

    // biome-ignore lint/performance/noDynamicNamespaceImportAccess: Accessing the icon dynamically from the imported icons object is necessary for flexibility
    button.innerHTML = icons[icon] || "";

    return button;
}
