import { $, $$ } from "../../utils/selectors";
import { getOuterHeight, getOuterWidth } from "../../utils/dimensions";
import { insertIcon } from "../icons";
import { throttle } from "../../utils/events";

const inputValues = {};

function handleLongClick(element, callback, timeout, interval) {
    let timer;
    function clear() {
        clearTimeout(timer);
    }
    element.addEventListener("mousedown", function (event) {
        const context = this;
        if (event.button !== 0) {
            clear();
        } else {
            callback.call(context, event);
            timer = setTimeout(() => (timer = setInterval(callback.bind(context, event), interval)), timeout);
        }
    });
    element.addEventListener("mouseout", clear);
    window.addEventListener("mouseup", clear);
}

export class DateInput {
    constructor(input, options) {
        const defaults = {
            weekStarts: 0,
            format: "YYYY-MM-DD",
            time: false,
            labels: {
                today: "Today",
                weekdays: {
                    long: ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"],
                    short: ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"],
                },
                months: {
                    long: ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"],
                    short: ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"],
                },
            },
        };

        options = Object.assign({}, defaults, options);

        inputValues[input.id] = new Date();

        const calendar = new Calendar($(".calendar"), inputValues[input.id]);

        options.onChange = (date) => {
            const dateInput = getCurrentInput();
            if (dateInput !== null) {
                inputValues[dateInput.id] = date;
                dateInput.value = formatDateTime(date);
            }
        };

        initInput();

        function initInput() {
            const value = input.value;

            input.readOnly = true;
            input.size = options.format.length;

            if (isValidDate(value)) {
                inputValues[input.id] = new Date(value);
                input.value = formatDateTime(inputValues[input.id]);
            }

            input.addEventListener("focus", () => {
                calendar.gotoDate(inputValues[input.id]);
                calendar.show();
            });

            input.addEventListener("blur", () => {
                calendar.hide();
            });

            input.addEventListener("keydown", (event) => {
                switch (event.key) {
                    case "Backspace":
                        input.value = "";
                        input.blur();
                        break;
                    case "Escape":
                        input.blur();
                        break;
                    case "Tab":
                        input.blur();
                        return;
                }
                event.preventDefault();
            });
        }

        function getCurrentInput() {
            const currentElement = document.activeElement;
            return currentElement.matches(".input-date") ? currentElement : null;
        }

        function Calendar(element, date) {
            let year, month, day, hours, minutes, seconds;

            element = element || createElement();

            setDate(date);

            function setDate(date) {
                year = date.getFullYear();
                month = date.getMonth();
                day = date.getDate();
                hours = date.getHours();
                minutes = date.getMinutes();
                seconds = date.getSeconds();
            }

            function gotoDate(date) {
                setDate(date);
                update();
            }

            function getDate() {
                return new Date(year, month, day, hours, minutes, seconds);
            }

            function getElement() {
                return element;
            }

            function setNow() {
                setDate(new Date());
            }

            function now() {
                setNow();
                update();
            }

            function setPrevYear() {
                year--;
            }

            function prevYear() {
                setPrevYear();
                update();
            }

            function setNextYear() {
                year++;
            }

            function nextYear() {
                setNextYear();
                update();
            }

            function setLastDayOfMonth() {
                day = daysInMonth(month, year);
            }

            function lastDayOfMonth() {
                setLastDayOfMonth();
                update();
            }

            function setPrevMonth() {
                month = mod(month - 1, 12);
                if (month === 11) {
                    setPrevYear();
                }
                if (day > daysInMonth(month, year)) {
                    setLastDayOfMonth();
                }
            }

            function prevMonth() {
                setPrevMonth();
                update();
            }

            function setNextMonth() {
                month = mod(month + 1, 12);
                if (month === 0) {
                    setNextYear();
                }
                if (day > daysInMonth(month, year)) {
                    setLastDayOfMonth();
                }
            }

            function nextMonth() {
                setNextMonth();
                update();
            }

            function setPrevWeek() {
                day -= 7;
                if (day < 1) {
                    setPrevMonth();
                    day += daysInMonth(month, year);
                }
            }

            function prevWeek() {
                setPrevWeek();
                update();
            }

            function setNextWeek() {
                day += 7;
                if (day > daysInMonth(month, year)) {
                    day -= daysInMonth(month, year);
                    setNextMonth();
                }
            }

            function nextWeek() {
                setNextWeek();
                update();
            }

            function setPrevDay() {
                day--;
                if (day < 1) {
                    setPrevMonth();
                    setLastDayOfMonth();
                }
            }

            function prevDay() {
                setPrevDay();
                update();
            }

            function setNextDay() {
                day++;
                if (day > daysInMonth(month, year)) {
                    setNextMonth();
                    day = 1;
                }
            }

            function nextDay() {
                setNextDay();
                update();
            }

            function setNextHour() {
                hours = mod(hours + 1, 24);
                if (hours === 0) {
                    setNextDay();
                }
            }

            function nextHour() {
                setNextHour();
                update();
            }

            function setPrevHour() {
                hours = mod(hours - 1, 24);
                if (hours === 23) {
                    setPrevDay();
                }
            }

            function prevHour() {
                setPrevHour();
                update();
            }

            function setNextMinute() {
                minutes = mod(minutes + 1, 60);
                if (minutes === 0) {
                    setNextHour();
                }
            }

            function nextMinute() {
                setNextMinute();
                update();
            }

            function setPrevMinute() {
                minutes = mod(minutes - 1, 60);
                if (minutes === 59) {
                    setPrevHour();
                }
            }

            function prevMinute() {
                setPrevMinute();
                update();
            }

            function setNextSecond() {
                seconds = mod(seconds + 1, 60);
                if (seconds === 0) {
                    setNextMinute();
                }
            }

            function nextSecond() {
                setNextSecond();
                update();
            }

            function setPrevSecond() {
                seconds = mod(seconds - 1, 60);
                if (seconds === 59) {
                    setPrevMinute();
                }
            }

            function prevSecond() {
                setPrevSecond();
                update();
            }

            function show() {
                element.style.display = "block";
                setCalendarPosition();
            }

            function hide() {
                element.style.display = "none";
            }

            function isVisible() {
                return getComputedStyle(element).display !== "none";
            }

            function update() {
                $(".calendar-table", element).innerHTML = getInnerHTML();

                setEvents();

                if (options.time) {
                    updateTime();
                }

                function getInnerHTML() {
                    const firstDay = new Date(year, month, 1).getDay();
                    const start = mod(firstDay - options.weekStarts, 7);
                    const monthLength = daysInMonth(month, year);

                    let num = 1;
                    let html = "";

                    html += '<tr><th class="calendar-header" colspan="7">';
                    html += `${options.labels.months.long[month]}&nbsp;${year}`;
                    html += "</th></tr>";
                    html += "<tr>";

                    for (let i = 0; i < 7; i++) {
                        html += '<td class="calendar-header-day">';
                        html += options.labels.weekdays.short[mod(i + options.weekStarts, 7)];
                        html += "</td>";
                    }

                    html += "</tr><tr>";

                    for (let i = 0; i < 6; i++) {
                        for (let j = 0; j < 7; j++) {
                            if (num <= monthLength && (i > 0 || j >= start)) {
                                if (num === day) {
                                    html += '<td class="calendar-day selected">';
                                } else {
                                    html += '<td class="calendar-day">';
                                }
                                html += num++;
                            } else if (num === 1) {
                                html += '<td class="calendar-prev-month-day">';
                                html += daysInMonth(mod(month - 1, 12), year) - start + j + 1;
                            } else {
                                html += '<td class="calendar-next-month-day">';
                                html += num++ - monthLength;
                            }
                            html += "</td>";
                        }
                        html += "</tr><tr>";
                    }
                    html += "</tr>";

                    return html;
                }

                function setEvents() {
                    $$(".calendar-day", element).forEach((element) => {
                        element.addEventListener("mousedown", (event) => {
                            event.stopPropagation();
                            event.preventDefault();
                        });
                        element.addEventListener("click", () => {
                            day = parseInt(element.textContent);
                            update();
                            options.onChange(getDate());
                        });
                    });
                }

                function updateTime() {
                    $(".calendar-hours", element).innerHTML = pad(has12HourFormat(options.format) ? mod(hours, 12) || 12 : hours, 2);
                    $(".calendar-minutes", element).innerHTML = pad(minutes, 2);
                    $(".calendar-meridiem", element).innerHTML = has12HourFormat(options.format) ? (hours < 12 ? "AM" : "PM") : "";
                }
            }

            function createElement() {
                const element = document.createElement("div");
                element.className = "calendar";
                element.innerHTML = `<div class="calendar-buttons"><button type="button" class="prevMonth"></button><button class="currentMonth">${options.labels.today}</button><button type="button" class="nextMonth"></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>`;

                if (options.time) {
                    element.innerHTML += '<div class="calendar-separator"></div><table class="calendar-time"><tr><td><button type="button" class="nextHour"></button></td><td></td><td><button type="button" class="nextMinute"></button></td></tr><tr><td class="calendar-hours"></td><td>:</td><td class="calendar-minutes"></td><td class="calendar-meridiem"></td></tr><tr><td><button type="button" class="prevHour"></button></td><td></td><td><button type="button" class="prevMinute"></button></td></tr></table></div>';

                    insertIcon("chevron-down", $(".prevHour", element));
                    insertIcon("chevron-up", $(".nextHour", element));

                    insertIcon("chevron-down", $(".prevMinute", element));
                    insertIcon("chevron-up", $(".nextMinute", element));
                }

                insertIcon("calendar-clock", $(".currentMonth", element));

                insertIcon("chevron-left", $(".prevMonth", element));
                insertIcon("chevron-right", $(".nextMonth", element));

                $(".currentMonth", element).addEventListener("mousedown", (event) => {
                    now();
                    options.onChange(getDate());
                    event.preventDefault();
                });

                handleLongClick(
                    $(".prevMonth", element),
                    (event) => {
                        prevMonth();
                        options.onChange(getDate());
                        event.preventDefault();
                    },
                    750,
                    500,
                );

                handleLongClick(
                    $(".nextMonth", element),
                    (event) => {
                        nextMonth();
                        options.onChange(getDate());
                        event.preventDefault();
                    },
                    750,
                    500,
                );

                if (options.time) {
                    handleLongClick(
                        $(".nextHour", element),
                        (event) => {
                            nextHour();
                            options.onChange(getDate());
                            event.preventDefault();
                        },
                        750,
                        250,
                    );

                    handleLongClick(
                        $(".prevHour", element),
                        (event) => {
                            prevHour();
                            options.onChange(getDate());
                            event.preventDefault();
                        },
                        750,
                        250,
                    );

                    handleLongClick(
                        $(".nextMinute", element),
                        (event) => {
                            nextMinute();
                            options.onChange(getDate());
                            event.preventDefault();
                        },
                        750,
                        250,
                    );

                    handleLongClick(
                        $(".prevMinute", element),
                        (event) => {
                            prevMinute();
                            options.onChange(getDate());
                            event.preventDefault();
                        },
                        750,
                        250,
                    );
                }

                window.addEventListener("resize", throttle(setCalendarPosition, 100));

                window.addEventListener("mousedown", (event) => {
                    if (element.style.display !== "none") {
                        if (event.target.closest(".calendar")) {
                            event.preventDefault();
                        }
                    }
                });

                window.addEventListener("keydown", (event) => {
                    if (!isVisible()) {
                        return;
                    }
                    switch (event.key) {
                        case "Enter":
                            $(".calendar-day.selected", element).click();
                            hide();
                            break;
                        case "Backspace":
                        case "Escape":
                        case "Tab":
                            hide();
                            break;
                        case "ArrowLeft":
                            if (event.ctrlKey || event.metaKey) {
                                if (event.shiftKey) {
                                    prevYear();
                                } else {
                                    prevMonth();
                                }
                            } else {
                                prevDay();
                            }
                            options.onChange(getDate());
                            break;
                        case "ArrowUp":
                            prevWeek();
                            options.onChange(getDate());
                            break;
                        case "ArrowRight":
                            if (event.ctrlKey || event.metaKey) {
                                if (event.shiftKey) {
                                    nextYear();
                                } else {
                                    nextMonth();
                                }
                            } else {
                                nextDay();
                            }
                            options.onChange(getDate());
                            break;
                        case "ArrowDown":
                            nextWeek();
                            options.onChange(getDate());
                            break;
                        case "0":
                            if (event.ctrlKey || event.metaKey) {
                                now();
                            }
                            options.onChange(getDate());
                            break;
                        default:
                            return;
                    }

                    event.preventDefault();
                });

                document.body.appendChild(element);

                return element;
            }

            return {
                setDate,
                gotoDate,
                getDate,
                getElement,
                now,
                prevYear,
                nextYear,
                lastDayOfMonth,
                prevMonth,
                nextMonth,
                prevWeek,
                nextWeek,
                prevDay,
                nextDay,
                nextHour,
                prevHour,
                nextMinute,
                prevMinute,
                nextSecond,
                prevSecond,
                show,
                hide,
                isVisible,
            };
        }

        function setCalendarPosition() {
            const input = getCurrentInput();

            if (!input || !calendar.isVisible()) {
                return;
            }

            const inputRect = input.getBoundingClientRect();
            const inputTop = inputRect.top + window.scrollY;
            const inputLeft = inputRect.left + window.scrollX;

            const calendarElement = calendar.getElement();
            calendarElement.style.top = `${inputTop + input.offsetHeight}px`;
            calendarElement.style.left = `${inputLeft + input.offsetLeft}px`;

            const calendarRect = calendarElement.getBoundingClientRect();
            const calendarTop = calendarRect.top + window.scrollY;
            const calendarLeft = calendarRect.left + window.scrollX;
            const calendarWidth = getOuterWidth(calendarElement);
            const calendarHeight = getOuterHeight(calendarElement);

            const windowWidth = document.documentElement.clientWidth;
            const windowHeight = document.documentElement.clientHeight;

            if (calendarLeft + calendarWidth > windowWidth) {
                calendarElement.style.left = `${windowWidth - calendarWidth}px`;
            }

            if (calendarTop < window.scrollY || window.scrollY < calendarTop + calendarHeight - windowHeight) {
                window.scrollTo(window.scrollX, calendarTop + calendarHeight - windowHeight);
            }
        }

        function mod(x, y) {
            // Return x mod y (always rounded downwards, differs from x % y which is the remainder)
            return x - y * Math.floor(x / y);
        }

        function pad(num, length) {
            let result = num.toString();
            while (result.length < length) {
                result = `0${result}`;
            }
            return result;
        }

        function isValidDate(date) {
            return date && !isNaN(Date.parse(date));
        }

        function isLeapYear(year) {
            return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
        }

        function daysInMonth(month, year) {
            const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
            return month === 1 && isLeapYear(year) ? 29 : daysInMonth[month];
        }

        function weekStart(date, firstDay = options.weekStarts) {
            let day = date.getDate();
            day -= mod(date.getDay() - firstDay, 7);
            return new Date(date.getFullYear(), date.getMonth(), day);
        }

        function weekNumberingYear(date) {
            const year = date.getFullYear();
            const thisYearFirstWeekStart = weekStart(new Date(year, 0, 4), 1);
            const nextYearFirstWeekStart = weekStart(new Date(year + 1, 0, 4), 1);
            if (date.getTime() >= nextYearFirstWeekStart.getTime()) {
                return year + 1;
            } else if (date.getTime() >= thisYearFirstWeekStart.getTime()) {
                return year;
            }
            return year - 1;
        }

        function weekOfYear(date) {
            const weekNumberingYear = weekNumberingYear(date);
            const firstWeekStart = weekStart(new Date(weekNumberingYear, 0, 4), 1);
            const weekStart = weekStart(date, 1);
            return Math.round((weekStart.getTime() - firstWeekStart.getTime()) / 604800000) + 1;
        }

        function has12HourFormat(format) {
            const match = format.match(/\[([^\]]*)\]|H{1,2}/);
            return match !== null && match[0][0] === "H";
        }

