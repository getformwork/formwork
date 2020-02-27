var Formwork = {
    config: {},

    init: function () {
        Formwork.Modals.init();
        Formwork.Forms.init();
        Formwork.Dropdowns.init();
        Formwork.Tooltips.init();

        Formwork.Dashboard.init();
        Formwork.Pages.init();
        Formwork.Updates.init();

        $('.toggle-navigation').addEventListener('click', function () {
            $('.sidebar').classList.toggle('show');
        });

        $$('[data-chart-data]').forEach(function (element) {
            var data = JSON.parse(element.getAttribute('data-chart-data'));
            Formwork.Chart(element, data);
        });

        $$('meta[name=notification]').forEach(function (element) {
            var notification = new Formwork.Notification(element.getAttribute('content'), element.getAttribute('data-type'), element.getAttribute('data-interval'));
            notification.show();
            element.parentNode.removeChild(element);
        });

        if ($('[data-command=save]')) {
            document.addEventListener('keydown', function (event) {
                if (!event.altKey && (event.ctrlKey || event.metaKey)) {
                    if (event.which === 83) { // ctrl/cmd + S
                        $('[data-command=save]').click();
                        event.preventDefault();
                    }
                }
            });
        }

    },

    initGlobals: function (global) {
        global.$ = function (selector, parent) {
            if (typeof parent === 'undefined') {
                parent = document;
            }
            return parent.querySelector(selector);
        };

        global.$$ = function (selector, parent) {
            if (typeof parent === 'undefined') {
                parent = document;
            }
            return parent.querySelectorAll(selector);
        };

        // NodeList.prototype.forEach polyfill
        if (!('forEach' in global.NodeList.prototype)) {
            global.NodeList.prototype.forEach = global.Array.prototype.forEach;
        }

        // Element.prototype.matches polyfill
        if (!('matches' in global.Element.prototype)) {
            global.Element.prototype.matches = global.Element.prototype.msMatchesSelector || global.Element.prototype.webkitMatchesSelector;
        }

        // Element.prototype.closest polyfill
        if (!('closest' in global.Element.prototype)) {
            global.Element.prototype.closest = function (selectors) {
                var element = this;
                do {
                    if (element.matches(selectors)) {
                        return element;
                    }
                    element = element.parentElement || element.parentNode;
                } while (element !== null && element.nodeType === 1);
                return null;
            };
        }
    }
};

document.addEventListener('DOMContentLoaded', function () {
    Formwork.init();
});

Formwork.initGlobals(window);

Formwork.ArrayInput = function (input) {
    var isAssociative = input.classList.contains('array-input-associative');
    var inputName = input.getAttribute('data-name');

    $('.array-input-add', input).addEventListener('click', addRow);
    $('.array-input-remove', input).addEventListener('click', removeRow);

    if (isAssociative) {
        $('.array-input-key', input).addEventListener('keyup', function () {
            $('.array-input-value', this.parentNode).setAttribute('name', inputName + '[' + this.value + ']');
        });
        $('.array-input-value', input).addEventListener('keyup', function () {
            this.setAttribute('name', inputName + '[' + $('.array-input-key', this.parentNode).value + ']');
        });
    }

    /* global Sortable:false */
    Sortable.create(input, {
        handle: '.sort-handle',
        forceFallback: true
    });

    function addRow() {
        var row = this.closest('.array-input-row');
        var clone = row.cloneNode(true);
        $('.array-input-key', clone).value = '';
        $('.array-input-value', clone).value = '';
        $('.array-input-add', clone).addEventListener('click', addRow);
        $('.array-input-remove', clone).addEventListener('click', removeRow);
        if (row.nextSibling) {
            row.parentNode.insertBefore(clone, row.nextSibling);
        } else {
            row.parentNode.appendChild(clone);
        }
    }

    function removeRow() {
        var row = this.closest('.array-input-row');
        if ($$('.array-input-row', row.parentNode).length > 0) {
            row.parentNode.removeChild(row);
        } else {
            $('.array-input-key', row).value = '';
            $('.array-input-value', row).value = '';

            Formwork.Utils.triggerEvent($('.array-input-key', row), 'keyup');
        }
    }
};

Formwork.Chart = function (element, data) {
    var options = {
        showArea: true,
        fullWidth: true,
        scaleMinSpace: 20,
        divisor: 5,
        chartPadding: 20,
        lineSmooth: false,
        low: 0,
        axisX: {
            showGrid: false,
            labelOffset: {
                x: 0, y: 10
            }
        },
        axisY: {
            onlyInteger: true,
            offset: 15,
            labelOffset: {
                x: 0, y: 5
            }
        }
    };

    /* global Chartist:false */
    var chart = new Chartist.Line(element, data, options);

    chart.container.addEventListener('mouseover', function (event) {
        var tooltipOffset, tooltip, strokeWidth;

        if (event.target.getAttribute('class') === 'ct-point') {
            tooltipOffset = {
                x: 0, y: -8
            };
            if (navigator.userAgent.indexOf('Firefox') !== -1) {
                strokeWidth = parseFloat(getComputedStyle(event.target)['stroke-width']);
                tooltipOffset.x += strokeWidth / 2;
                tooltipOffset.y += strokeWidth / 2;
            }
            tooltip = new Formwork.Tooltip(event.target.getAttribute('ct:value'), {
                referenceElement: event.target,
                offset: tooltipOffset
            });
            tooltip.show();
        }
    });
};

Formwork.Dashboard = {
    init: function () {
        var clearCacheCommand = $('[data-command=clear-cache]');
        var makeBackupCommand = $('[data-command=make-backup]');

        if (clearCacheCommand) {
            clearCacheCommand.addEventListener('click', function () {
                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'cache/clear/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();
                });
            });
        }

        if (makeBackupCommand) {
            makeBackupCommand.addEventListener('click', function () {
                var button = this;
                button.setAttribute('disabled', '');
                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'backup/make/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();
                    setTimeout(function () {
                        if (response.status === 'success') {
                            Formwork.Utils.triggerDownload(response.data.uri, $('meta[name=csrf-token]').getAttribute('content'));
                        }
                        button.removeAttribute('disabled');
                    }, 1000);
                });
            });
        }
    }
};

