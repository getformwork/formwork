Formwork.Editor = function (id) {
    var textarea = document.getElementById(id);

    /* global CodeMirror:false */
    var editor = CodeMirror.fromTextArea(textarea, {
        mode: 'markdown',
        theme: 'formwork',
        indentUnit: 4,
        lineWrapping: true,
        addModeClass: true,
        extraKeys: {'Enter': 'newlineAndIndentContinueMarkdownList'}
    });

    var $toolbar = '.editor-toolbar[data-for=' + id + ']';

    $('[data-command=bold]', $toolbar).on('click', function () {
        insertAtCursor('**');
    });

    $('[data-command=italic]', $toolbar).on('click', function () {
        insertAtCursor('_');
    });

    $('[data-command=ul]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '- ', '');
    });

    $('[data-command=ol]', $toolbar).on('click', function () {
        var num = /^\d+\./.exec(lastLine(editor.getValue()));
        if (num) {
            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(prependSequence() + '1. ', '');
        }
    });

    $('[data-command=quote]', $toolbar).on('click', function () {
        insertAtCursor(prependSequence() + '> ', '');
    });

    $('[data-command=link]', $toolbar).on('click', function () {
        var selection = editor.getSelection();
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            insertAtCursor('[', '](' + selection + ')', true);
        } else if (selection !== '') {
            insertAtCursor('[' + selection + '](http://', ')', true);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', $toolbar).on('click', function () {
        Formwork.Modals.show('imagesModal', null, function ($modal) {
            $('.image-picker-thumbnail.selected', $modal).removeClass('selected');
            $('.image-picker-confirm', $modal).data('target', function (filename) {
                if (filename !== undefined) {
                    insertAtCursor(prependSequence() + '![', '](' + filename + ')');
                } else {
                    insertAtCursor(prependSequence() + '![](', ')');
                }
            });
        });
    });

    $('[data-command=summary]', $toolbar).on('click', function () {
        if (!hasSummarySequence()) {
            var prevChar = prevCursorChar();
            var prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(prepend + '\n===\n\n', '');
            $(this).attr('disabled', true);
        }
    });

    $('[data-command=undo]', $toolbar).on('click', function () {
        editor.undo();
        editor.focus();
    });

    $('[data-command=redo]', $toolbar).on('click', function () {
        editor.redo();
        editor.focus();
    });

    disableSummaryCommand();

    editor.on('changes', Formwork.Utils.debounce(function () {
        textarea.value = editor.getValue();
        disableSummaryCommand();
        $('[data-command=undo]').attr('disabled', editor.historySize().undo < 1);
        $('[data-command=redo]').attr('disabled', editor.historySize().redo < 1);
    }, 500));

    $(document).on('keydown', function (event) {
        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
            switch (event.which) {
            case 66: // ctrl/cmd + B
                $('[data-command=bold]', $toolbar).trigger('click');
                return false;
            case 73: // ctrl/cmd + I
                $('[data-command=italic]', $toolbar).trigger('click');
                return false;
            case 75: // ctrl/cmd + K
                $('[data-command=link]', $toolbar).trigger('click');
                return false;
            }
        }
    });

    function hasSummarySequence() {
        return /\n+===\n+/.test(editor.getValue());
    }

    function disableSummaryCommand() {
        $('[data-command=summary]', $toolbar).attr('disabled', hasSummarySequence());
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
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        var selection = dropSelection === true ? '' : editor.getSelection();
        var cursor = editor.getCursor();
        var lineBreaks = leftValue.split('\n').length - 1;
        editor.replaceSelection(leftValue + selection + rightValue);
        editor.setCursor(cursor.line + lineBreaks, cursor.ch + leftValue.length - lineBreaks);
        editor.focus();
    }
};
