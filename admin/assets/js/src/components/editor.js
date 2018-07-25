Formwork.Editor = function(id) {
    var textarea = $('#' + id)[0];
    var toolbarSel = '.editor-toolbar[data-for=' + id + ']';

    $('[data-command=bold]', toolbarSel).click(function() {
        insertAtCursor('**');
    });

    $('[data-command=italic]', toolbarSel).click(function() {
        insertAtCursor('_');
    });

    $('[data-command=ul]', toolbarSel).click(function() {
        var prevChar = prevCursorChar();
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        insertAtCursor(prevChar === undefined ? '- ' : prepend + '- ', '');
    });

    $('[data-command=ol]', toolbarSel).click(function() {
        var prevChar = prevCursorChar();
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        var num = /^\d+\./.exec(lastLine(textarea.value));
        if (num) {
            insertAtCursor('\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(prevChar === undefined ? '1. ' : prepend + '1. ', '');
        }
    });

    $('[data-command=quote]', toolbarSel).click(function() {
        var prevChar = prevCursorChar();
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        insertAtCursor(prevChar === undefined ? '> ' : prepend + '> ', '');
    });

    $('[data-command=link]', toolbarSel).click(function() {
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var selection = startPos === endPos ? '' : textarea.value.substring(startPos, endPos);
        var left = textarea.value.substring(0, startPos);
        var right = textarea.value.substring(endPos, textarea.value.length);
        if (/^(https?:\/\/|mailto:)/i.test(selection)) {
            textarea.value = left + '[](' + selection + ')' + right;
            textarea.focus();
            textarea.setSelectionRange(startPos + 1, startPos + 1);
        } else if (selection !== '') {
            textarea.value = left + '[' + selection + '](http://)' + right;
            textarea.focus();
            textarea.setSelectionRange(startPos + selection.length + 10, startPos + selection.length + 10);
        } else {
            insertAtCursor('[', '](http://)');
        }
    });

    $('[data-command=image]', toolbarSel).click(function() {
        var prevChar = prevCursorChar();
        var prepend = '\n\n';
        if (prevChar === '\n') {
            prepend = '\n';
        } else if (prevChar === undefined) {
            prepend = '';
        }
        insertAtCursor(prepend + '![](', ')');
    });

    $('[data-command=summary]', toolbarSel).click(function() {
        var prevChar = prevCursorChar();
        if (!hasSummarySequence()) {
            console.log(prevChar);
            var prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(prepend + '\n===\n\n', '');
            $(this).attr('disabled', true);
        }
    });

    $(textarea).keyup(Formwork.Utils.debounce(disableSummaryCommand, 1000));
    disableSummaryCommand();

    $(document).keydown(function(event) {
        if (!event.altKey && (event.ctrlKey || event.metaKey)) {
            switch (event.which) {
                case 66: // ctrl/cmd + B
                    $('[data-command=bold]').click();
                    return false;
                case 73: // ctrl/cmd + I
                    $('[data-command=italic]').click();
                    return false;
                case 83: // ctrl/cmd + S
                    $('[data-command=save]').click();
                    return false;
                case 89: //ctrl/cmd + Y
                case 90: // ctrl/cmd + Z
                    return false;
            }
        }
    });

    function hasSummarySequence() {
        return /\n+===\n+/.test(textarea.value);
    }

    function disableSummaryCommand() {
        $('[data-command=summary]', toolbarSel).attr('disabled', hasSummarySequence());
    }

    function lastLine(text) {
        var index = text.lastIndexOf('\n');
        if (index == -1) {
            return text;
        }
        return text.substring(index + 1);
    }

    function prevCursorChar() {
        var startPos = textarea.selectionStart;
        return startPos === 0 ? undefined : textarea.value.substring(startPos - 1, startPos);
    }

    function insertAtCursor(leftValue, rightValue) {
        if (rightValue === undefined) {
            rightValue = leftValue;
        }
        var startPos = textarea.selectionStart;
        var endPos = textarea.selectionEnd;
        var selection = startPos === endPos ? '' : textarea.value.substring(startPos, endPos);
        textarea.value = textarea.value.substring(0, startPos) + leftValue + selection + rightValue + textarea.value.substring(endPos, textarea.value.length);
        textarea.setSelectionRange(startPos + leftValue.length, startPos + leftValue.length + selection.length);
        $(textarea).blur().focus();
    }
};
