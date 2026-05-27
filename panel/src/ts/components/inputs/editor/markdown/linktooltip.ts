import { debounce, throttle } from "../../../../utils/events";
import { getMarkRange, insertLink, removeLink } from "./commands";
import { link as linkIcon, pencil, trash } from "../../../icons";
import { $ } from "../../../../utils/selectors";
import { app } from "../../../../app";
import type { EditorView } from "prosemirror-view";
import type { Mark } from "prosemirror-model";
import { Plugin } from "prosemirror-state";
import { schema } from "prosemirror-markdown";
import { Tooltip } from "../../../tooltip";

function addBaseUri(text: string, baseUri: string) {
    return text.replace(/^\/?(?!https?:\/\/)(.+)/, `${baseUri}$1`);
}
class LinkTooltipView {
    editorView: EditorView;
    tooltip?: Tooltip;
    currentLink?: Mark;
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

    update(view: EditorView) {
        const state = view.state;

        const { from, to } = state.selection;

        const range = getMarkRange(state, schema.marks.link);
        if (!(range && from >= range.from && to <= range.to)) {
            this.destroy();
            return;
        }

        const link = range.mark;

        if (link) {
            if (this.tooltip) {
                this.tooltip.remove();
            }

            const labels = app.config.EditorInput?.labels;
            const domAtPos = view.domAtPos(range.from + 1);

            let linkDom: HTMLElement | null = domAtPos.node as HTMLElement;

            while (linkDom && linkDom.tagName !== "A") {
                linkDom = linkDom.parentElement;
            }

            if (!linkDom) {
                return;
            }

            this.tooltip = new Tooltip(
                `<div class="editor-link-tooltip truncate mr-4"><a href="${addBaseUri(link.attrs.href, this.baseUri)}" target="_blank">${link.attrs.href}</a></div>
                <div class="separator"></div>
                <button type="button" class="tooltip-button" data-command="edit-link">${labels?.edit ?? ""}</button>
                <div class="separator"></div>
                <button type="button" class="tooltip-button" data-command="delete-link" aria-label="${labels?.delete ?? ""}" data-tooltip="${labels?.delete ?? ""}"></button>`,
                {
                    referenceElement: linkDom,
                    removeOnMouseout: false,
                    delay: 0,
                    zIndex: 18,
                },
            );

            const tooltipLink = $(".editor-link-tooltip", this.tooltip.element) as HTMLAnchorElement;
            const tooltipEditLink = $('[data-command="edit-link"]', this.tooltip.element) as HTMLButtonElement;
            const tooltipDeleteLink = $('[data-command="delete-link"]', this.tooltip.element) as HTMLButtonElement;

            tooltipLink.insertAdjacentHTML("afterbegin", linkIcon);
            tooltipEditLink.insertAdjacentHTML("afterbegin", pencil);
            tooltipDeleteLink.insertAdjacentHTML("afterbegin", trash);

            const { state, dispatch } = this.editorView;

            tooltipEditLink.addEventListener("click", () => insertLink(state, dispatch, this.editorView));
            tooltipDeleteLink.addEventListener("click", () => removeLink(state, dispatch, this.editorView));

            this.tooltip.element.addEventListener("mousedown", (event) => event.preventDefault());

            this.tooltip.show();
        }
    }

    destroy() {
        if (this.tooltip) {
            const tooltip = this.tooltip;
            window.setTimeout(() => tooltip.remove(), 100);
        }
        this.tooltip = undefined;
    }
}

export function linkTooltip(baseUri: string): Plugin {
    return new Plugin({
        view(editorView: EditorView) {
            return new LinkTooltipView(editorView, baseUri);
        },
    });
}
