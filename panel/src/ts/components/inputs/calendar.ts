import { effect } from "@preact/signals-core";
import { CalendarModel, type CalendarState, daysInMonth } from "../../models/calendar";
import { getOuterHeight, getOuterWidth } from "../../utils/dimensions";
import { longClick, throttle } from "../../utils/events";
import { mod } from "../../utils/math";
import { $, $$ } from "../../utils/selectors";
import { calendarClock, chevronDown, chevronLeft, chevronRight, chevronUp } from "../ui/icons";
import type { DateInput } from "./date-input";

/**
 * Represents the calendar of a date input
 */
export class Calendar {
    /**
     * Date input of the calendar
     */
    private readonly input: DateInput;

    /**
     * Element of the calendar
     */
    readonly element: HTMLElement;

    /**
     * Selected date and visibility of the calendar
     */
    private readonly model = new CalendarModel();

    /**
     * Create the calendar of a date input
     */
    constructor(input: DateInput) {
        this.input = input;
        this.element = this.createElement();

        this.bindButtons();
        this.bindWindowEvents();

        document.body.appendChild(this.element);

        effect(() => this.render(this.model.state.value));

        effect(() => {
            if (this.model.shown.value) {
                this.element.style.display = "block";
                this.setCalendarPosition();
            } else if (this.element.style.display === "block") {
                this.element.style.display = "none";
            }
        });
    }

    /**
     * Get the selected date
     */
    get date() {
        return this.model.toDate();
    }

    /**
     * Set the selected date
     */
    set date(date: Date) {
        this.model.setDate(date);
    }

    /**
     * Return whether the calendar is visible
     */
    get visible() {
        return this.model.shown.value;
    }

    /**
     * Select the current date and time
     */
    now() {
        this.model.now();
    }

    /**
     * Show the calendar
     */
    show() {
        this.model.show();
    }

    /**
     * Hide the calendar
     */
    hide() {
        this.model.hide();
    }

    /**
     * Create the element of the calendar, with the buttons to change month and, if the input has
     * the time, hours and minutes
     */
    private createElement() {
        const { labels, time } = this.input.options;

        const element = document.createElement("div");
        element.className = "calendar";
        element.dataset.for = this.input.element.id;
        element.innerHTML = `<div class="calendar-buttons"><button type="button" class="prevMonth" aria-label="${labels.prevMonth}"></button><button class="currentMonth" aria-label="${labels.today}">${labels.today}</button><button type="button" class="nextMonth" aria-label="${labels.nextMonth}"></button></div><div class="calendar-separator"></div><table class="calendar-table"></table>`;

        if (time) {
            element.innerHTML += `<div class="calendar-separator"></div><table class="calendar-time"><tr><td><button type="button" class="nextHour" aria-label="${labels.nextHour}"></button></td><td></td><td><button type="button" class="nextMinute" aria-label="${labels.nextMinute}"></button></td></tr><tr><td class="calendar-hours"></td><td>:</td><td class="calendar-minutes"></td><td class="calendar-meridiem"></td></tr><tr><td><button type="button" class="prevHour" aria-label="${labels.prevHour}"></button></td><td></td><td><button type="button" class="prevMinute" aria-label="${labels.prevMinute}"></button></td></tr></table></div>`;

            ($(".prevHour", element) as HTMLElement).innerHTML = chevronDown;
            ($(".nextHour", element) as HTMLElement).innerHTML = chevronUp;

            ($(".prevMinute", element) as HTMLElement).innerHTML = chevronDown;
            ($(".nextMinute", element) as HTMLElement).innerHTML = chevronUp;
        }

        ($(".currentMonth", element) as HTMLElement).insertAdjacentHTML("afterbegin", calendarClock);

        ($(".prevMonth", element) as HTMLElement).innerHTML = chevronLeft;
        ($(".nextMonth", element) as HTMLElement).innerHTML = chevronRight;

        return element;
    }

    /**
     * Bind the buttons of the calendar: a long press repeats the change
     */
    private bindButtons() {
        ($(".currentMonth", this.element) as HTMLElement).addEventListener("click", (event) => {
            this.now();
            this.dispatchChange();
            event.preventDefault();
        });

        this.bindLongClick(".prevMonth", () => this.model.prevMonth(), 500);
        this.bindLongClick(".nextMonth", () => this.model.nextMonth(), 500);

        if (this.input.options.time) {
            this.bindLongClick(".nextHour", () => this.model.nextHour(), 250);
            this.bindLongClick(".prevHour", () => this.model.prevHour(), 250);
            this.bindLongClick(".nextMinute", () => this.model.nextMinute(), 250);
            this.bindLongClick(".prevMinute", () => this.model.prevMinute(), 250);
        }
    }

