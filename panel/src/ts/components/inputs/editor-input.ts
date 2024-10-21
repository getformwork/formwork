import { $ } from "../../utils/selectors";
import { CodeView } from "./editor/code/view";
import { debounce } from "../../utils/events";
import { escapeRegExp } from "../../utils/validation";
import { MarkdownView } from "./editor/markdown/view";

function addBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(/(!\[.*\])\((?!https?:\/\/)([^)]+)\)/g, `$1(${baseUri}$2)`);
}

function removeBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(new RegExp(`(!\\[.*\\])\\(${escapeRegExp(baseUri)}([^)]+)\\)`, "g"), "$1($2)");
}

export class EditorInput {
    constructor(textarea: HTMLTextAreaElement) {
        const editorWrap = (textarea.parentNode as HTMLElement).classList.contains("editor-wrap") ? (textarea.parentNode as HTMLElement) : null;

        if (editorWrap) {
            const textareaHeight = textarea.offsetHeight;
            const baseUri = textarea.dataset.baseUri ?? "";

            const attributes = {
                spellcheck: textarea.spellcheck ? "true" : "false",
            };

            textarea.style.display = "none";

            const inputEventHandler = debounce((content: string) => {
                textarea.value = removeBaseUri(content, baseUri);
                textarea.dispatchEvent(new Event("input", { bubbles: true }));
                textarea.dispatchEvent(new Event("change", { bubbles: true }));
            }, 500);

            let editor: MarkdownView | CodeView = new MarkdownView(editorWrap, addBaseUri(textarea.value, baseUri), inputEventHandler, attributes, baseUri);
            editor.view.dom.style.height = `${textareaHeight}px`;

            const codeSwitch = $("[data-command=toggle-markdown]", editorWrap) as HTMLButtonElement;
            codeSwitch.addEventListener("click", () => {
                codeSwitch.classList.toggle("is-active");
                if (codeSwitch.classList.contains("is-active")) {
                    editor.destroy();
                    editor = new CodeView(editorWrap, removeBaseUri(editor.content, baseUri), inputEventHandler);
                    editor.view.dom.style.height = `${textareaHeight}px`;
                } else {
                    editor.destroy();
                    editor = new MarkdownView(editorWrap, addBaseUri(editor.content, baseUri), inputEventHandler, attributes, baseUri);
                    editor.view.dom.style.height = `${textareaHeight}px`;
                }
                codeSwitch.blur();
            });
        }
    }
}
