import { schema } from "prosemirror-markdown";
import type { Mark } from "prosemirror-model";
import { Plugin } from "prosemirror-state";
import type { EditorView } from "prosemirror-view";
import { app } from "../../services/app";
import { debounce, throttle } from "../../utils/events";
import { $ } from "../../utils/selectors";
import { link as linkIcon, pencil, trash } from "../ui/icons";
import { Tooltip } from "../ui/tooltip";
import { getMarkRange, insertLink, removeLink } from "./markdown-commands";

/**
 * Make a relative link absolute
 */
function addBaseUri(text: string, baseUri: string) {
    return text.replace(/^\/?(?!https?:\/\/)(.+)/, `${baseUri}$1`);
}
/**
 * Shows a tooltip with the address of the link at the cursor and the buttons to edit or remove it
 */
class LinkTooltipView {
    editorView: EditorView;
    tooltip?: Tooltip;
    baseUri: string;

    constructor(view: EditorView, baseUri: string) {
        this.editorView = view;

        this.baseUri = baseUri;

        const resizeHandler = throttle(() => this.update(this.editorView), 100);

        const scrollHandler = debounce(() => this.destroy(), 100, true);

        window.addEventListener("resize", resizeHandler);
        window.addEventListener("scroll", scrollHandler);

        this.editorView.dom.addEventListener("scroll", scrollHandler);
        this.editorView.dom.addEventListener("blur", () => this.destroy());
    }

    /**
     * Show the tooltip if the cursor is in a link
     */
    update(view: EditorView) {
        const { from, to } = view.state.selection;

        const range = getMarkRange(view.state, schema.marks.link);

        if (!(range && from >= range.from && to <= range.to)) {
            this.destroy();
            return;
        }

        if (this.tooltip) {
            this.tooltip.remove();
        }

        const linkDom = this.findLinkElement(view, range.from + 1);

        if (linkDom) {
            this.tooltip = this.createTooltip(range.mark, linkDom);
            this.tooltip.show();
        }
    }

    /**
     * Get the element of the link that contains a position of the document
     */
    private findLinkElement(view: EditorView, position: number) {
        let element: HTMLElement | null = view.domAtPos(position).node as HTMLElement;

        while (element && element.tagName !== "A") {
            element = element.parentElement;
        }

        return element;
    }

    /**
     * Create the tooltip of a link, with its address and the buttons to edit and remove it
     */
    private createTooltip(link: Mark, linkDom: HTMLElement) {
        const t = (key: string, defaultValue: string) => app.translation.get(key, defaultValue);

        const tooltip = new Tooltip(
            `<div class="editor-link-tooltip truncate mr-4"><a href="${addBaseUri(link.attrs.href, this.baseUri)}" target="_blank">${link.attrs.href}</a></div>
                <div class="separator"></div>
                <button type="button" class="tooltip-button" data-command="edit-link">${t("panel.modal.action.edit", "Edit")}</button>
                <div class="separator"></div>
                <button type="button" class="tooltip-button" data-command="delete-link" aria-label="${t("panel.modal.action.delete", "Delete")}" data-tooltip="${t("panel.modal.action.delete", "Delete")}"></button>`,
            {
                referenceElement: linkDom,
                removeOnMouseout: false,
                delay: 0,
                zIndex: 18,
            },
        );

        const tooltipLink = $(".editor-link-tooltip", tooltip.element) as HTMLAnchorElement;
        const tooltipEditLink = $('[data-command="edit-link"]', tooltip.element) as HTMLButtonElement;
        const tooltipDeleteLink = $('[data-command="delete-link"]', tooltip.element) as HTMLButtonElement;

        tooltipLink.insertAdjacentHTML("afterbegin", linkIcon);
        tooltipEditLink.insertAdjacentHTML("afterbegin", pencil);
        tooltipDeleteLink.insertAdjacentHTML("afterbegin", trash);

        const { state, dispatch } = this.editorView;

        tooltipEditLink.addEventListener("click", () => insertLink(state, dispatch, this.editorView));
        tooltipDeleteLink.addEventListener("click", () => removeLink(state, dispatch, this.editorView));

        tooltip.element.addEventListener("mousedown", (event) => event.preventDefault());

        return tooltip;
    }

    /**
     * Hide the tooltip
     */
    destroy() {
        if (this.tooltip) {
            const tooltip = this.tooltip;
            window.setTimeout(() => tooltip.remove(), 100);
        }
        this.tooltip = undefined;
    }
}

/**
 * Get the plugin that shows the tooltip of the links
 *
 * @param baseUri Base URI used to resolve relative links
 */
export function linkTooltip(baseUri: string): Plugin {
    return new Plugin({
        view(editorView: EditorView) {
            return new LinkTooltipView(editorView, baseUri);
        },
    });
}
