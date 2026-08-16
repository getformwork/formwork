import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { debounce } from "../../utils/events";
import { escapeRegExp } from "../../utils/validation";
import { markdown } from "../icons";

interface EditorView {
    view: { dom: HTMLElement; focus(): void };
    content: string;
    editable: boolean;
    destroy(): void;
}

function addBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(/(!\[.*\])\((?!https?:\/\/)([^)]+)\)/g, `$1(${baseUri}$2)`);
}

function removeBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(new RegExp(`(!\\[.*\\])\\(${escapeRegExp(baseUri)}([^)]+)\\)`, "g"), "$1($2)");
}

function getTextareaHeight(textarea: HTMLTextAreaElement): number {
    const clone = textarea.cloneNode() as HTMLTextAreaElement;
    clone.style.visibility = "hidden";
    clone.style.display = "";
    document.body.appendChild(clone);
    const height = clone.offsetHeight;
    document.body.removeChild(clone);
    return height;
}

interface EditorInputOptions {
    baseUri: string;
    height: number;
    spellcheck: boolean;
    inputEventHandler: (value: string) => void;
}

export class EditorInput {
    readonly element: HTMLTextAreaElement;

    readonly id: string;

    readonly options: EditorInputOptions;

    private container: HTMLElement | null;

    private editor: EditorView | undefined;

    private editorPromise: Promise<void>;

    constructor(textarea: HTMLTextAreaElement, options: Partial<EditorInputOptions> = {}) {
        this.element = textarea;
        this.id = textarea.id;

        const defaults: EditorInputOptions = {
            baseUri: textarea.dataset.baseUri ?? "",
            height: getTextareaHeight(textarea) ?? 200,
            spellcheck: true,
            inputEventHandler: (value: string) => {
                this.element.value = removeBaseUri(value, this.options.baseUri);
                debounce(
                    () => {
                        this.element.dispatchEvent(new Event("input", { bubbles: true }));
                        this.element.dispatchEvent(new Event("change", { bubbles: true }));
                    },
                    500,
                    true,
                )();
            },
        };

        this.options = { ...defaults, ...options };

        this.container = (textarea.parentNode as HTMLElement)?.classList.contains("editor-wrap") ? (textarea.parentNode as HTMLElement) : null;

        if (!this.container) {
            this.container = document.createElement("div");
            this.container.classList.add("editor-wrap");
            textarea.parentNode?.insertBefore(this.container, textarea);
            const toolbar = document.createElement("div");
            toolbar.classList.add("editor-toolbar");

            const toggleButton = document.createElement("button");
            toggleButton.type = "button";
            toggleButton.classList.add("button", "toolbar-button", "editor-toggle-markdown");
            toggleButton.dataset.command = "toggle-markdown";
            toggleButton.title = app.config.EditorInput.labels.toggleMarkdown;
            toggleButton.ariaLabel = app.config.EditorInput.labels.toggleMarkdown;
            toggleButton.disabled = this.element.disabled;
            toggleButton.innerHTML = markdown;
            toolbar.appendChild(toggleButton);

            this.container.appendChild(toolbar);
            this.container.appendChild(textarea);

            this.options.height -= toolbar.offsetHeight;
            this.element.style.height = `${this.options.height}px`;
        }

        const formName = this.element.form?.dataset.form;

        const key = formName ? `${formName}.${this.name}` : this.name;

        const mode = window.localStorage.getItem(`formwork.editorMode[${key}]`);

        const toggleButton = $("[data-command=toggle-markdown]", this.container) as HTMLButtonElement;

        if (mode === "code") {
            this.editorPromise = this.switchToCode();
            toggleButton.classList.add("is-active");
        } else {
            this.editorPromise = this.switchToMarkdown();
            toggleButton.classList.remove("is-active");
        }

        toggleButton.addEventListener("click", () => {
            toggleButton.disabled = true;
            if (toggleButton.classList.toggle("is-active")) {
                this.editorPromise = this.switchToCode();
                window.localStorage.setItem(`formwork.editorMode[${key}]`, "code");
            } else {
                this.editorPromise = this.switchToMarkdown();
                window.localStorage.setItem(`formwork.editorMode[${key}]`, "markdown");
            }
            this.editorPromise.then(() => {
                this.editor?.view.focus();
                toggleButton.disabled = false;
            });
        });

        $(`label[for="${textarea.id}"]`)?.addEventListener("click", () => {
            this.editorPromise.then(() => this.editor?.view.focus());
        });
    }

    async switchToMarkdown() {
        if (!this.container) {
            return;
        }
        this.editor?.destroy();
        this.element.classList.add("is-loading");
        this.element.style.display = "";
        const { MarkdownView } = await import("./editor/markdown/view");
        this.editor = new MarkdownView(this.name, this.container, addBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler, {
            editable: !(this.element.disabled || this.element.readOnly),
            placeholder: this.element.placeholder,
            attributes: {
                spellcheck: this.options.spellcheck ? "true" : "false",
            },
        });
        this.element.style.display = "none";
        this.element.classList.remove("is-loading");
        this.editor.view.dom.style.height = `${this.options.height}px`;
    }

    async switchToCode() {
        if (!this.container) {
            return;
        }
        this.editor?.destroy();
        this.element.classList.add("is-loading");
        this.element.style.display = "";
        const { CodeView } = await import("./editor/code/view");
        this.editor = new CodeView(this.container, removeBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler, {
            editable: !(this.element.disabled || this.element.readOnly),
            placeholder: this.element.placeholder,
        });
        this.element.style.display = "none";
        this.element.classList.remove("is-loading");
        this.editor.view.dom.style.height = `${this.options.height}px`;
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value(): string {
        return this.editor?.content ?? this.element.value;
    }

    get disabled(): boolean {
        return this.editor ? !this.editor.editable : this.element.disabled;
    }

    set disabled(value: boolean) {
        this.element.disabled = value;
        this.editorPromise.then(() => {
            if (this.editor) {
                this.editor.editable = !value;
            }
        });
        const toggleButton = $("[data-command=toggle-markdown]", this.container!) as HTMLButtonElement;
        toggleButton.disabled = value;
    }

    set value(value: string) {
        this.element.value = value;
        this.editorPromise.then(() => {
            if (this.editor) {
                this.editor.content = value;
            }
        });
    }
}
