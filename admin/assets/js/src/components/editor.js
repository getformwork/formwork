import CodeMirror from 'codemirror/lib/codemirror.js';
import Modals from './modals';
import Utils from './utils';

import 'codemirror/mode/markdown/markdown.js';
import 'codemirror/addon/edit/continuelist.js';

export default function Editor(textarea) {
    var editor = CodeMirror.fromTextArea(textarea, {
        mode: 'markdown',
        theme: 'formwork',
        indentUnit: 4,
        lineWrapping: true,
        addModeClass: true,
        extraKeys: {'Enter': 'newlineAndIndentContinueMarkdownList'}
    });

    var toolbar = $('.editor-toolbar[data-for=' + textarea.id + ']');

    $('[data-command=bold]', toolbar).addEventListener('click', function () {
        insertAtCursor('**');
    });

    $('[data-command=italic]', toolbar).addEventListener('click', function () {
        insertAtCursor('_');
    });

    $('[data-command=ul]', toolbar).addEventListener('click', function () {
        insertAtCursor(prependSequence() + '- ', '');
    });

    $('[data-command=ol]', toolbar).addEventListener('click', function () {
        var num = /^\d+\./.exec(lastLine(editor.getValue()));
        if (num) {
            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(prependSequence() + '1. ', '');
        }
    });

    $('[data-command=quote]', toolbar).addEventListener('click', function () {
        insertAtCursor(prependSequence() + '> ', '');
    });

    $('[data-command=link]', toolbar).addEventListener('click', function () {
        var selection = editor.getSelection();
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            insertAtCursor('[', '](' + selection + ')', true);
        } else if (selection !== '') {
            insertAtCursor('[' + selection + '](http://', ')', true);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', toolbar).addEventListener('click', function () {
        Modals.show('imagesModal', null, function (modal) {
            var selected = $('.image-picker-thumbnail.selected', modal);
            if (selected) {
                selected.classList.remove('selected');
            }
            function confirmImage() {
                var filename = $('.image-picker-thumbnail.selected', $('#imagesModal')).getAttribute('data-filename');
                if (filename !== undefined) {
                    insertAtCursor(prependSequence() + '![', '](' + filename + ')');
                } else {
                    insertAtCursor(prependSequence() + '![](', ')');
                }
                this.removeEventListener('click', confirmImage);
            }
            $('.image-picker-confirm', modal).addEventListener('click', confirmImage);
        });
    });

    $('[data-command=summary]', toolbar).addEventListener('click', function () {
        var prevChar, prepend;
        if (!hasSummarySequence()) {
            prevChar = prevCursorChar();
            prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(prepend + '\n===\n\n', '');
            this.setAttribute('disabled', '');
        }
    });

    $('[data-command=undo]', toolbar).addEventListener('click', function () {
        editor.undo();
        editor.focus();
    });

    $('[data-command=redo]', toolbar).addEventListener('click', function () {
        editor.redo();
        editor.focus();
    });

    disableSummaryCommand();

    editor.on('changes', Utils.debounce(function () {
        textarea.value = editor.getValue();
        disableSummaryCommand();
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

    document.addEventListener('keydown', function (event) {
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

    function hasSummarySequence() {
        return /\n+===\n+/.test(editor.getValue());
    }

    function disableSummaryCommand() {
        if (hasSummarySequence()) {
            $('[data-command=summary]', toolbar).setAttribute('disabled', '');
        } else {
            $('[data-command=summary]', toolbar).removeAttribute('disabled');
        }
    }

    function lastLine(text) {
        var index = text.lastIndexOf('\n');
        if (index === -1) {
            return text;
        }
        return text.substring(index + 1);
    }

    function prevCursorChar() {
        var line = editor.getLine(editor.getCursor().line);
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
        var selection, cursor, lineBreaks;
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        selection = dropSelection === true ? '' : editor.getSelection();
        cursor = editor.getCursor();
        lineBreaks = leftValue.split('\n').length - 1;
        editor.replaceSelection(leftValue + selection + rightValue);
        editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
        editor.focus();
    }
}