Formwork.DatePicker = function (input, options) {
    var defaults = {
        dayLabels:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        monthLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
        weekStarts: 0,
        todayLabel: 'Today',
        format: 'YYYY-MM-DD'
    };

    var today = new Date();
    var dateKeeper, dateHelpers, calendar;

    options = Formwork.Utils.extendObject({}, defaults, options);

    dateKeeper = {
        year: today.getFullYear(),
        month: today.getMonth(),
        day: today.getDate(),
        setDate: function (date) {
            this.year = date.getFullYear();
            this.month = date.getMonth();
            this.day = date.getDate();
        },
        lastDay: function () {
            this.day = dateHelpers.daysInMonth(this.month, this.year);
        },
        prevYear: function () {
            this.year--;
        },
        nextYear: function () {
            this.year++;
        },
        prevMonth: function () {
            this.month = dateHelpers.mod(this.month - 1, 12);
            if (this.month === 11) {
                this.prevYear();
            }
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.lastDay();
            }
        },
        nextMonth: function () {
            this.month = dateHelpers.mod(this.month + 1, 12);
            if (this.month === 0) {
                this.nextYear();
            }
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.lastDay();
            }
        },
        prevWeek: function () {
            this.day -= 7;
            if (this.day < 1) {
                this.prevMonth();
                this.day += dateHelpers.daysInMonth(this.month, this.year);
            }
        },
        nextWeek: function () {
            this.day += 7;
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.day -= dateHelpers.daysInMonth(this.month, this.year);
                this.nextMonth();
            }
        },
        prevDay: function () {
            this.day--;
            if (this.day < 1) {
                this.prevMonth();
                this.lastDay();
            }
        },
        nextDay: function () {
            this.day++;
            if (this.day > dateHelpers.daysInMonth(this.month, this.year)) {
                this.nextMonth();
                this.day = 1;
            }
        }
    };

    dateHelpers = {
        _daysInMonth: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
        mod: function (n, m) {
            return ((n % m) + m) % m;
        },
        pad: function (num) {
            return num.toString().length === 1 ? '0' + num : num;
        },
        isValidDate: function (date) {
            return date && !isNaN(Date.parse(date));
        },
        isLeapYear: function (year) {
            return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
        },
        daysInMonth: function (month, year) {
            return month === 1 && this.isLeapYear(year) ? 29 : this._daysInMonth[month];
        },
        formatDateTime: function (date) {
            var format = options.format;
            var year = date.getFullYear();
            var month = date.getMonth() + 1;
            var day = date.getDate();
            var hours = date.getHours();
            var minutes = date.getMinutes();
            var seconds = date.getSeconds();
            var am = hours < 12;
            if (format.indexOf('a') > -1) {
                hours = dateHelpers.mod(hours, 12) > 0 ? dateHelpers.mod(hours, 12) : 12;
            }
            return format.replace('YYYY', year)
                .replace('YY', year.toString().substr(-2))
                .replace('MM', dateHelpers.pad(month))
                .replace('DD', dateHelpers.pad(day))
                .replace('hh', dateHelpers.pad(hours))
                .replace('mm', dateHelpers.pad(minutes))
                .replace('ss', dateHelpers.pad(seconds))
                .replace('a', am ? 'AM' : 'PM');
        }
    };

    calendar = $('.calendar') ? $('.calendar') : generateCalendar();

    initInput();

    function initInput() {
        var value = input.value;
        input.readOnly = true;
        input.size = options.format.length;
        if (dateHelpers.isValidDate(value)) {
            value = new Date(value);
            input.setAttribute('data-date', value);
            input.value = dateHelpers.formatDateTime(value);
        }
        input.addEventListener('change', function () {
            if (this.value === '') {
                this.setAttribute('data-date', '');
            } else {
                this.value = dateHelpers.formatDateTime(this.getAttribute('data-date'));
            }
        });
        input.addEventListener('keydown', function (event) {
            var date = this.getAttribute('data-date');
            dateKeeper.setDate(dateHelpers.isValidDate(date) ? new Date(date) : new Date());
            switch (event.which) {
            case 13: // enter
                $('.calendar-day.selected', calendar).click();
                calendar.style.display = 'none';
                break;
            case 8: // backspace
                this.value = '';
                this.blur();
                calendar.style.display = 'none';
                break;
            case 27: // escape
                this.blur();
                calendar.style.display = 'none';
                break;
            case 37: // left arrow
                if (event.ctrlKey || event.metaKey) {
                    if (event.shiftKey) {
                        dateKeeper.prevYear();
                    } else {
                        dateKeeper.prevMonth();
                    }
                } else {
                    dateKeeper.prevDay();
                }
                updateInput(this);
                break;
            case 38: // up arrow
                dateKeeper.prevWeek();
                updateInput(this);
                break;
            case 39: // right arrow
                if (event.ctrlKey || event.metaKey) {
                    if (event.shiftKey) {
                        dateKeeper.nextYear();
                    } else {
                        dateKeeper.nextMonth();
                    }
                } else {
                    dateKeeper.nextDay();
                }
                updateInput(this);
                break;
            case 40: // down arrow
                dateKeeper.nextWeek();
                updateInput(this);
                break;
            case 48: // 0
                if (event.ctrlKey || event.metaKey) {
                    dateKeeper.setDate(new Date());
                }
                updateInput(this);
                break;
            default:
                return;
            }
            event.stopPropagation();
            event.preventDefault();
        });

        input.addEventListener('focus', function () {
            var date = dateHelpers.isValidDate(this.getAttribute('data-date')) ? new Date(this.getAttribute('data-date')) : new Date();
            dateKeeper.setDate(date);
            generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
            calendar.style.display = 'block';
            setCalendarPosition();
        });

        input.addEventListener('blur', function () {
            calendar.style.display = 'none';
        });
    }

    function updateInput(input) {
        var date = new Date(dateKeeper.year, dateKeeper.month, dateKeeper.day);
        generateCalendarTable(dateKeeper.year, dateKeeper.month, dateKeeper.day);
        input.value = dateHelpers.formatDateTime(date);
        input.setAttribute('data-date', date);
    }

    function getCurrentInput() {
        return document.activeElement.classList.contains('date-input') ? document.activeElement : null;
    }

    function generateCalendar() {
        calendar = document.createElement('div');
        calendar.className = 'calendar';
        calendar.innerHTML = '<div class="calendar-buttons"><button type="button" class="prevMonth"><i class="i-chevron-left"></i></button><button class="currentMonth">' + options.todayLabel + '</button><button type="button" class="nextMonth"><i class="i-chevron-right"></i></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>';
        document.body.appendChild(calendar);

        $('.currentMonth', calendar).addEventListener('mousedown', function (event) {
            var input = getCurrentInput();
            var today = new Date();
            dateKeeper.setDate(today);
            updateInput(input);
            input.blur();
            event.preventDefault();
        });

        Formwork.Utils.longClick($('.prevMonth', calendar), function (event) {
            dateKeeper.prevMonth();
            generateCalendarTable(dateKeeper.year, dateKeeper.month);
            event.preventDefault();
        }, 750, 500);

        Formwork.Utils.longClick($('.nextMonth', calendar), function (event) {
            dateKeeper.nextMonth();
            generateCalendarTable(dateKeeper.year, dateKeeper.month);
            event.preventDefault();
        }, 750, 500);

        window.addEventListener('mousedown', function (event) {
            if (calendar.style.display !== 'none') {
                if (event.target.closest('.calendar')) {
                    event.preventDefault();
                }
            }
        });

        window.addEventListener('resize', Formwork.Utils.throttle(setCalendarPosition, 100));

        return calendar;
    }

    function generateCalendarTable(year, month, day) {
        var i, j;
        var num = 1;
        var firstDay = new Date(year, month, 1).getDay();
        var monthLength = dateHelpers.daysInMonth(month, year);
        var monthName = options.monthLabels[month];
        var start = dateHelpers.mod(firstDay - options.weekStarts, 7);
        var html = '';
        html += '<tr><th class="calendar-header" colspan="7">';
        html += monthName + '&nbsp;' + year;
        html += '</th></tr>';
        html += '<tr>';
        for (i = 0; i < 7; i++ ){
            html += '<td class="calendar-header-day">';
            html += options.dayLabels[dateHelpers.mod(i + options.weekStarts, 7)];
            html += '</td>';
        }
        html += '</tr><tr>';
        for (i = 0; i < 6; i++) {
            for (j = 0; j < 7; j++) {
                if (num <= monthLength && (i > 0 || j >= start)) {
                    if (num === day) {
                        html += '<td class="calendar-day selected">';
                    } else {
                        html += '<td class="calendar-day">';
                    }
                    html += num++;
                } else if (num === 1) {
                    html += '<td class="calendar-prev-month-day">';
                    html += dateHelpers.daysInMonth(dateHelpers.mod(month - 1, 12), year) - start + j + 1;
                } else {
                    html += '<td class="calendar-next-month-day">';
                    html += num++ - monthLength;
                }
                html += '</td>';
            }
            html += '</tr><tr>';
        }
        html += '</tr>';
        $('.calendar-table', calendar).innerHTML = html;
        $$('.calendar-day', calendar).forEach(function (element) {
            element.addEventListener('mousedown', function (event) {
                event.stopPropagation();
                event.preventDefault();
            });
            element.addEventListener('click', function () {
                var input = getCurrentInput();
                var date = new Date(dateKeeper.year, dateKeeper.month, parseInt(this.textContent));
                input.setAttribute('data-date', date);
                input.value = dateHelpers.formatDateTime(date);
                input.blur();
            });
        });
    }

    function setCalendarPosition() {
        var inputRect, inputTop, inputLeft,
            calendarRect, calendarTop, calendarLeft, calendarWidth, calendarHeight,
            windowWidth, windowHeight;

        input = getCurrentInput();

        if (!input || calendar.style.display !== 'block') {
            return;
        }

        inputRect = input.getBoundingClientRect();
        inputTop = inputRect.top + window.pageYOffset;
        inputLeft = inputRect.left + window.pageXOffset;
        calendar.style.top = (inputTop + input.offsetHeight) + 'px';
        calendar.style.left = (inputLeft + input.offsetLeft) + 'px';

        calendarRect = calendar.getBoundingClientRect();
        calendarTop = calendarRect.top + window.pageYOffset;
        calendarLeft = calendarRect.left + window.pageXOffset;
        calendarWidth = Formwork.Utils.outerWidth(calendar);
        calendarHeight = Formwork.Utils.outerHeight(calendar);

        windowWidth = document.documentElement.clientWidth;
        windowHeight = document.documentElement.clientHeight;

        if (calendarLeft + calendarWidth > windowWidth) {
            calendar.style.left = (windowWidth - calendarWidth) + 'px';
        }

        if (calendarTop < window.pageYOffset || window.pageYOffset < calendarTop + calendarHeight - windowHeight) {
            window.scrollTo(window.pageXOffset, calendarTop + calendarHeight - windowHeight);
        }
    }
};

