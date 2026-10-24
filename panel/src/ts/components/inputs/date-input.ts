import { app } from "../../services/app";
import { formatDateTime } from "../../utils/date";
import { Calendar } from "./calendar";
import { InputBase } from "./input";

/**
 * Holds the options of a date input
 */
export interface DateInputOptions {
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

/**
 * Provides a date input with a calendar to pick the date and, optionally, the time
 */
export class DateInput extends InputBase<HTMLInputElement> {
    /**
     * Options of the input
     */
    readonly options: DateInputOptions;

    /**
     * Format of the date
     */
    readonly format: string;

    /**
     * Calendar of the input
     */
    private calendar: Calendar;

    /**
     * Create the date input
     */
    constructor(element: HTMLInputElement, options: Partial<DateInputOptions> = {}) {
        const defaults: DateInputOptions = {
            weekStarts: 0,
            dateFormat: "YYYY-MM-DD",
            dateTimeFormat: "YYYY-MM-DD HH:mm:ss",
            time: false,
            labels: {
                today: app.translation.get("date.today", "Today"),
                weekdays: {
                    long: app.translation.getStrings("date.weekdays.long", ["Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"]),
                    short: app.translation.getStrings("date.weekdays.short", ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"]),
                },
                months: {
                    long: app.translation.getStrings("date.months.long", ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]),
                    short: app.translation.getStrings("date.months.short", ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"]),
                },
                prevMonth: app.translation.get("fields.date.previousMonth", "Previous month"),
                nextMonth: app.translation.get("fields.date.nextMonth", "Next month"),
                prevHour: app.translation.get("fields.date.previousHour", "Previous hour"),
                nextHour: app.translation.get("fields.date.nextHour", "Next hour"),
                prevMinute: app.translation.get("fields.date.previousMinute", "Previous minute"),
                nextMinute: app.translation.get("fields.date.nextMinute", "Next minute"),
            },
            onChange(date: Date, input: DateInput) {
                input.element.value = input.formatDateTime(date, input.format);
                input.dispatchChange();
            },
        };

        super(element);

        this.options = { ...defaults, ...options, time: this.element.dataset.time === "true" };

        this.format = this.options.time ? this.options.dateTimeFormat : this.options.dateFormat;

        this.calendar = new Calendar(this);

        this.initInput();
    }

    /**
     * Get the date as `YYYY-MM-DDThh:mm:ss`, or an empty string if there is no date
     */
    get value() {
        if (!this.element.value) {
            return "";
        }

        return this.formatDateTime(this.calendar.date, "YYYY-MM-DD[T]hh:mm:ss");
    }

    /**
     * Set the date from a string, clearing the input if it is not a valid date
     */
    set value(value: string) {
        if (this.isValidDate(value)) {
            this.calendar.date = new Date(value);
            this.element.value = this.formatDateTime(this.calendar.date, this.format);
        } else {
            this.calendar.now();
            this.element.value = "";
        }
    }

    /**
     * Show the calendar when the input is focused or clicked and handle the keyboard
     *
     * Backspace clears the date and Escape hides the calendar, keeping the focus; the calendar is
     * shown again with the arrows, Enter or Space
     */
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

        this.element.addEventListener("click", () => {
            this.calendar.show();
        });

        this.element.addEventListener("keydown", (event: KeyboardEvent) => {
            switch (event.key) {
                case "Backspace":
                    this.element.value = "";
                    this.calendar.hide();
                    this.dispatchChange();
                    break;
                case "Escape":
                    this.calendar.hide();
                    break;
                case "Tab":
                    return;
                case "ArrowDown":
                case "ArrowUp":
                case "Enter":
                case " ":
                    // The key only shows a calendar that is hidden, it does not change the date as well
                    if (!this.calendar.visible) {
                        this.calendar.show();
                        event.stopPropagation();
                    }
                    break;
            }
            event.preventDefault();
        });
    }

    /**
     * Return whether a string is a valid date
     */
    private isValidDate(date: string) {
        return date && !Number.isNaN(Date.parse(date));
    }
    /**
     * Format a date with the options of the input
     */
    private formatDateTime(date: Date, format: string) {
        return formatDateTime(date, format, this.options);
    }
}
