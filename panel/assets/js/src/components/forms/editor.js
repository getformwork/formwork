import CodeMirror from 'codemirror/lib/codemirror.js';
import Modals from '../modals';
import Utils from '../utils';

import 'codemirror/mode/markdown/markdown.js';
import 'codemirror/addon/display/placeholder.js';
import 'codemirror/addon/edit/continuelist.js';

export default function Editor(textarea) {
    const height = textarea.offsetHeight;

    const editor = CodeMirror.fromTextArea(textarea, {
        mode: {
            name: 'markdown',
            highlightFormatting: true,
        },
        theme: 'formwork',
        indentUnit: 4,
        lineWrapping: true,
        addModeClass: true,
        extraKeys: { 'Enter': 'newlineAndIndentContinueMarkdownList' },
        configureMouse: () => ({
            extend: false,
            addNew: false,
        }),
    });

    const toolbar = $(`.editor-toolbar[data-for=${textarea.id}]`);

    const wrap = textarea.parentNode.classList.contains('editor-wrap') ? textarea.parentNode : null;

    let activeLines = [];

    editor.getWrapperElement().style.height = `${height}px`;

    $('[data-command=bold]', toolbar).addEventListener('click', () => {
        insertAtCursor('**');
    });

    $('[data-command=italic]', toolbar).addEventListener('click', () => {
        insertAtCursor('_');
    });

    $('[data-command=ul]', toolbar).addEventListener('click', () => {
        insertAtCursor(`${prependSequence()}- `, '');
    });

    $('[data-command=ol]', toolbar).addEventListener('click', () => {
        const num = /^\d+\./.exec(lastLine(editor.getValue()));
        if (num) {
            insertAtCursor(`\n${parseInt(num) + 1}. `, '');
        } else {
            insertAtCursor(`${prependSequence()}1. `, '');
        }
    });

    $('[data-command=quote]', toolbar).addEventListener('click', () => {
        insertAtCursor(`${prependSequence()}> `, '');
    });

    $('[data-command=link]', toolbar).addEventListener('click', () => {
        const selection = editor.getSelection();
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            insertAtCursor('[', `](${selection})`, true);
        } else if (selection !== '') {
            insertAtCursor(`[${selection}](http://`, ')', true);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', toolbar).addEventListener('click', () => {
        Modals.show('imagesModal', null, (modal) => {
            const selected = $('.image-picker-thumbnail.selected', modal);
            if (selected) {
                selected.classList.remove('selected');
            }
            function confirmImage() {
                const filename = $('.image-picker-thumbnail.selected', $('#imagesModal')).getAttribute('data-filename');
                if (filename !== undefined) {
                    insertAtCursor(`${prependSequence()}![`, `](${filename})`);
                } else {
                    insertAtCursor(`${prependSequence()}![](`, ')');
                }
                this.removeEventListener('click', confirmImage);
            }
            $('.image-picker-confirm', modal).addEventListener('click', confirmImage);
        });
    });

    $('[data-command=undo]', toolbar).addEventListener('click', () => {
        editor.undo();
        editor.focus();
    });

    $('[data-command=redo]', toolbar).addEventListener('click', () => {
        editor.redo();
        editor.focus();
    });

    editor.on('changes', Utils.debounce(() => {
        textarea.value = editor.getValue();
        if (editor.historySize().undo < 1) {
            $('[data-command=undo]').setAttribute('disabled', '');
        } else {
            $('[data-command=undo]').removeAttribute('disabled');
        }
        if (editor.historySize().redo < 1) {
            $('[data-command=redo]').setAttribute('disabled', '');
        } else {
            $('[data-command=redo]').removeAttribute('disabled');
        }
    }, 500));

    editor.on('beforeSelectionChange', (editor, selection) => {
        const lines = getLinesFromRange(selection.ranges);
        editor.operation(() => {
            if (!Utils.sameArray(lines, activeLines)) {
                removeActiveLines(editor, activeLines);
                addActiveLines(editor, lines);
                activeLines = lines;
            }
        });
        editor.refresh();
    });

    editor.on('focus', () => {
        if (wrap !== null) {
            wrap.classList.add('focused');
        }
    });

    editor.on('blur', (editor) => {
        if (wrap !== null) {
            wrap.classList.remove('focused');
        }
        removeActiveLines(editor, activeLines);
        activeLines = [];
    });

    document.addEventListener('keydown', (event) => {
        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
            switch (event.which) {
            case 66: // ctrl/cmd + B
                $('[data-command=bold]', toolbar).click();
                event.preventDefault();
                break;
            case 73: // ctrl/cmd + I
                $('[data-command=italic]', toolbar).click();
                event.preventDefault();
                break;
            case 75: // ctrl/cmd + K
                $('[data-command=link]', toolbar).click();
                event.preventDefault();
                break;
            }
        }
    });

    function lastLine(text) {
        const index = text.lastIndexOf('\n');
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
            return '';
        case '\n':
            return '\n';
        default:
            return '\n\n';
        }
    }

    function insertAtCursor(leftValue, rightValue, dropSelection) {
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        const selection = dropSelection === true ? '' : editor.getSelection();
        const cursor = editor.getCursor();
        const lineBreaks = leftValue.split('\n').length - 1;
        editor.replaceSelection(leftValue + selection + rightValue);
        editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
        editor.focus();
    }

    function getLinesFromRange(ranges) {
        const lines = [];
        for (const range of ranges) {
            lines.push(range.head.line);
        }
        return lines;
    }

    function removeActiveLines(editor, lines) {
        for (const line of lines) {
            editor.removeLineClass(line, 'wrap', 'CodeMirror-activeline');
        }
    }

    function addActiveLines(editor, lines) {
        for (const line of lines) {
            editor.addLineClass(line, 'wrap', 'CodeMirror-activeline');
        }
    }
}
