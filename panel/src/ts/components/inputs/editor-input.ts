import { effect, signal } from "@preact/signals-core";
import { app } from "../../services/app";
import { debounce } from "../../utils/events";
import { $ } from "../../utils/selectors";
import { initToolbar } from "../../utils/toolbar";
import { escapeRegExp } from "../../utils/validation";
import { markdown } from "../ui/icons";
import { InputBase } from "./input";

/**
 * Describes the editor view used by the input
 */
interface EditorView {
    view: { dom: HTMLElement; focus(): void };
    content: string;
    editable: boolean;
    destroy(): void;
}

/**
 * Make the relative image paths of a Markdown text absolute
 *
 * @param baseUri Base URI prepended to the relative paths
 */
function addBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(/(!\[.*\])\((?!https?:\/\/)([^)]+)\)/g, `$1(${baseUri}$2)`);
}

/**
 * Make the image paths of a Markdown text relative
 *
 * @param baseUri Base URI removed from the paths
 */
function removeBaseUri(markdown: string, baseUri: string) {
    return markdown.replace(new RegExp(`(!\\[.*\\])\\(${escapeRegExp(baseUri)}([^)]+)\\)`, "g"), "$1($2)");
}

/**
 * Get the height of a textarea
 */
function getTextareaHeight(textarea: HTMLTextAreaElement): number {
    const clone = textarea.cloneNode() as HTMLTextAreaElement;
    clone.style.visibility = "hidden";
    clone.style.display = "";
    document.body.appendChild(clone);
    const height = clone.offsetHeight;
    document.body.removeChild(clone);
    return height;
}

/**
 * Holds the options of an editor input
 */
interface EditorInputOptions {
    baseUri: string;
    height: number;
    spellcheck: boolean;
    inputEventHandler: (value: string) => void;
}

/**
 * Provides an input with a Markdown or code editor in place of a textarea
 *
 * The editor mode is chosen with a button of the toolbar and remembered. Editors are loaded on
 * demand
 */
export class EditorInput extends InputBase<HTMLTextAreaElement> {
    /**
     * Id of the textarea
     */
    readonly id: string;

    /**
     * Options of the editor
     */
    readonly options: EditorInputOptions;

    /**
     * Element wrapping toolbar and editor
     */
    private container: HTMLElement;

    /**
     * Current editor
     */
    private editor: EditorView | undefined;

    /**
     * Promise resolved when the current editor is loaded
     */
    private editorPromise!: Promise<void>;

    /**
     * Create the editor input
     */
    constructor(textarea: HTMLTextAreaElement, options: Partial<EditorInputOptions> = {}) {
        super(textarea);

        this.id = textarea.id;

        const defaults: EditorInputOptions = {
            baseUri: textarea.dataset.baseUri ?? "",
            height: getTextareaHeight(textarea) ?? 200,
            spellcheck: true,
            inputEventHandler: (value: string) => {
                this.element.value = removeBaseUri(value, this.options.baseUri);
                debounce(() => this.dispatchChange(), 500, true)();
            },
        };

        this.options = { ...defaults, ...options };

        this.container = this.getContainer();

        this.bindModeToggle();
        this.bindLabel();
    }

    /**
     * Get the element wrapping the textarea, creating it with the toolbar if the textarea has none
     */
    private getContainer() {
        const textarea = this.element;

        if ((textarea.parentNode as HTMLElement)?.classList.contains("editor-wrap")) {
            return textarea.parentNode as HTMLElement;
        }

        const container = document.createElement("div");
        container.classList.add("editor-wrap");
        textarea.parentNode?.insertBefore(container, textarea);

        const toolbar = document.createElement("div");
        toolbar.classList.add("editor-toolbar");
        toolbar.appendChild(this.createToggleButton());

        container.appendChild(toolbar);
        container.appendChild(textarea);

        this.options.height -= toolbar.offsetHeight;
        textarea.style.height = `${this.options.height}px`;

        return container;
    }

    /**
     * Create the button of the toolbar that switches between the Markdown and the code editor
     */
    private createToggleButton() {
        const title = app.translation.get("panel.editor.toggleMarkdown", "Toggle Markdown");

        const button = document.createElement("button");
        button.type = "button";
        button.classList.add("button", "toolbar-button", "editor-toggle-markdown");
        button.dataset.command = "toggle-markdown";
        button.title = title;
        button.ariaLabel = title;
        button.disabled = this.element.disabled;
        button.innerHTML = markdown;

        return button;
    }