Formwork.Dropdowns = {
    init: function () {
        if ($('.dropdown')) {
            document.addEventListener('click', function (event) {
                var button = event.target.closest('.dropdown-button');
                var dropdown, isVisible;
                if (button) {
                    dropdown = document.getElementById(button.getAttribute('data-dropdown'));
                    isVisible = getComputedStyle(dropdown).display !== 'none';
                    event.preventDefault();
                }
                $$('.dropdown-menu').forEach(function (element) {
                    element.style.display = '';
                });
                if (dropdown && !isVisible) {
                    dropdown.style.display = 'block';
                }
            });
        }
    }
};

Formwork.Editor = function (textarea) {
    /* global CodeMirror:false */
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
        Formwork.Modals.show('imagesModal', null, function (modal) {
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

    editor.on('changes', Formwork.Utils.debounce(function () {
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
};

Formwork.FileInput = function (input) {
    var label = $('label[for="' + input.id + '"]');

    input.setAttribute('data-label', $('label[for="' + input.id + '"] span').innerHTML);
    input.addEventListener('change', updateLabel);
    input.addEventListener('input', updateLabel);

    label.addEventListener('drag', preventDefault);
    label.addEventListener('dragstart', preventDefault);
    label.addEventListener('dragend', preventDefault);
    label.addEventListener('dragover', handleDragenter);
    label.addEventListener('dragenter', handleDragenter);
    label.addEventListener('dragleave', handleDragleave);

    label.addEventListener('drop', function (event) {
        var target = document.getElementById(this.getAttribute('for'));
        target.files = event.dataTransfer.files;
        // Firefox won't trigger a change event, so we explicitly do that
        Formwork.Utils.triggerEvent(target, 'change');
        event.preventDefault();
    });

    function updateLabel() {
        var span = $('label[for="' + this.id + '"] span');
        if (this.files.length > 0) {
            span.innerHTML = this.files[0].name;
        } else {
            span.innerHTML = this.getAttribute('data-label');
        }
    }

    function preventDefault(event) {
        event.preventDefault();
    }

    function handleDragenter(event) {
        this.classList.add('drag');
        event.preventDefault();
    }

    function handleDragleave(event) {
        this.classList.remove('drag');
        event.preventDefault();
    }
};

Formwork.Form = function (form) {
    var originalData = Formwork.Utils.serializeForm(form);

    form.addEventListener('submit', function () {
        window.removeEventListener('beforeunload', handleBeforeunload);
    });

    window.addEventListener('beforeunload', handleBeforeunload);

    $$('input[type=file][data-auto-upload]', form).forEach(function (element) {
        element.addEventListener('change', function () {
            if (!hasChanged(false)) {
                form.submit();
            }
        });
    });

    $('#changesModal [data-command=continue]').addEventListener('click', function () {
        window.removeEventListener('beforeunload', handleBeforeunload);
        window.location.href = this.getAttribute('data-href');
    });

    $$('a[href]:not([href^="#"]):not([target="_blank"])').forEach(function (element) {
        element.addEventListener('click', function (event) {
            if (hasChanged()) {
                event.preventDefault();
                Formwork.Modals.show('changesModal', null, function (modal) {
                    $('[data-command=continue]', modal).setAttribute('data-href', element.href);
                });
            }
        });
    });

    function handleBeforeunload(event) {
        if (hasChanged()) {
            event.preventDefault();
            event.returnValue = '';
        }
    }

    function hasChanged(checkFileInputs) {
        var fileInputs, i;
        fileInputs = $$('input[file]', form);
        if (typeof checkFileInputs === 'undefined') {
            checkFileInputs = true;
        }
        if (checkFileInputs === true && fileInputs.length > 0) {
            for (i = 0; i < fileInputs.length; i++) {
                if (fileInputs[i].files.length > 0) {
                    return true;
                }
            }
        }
        return Formwork.Utils.serializeForm(form) !== originalData;
    }
};

Formwork.Forms = {
    init: function () {

        $$('[data-form]').forEach(function (element) {
            Formwork.Form(element);
        });

        $$('input[data-enable]').forEach(function (element) {
            element.addEventListener('change', function () {
                var i, input;
                var inputs = this.getAttribute('data-enable').split(',');
                for (i = 0; i < inputs.length; i++) {
                    input = $('input[name="' + inputs[i] + '"]');
                    if (!this.checked) {
                        input.setAttribute('disabled', '');
                    } else {
                        input.removeAttribute('disabled');
                    }
                }
            });
        });

        $$('.input-reset').forEach(function (element) {
            element.addEventListener('click', function () {
                var target = document.getElementById(this.getAttribute('data-reset'));
                target.value = '';
                Formwork.Utils.triggerEvent(target, 'change');
            });
        });

        $$('.date-input').forEach(function (element) {
            Formwork.DatePicker(element, Formwork.config.DatePicker);
        });

        $$('.image-input').forEach(function (element) {
            element.addEventListener('click', function () {
                Formwork.Modals.show('imagesModal', null, function (modal) {
                    var selected = $('.image-picker-thumbnail.selected', modal);
                    if (selected) {
                        selected.classList.remove('selected');
                    }
                    if (this.value) {
                        $('.image-picker-thumbnail[data-filename="' + this.value + '"]', modal).classList.add('selected');
                    }
                    $('.image-picker-confirm', modal).setAttribute('data-target', element.id);
                });
            });
        });

        $$('.image-picker').forEach(function (element) {
            Formwork.ImagePicker(element);
        });

        $$('.editor-textarea').forEach(function (element) {
            Formwork.Editor(element);
        });

        $$('input[type=file]').forEach(function (element) {
            Formwork.FileInput(element);
        });

        $$('input[data-field=tags]').forEach(function (element) {
            Formwork.TagInput(element);
        });

        $$('input[type=range]').forEach(function (element) {
            Formwork.RangeInput(element);
        });

        $$('.array-input').forEach(function (element) {
            Formwork.ArrayInput(element);
        });
    }
};

Formwork.ImagePicker = function (element) {
    var options = $$('option', element);
    var confirmCommand = $('.image-picker-confirm', element.parentNode);
    var uploadCommand = $('[data-command=upload]', element.parentNode);

    var container, thumbnail, i;

    element.style.display = 'none';

    if (options.length > 0) {
        container = document.createElement('div');
        container.className = 'image-picker-thumbnails';

        for (i = 0; i < options.length; i++) {
            thumbnail = document.createElement('div');
            thumbnail.className = 'image-picker-thumbnail';
            thumbnail.style.backgroundImage = 'url(' + options[i].value + ')';
            thumbnail.setAttribute('data-uri', options[i].value);
            thumbnail.setAttribute('data-filename', options[i].text);
            thumbnail.addEventListener('click', handleThumbnailClick);
            thumbnail.addEventListener('dblclick', handleThumbnailDblclick);
            container.appendChild(thumbnail);
        }

        element.parentNode.insertBefore(container, element);
        $('.image-picker-empty-state').style.display = 'none';
    }

    confirmCommand.addEventListener('click', function () {
        var selectedThumbnail = $('.image-picker-thumbnail.selected');
        var target = document.getElementById(this.getAttribute('data-target'));
        if (selectedThumbnail && target) {
            target.value = selectedThumbnail.getAttribute('data-filename');
        }
    });

    uploadCommand.addEventListener('click', function () {
        document.getElementById(this.getAttribute('data-upload-target')).click();
    });

    function handleThumbnailClick() {
        var target = document.getElementById($('.image-picker-confirm').getAttribute('data-target'));
        if (target) {
            target.value = this.getAttribute('data-filename');
        }
        $$('.image-picker-thumbnail').forEach(function (element) {
            element.classList.remove('selected');
        });
        this.classList.add('selected');
    }

    function handleThumbnailDblclick() {
        this.click();
        $('.image-picker-confirm').click();
    }
};

Formwork.Modals = {
    init: function () {
        $$('[data-modal]').forEach(function (element) {
            element.addEventListener('click', function () {
                var modal = this.getAttribute('data-modal');
                var action = this.getAttribute('data-modal-action');
                if (action) {
                    Formwork.Modals.show(modal, action);
                } else {
                    Formwork.Modals.show(modal);
                }
            });
        });

        $$('.modal [data-dismiss]').forEach(function (element) {
            element.addEventListener('click', function () {
                var valid;
                if (this.hasAttribute('data-validate')) {
                    valid = Formwork.Modals.validate(this.getAttribute('data-dismiss'));
                    if (!valid) {
                        return;
                    }
                }
                Formwork.Modals.hide(this.getAttribute('data-dismiss'));
            });
        });

        $$('.modal').forEach(function (element) {
            element.addEventListener('click', function (event) {
                if (event.target === this) {
                    Formwork.Modals.hide();
                }
            });
        });

        document.addEventListener('keyup', function (event) {
            // ESC key
            if (event.which === 27) {
                Formwork.Modals.hide();
            }
        });
    },

    show: function (id, action, callback) {
        var modal = document.getElementById(id);
        if (!modal) {
            return;
        }
        modal.classList.add('show');
        if (action) {
            $('form', modal).setAttribute('action', action);
        }
        if ($('[autofocus]', modal)) {
            Formwork.Utils.triggerEvent($('[autofocus]', modal), 'focus'); // Firefox bug
        }
        if (typeof callback === 'function') {
            callback(modal);
        }
        $$('.tooltip').forEach(function (element) {
            element.parentNode.removeChild(element);
        });
        this.createBackdrop();
    },

    hide: function (id) {
        if (typeof id !== 'undefined') {
            document.getElementById(id).classList.remove('show');
        } else {
            $$('.modal').forEach(function (element) {
                element.classList.remove('show');
            });
        }
        this.removeBackdrop();
    },

    createBackdrop: function () {
        var backdrop;
        if (!$('.modal-backdrop')) {
            backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop';
            document.body.appendChild(backdrop);
        }
    },

    removeBackdrop: function () {
        var backdrop = $('.modal-backdrop');
        if (backdrop) {
            backdrop.parentNode.removeChild(backdrop);
        }
    },

    validate: function (id) {
        var valid = false;
        var modal = document.getElementById(id);
        $$('[required]', id).forEach(function (element) {
            if (element.value === '') {
                element.classList('input-invalid');
                Formwork.Utils.triggerEvent(element, 'focus');
                $('.modal-error', modal).style.display = 'block';
                valid = false;
                return false;
            }
            valid = true;
        });
        return valid;
    }
};

Formwork.Notification = function (text, type, interval, options) {
    var defaults = {
        newestOnTop: true,
        fadeOutDelay: 300,
        mouseleaveDelay: 1000
    };

    var container = $('.notification-container');

    var notification, timer;

    options = Formwork.Utils.extendObject({}, defaults, options);

    function show() {
        if (!container) {
            container = document.createElement('div');
            container.className = 'notification-container';
            document.body.appendChild(container);
        }

        notification = document.createElement('div');
        notification.className = 'notification notification-' + type;
        notification.innerHTML = text;

        if (options.newestOnTop && container.childNodes.length > 0) {
            container.insertBefore(notification, container.childNodes[0]);
        } else {
            container.appendChild(notification);
        }

        timer = setTimeout(remove, interval);

        notification.addEventListener('click', remove);

        notification.addEventListener('mouseenter', function () {
            clearTimeout(timer);
        });

        notification.addEventListener('mouseleave', function () {
            timer = setTimeout(remove, options.mouseleaveDelay);
        });
    }

    function remove() {
        notification.classList.add('fadeout');

        setTimeout(function () {
            if (notification.parentNode) {
                container.removeChild(notification);
            }
            if (container.childNodes.length < 1) {
                if (container.parentNode) {
                    document.body.removeChild(container);
                }
                container = null;
            }
        }, options.fadeOutDelay);
    }

    return {
        show: show,
        remove: remove
    };
};

Formwork.Pages = {
    init: function () {

        var commandExpandAllPages = $('[data-command=expand-all-pages]');
        var commandCollapseAllPages = $('[data-command=collapse-all-pages]');
        var commandReorderPages = $('[data-command=reorder-pages]');

        var searchInput = $('.page-search');

        var newPageModal = document.getElementById('newPageModal');
        var slugModal = document.getElementById('slugModal');

        $$('.pages-list').forEach(function (element) {
            if (element.getAttribute('data-sortable-children') === 'true') {
                initSortable(element);
            }
        });

        $$('.page-details').forEach(function (element) {
            var toggle = $('.page-children-toggle', element);
            if (toggle) {
                element.addEventListener('click', function () {
                    toggle.click();
                });
            }
        });

        $$('.page-details a').forEach(function (element) {
            element.addEventListener('click', function (event) {
                event.stopPropagation();
            });
        });

        $$('.page-children-toggle').forEach(function (element) {
            element.addEventListener('click', function (event) {
                togglePagesList(this);
                event.stopPropagation();
            });
        });

        if (commandExpandAllPages) {
            commandExpandAllPages.addEventListener('click', function () {
                expandAllPages();
                this.blur();
            });
        }

        if (commandCollapseAllPages) {
            commandCollapseAllPages.addEventListener('click', function () {
                collapseAllPages();
                this.blur();
            });
        }

        if (commandReorderPages) {
            commandReorderPages.addEventListener('click', function () {
                this.classList.toggle('active');
                $$('.pages-list .sort-handle').forEach(function (element) {
                    Formwork.Utils.toggleElement(element, 'inline');
                });
                this.blur();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('focus', function () {
                $$('.pages-children').forEach(function (element) {
                    element.setAttribute('data-display', getComputedStyle(element).display);
                });
            });

            searchInput.addEventListener('keyup', Formwork.Utils.debounce(handleSearch, 100));
            searchInput.addEventListener('search', handleSearch);

            document.addEventListener('keydown', function (event) {
                if (event.ctrlKey || event.metaKey) {
                    // ctrl/cmd + F
                    if (event.which === 70 && document.activeElement !== searchInput) {
                        searchInput.focus();
                        event.preventDefault();
                    }
                }
            });
        }

        if (newPageModal) {
            $('#page-title', newPageModal).addEventListener('keyup', function () {
                $('#page-slug', newPageModal).value = Formwork.Utils.slug(this.value);
            });

            $('#page-slug', newPageModal).addEventListener('keyup', handleSlugChange);
            $('#page-slug', newPageModal).addEventListener('blur', handleSlugChange);

            $('#page-parent', newPageModal).addEventListener('change', function () {
                var option = this.options[this.selectedIndex];
                var pageTemplate = $('#page-template', newPageModal);
                var allowedTemplates = option.getAttribute('data-allowed-templates');
                var i = 0;

                if (allowedTemplates !== null) {
                    allowedTemplates = allowedTemplates.split(', ');
                    pageTemplate.setAttribute('data-previous-value', pageTemplate.value);
                    pageTemplate.value = allowedTemplates[0];
                    for (i = 0; i < pageTemplate.options.length; i++) {
                        if (allowedTemplates.indexOf(pageTemplate.options[i].value) === -1) {
                            pageTemplate.options[i].setAttribute('disabled', '');
                        }
                    }
                } else {
                    pageTemplate.value = pageTemplate.getAttribute('data-previous-value');
                    pageTemplate.removeAttribute('data-previous-value');
                    for (i = 0; i < pageTemplate.options.length; i++) {
                        pageTemplate.options[i].disabled = false;
                    }
                }
            });
        }

        if (slugModal) {
            $('[data-command=change-slug]').addEventListener('click', function () {
                Formwork.Modals.show('slugModal', null, function (modal) {
                    var slug = document.getElementById('slug').value;
                    var slugInput = $('#page-slug', modal);
                    slugInput.value = slug;
                    slugInput.setAttribute('placeholder', slug);
                    slugInput.focus();
                });
            });

            $('#page-slug', slugModal).addEventListener('keydown', function (event) {
                // enter
                if (event.which === 13) {
                    $('[data-command=continue]', slugModal).click();
                }
            });

            $('#page-slug', slugModal).addEventListener('keyup', handleSlugChange);
            $('#page-slug', slugModal).addEventListener('blur', handleSlugChange);

            $('[data-command=generate-slug]', slugModal).addEventListener('click', function () {
                var slug = Formwork.Utils.slug(document.getElementById('title').value);
                $('#page-slug', slugModal).value = slug;
                $('#page-slug', slugModal).focus();
            });

            $('[data-command=continue]', slugModal).addEventListener('click', function () {
                var slug = $('#page-slug', slugModal).value.replace(/^-+|-+$/, '');
                var route;
                if (slug.length > 0) {
                    route = $('.page-route span').innerHTML;
                    $$('#page-slug, #slug').forEach(function (element) {
                        element.value = slug;
                    });
                    $('#page-slug', slugModal).value = slug;
                    document.getElementById('slug').value = slug;
                    $('.page-route span').innerHTML = route.replace(/\/[a-z0-9-]+\/$/, '/' + slug + '/');
                }
                Formwork.Modals.hide('slugModal');
            });
        }

        function expandAllPages() {
            $$('.pages-children').forEach(function (element) {
                element.style.display = 'block';
            });
            $$('.pages-list .page-children-toggle').forEach(function (element) {
                element.classList.remove('toggle-collapsed');
                element.classList.add('toggle-expanded');
            });
        }

        function collapseAllPages() {
            $$('.pages-children').forEach(function (element) {
                element.style.display = 'none';
            });
            $$('.pages-list .page-children-toggle').forEach(function (element) {
                element.classList.remove('toggle-expanded');
                element.classList.add('toggle-collapsed');
            });
        }

        function togglePagesList(list) {
            $$('.pages-list', list.closest('li')).forEach(function (element) {
                Formwork.Utils.toggleElement(element);
            });
            list.classList.toggle('toggle-expanded');
            list.classList.toggle('toggle-collapsed');
        }

        function initSortable(element) {
            var originalOrder = [];

            /* global Sortable:false */
            var sortable = Sortable.create(element, {
                handle: '.sort-handle',
                filter: '[data-sortable=false]',
                forceFallback: true,

                onClone: function (event) {
                    event.item.closest('.pages-list').classList.add('dragging');

                    $$('.pages-children', event.item).forEach(function (element) {
                        element.style.display = 'none';
                    });
                    $$('.page-children-toggle').forEach(function (element) {
                        element.classList.remove('toggle-expanded');
                        element.classList.add('toggle-collapsed');
                        element.style.opacity = '0.5';
                    });
                },

                onMove: function (event) {
                    if (event.related.getAttribute('data-sortable') === 'false') {
                        return false;
                    }
                    $$('.pages-children', event.related).forEach(function (element) {
                        element.style.display = 'none';
                    });
                },

                onEnd: function (event) {
                    var data, notification;

                    event.item.closest('.pages-list').classList.remove('dragging');

                    $$('.page-children-toggle').forEach(function (element) {
                        element.style.opacity = '';
                    });

                    if (event.newIndex === event.oldIndex) {
                        return;
                    }

                    sortable.option('disabled', true);

                    data = {
                        'csrf-token': $('meta[name=csrf-token]').getAttribute('content'),
                        parent: element.getAttribute('data-parent'),
                        from: event.oldIndex,
                        to: event.newIndex
                    };

                    Formwork.Request({
                        method: 'POST',
                        url: Formwork.config.baseUri + 'pages/reorder/',
                        data: data
                    }, function (response) {
                        if (response.status) {
                            notification = new Formwork.Notification(response.message, response.status, 5000);
                            notification.show();
                        }
                        if (!response.status || response.status === 'error') {
                            sortable.sort(originalOrder);
                        }
                        sortable.option('disabled', false);
                        originalOrder = sortable.toArray();
                    });

                }
            });

            originalOrder = sortable.toArray();
        }

        function handleSearch() {
            var value = this.value;
            var regexp;
            if (value.length === 0) {
                $$('.pages-children').forEach(function (element) {
                    element.style.display = element.getAttribute('data-display');
                });
                $$('.pages-item, .page-children-toggle').forEach(function (element) {
                    element.style.display = '';
                });
                $$('.page-details').forEach(function (element) {
                    element.style.paddingLeft = '';
                });
                $$('.page-title a').forEach(function (element) {
                    element.innerHTML = element.textContent;
                });
            } else {
                regexp = new RegExp(Formwork.Utils.makeDiacriticsRegExp(Formwork.Utils.escapeRegExp(value)), 'gi');
                $$('.pages-children').forEach(function (element) {
                    element.style.display = 'block';
                });
                $$('.page-children-toggle').forEach(function (element) {
                    element.style.display = 'none';
                });
                $$('.page-details').forEach(function (element) {
                    element.style.paddingLeft = '0';
                });
                $$('.page-title a').forEach(function (element) {
                    var pagesItem = element.closest('.pages-item');
                    var text = element.textContent;
                    if (text.match(regexp) !== null) {
                        element.innerHTML = text.replace(regexp, '<mark>$&</mark>');
                        pagesItem.style.display = '';
                    } else {
                        pagesItem.style.display = 'none';
                    }
                });
            }
        }

        function handleSlugChange() {
            this.value = Formwork.Utils.validateSlug(this.value);
        }
    }
};

Formwork.RangeInput = function (input) {
    input.addEventListener('change', updateValueLabel);
    input.addEventListener('input', updateValueLabel);

    function updateValueLabel() {
        $('.range-input-value', this.parentNode).innerHTML = this.value;
    }
};

Formwork.Request = function (options, callback) {

    var request = new XMLHttpRequest();

    var handler, response, code;

    request.open(options.method, options.url, true);
    request.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    request.send(Formwork.Utils.serializeObject(options.data));

    if (typeof callback === 'function') {
        handler = function () {
            response = JSON.parse(this.response);
            code = response.code || this.status;
            if (parseInt(code) === 400) {
                location.reload();
            } else {
                callback(response, request);
            }
        };
        request.onload = handler;
        request.onerror = handler;
    }

    return request;
};

Formwork.TagInput = function (input) {
    var options = {addKeyCodes: [32]};
    var tags = [];
    var field, innerInput, hiddenInput, placeholder, dropdown;

    createField();
    createDropdown();

    registerInputEvents();

    function createField() {
        var isRequired = input.hasAttribute('required');
        var isDisabled = input.hasAttribute('disabled');

        field = document.createElement('div');
        field.className = 'tag-input';

        innerInput = document.createElement('input');
        innerInput.className = 'tag-inner-input';
        innerInput.id = input.id;
        innerInput.type = 'text';
        innerInput.placeholder = input.placeholder;

        innerInput.setAttribute('size', '');

        hiddenInput = document.createElement('input');
        hiddenInput.className = 'tag-hidden-input';
        hiddenInput.name = input.name;
        hiddenInput.id = input.id;
        hiddenInput.type = 'text';
        hiddenInput.value = input.value;
        hiddenInput.readOnly = true;
        hiddenInput.hidden = true;

        if (isRequired) {
            hiddenInput.required = true;
        }

        if (isDisabled) {
            field.disabled = true;
            innerInput.disabled = true;
            hiddenInput.disabled = true;
        }

        input.parentNode.replaceChild(field, input);
        field.appendChild(innerInput);
        field.appendChild(hiddenInput);

        if (hiddenInput.value) {
            tags = hiddenInput.value.split(', ');
            tags.forEach(function (value, index) {
                value = value.trim();
                tags[index] = value;
                insertTag(value);
            });
        }

        if (innerInput.placeholder) {
            placeholder = innerInput.placeholder;
            updatePlaceholder();
        } else {
            placeholder = '';
        }

        field.addEventListener('mousedown', function (event) {
            innerInput.focus();
            event.preventDefault();
        });
    }

    function createDropdown() {
        var list, key, item;

        if (input.hasAttribute('data-options')) {

            list = JSON.parse(input.getAttribute('data-options'));

            dropdown = document.createElement('div');
            dropdown.className = 'dropdown-list';

            for (key in list) {
                item = document.createElement('div');
                item.className = 'dropdown-item';
                item.innerHTML = list[key];
                item.setAttribute('data-value', key);
                item.addEventListener('click', function () {
                    addTag(this.getAttribute('data-value'));
                });
                dropdown.appendChild(item);
            }

            field.appendChild(dropdown);

            innerInput.addEventListener('focus', function () {
                if (getComputedStyle(dropdown).display === 'none') {
                    updateDropdown();
                    dropdown.scrollTop = 0;
                    dropdown.style.display = 'block';
                }
            });

            innerInput.addEventListener('blur', function () {
                if (getComputedStyle(dropdown).display !== 'none') {
                    updateDropdown();
                    dropdown.style.display = 'none';
                }
            });

            innerInput.addEventListener('keydown', function (event) {
                switch (event.which) {
                case 8: // backspace
                    updateDropdown();
                    break;
                case 13: // enter
                    if (getComputedStyle(dropdown).display !== 'none') {
                        addTagFromSelectedDropdownItem();
                        event.preventDefault();
                    }
                    break;
                case 38: // up arrow
                    if (getComputedStyle(dropdown).display !== 'none') {
                        selectPrevDropdownItem();
                        event.preventDefault();
                    }
                    break;
                case 40: // down arrow
                    if (getComputedStyle(dropdown).display !== 'none') {
                        selectNextDropdownItem();
                        event.preventDefault();
                    }
                    break;
                default:
                    if (options.addKeyCodes.indexOf(event.which) > -1) {
                        addTagFromSelectedDropdownItem();
                        event.preventDefault();
                    }
                }
            });

            innerInput.addEventListener('keyup', Formwork.Utils.debounce(function (event) {
                var value = innerInput.value.trim();
                switch (event.which) {
                case 27: // escape
                    dropdown.style.display = 'none';
                    break;
                case 38: // up arrow
                case 40: // down arrow
                    return true;
                default:
                    dropdown.style.display = 'block';
                    filterDropdown(value);
                    if (value.length > 0) {
                        selectFirstDropdownItem();
                    }
                }
            }, 100));
        }
    }

    function registerInputEvents() {
        innerInput.addEventListener('focus', function () {
            field.classList.add('focused');
        });

        innerInput.addEventListener('blur', function () {
            var value = innerInput.value.trim();
            if (value !== '') {
                addTag(value);
            }
            field.classList.remove('focused');
        });

        innerInput.addEventListener('keydown', function () {
            var value = innerInput.value.trim();
            switch (event.which) {
            case 8: // backspace
                if (value === '') {
                    removeTag(tags[tags.length - 1]);
                    if (innerInput.previousSibling){
                        innerInput.parentNode.removeChild(innerInput.previousSibling);
                    }
                    event.preventDefault();
                } else {
                    innerInput.size = Math.max(innerInput.value.length, innerInput.placeholder.length, 1);
                }
                break;
            case 13: // enter
            case 188: // comma
                if (value !== '') {
                    addTag(value);
                }
                event.preventDefault();
                break;
            case 27: // escape
                clearInput();
                innerInput.blur();
                event.preventDefault();
                break;
            default:
                if (value !== '' && options.addKeyCodes.indexOf(event.which) > -1) {
                    addTag(value);
                    event.preventDefault();
                    break;
                }
                if (value.length > 0) {
                    innerInput.size = innerInput.value.length + 2;
                }
                break;
            }
        });
    }

    function updateTags() {
        hiddenInput.value = tags.join(', ');
        updatePlaceholder();
    }

    function updatePlaceholder() {
        if (placeholder.length > 0) {
            if (tags.length === 0) {
                innerInput.placeholder = placeholder;
                innerInput.size = placeholder.length;
            } else {
                innerInput.placeholder = '';
                innerInput.size = 1;
            }
        }
    }

    function validateTag(value) {
        if (tags.indexOf(value) === -1) {
            if (dropdown) {
                return $('[data-value="' + value + '"]', dropdown) !== null;
            }
            return true;
        }
        return false;
    }

    function insertTag(value) {
        var tag = document.createElement('span');
        var tagRemove = document.createElement('i');
        tag.className = 'tag';
        tag.innerHTML = value;
        tag.style.marginRight = '.25rem';
        innerInput.parentNode.insertBefore(tag, innerInput);

        tagRemove.className = 'tag-remove';
        tagRemove.addEventListener('mousedown', function (event) {
            removeTag(value);
            tag.parentNode.removeChild(tag);
            event.preventDefault();
        });
        tag.appendChild(tagRemove);
    }

    function addTag(value) {
        if (validateTag(value)) {
            tags.push(value);
            insertTag(value);
            updateTags();
        } else {
            updatePlaceholder();
        }
        innerInput.value = '';
        if (dropdown) {
            updateDropdown();
        }
    }

    function removeTag(value) {
        var index = tags.indexOf(value);
        if (index > -1) {
            tags.splice(index, 1);
            updateTags();
        }
        if (dropdown) {
            updateDropdown();
        }
    }

    function clearInput() {
        innerInput.value = '';
        updatePlaceholder();
    }

    function updateDropdown() {
        var visibleItems = 0;
        $$('.dropdown-item', dropdown).forEach(function (element) {
            if (getComputedStyle(element).display !== 'none') {
                visibleItems++;
            }
            if (tags.indexOf(element.getAttribute('data-value')) === -1) {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
            element.classList.remove('selected');
        });
        if (visibleItems > 0) {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }

    function filterDropdown(value) {
        var visibleItems = 0;
        dropdown.style.display = 'block';
        $$('.dropdown-item', dropdown).forEach(function (element) {
            var text = element.textContent;
            var regexp = new RegExp(Formwork.Utils.makeDiacriticsRegExp(Formwork.Utils.escapeRegExp(value)), 'i');
            if (text.match(regexp) !== null && element.style.display !== 'none') {
                element.style.display = 'block';
                visibleItems++;
            } else {
                element.style.display = 'none';
            }
        });
        if (visibleItems > 0) {
            dropdown.style.display = 'block';
        } else {
            dropdown.style.display = 'none';
        }
    }

    function scrollToDropdownItem(item) {
        var dropdownScrollTop = dropdown.scrollTop;
        var dropdownHeight = dropdown.clientHeight;
        var dropdownScrollBottom = dropdownScrollTop + dropdownHeight;
        var dropdownStyle = getComputedStyle(dropdown);
        var dropdownPaddingTop = parseInt(dropdownStyle.paddingTop);
        var dropdownPaddingBottom = parseInt(dropdownStyle.paddingBottom);
        var itemTop = item.offsetTop;
        var itemHeight = item.clientHeight;
        var itemBottom = itemTop + itemHeight;
        if (itemTop < dropdownScrollTop) {
            dropdown.scrollTop = itemTop - dropdownPaddingTop;
        } else if (itemBottom > dropdownScrollBottom) {
            dropdown.scrollTop = itemBottom - dropdownHeight + dropdownPaddingBottom;
        }
    }

    function addTagFromSelectedDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        if (getComputedStyle(selectedItem).display !== 'none') {
            innerInput.value = selectedItem.getAttribute('data-value');
        }
    }

    function selectDropdownItem(item) {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        if (selectedItem) {
            selectedItem.classList.remove('selected');
        }
        if (item) {
            item.classList.add('selected');
            scrollToDropdownItem(item);
        }
    }

    function selectFirstDropdownItem() {
        var items = $$('.dropdown-item', dropdown);
        var i;
        for (i = 0; i < items.length; i++) {
            if (getComputedStyle(items[i]).display !== 'none') {
                selectDropdownItem(items[i]);
                return;
            }
        }
    }

    function selectLastDropdownItem() {
        var items = $$('.dropdown-item', dropdown);
        var i;
        for (i = items.length - 1; i >= 0; i--) {
            if (getComputedStyle(items[i]).display !== 'none') {
                selectDropdownItem(items[i]);
                return;
            }
        }
    }

    function selectPrevDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        var previousItem;
        if (selectedItem) {
            previousItem = selectedItem.previousSibling;
            while (previousItem && previousItem.style.display === 'none') {
                previousItem = previousItem.previousSibling;
            }
            if (previousItem) {
                return selectDropdownItem(previousItem);
            }
            selectDropdownItem(selectedItem.previousSibling);
        }
        selectLastDropdownItem();
    }

    function selectNextDropdownItem() {
        var selectedItem = $('.dropdown-item.selected', dropdown);
        var nextItem;
        if (selectedItem) {
            nextItem = selectedItem.nextSibling;
            while (nextItem && nextItem.style.display === 'none') {
                nextItem = nextItem.nextSibling;
            }
            if (nextItem) {
                return selectDropdownItem(nextItem);
            }
        }
        selectFirstDropdownItem();
    }
};

Formwork.Tooltip = function (text, options) {
    var defaults = {
        container: document.body,
        referenceElement: document.body,
        position: 'top',
        offset: {
            x: 0, y: 0
        },
        delay: 500
    };

    var referenceElement = options.referenceElement;
    var tooltip, timer;

    options = Formwork.Utils.extendObject({}, defaults, options);

    // IE 10-11 support classList only on HTMLElement
    if (referenceElement instanceof HTMLElement) {
        // Remove tooltip when clicking on buttons
        if (referenceElement.tagName.toLowerCase() === 'button' || referenceElement.classList.contains('button')) {
            referenceElement.addEventListener('click', remove);
        }
    }

    referenceElement.addEventListener('mouseout', remove);

    function show() {
        timer = setTimeout(function () {
            var position;
            tooltip = document.createElement('div');
            tooltip.className = 'tooltip';
            tooltip.setAttribute('role', 'tooltip');
            tooltip.style.display = 'block';
            tooltip.innerHTML = text;

            options.container.appendChild(tooltip);

            position = getTooltipPosition(tooltip);
            tooltip.style.top = position.top + 'px';
            tooltip.style.left = position.left + 'px';
        }, options.delay);
    }

    function remove() {
        clearTimeout(timer);
        if (tooltip !== undefined && options.container.contains(tooltip)) {
            options.container.removeChild(tooltip);
        }
    }

    function getTooltipPosition(tooltip) {
        var rect = referenceElement.getBoundingClientRect();
        var top = rect.top + window.pageYOffset;
        var left = rect.left + window.pageXOffset;

        var hw = (rect.width - tooltip.offsetWidth) / 2;
        var hh = (rect.height - tooltip.offsetHeight) / 2;

        switch (options.position) {
        case 'top':
            return {
                top: Math.round(top - tooltip.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'right':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left + referenceElement.offsetWidth + options.offset.x)
            };
        case 'bottom':
            return {
                top: Math.round(top + referenceElement.offsetHeight + options.offset.y),
                left: Math.round(left + hw + options.offset.x)
            };
        case 'left':
            return {
                top: Math.round(top + hh + options.offset.y),
                left: Math.round(left - tooltip.offsetWidth + options.offset.x)
            };
        }
    }

    return {
        show: show,
        remove: remove
    };
};

Formwork.Tooltips = {
    init: function () {
        $$('[title]').forEach(function (element) {
            element.setAttribute('data-tooltip', element.getAttribute('title'));
            element.removeAttribute('title');
        });

        $$('[data-tooltip]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip = new Formwork.Tooltip(this.getAttribute('data-tooltip'), {
                    referenceElement: this,
                    position: 'bottom',
                    offset: {
                        x: 0, y: 4
                    }
                });
                tooltip.show();
            });
        });

        $$('[data-overflow-tooltip="true"]').forEach(function (element) {
            element.addEventListener('mouseover', function () {
                var tooltip;
                if (this.offsetWidth < this.scrollWidth) {
                    tooltip = new Formwork.Tooltip(this.textContent.trim(), {
                        referenceElement: this,
                        position: 'bottom',
                        offset: {
                            x: 0, y: 4
                        }
                    });
                    tooltip.show();
                }
            });
        });
    }
};

Formwork.Updates = {
    init: function () {
        var updaterComponent = document.getElementById('updater-component');
        var updateStatus, spinner,
            currentVersion, currentVersionName,
            newVersion, newVersionName;

        if (updaterComponent) {
            updateStatus = $('.update-status');
            spinner = $('.spinner');
            currentVersion = $('.current-version');
            currentVersionName = $('.current-version-name');
            newVersion = $('.new-version');
            newVersionName = $('.new-version-name');

            setTimeout(function () {
                var data = {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')};

                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'updates/check/',
                    data: data
                }, function (response) {
                    updateStatus.innerHTML = response.message;

                    if (response.status === 'success') {
                        if (response.data.uptodate === false) {
                            showNewVersion(response.data.release.name);
                        } else {
                            showCurrentVersion();
                        }
                    } else {
                        spinner.classList.add('spinner-error');
                    }
                });
            }, 1000);

            $('[data-command=install-updates]').addEventListener('click', function () {
                newVersion.style.display = 'none';
                spinner.classList.remove('spinner-info');
                updateStatus.innerHTML = updateStatus.getAttribute('data-installing-text');

                Formwork.Request({
                    method: 'POST',
                    url: Formwork.config.baseUri + 'updates/update/',
                    data: {'csrf-token': $('meta[name=csrf-token]').getAttribute('content')}
                }, function (response) {
                    var notification = new Formwork.Notification(response.message, response.status, 5000);
                    notification.show();

                    updateStatus.innerHTML = response.data.status;

                    if (response.status === 'success') {
                        showInstalledVersion();
                    } else {
                        spinner.classList.add('spinner-error');
                    }
                });
            });
        }

        function showNewVersion(name) {
            spinner.classList.add('spinner-info');
            newVersionName.innerHTML = name;
            newVersion.style.display = 'block';
        }

        function showCurrentVersion() {
            spinner.classList.add('spinner-success');
            currentVersion.style.display = 'block';
        }

        function showInstalledVersion() {
            spinner.classList.add('spinner-success');
            currentVersionName.innerHTML = newVersionName.innerHTML;
            currentVersion.style.display = 'block';
        }
    }
};

Formwork.Utils = {
    escapeRegExp: function (string) {
        return string.replace(/[-[\]/{}()*+?.\\^$|]/g, '\\$&');
    },

    makeDiacriticsRegExp: function (string) {
        var char;
        var diacritics = {
            'a': '[aáàăâǎåäãȧąāảȁạ]',
            'b': '[bḃḅ]',
            'c': '[cćĉčċç]',
            'd': '[dďḋḑḍ]',
            'e': '[eéèĕêěëẽėȩęēẻȅẹ]',
            'g': '[gǵğĝǧġģḡ]',
            'h': '[hĥȟḧḣḩḥ]',
            'i': '[iiíìĭîǐïĩįīỉȉịı]',
            'j': '[jĵǰ]',
            'k': '[kḱǩķḳ]',
            'l': '[lĺľļḷ]',
            'm': '[mḿṁṃ]',
            'n': '[nńǹňñṅņṇ]',
            'o': '[oóòŏôǒöőõȯǿǫōỏȍơọ]',
            'p': '[pṕṗ]',
            'r': '[rŕřṙŗȑṛ]',
            's': '[sśŝšṡşṣș]',
            't': '[tťẗṫţṭț]',
            'u': '[uúùŭûǔůüűũųūủȕưụ]',
            'v': '[vṽṿ]',
            'w': '[wẃẁŵẘẅẇẉ]',
            'x': '[xẍẋ]',
            'y': '[yýỳŷẙÿỹẏȳỷỵ]',
            'z': '[zźẑžżẓ]'
        };
        for (char in diacritics) {
            if (diacritics.hasOwnProperty(char)) {
                string = string.split(char).join(diacritics[char]);
                string = string.split(char.toUpperCase()).join(diacritics[char].toUpperCase());
            }
        }
        return string;
    },

    slug: function (string) {
        var char;
        var translate = {
            '\t': '', '\r': '', '!': '', '"': '', '#': '', '$': '', '%': '', '\'': '-', '(': '', ')': '', '*': '', '+': '', ',': '', '.': '', ':': '', ';': '', '<': '', '=': '', '>': '', '?': '', '@': '', '[': '', ']': '', '^': '', '`': '', '{': '', '|': '', '}': '', '¡': '', '£': '', '¤': '', '¥': '', '¦': '', '§': '', '«': '', '°': '', '»': '', '‘': '', '’': '', '“': '', '”': '', '\n': '-', ' ': '-', '-': '-', '–': '-', '—': '-', '/': '-', '\\': '-', '_': '-', '~': '-', 'À': 'A', 'Á': 'A', 'Â': 'A', 'Ã': 'A', 'Ä': 'A', 'Å': 'A', 'Æ': 'Ae', 'Ç': 'C', 'Ð': 'D', 'È': 'E', 'É': 'E', 'Ê': 'E', 'Ë': 'E', 'Ì': 'I', 'Í': 'I', 'Î': 'I', 'Ï': 'I', 'Ñ': 'N', 'Ò': 'O', 'Ó': 'O', 'Ô': 'O', 'Õ': 'O', 'Ö': 'O', 'Ø': 'O', 'Œ': 'Oe', 'Š': 'S', 'Þ': 'Th', 'Ù': 'U', 'Ú': 'U', 'Û': 'U', 'Ü': 'U', 'Ý': 'Y', 'à': 'a', 'á': 'a', 'â': 'a', 'ã': 'a', 'ä': 'ae', 'å': 'a', 'æ': 'ae', '¢': 'c', 'ç': 'c', 'ð': 'd', 'è': 'e', 'é': 'e', 'ê': 'e', 'ë': 'e', 'ì': 'i', 'í': 'i', 'î': 'i', 'ï': 'i', 'ñ': 'n', 'ò': 'o', 'ó': 'o', 'ô': 'o', 'õ': 'o', 'ö': 'oe', 'ø': 'o', 'œ': 'oe', 'š': 's', 'ß': 'ss', 'þ': 'th', 'ù': 'u', 'ú': 'u', 'û': 'u', 'ü': 'ue', 'ý': 'y', 'ÿ': 'y', 'Ÿ': 'y'
        };
        string = string.toLowerCase();
        for (char in translate) {
            if (translate.hasOwnProperty(char)) {
                string = string.split(char).join(translate[char]);
            }
        }
        return string.replace(/[^a-z0-9-]/g, '').replace(/^-+|-+$/g, '').replace(/-+/g, '-');
    },

    validateSlug: function (slug) {
        return slug.toLowerCase().replace(' ', '-').replace(/[^a-z0-9-]/g, '');
    },

    debounce: function (callback, delay, leading) {
        var context, args, result;
        var timer = null;

        function wrapper() {
            context = this;
            args = arguments;
            if (timer) {
                clearTimeout(timer);
            }
            if (leading && !timer) {
                result = callback.apply(context, args);
            }
            timer = setTimeout(function () {
                if (!leading) {
                    result = callback.apply(context, args);
                }
                timer = null;
            }, delay);
            return result;
        }

        return wrapper;
    },

    throttle: function (callback, delay) {
        var context, args, result;
        var previous = 0;
        var timer = null;

        function wrapper() {
            var now = Date.now();
            var remaining;
            if (previous === 0) {
                previous = now;
            }
            remaining = (previous + delay) - now;
            context = this;
            args = arguments;
            if (remaining <= 0 || remaining > delay) {
                if (timer) {
                    clearTimeout(timer);
                    timer = null;
                }
                previous = now;
                result = callback.apply(context, args);
            } else if (!timer){
                timer = setTimeout(function () {
                    previous = Date.now();
                    result = callback.apply(context, args);
                    timer = null;
                }, remaining);
            }
            return result;
        }

        return wrapper;
    },

    outerWidth: function (element) {
        var width = element.offsetWidth;
        var style = getComputedStyle(element);
        width += parseInt(style.marginLeft) + parseInt(style.marginRight);
        return width;
    },

    outerHeight: function (element) {
        var height = element.offsetHeight;
        var style = getComputedStyle(element);
        height += parseInt(style.marginTop) + parseInt(style.marginBottom);
        return height;
    },

    toggleElement: function (element, type) {
        var display = element.style.display || getComputedStyle(element).display;
        if (typeof type === 'undefined') {
            type = 'block';
        }
        if (display === 'none') {
            element.style.display = type;
        } else {
            element.style.display = 'none';
        }
    },

    extendObject: function (target) {
        var i, source, property;
        target = target || {};
        for (i = 1; i < arguments.length; i++) {
            source = arguments[i];
            for (property in source) {
                target[property] = source[property];
            }
        }
        return target;
    },

    serializeObject: function (object) {
        var property;
        var serialized = [];
        for (property in object) {
            if (object.hasOwnProperty(property)) {
                serialized.push(encodeURIComponent(property) + '=' + encodeURIComponent(object[property]));
            }
        }
        return serialized.join('&');
    },

    serializeForm: function (form) {
        var field, i, j;
        var serialized = [];
        for (i = 0; i < form.elements.length; i++) {
            field = form.elements[i];
            if (field.name && !field.disabled && field.type !== 'file' && field.type !== 'reset' && field.type !== 'submit' && field.type !== 'button') {
                if (field.type === 'select-multiple') {
                    for (j = form.elements[i].options.length - 1; j >= 0; j--) {
                        if (field.options[j].selected) {
                            serialized.push(encodeURIComponent(field.name) + '=' + encodeURIComponent(field.options[j].value));
                        }
                    }
                } else if ((field.type !== 'checkbox' && field.type !== 'radio') || field.checked) {
                    serialized.push(encodeURIComponent(field.name) + '=' + encodeURIComponent(field.value));
                }
            }
        }
        return serialized.join('&');
    },

    triggerEvent: function (target, type) {
        var event;
        try {
            event = new Event(type);
        } catch (error) {
            // The browser doesn't support Event constructor
            event = document.createEvent('HTMLEvents');
            event.initEvent(type, true, true);
        }
        target.dispatchEvent(event);
    },

    triggerDownload: function (uri, csrfToken) {
        var form = document.createElement('form');
        var input = document.createElement('input');
        form.action = uri;
        form.method = 'post';
        input.type = 'hidden';
        input.name = 'csrf-token';
        input.value = csrfToken;
        form.appendChild(input);
        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    },

    longClick: function (element, callback, timeout, interval) {
        var timer;
        function clear() {
            clearTimeout(timer);
        }
        element.addEventListener('mousedown', function (event) {
            var context = this;
            if (event.which !== 1) {
                clear();
            } else {
                callback.call(context, event);
                timer = setTimeout(function () {
                    timer = setInterval(callback.bind(context, event), interval);
                }, timeout);
            }
        });
        element.addEventListener('mouseout', clear);
        window.addEventListener('mouseup', clear);
    }
};
