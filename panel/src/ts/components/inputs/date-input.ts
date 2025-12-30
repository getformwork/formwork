import { $, $$ } from "../../utils/selectors";
import { calendarClock, chevronDown, chevronLeft, chevronRight, chevronUp } from "../icons";
import { getOuterHeight, getOuterWidth } from "../../utils/dimensions";
import { longClick } from "../../utils/events";
import { mod } from "../../utils/math";
import { throttle } from "../../utils/events";

interface DateInputOptions {
    weekStarts: number;
    dateFormat: string;
    dateTimeFormat: string;
    time: boolean;
    labels: {
        today: string;
        weekdays: {
            long: string[];
            short: string[];
        };
        months: {
            long: string[];
            short: string[];
        };
        prevMonth: string;
        nextMonth: string;
        prevHour: string;
        nextHour: string;
        prevMinute: string;
        nextMinute: string;
    };
    onChange: (date: Date, input: DateInput) => void;
}

export class DateInput {
    readonly element: HTMLInputElement;

    readonly options: DateInputOptions;

    readonly format: string;

    private calendar: Calendar;

    constructor(element: HTMLInputElement, options: Partial<DateInputOptions> = {}) {
        const defaults: DateInputOptions = {
            weekStarts: 0,
            dateFormat: "YYYY-MM-DD",
            dateTimeFormat: "YYYY-MM-DD HH:mm:ss",
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
                prevMonth: "Previous month",
                nextMonth: "Next month",
                prevHour: "Previous hour",
                nextHour: "Next hour",
                prevMinute: "Previous minute",
                nextMinute: "Next minute",
            },
            onChange(date: Date, input: DateInput) {
                input.element.value = input.formatDateTime(date, input.format);
                input.element.dispatchEvent(new Event("input", { bubbles: true }));
                input.element.dispatchEvent(new Event("change", { bubbles: true }));
            },
        };

        this.element = element;

        this.options = { ...defaults, ...options, time: this.element.dataset.time === "true" };

        this.format = this.options.time ? this.options.dateTimeFormat : this.options.dateFormat;

        this.calendar = new Calendar(this);