    /**
     * Show the editor of the mode remembered, and switch it with the button of the toolbar
     */
    private bindModeToggle() {
        const formName = this.element.form?.dataset.form;

        const key = formName ? `${formName}.${this.name}` : this.name;

        const mode = signal<"markdown" | "code">(window.localStorage.getItem(`formwork.editorMode[${key}]`) === "code" ? "code" : "markdown");

        const toggleButton = $("[data-command=toggle-markdown]", this.container) as HTMLButtonElement;

        effect(() => {
            toggleButton.classList.toggle("is-active", mode.value === "code");
            this.editorPromise = mode.value === "code" ? this.switchToCode() : this.switchToMarkdown();
        });

        toggleButton.addEventListener("click", () => {
            toggleButton.disabled = true;
            mode.value = mode.value === "code" ? "markdown" : "code";
            window.localStorage.setItem(`formwork.editorMode[${key}]`, mode.value);
            void this.editorPromise.then(() => {
                this.editor?.view.focus();
                toggleButton.disabled = false;
            });
        });
    }

    /**
     * Focus the editor when the label is clicked and make the toolbar accessible
     */
    private bindLabel() {
        const label = $(`label[for="${this.id}"]`);

        const toolbar = $<HTMLElement>(".editor-toolbar", this.container);

        if (label) {
            label.addEventListener("click", () => {
                void this.editorPromise.then(() => this.editor?.view.focus());
            });
        }

        if (toolbar) {
            initToolbar(toolbar, label?.id);
        }
    }

    /**
     * Switch to the Markdown editor
     */
    async switchToMarkdown() {
        await this.switchEditor(async () => {
            const { MarkdownView } = await import("../editor/markdown-view");

            return new MarkdownView(this.name, this.container, addBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler, {
                editable: !(this.element.disabled || this.element.readOnly),
                placeholder: this.element.placeholder,
                attributes: {
                    spellcheck: this.options.spellcheck ? "true" : "false",
                    ...this.accessibilityAttributes(),
                },
            });
        });
    }

    /**
     * Switch to the code editor
     */
    async switchToCode() {
        await this.switchEditor(async () => {
            const { CodeView } = await import("../editor/code-view");

            return new CodeView(this.container, removeBaseUri(this.element.value, this.options.baseUri), this.options.inputEventHandler, {
                editable: !(this.element.disabled || this.element.readOnly),
                placeholder: this.element.placeholder,
            });
        });
    }

    /**
     * Replace the current editor with a new one, hiding the textarea
     *
     * @param createEditor Function creating the new editor, which loads its code on demand
     */
    private async switchEditor(createEditor: () => Promise<EditorView>) {
        this.editor?.destroy();
        this.element.classList.add("is-loading");
        this.element.style.display = "";

        this.editor = await createEditor();

        this.element.style.display = "none";
        this.element.classList.remove("is-loading");
        this.editor.view.dom.style.height = `${this.options.height}px`;
    }

    /**
     * Get the accessibility attributes of the editor
     */
    private accessibilityAttributes(): Record<string, string> {
        const attributes: Record<string, string> = {
            role: "textbox",
            "aria-multiline": "true",
        };

        const labelId = $(`label[for="${this.id}"]`)?.id;

        if (labelId) {
            attributes["aria-labelledby"] = labelId;
        } else if (this.element.ariaLabel) {
            attributes["aria-label"] = this.element.ariaLabel;
        }

        const describedBy = this.element.getAttribute("aria-describedby");

        if (describedBy) {
            attributes["aria-describedby"] = describedBy;
        }

        if (this.element.required) {
            attributes["aria-required"] = "true";
        }

        if (this.element.readOnly) {
            attributes["aria-readonly"] = "true";
        }

        return attributes;
    }

    /**
     * Get the content of the editor
     */
    get value(): string {
        return this.editor?.content ?? this.element.value;
    }

    /**
     * Return whether the editor is read-only
     */
    get disabled(): boolean {
        return this.editor ? !this.editor.editable : this.element.disabled;
    }

    /**
     * Make the editor read-only or editable
     */
    set disabled(value: boolean) {
        this.element.disabled = value;
        void this.editorPromise.then(() => {
            if (this.editor) {
                this.editor.editable = !value;
            }
        });
        const toggleButton = $("[data-command=toggle-markdown]", this.container) as HTMLButtonElement;
        toggleButton.disabled = value;
    }

    /**
     * Set the content of the editor
     */
    set value(value: string) {
        this.element.value = value;
        void this.editorPromise.then(() => {
            if (this.editor) {
                this.editor.content = value;
            }
        });
    }
}