        function formatDateTime(date, format = options.format) {
            const regex = /\[([^\]]*)\]|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]/g;

            function splitTimezoneOffset(offset) {
                // Note that the offset returned by Date.getTimezoneOffset()
                // is positive if behind UTC and negative if ahead UTC
                const sign = offset > 0 ? "-" : "+";
                const hours = Math.floor(Math.abs(offset) / 60);
                const minutes = Math.abs(offset) % 60;
                return [sign + pad(hours, 2), pad(minutes, 2)];
            }

            return format.replace(regex, (match, $1) => {
                switch (match) {
                    case "YY":
                        return date.getFullYear().toString().substr(-2);
                    case "YYYY":
                        return date.getFullYear();
                    case "M":
                        return date.getMonth() + 1;
                    case "MM":
                        return pad(date.getMonth() + 1, 2);
                    case "MMM":
                        return options.labels.months.short[date.getMonth()];
                    case "MMMM":
                        return options.labels.months.long[date.getMonth()];
                    case "D":
                        return date.getDate();
                    case "DD":
                        return pad(date.getDate(), 2);
                    case "DDD":
                        return options.labels.weekdays.short[mod(date.getDay() + options.weekStarts, 7)];
                    case "DDDD":
                        return options.labels.weekdays.long[mod(date.getDay() + options.weekStarts, 7)];
                    case "W":
                        return weekOfYear(date);
                    case "WW":
                        return pad(weekOfYear(date), 2);
                    case "RR":
                        return weekNumberingYear(date).toString().substr(-2);
                    case "RRRR":
                        return weekNumberingYear(date);
                    case "H":
                        return mod(date.getHours(), 12) || 12;
                    case "HH":
                        return pad(mod(date.getHours(), 12) || 12, 2);
                    case "h":
                        return date.getHours();
                    case "hh":
                        return pad(date.getHours(), 2);
                    case "m":
                        return date.getMinutes();
                    case "mm":
                        return pad(date.getMinutes(), 2);
                    case "s":
                        return date.getSeconds();
                    case "ss":
                        return pad(date.getSeconds(), 2);
                    case "uuu":
                        return pad(date.getMilliseconds(), 3);
                    case "A":
                        return date.getHours() < 12 ? "AM" : "PM";
                    case "a":
                        return date.getHours() < 12 ? "am" : "pm";
                    case "Z":
                        return splitTimezoneOffset(date.getTimezoneOffset()).join(":");
                    case "z":
                        return splitTimezoneOffset(date.getTimezoneOffset()).join("");
                    default:
                        return $1 || match;
                }
            });
        }
    }
}
