import { $ } from "../../utils/selectors";
import { app } from "../../app";
import { arrayEquals } from "../../utils/arrays";
import { debounce } from "../../utils/events";

import CodeMirror from "codemirror";

import "codemirror/mode/markdown/markdown.js";
import "codemirror/addon/display/placeholder.js";
import "codemirror/addon/edit/continuelist.js";

export class EditorInput {
    constructor(textarea: HTMLTextAreaElement) {
        const height = textarea.offsetHeight;

        const editor = CodeMirror.fromTextArea(textarea, {
            mode: {
                name: "markdown",
                highlightFormatting: true,
            },
            theme: "formwork",
            indentUnit: 4,
            lineWrapping: true,
            addModeClass: true,
            extraKeys: { Enter: "newlineAndIndentContinueMarkdownList" },
            configureMouse: () => ({
                extend: false,
                addNew: false,
            }),
        });

        const toolbar = $(`.editor-toolbar[data-for=${textarea.id}]`) as HTMLElement;

        const wrap = (textarea.parentNode as HTMLElement).classList.contains("editor-wrap") ? (textarea.parentNode as HTMLElement) : null;

        let activeLines: number[] = [];

        editor.getWrapperElement().style.height = `${height}px`;

        $("[data-command=bold]", toolbar)?.addEventListener("click", () => {
            insertAtCursor("**");
        });

        $("[data-command=italic]", toolbar)?.addEventListener("click", () => {
            insertAtCursor("_");
        });

        $("[data-command=ul]", toolbar)?.addEventListener("click", () => {
            insertAtCursor(`${prependSequence()}- `, "");
        });

        $("[data-command=ol]", toolbar)?.addEventListener("click", () => {
            const num = /^(\d+)\./.exec(lastLine(editor.getValue()));
            if (num) {
                insertAtCursor(`\n${parseInt(num[1]) + 1}. `, "");
            } else {
                insertAtCursor(`${prependSequence()}1. `, "");
            }
        });

        $("[data-command=quote]", toolbar)?.addEventListener("click", () => {
            insertAtCursor(`${prependSequence()}> `, "");
        });

        $("[data-command=link]", toolbar)?.addEventListener("click", () => {
            const selection = editor.getSelection();
            if (/^(https?:\/\/|mailto:)/i.test(selection)) {
                insertAtCursor("[", `](${selection})`, true);
            } else if (selection !== "") {
                insertAtCursor(`[${selection}](http://`, ")", true);
            } else {
                insertAtCursor("[", "](http://)");
            }
        });

        $("[data-command=image]", toolbar)?.addEventListener("click", () => {
            app.modals["imagesModal"].show(undefined, (modal) => {
                const selected = $(".image-picker-thumbnail.selected", modal.element);
                if (selected) {
                    selected.classList.remove("selected");
                }
                function confirmImage(this: HTMLElement) {
                    if (selected) {
                        const filename = selected.dataset.filename;
                        insertAtCursor(`${prependSequence()}![`, `](${filename})`);
                    }
                    modal.hide();
                    this.removeEventListener("click", confirmImage);
                }
                ($(".image-picker-confirm", modal.element) as HTMLElement).addEventListener("click", confirmImage);
            });
        });

        $("[data-command=undo]", toolbar)?.addEventListener("click", () => {
            editor.undo();
            editor.focus();
        });

        $("[data-command=redo]", toolbar)?.addEventListener("click", () => {
            editor.redo();
            editor.focus();
        });

        editor.on(
            "changes",
            debounce(() => {
                textarea.value = editor.getValue();
                if (editor.historySize().undo < 1) {
                    ($("[data-command=undo]") as HTMLButtonElement).disabled = true;
                } else {
                    ($("[data-command=undo]") as HTMLButtonElement).disabled = false;
                }
                if (editor.historySize().redo < 1) {
                    ($("[data-command=redo]") as HTMLButtonElement).disabled = true;
                } else {
                    ($("[data-command=redo]") as HTMLButtonElement).disabled = false;
                }
            }, 500),
        );

        editor.on("beforeSelectionChange", (editor, selection) => {
            const lines = getLinesFromRange(selection.ranges);
            editor.operation(() => {
                if (!arrayEquals(lines, activeLines)) {
                    removeActiveLines(editor, activeLines);
                    addActiveLines(editor, lines);
                    activeLines = lines;
                }
            });
            editor.refresh();
        });

        editor.on("focus", () => {
            if (wrap !== null) {
                wrap.classList.add("focused");
            }
        });

        editor.on("blur", (editor) => {
            if (wrap !== null) {
                wrap.classList.remove("focused");
            }
            removeActiveLines(editor, activeLines);
            activeLines = [];
        });

        document.addEventListener("keydown", (event) => {
            if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                switch (event.key) {
                    case "b":
                        $("[data-command=bold]", toolbar)?.click();
                        event.preventDefault();
                        break;
                    case "i":
                        $("[data-command=italic]", toolbar)?.click();
                        event.preventDefault();
                        break;
                    case "k":
                        $("[data-command=link]", toolbar)?.click();
                        event.preventDefault();
                        break;
                }
            }
        });

        function lastLine(text: string) {
            const index = text.lastIndexOf("\n");
            if (index === -1) {
                return text;
            }
            return text.substring(index + 1);
        }

        function prevCursorChar() {
            const line = editor.getLine(editor.getCursor().line);
            return line.length === 0 ? undefined : line.slice(-1);
        }

        function prependSequence() {
            switch (prevCursorChar()) {
                case undefined:
                    return "";
                case "\n":
                    return "\n";
                default:
                    return "\n\n";
            }
        }

        function insertAtCursor(leftValue: string, rightValue?: string, dropSelection: boolean = false) {
            if (rightValue === undefined) {
                rightValue = leftValue;
            }
            const selection = dropSelection === true ? "" : editor.getSelection();
            const cursor = editor.getCursor();
            const lineBreaks = leftValue.split("\n").length - 1;
            editor.replaceSelection(leftValue + selection + rightValue);
            editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
            editor.focus();
        }

        function getLinesFromRange(ranges: CodeMirror.Range[]) {
            const lines: number[] = [];
            for (const range of ranges) {
                lines.push(range.head.line);
            }
            return lines;
        }

        function removeActiveLines(editor: CodeMirror.Editor, lines: number[]) {
            for (const line of lines) {
                editor.removeLineClass(line, "wrap", "CodeMirror-activeline");
            }
        }

        function addActiveLines(editor: CodeMirror.Editor, lines: number[]) {
            for (const line of lines) {
                editor.addLineClass(line, "wrap", "CodeMirror-activeline");
            }
        }
    }
}
