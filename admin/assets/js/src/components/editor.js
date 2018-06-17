var Editor = function(id) {
    textarea = $('#' + id)[0];
    var toolbarSel = '.editor-toolbar[data-for=' + id + ']';

    disableSummaryCommand();
    $(textarea).keyup(Utils.debounce(disableSummaryCommand, 1000));

    $('[data-command=bold]', toolbarSel).click(function() {
        var pos = insertAtCursor(textarea, '**');
    });

    $('[data-command=italic]', toolbarSel).click(function() {
        var pos = insertAtCursor(textarea, '_');
    });

    $('[data-command=ul]', toolbarSel).click(function() {
        var prevChar = prevCursorChar(textarea);
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        insertAtCursor(textarea, prevChar === undefined ? '- ' : prepend + '- ', '');
    });

    $('[data-command=ol]', toolbarSel).click(function() {
        var prevChar = prevCursorChar(textarea);
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        var num = /^\d+\./.exec(lastLine(textarea.value));
        if (num) {
            insertAtCursor(textarea, '\n' + (parseInt(num) + 1) + '. ', '');
        } else {
            insertAtCursor(textarea, prevChar === undefined ? '1. ' : prepend + '1. ', '');
        }
    });

    $('[data-command=quote]', toolbarSel).click(function() {
        var prevChar = prevCursorChar(textarea);
        var prepend = prevChar === '\n' ? '\n' : '\n\n';
        insertAtCursor(textarea, prevChar === undefined ? '> ' : prepend + '> ', '');
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
            insertAtCursor(textarea, '[', '](http://)');
        }
    });

    $('[data-command=image]', toolbarSel).click(function() {
        var prevChar = prevCursorChar(textarea);
        var prepend = '\n\n';
        if (prevChar === '\n') {
            prepend = '\n';
        } else if (prevChar === undefined) {
            prepend = '';
        }
        insertAtCursor(textarea, prepend + '![](', ')');
    });

    $('[data-command=summary]', toolbarSel).click(function() {
        var prevChar = prevCursorChar(textarea);
        if (!hasSummarySequence()) {
            console.log(prevChar);
            var prepend = (prevChar === undefined || prevChar === '\n') ? '' : '\n';
            insertAtCursor(textarea, prepend + '\n===\n\n', '');
            $(this).attr('disabled', true);
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
        if (index == -1) return text;
        return text.substring(index + 1);
    }

    function prevCursorChar(field) {
        var startPos = field.selectionStart;
        return startPos === 0 ? undefined : field.value.substring(startPos - 1, startPos);
    }

    function insertAtCursor(field, leftValue, rightValue) {
        if (rightValue === undefined) rightValue = leftValue;
        var startPos = field.selectionStart;
        var endPos = field.selectionEnd;
        var selection = startPos === endPos ? '' : field.value.substring(startPos, endPos);
        field.value = field.value.substring(0, startPos) + leftValue + selection + rightValue + field.value.substring(endPos, field.value.length);
        field.setSelectionRange(startPos + leftValue.length, startPos + leftValue.length + selection.length);
        $(field).blur().focus();
    }
}