    /**
     * Change the date when a button is pressed, and repeat the change while it stays pressed
     *
     * @param selector Selector of the button
     * @param change   Change of the date
     * @param interval Interval in milliseconds between repeated changes
     */
    private bindLongClick(selector: string, change: () => void, interval: number) {
        longClick(
            $(selector, this.element) as HTMLElement,
            (event) => {
                change();
                this.dispatchChange();
                event.preventDefault();
            },
            750,
            interval,
        );
    }

    /**
     * Keep the calendar in place and let the user change the date with the keyboard
     */
    private bindWindowEvents() {
        window.addEventListener(
            "resize",
            throttle(() => this.setCalendarPosition(), 100),
        );

        window.addEventListener("mousedown", (event) => {
            if (this.element.style.display !== "none") {
                if ((event.target as HTMLElement).closest(".calendar")) {
                    event.preventDefault();
                }
            }
        });

        window.addEventListener("keydown", (event) => this.handleKeydown(event));
    }

    /**
     * Change the date with the arrows, pick it with Enter and close the calendar with Backspace,
     * Escape or Tab
     */
    private handleKeydown(event: KeyboardEvent) {
        if (!this.visible) {
            return;
        }

        const navigate = this.getNavigation(event);

        if (navigate) {
            navigate();
            this.dispatchChange();
            event.preventDefault();
            return;
        }

        switch (event.key) {
            case "Enter":
                ($(".calendar-day.selected", this.element) as HTMLElement).click();
                this.hide();
                break;

            case "Backspace":
            case "Escape":
            case "Tab":
                this.hide();
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
    }

    /**
     * Get the change of the date for an arrow key
     *
     * Left and right move by a day, or by a month (by a year with Shift) with Ctrl or Cmd; up and
     * down move by a week
     */
    private getNavigation(event: KeyboardEvent) {
        const byMonth = event.ctrlKey || event.metaKey;

        switch (event.key) {
            case "ArrowLeft":
                if (byMonth) {
                    return event.shiftKey ? () => this.model.prevYear() : () => this.model.prevMonth();
                }
                return () => this.model.prevDay();

            case "ArrowRight":
                if (byMonth) {
                    return event.shiftKey ? () => this.model.nextYear() : () => this.model.nextMonth();
                }
                return () => this.model.nextDay();

            case "ArrowUp":
                return () => this.model.prevWeek();

            case "ArrowDown":
                return () => this.model.nextWeek();
        }
    }

    /**
     * Notify the input that the date has changed
     */
    private dispatchChange() {
        this.input.options.onChange(this.date, this.input);
    }

    /**
     * Render the days of the month, and the time if the input has one
     */
    private render(state: CalendarState) {
        ($(".calendar-table", this.element) as HTMLElement).innerHTML = this.getInnerHTML(state);

        if (this.input.options.time) {
            const has12HourFormat = this.has12HourFormat(this.input.format);
            const hours = has12HourFormat ? mod(state.hours, 12) || 12 : state.hours;

            ($(".calendar-hours", this.element) as HTMLElement).textContent = `${hours}`.padStart(2, "0");
            ($(".calendar-minutes", this.element) as HTMLElement).textContent = `${state.minutes}`.padStart(2, "0");
            ($(".calendar-meridiem", this.element) as HTMLElement).textContent = has12HourFormat ? (state.hours < 12 ? "AM" : "PM") : "";
        }

        $$(".calendar-day", this.element).forEach((element) => {
            element.addEventListener("mousedown", (event) => {
                event.stopPropagation();
                event.preventDefault();
            });
            element.addEventListener("click", () => {
                this.model.setDay(parseInt(`${element.textContent}`, 10));
                this.dispatchChange();
            });
        });
    }

    /**
     * Position the calendar below the input, inside the viewport
     */
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

    /**
     * Get the table of the days of a month
     */
    private getInnerHTML({ year, month, day }: CalendarState) {
        const firstDay = new Date(year, month, 1).getDay();
        const start = mod(firstDay - this.input.options.weekStarts, 7);
        const monthLength = daysInMonth(month, year);

        let num = 1;
        let html = "";

        html += '<tr><th class="calendar-header" colspan="7">';
        html += `${this.input.options.labels.months.long[month]}&nbsp;${year}`;
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

    /**
     * Return whether a format uses the 12-hour clock
     */
    private has12HourFormat(format: string) {
        const match = format.match(/\[([^\]]*)\]|H{1,2}/);
        return match !== null && match[0][0] === "H";
    }
}
