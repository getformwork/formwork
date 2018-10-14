(function ($) {
    $.fn.datePicker = function (options) {
        var $input;
        var $calendar;

        var today = new Date();

        var calendar = {
            year: today.getFullYear(),
            month: today.getMonth(),
            day: today.getDate(),
            setDate: function (date) {
                this.year = date.getFullYear();
                this.month = date.getMonth();
                this.day = date.getDate();
            },
            lastDay: function () {
                this.day = helpers.daysInMonth(this.month, this.year);
            },
            prevYear: function () {
                this.year--;
            },
            nextYear: function () {
                this.year++;
            },
            prevMonth: function () {
                this.month = helpers.mod(this.month - 1, 12);
                if (this.month === 11) {
                    this.prevYear();
                }
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.lastDay();
                }
            },
            nextMonth: function () {
                this.month = helpers.mod(this.month + 1, 12);
                if (this.month === 0) {
                    this.nextYear();
                }
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.lastDay();
                }
            },
            prevWeek: function () {
                this.day -= 7;
                if (this.day < 1) {
                    this.prevMonth();
                    this.day += helpers.daysInMonth(this.month, this.year);
                }
            },
            nextWeek: function () {
                this.day += 7;
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.day -= helpers.daysInMonth(this.month, this.year);
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
                if (this.day > helpers.daysInMonth(this.month, this.year)) {
                    this.nextMonth();
                    this.day = 1;
                }
            }
        };

        var helpers = {
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
                    hours = helpers.mod(hours, 12) > 0 ? helpers.mod(hours, 12) : 12;
                }
                return format.replace('YYYY', year)
                    .replace('YY', year.toString().substr(-2))
                    .replace('MM', helpers.pad(month))
                    .replace('DD', helpers.pad(day))
                    .replace('hh', helpers.pad(hours))
                    .replace('mm', helpers.pad(minutes))
                    .replace('ss', helpers.pad(seconds))
                    .replace('a', am ? 'AM' : 'PM');
            }
        };

        options = $.extend({}, $.fn.datePicker.defaults, options);

        this.each(function () {
            var $this = $(this);
            var value = $this.val();
            $this.prop('readonly', true);
            $this.prop('size', options.format.length);
            if (helpers.isValidDate(value)) {
                value = new Date(value);
                $this.data('date', value);
                $this.val(helpers.formatDateTime(value));
            }
            $this.change(function () {
                if ($this.val() === '') {
                    $this.data('date', '');
                } else {
                    $this.val(helpers.formatDateTime($this.data('date')));
                }
            });
            $this.keydown(function (event) {
                var date = $(this).data('date');
                calendar.setDate(helpers.isValidDate(date) ? date : new Date());
                switch (event.which) {
                case 13: // enter
                    $('.calendar-day.selected').click();
                    $calendar.hide();
                    return false;
                case 8: // backspace
                    $this.val('');
                    $input.blur();
                    $calendar.hide();
                    return false;
                case 27: // escape
                    $input.blur();
                    $calendar.hide();
                    return false;
                case 37: // left arrow
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            calendar.prevYear();
                        } else {
                            calendar.prevMonth();
                        }
                    } else {
                        calendar.prevDay();
                    }
                    break;
                case 38: // up arrow
                    calendar.prevWeek();
                    break;
                case 39: // right arrow
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            calendar.nextYear();
                        } else {
                            calendar.nextMonth();
                        }
                    } else {
                        calendar.nextDay();
                    }
                    break;
                case 40: // down arrow
                    calendar.nextWeek();
                    break;
                case 48: // 0
                    if (event.ctrlKey || event.metaKey) {
                        var today = new Date();
                        calendar.setDate(today);
                    }
                    break;
                default:
                    return true;
                }
                updateInput();
                return false;
            });
        });

        $calendar = $('<div class="calendar"><div class="calendar-buttons"><button class="prevMonth"><i class="i-chevron-left"></i></button><button class="currentMonth">' + options.todayLabel + '</button><button class="nextMonth"><i class="i-chevron-right"></i></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>').appendTo('body');

        $('.currentMonth').click(function () {
            var today = new Date();
            calendar.setDate(today);
            updateInput();
            $input.blur();
        });

        $('.prevMonth').longclick(function () {
            calendar.prevMonth();
            generateTable(calendar.year, calendar.month);
        }, 750, 500);

        $('.nextMonth').longclick(function () {
            calendar.nextMonth();
            generateTable(calendar.year, calendar.month);
        }, 750, 500);

        $('.prevMonth, .currentMonth, .nextMonth').mousedown(function () {
            return false;
        });

        function updateInput() {
            var date = new Date(calendar.year, calendar.month, calendar.day);
            generateTable(calendar.year, calendar.month, calendar.day);
            $input.val(helpers.formatDateTime(date));
            $input.data('date', date);
        }

        $calendar.on('mousedown', '.calendar-day', false);

        $calendar.on('click', '.calendar-day', function () {
            var date = new Date(calendar.year, calendar.month, parseInt($(this).text()));
            $input.data('date', date);
            $input.val(helpers.formatDateTime(date));
            $input.blur();
        });

        function generateTable(year, month, day) {
            var num = 1;
            var firstDay = new Date(year, month, 1).getDay();
            var monthLength = helpers.daysInMonth(month, year);
            var monthName = options.monthLabels[month];
            var start = helpers.mod(firstDay - options.weekStarts, 7);
            var html = '<table class="calendar-table">';
            html += '<tr><th class="calendar-header" colspan="7">';
            html += monthName + '&nbsp;' + year;
            html += '</th></tr>';
            html += '<tr>';
            for(var i = 0; i < 7; i++ ){
                html += '<td class="calendar-header-day">';
                html += options.dayLabels[helpers.mod(i + options.weekStarts, 7)];
                html += '</td>';
            }
            html += '</tr><tr>';
            for (i = 0; i < 6; i++) {
                for (var j = 0; j < 7; j++) {
                    if (num <= monthLength && (i > 0 || j >= start)) {
                        if (num === day) {
                            html += '<td class="calendar-day selected">';
                        } else {
                            html += '<td class="calendar-day">';
                        }
                        html += num++;
                    } else if (num === 1) {
                        html += '<td class="calendar-prev-month-day">';
                        html += helpers.daysInMonth(helpers.mod(month - 1, 12), year) - start + j + 1;
                    } else {
                        html += '<td class="calendar-next-month-day">';
                        html += num++ - monthLength;
                    }
                    html += '</td>';
                }
                html += '</tr><tr>';
            }
            html += '</tr></table>';
            $('.calendar-table').replaceWith(html);
        }

        $('.date-input').blur(function () {
            $calendar.hide();
        });

        $('.date-input').focus(function () {
            $input = $(this);
            var date = helpers.isValidDate($input.data('date')) ? new Date($input.data('date')) : new Date();
            calendar.setDate(date);
            generateTable(calendar.year, calendar.month, calendar.day);
            $calendar.show();
            setPosition();
        });

        $(window).on('touchstart', function () {
            var $eventTarget = $(event.target);
            if (!$eventTarget.is('.date-input') && !$eventTarget.parents('.calendar, .date-input').length) {
                $input.blur();
            }
        });

        $(window).on('resize', Formwork.Utils.throttle(setPosition, 100));

        function setPosition() {
            if (!$input || !$calendar.is(':visible')) {
                return;
            }
            $calendar.css({
                top: $input.offset().top + $input.outerHeight(),
                left: $input.offset().left
            });
            if ($calendar.offset().left + $calendar.outerWidth(true) > $(window).width()) {
                $calendar.css('left', $(window).width() - $calendar.outerWidth(true));
            }
            if ($(window).scrollTop() + $(window).height() < $calendar.position().top + $calendar.outerHeight(true)) {
                $(window).scrollTop($calendar.position().top + $calendar.outerHeight(true) - $(window).height());
            }
        }

    };

    $.fn.datePicker.defaults = {
        dayLabels:  ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        monthLabels: ['January', 'February', 'March', 'April', 'May', 'June', 'July' ,'August', 'September', 'October', 'November', 'December'],
        weekStarts: 0,
        todayLabel: 'Today',
        format: 'YYYY-MM-DD'
    };

}(jQuery));