        this.initInput();
    }

    get name() {
        return this.element.name;
    }

    set name(value: string) {
        this.element.name = value;
    }

    get value() {
        return this.formatDateTime(this.calendar.date, "YYYY-MM-DD[T]HH:mm:ss").replace(":00$", "");
    }

    set value(value: string) {
        if (this.isValidDate(value)) {
            this.calendar.date = new Date(value);
            this.element.value = this.formatDateTime(this.calendar.date, this.format);
        } else {
            this.calendar.now();
            this.element.value = "";
        }
    }

    private initInput() {
        this.element.readOnly = true;
        this.element.size = this.format.length;

        this.value = this.element.value;

        this.element.addEventListener("focus", () => {
            this.calendar.show();
        });

        this.element.addEventListener("blur", () => {
            this.calendar.hide();
        });

        this.element.addEventListener("keydown", (event: KeyboardEvent) => {
            switch (event.key) {
                case "Backspace":
                    this.element.value = "";
                    this.element.blur();
                    this.element.dispatchEvent(new Event("input", { bubbles: true }));
                    this.element.dispatchEvent(new Event("change", { bubbles: true }));
                    break;
                case "Escape":
                    this.element.blur();
                    break;
                case "Tab":
                    this.element.blur();
                    return;
            }
            event.preventDefault();
        });
    }

    private isValidDate(date: string) {
        return date && !isNaN(Date.parse(date));
    }

    private formatDateTime(date: Date, format: string) {
        const regex = /\[([^\]]*)\]|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]/g;

        const weekStart = (date: Date, firstDay: number = this.options.weekStarts) => {
            let day = date.getDate();
            day -= mod(date.getDay() - firstDay, 7);
            return new Date(date.getFullYear(), date.getMonth(), day);
        };

        const weekNumberingYear = (date: Date) => {
            const year = date.getFullYear();
            const thisYearFirstWeekStart = weekStart(new Date(year, 0, 4), 1);
            const nextYearFirstWeekStart = weekStart(new Date(year + 1, 0, 4), 1);
            if (date.getTime() >= nextYearFirstWeekStart.getTime()) {
                return year + 1;
            } else if (date.getTime() >= thisYearFirstWeekStart.getTime()) {
                return year;
            }
            return year - 1;
        };

        const weekOfYear = (date: Date) => {
            const dateWeekNumberingYear = weekNumberingYear(date);
            const dateFirstWeekStart = weekStart(new Date(dateWeekNumberingYear, 0, 4), 1);
            const dateWeekStart = weekStart(date, 1);
            return Math.round((dateWeekStart.getTime() - dateFirstWeekStart.getTime()) / 604800000) + 1;
        };

        const splitTimezoneOffset = (offset: number) => {
            // Note that the offset returned by Date.getTimezoneOffset()
            // is positive if behind UTC and negative if ahead UTC
            const sign = offset > 0 ? "-" : "+";
            const hours = Math.floor(Math.abs(offset) / 60);
            const minutes = Math.abs(offset) % 60;
            return [sign + `${hours}`.padStart(2, "0"), `${minutes}`.padStart(2, "0")];
        };

        return format.replace(regex, (match: string, $1) => {
            switch (match) {
                case "YY":
                    return `${date.getFullYear()}`.substr(-2);
                case "YYYY":
                    return date.getFullYear();
                case "M":
                    return date.getMonth() + 1;
                case "MM":
                    return `${date.getMonth() + 1}`.padStart(2, "0");
                case "MMM":
                    return this.options.labels.months.short[date.getMonth()];
                case "MMMM":
                    return this.options.labels.months.long[date.getMonth()];
                case "D":
                    return date.getDate();
                case "DD":
                    return `${date.getDate()}`.padStart(2, "0");
                case "DDD":
                    return this.options.labels.weekdays.short[mod(date.getDay() + this.options.weekStarts, 7)];
                case "DDDD":
                    return this.options.labels.weekdays.long[mod(date.getDay() + this.options.weekStarts, 7)];
                case "W":
                    return weekOfYear(date);
                case "WW":
                    return `${weekOfYear(date)}`.padStart(2, "0");
                case "RR":
                    return weekNumberingYear(date).toString().substr(-2);
                case "RRRR":
                    return weekNumberingYear(date);
                case "H":
                    return mod(date.getHours(), 12) || 12;
                case "HH":
                    return `${mod(date.getHours(), 12) || 12}`.padStart(2, "0");
                case "h":
                    return date.getHours();
                case "hh":
                    return `${date.getHours()}`.padStart(2, "0");
                case "m":
                    return date.getMinutes();
                case "mm":
                    return `${date.getMinutes()}`.padStart(2, "0");
                case "s":
                    return date.getSeconds();
                case "ss":
                    return `${date.getSeconds()}`.padStart(2, "0");
                case "uuu":
                    return `${date.getMilliseconds()}`.padStart(3, "0");
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

class Calendar {
    private readonly input: DateInput;

    readonly element: HTMLElement;

    private year: number;
    private month: number;
    private day: number;
    private hours: number;
    private minutes: number;
    private seconds: number;

    constructor(input: DateInput) {
        this.input = input;
        this.element = this.createElement();
        this.date = new Date();
    }

    get date() {
        return new Date(this.year, this.month, this.day, this.hours, this.minutes, this.seconds);
    }

    set date(date: Date) {
        this.year = date.getFullYear();
        this.month = date.getMonth();
        this.day = date.getDate();
        this.hours = date.getHours();
        this.minutes = date.getMinutes();
        this.seconds = date.getSeconds();
        this.update();
    }

    get visible() {
        return getComputedStyle(this.element).display !== "none";
    }

    now() {
        this.date = new Date();
        this.update();
    }

    prevYear() {
        this.year--;
        this.update();
    }

    nextYear() {
        this.year++;
        this.update();
    }

    lastDayOfMonth() {
        this.day = this.daysInMonth(this.month, this.year);
        this.update();
    }

    prevMonth() {
        this.month = mod(this.month - 1, 12);
        if (this.month === 11) {
            this.prevYear();
        }
        if (this.day > this.daysInMonth(this.month, this.year)) {
            this.lastDayOfMonth();
        }
        this.update();
    }

    nextMonth() {
        this.month = mod(this.month + 1, 12);
        if (this.month === 0) {
            this.nextYear();
        }
        if (this.day > this.daysInMonth(this.month, this.year)) {
            this.lastDayOfMonth();
        }
        this.update();
    }

    prevWeek() {
        this.day -= 7;
        if (this.day < 1) {
            this.prevMonth();
            this.day += this.daysInMonth(this.month, this.year);
        }
        this.update();
    }

    nextWeek() {
        this.day += 7;
        if (this.day > this.daysInMonth(this.month, this.year)) {
            this.day -= this.daysInMonth(this.month, this.year);
            this.nextMonth();
        }
        this.update();
    }

    prevDay() {
        this.day--;
        if (this.day < 1) {
            this.prevMonth();
            this.lastDayOfMonth();
        }
        this.update();
    }

    nextDay() {
        this.day++;
        if (this.day > this.daysInMonth(this.month, this.year)) {
            this.nextMonth();
            this.day = 1;
        }
        this.update();
    }

    nextHour() {
        this.hours = mod(this.hours + 1, 24);
        if (this.hours === 0) {
            this.nextDay();
        }
        this.update();
    }

    prevHour() {
        this.hours = mod(this.hours - 1, 24);
        if (this.hours === 23) {
            this.prevDay();
        }
        this.update();
    }

    nextMinute() {
        this.minutes = mod(this.minutes + 1, 60);
        if (this.minutes === 0) {
            this.nextHour();
        }
        this.update();
    }

    prevMinute() {
        this.minutes = mod(this.minutes - 1, 60);
        if (this.minutes === 59) {
            this.prevHour();
        }
        this.update();
    }

    nextSecond() {
        this.seconds = mod(this.seconds + 1, 60);
        if (this.seconds === 0) {
            this.nextMinute();
        }
        this.update();
    }

    prevSecond() {
        this.seconds = mod(this.seconds - 1, 60);
        if (this.seconds === 59) {
            this.prevMinute();
        }
        this.update();
    }

    show() {
        this.update();
        this.element.style.display = "block";
        this.setCalendarPosition();
    }

    hide() {
        this.element.style.display = "none";
    }

    private createElement() {
        const element = document.createElement("div");
        element.className = "calendar";
        element.dataset.for = this.input.element.id;
        element.innerHTML = `<div class="calendar-buttons"><button type="button" class="prevMonth" aria-label="${this.input.options.labels.prevMonth}"></button><button class="currentMonth" aria-label="${this.input.options.labels.today}">${this.input.options.labels.today}</button><button type="button" class="nextMonth" aria-label="${this.input.options.labels.nextMonth}"></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>`;

        if (this.input.options.time) {
            element.innerHTML += `<div class="calendar-separator"></div><table class="calendar-time"><tr><td><button type="button" class="nextHour" aria-label="${this.input.options.labels.nextHour}"></button></td><td></td><td><button type="button" class="nextMinute" aria-label="${this.input.options.labels.nextMinute}"></button></td></tr><tr><td class="calendar-hours"></td><td>:</td><td class="calendar-minutes"></td><td class="calendar-meridiem"></td></tr><tr><td><button type="button" class="prevHour" aria-label="${this.input.options.labels.prevHour}"></button></td><td></td><td><button type="button" class="prevMinute" aria-label="${this.input.options.labels.prevMinute}"></button></td></tr></table></div>`;

            ($(".prevHour", element) as HTMLElement).innerHTML = chevronDown;
            ($(".nextHour", element) as HTMLElement).innerHTML = chevronUp;

            ($(".prevMinute", element) as HTMLElement).innerHTML = chevronDown;
            ($(".nextMinute", element) as HTMLElement).innerHTML = chevronUp;
        }

        ($(".currentMonth", element) as HTMLElement).insertAdjacentHTML("afterbegin", calendarClock);

        ($(".prevMonth", element) as HTMLElement).innerHTML = chevronLeft;
        ($(".nextMonth", element) as HTMLElement).innerHTML = chevronRight;

        ($(".currentMonth", element) as HTMLElement).addEventListener("click", (event) => {
            this.now();
            this.dispatchChange();
            event.preventDefault();
        });

        longClick(
            $(".prevMonth", element) as HTMLElement,
            (event) => {
                this.prevMonth();
                this.dispatchChange();
                event.preventDefault();
            },
            750,
            500,
        );

        longClick(
            $(".nextMonth", element) as HTMLElement,
            (event) => {
                this.nextMonth();
                this.dispatchChange();
                event.preventDefault();
            },
            750,
            500,
        );

        if (this.input.options.time) {
            longClick(
                $(".nextHour", element) as HTMLElement,
                (event) => {
                    this.nextHour();
                    this.dispatchChange();
                    event.preventDefault();
                },
                750,
                250,
            );

            longClick(
                $(".prevHour", element) as HTMLElement,
                (event) => {
                    this.prevHour();
                    this.dispatchChange();
                    event.preventDefault();
                },
                750,
                250,
            );

            longClick(
                $(".nextMinute", element) as HTMLElement,
                (event) => {
                    this.nextMinute();
                    this.dispatchChange();
                    event.preventDefault();
                },
                750,
                250,
            );

            longClick(
                $(".prevMinute", element) as HTMLElement,
                (event) => {
                    this.prevMinute();
                    this.dispatchChange();
                    event.preventDefault();
                },
                750,
                250,
            );
        }

        window.addEventListener(
            "resize",
            throttle(() => this.setCalendarPosition(), 100),
        );

        window.addEventListener("mousedown", (event) => {
            if (element.style.display !== "none") {
                if ((event.target as HTMLElement).closest(".calendar")) {
                    event.preventDefault();
                }
            }
        });

        window.addEventListener("keydown", (event) => {
            if (!this.visible) {
                return;
            }
            switch (event.key) {
                case "Enter":
                    ($(".calendar-day.selected", element) as HTMLElement).click();
                    this.hide();
                    break;
                case "Backspace":
                case "Escape":
                case "Tab":
                    this.hide();
                    break;
                case "ArrowLeft":
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            this.prevYear();
                        } else {
                            this.prevMonth();
                        }
                    } else {
                        this.prevDay();
                    }
                    this.dispatchChange();
                    break;
                case "ArrowUp":
                    this.prevWeek();
                    this.dispatchChange();
                    break;
                case "ArrowRight":
                    if (event.ctrlKey || event.metaKey) {
                        if (event.shiftKey) {
                            this.nextYear();
                        } else {
                            this.nextMonth();
                        }
                    } else {
                        this.nextDay();
                    }
                    this.dispatchChange();
                    break;
                case "ArrowDown":
                    this.nextWeek();
                    this.dispatchChange();
                    break;
                case "0":
                    if (event.ctrlKey || event.metaKey) {
                        this.now();
                    }
                    this.dispatchChange();
                    break;
                default:
                    return;
            }

            event.preventDefault();
        });

        document.body.appendChild(element);

        return element;
    }

    private dispatchChange() {
        this.input.options.onChange(this.date, this.input);
    }

    private update() {
        ($(".calendar-table", this.element) as HTMLElement).innerHTML = this.getInnerHTML();

        if (this.input.options.time) {
            ($(".calendar-hours", this.element) as HTMLElement).innerText = `${this.has12HourFormat(this.input.format) ? mod(this.hours, 12) || 12 : this.hours}`.padStart(2, "0");
            ($(".calendar-minutes", this.element) as HTMLElement).innerText = `${this.minutes}`.padStart(2, "0");
            ($(".calendar-meridiem", this.element) as HTMLElement).innerText = this.has12HourFormat(this.input.format) ? (this.hours < 12 ? "AM" : "PM") : "";
        }

        $$(".calendar-day", this.element).forEach((element) => {
            element.addEventListener("mousedown", (event) => {
                event.stopPropagation();
                event.preventDefault();
            });
            element.addEventListener("click", () => {
                this.day = parseInt(`${element.textContent}`);
                this.update();
                this.dispatchChange();
            });
        });
    }

    private setCalendarPosition() {
        if (!this.input?.element || !this.visible) {
            return;
        }

        const inputRect = this.input.element.getBoundingClientRect();
        const inputTop = inputRect.top + window.scrollY;
        const inputLeft = inputRect.left + window.scrollX;

        this.element.style.top = `${inputTop + this.input.element.offsetHeight}px`;
        this.element.style.left = `${inputLeft + this.input.element.offsetLeft}px`;

        const calendarRect = this.element.getBoundingClientRect();
        const calendarTop = calendarRect.top + window.scrollY;
        const calendarLeft = calendarRect.left + window.scrollX;
        const calendarWidth = getOuterWidth(this.element);
        const calendarHeight = getOuterHeight(this.element);

        const windowWidth = document.documentElement.clientWidth;
        const windowHeight = document.documentElement.clientHeight;

        if (calendarLeft + calendarWidth > windowWidth) {
            this.element.style.left = `${windowWidth - calendarWidth}px`;
        }

        if (calendarTop < window.scrollY || window.scrollY < calendarTop + calendarHeight - windowHeight) {
            window.scrollTo(window.scrollX, calendarTop + calendarHeight - windowHeight);
        }
    }

    private getInnerHTML() {
        const firstDay = new Date(this.year, this.month, 1).getDay();
        const start = mod(firstDay - this.input.options.weekStarts, 7);
        const monthLength = this.daysInMonth(this.month, this.year);

        let num = 1;
        let html = "";

        html += '<tr><th class="calendar-header" colspan="7">';
        html += `${this.input.options.labels.months.long[this.month]}&nbsp;${this.year}`;
        html += "</th></tr>";
        html += "<tr>";

        for (let i = 0; i < 7; i++) {
            html += '<td class="calendar-header-day">';
            html += this.input.options.labels.weekdays.short[mod(i + this.input.options.weekStarts, 7)];
            html += "</td>";
        }

        html += "</tr><tr>";

        for (let i = 0; i < 6; i++) {
            for (let j = 0; j < 7; j++) {
                if (num <= monthLength && (i > 0 || j >= start)) {
                    if (num === this.day) {
                        html += '<td class="calendar-day selected">';
                    } else {
                        html += '<td class="calendar-day">';
                    }
                    html += num++;
                } else if (num === 1) {
                    html += '<td class="calendar-prev-month-day">';
                    html += this.daysInMonth(mod(this.month - 1, 12), this.year) - start + j + 1;
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

    private has12HourFormat(format: string) {
        const match = format.match(/\[([^\]]*)\]|H{1,2}/);
        return match !== null && match[0][0] === "H";
    }

    private isLeapYear(year: number) {
        return (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;
    }

    private daysInMonth(month: number, year: number) {
        const daysInMonth = [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
        return month === 1 && this.isLeapYear(year) ? 29 : daysInMonth[month];
    }
}
