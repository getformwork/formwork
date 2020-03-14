import Utils from './utils';

export default function DatePicker(input, options) {
    var defaults = {
        dayLabels:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        monthLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
        weekStarts: 0,
        todayLabel: 'Today',
        format: 'YYYY-MM-DD'
    };

    var today = new Date();
    var dateKeeper, dateHelpers, calendar;

    options = Utils.extendObject({}, defaults, options);

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

        Utils.longClick($('.prevMonth', calendar), function (event) {
            dateKeeper.prevMonth();
            generateCalendarTable(dateKeeper.year, dateKeeper.month);
            event.preventDefault();
        }, 750, 500);

        Utils.longClick($('.nextMonth', calendar), function (event) {
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

        window.addEventListener('resize', Utils.throttle(setCalendarPosition, 100));

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
        calendarWidth = Utils.outerWidth(calendar);
        calendarHeight = Utils.outerHeight(calendar);

        windowWidth = document.documentElement.clientWidth;
        windowHeight = document.documentElement.clientHeight;

        if (calendarLeft + calendarWidth > windowWidth) {
            calendar.style.left = (windowWidth - calendarWidth) + 'px';
        }

        if (calendarTop < window.pageYOffset || window.pageYOffset < calendarTop + calendarHeight - windowHeight) {
            window.scrollTo(window.pageXOffset, calendarTop + calendarHeight - windowHeight);
        }
    }
}
