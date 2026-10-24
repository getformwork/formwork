import { createModel, signal } from "@preact/signals-core";
import { mod } from "../utils/math";

/**
 * Describes the date and time of a calendar
 *
 * The month is zero-based
 */
export interface CalendarState {
    year: number;
    month: number;
    day: number;
    hours: number;
    minutes: number;
    seconds: number;
}

/**
 * Represents a change of the date that carries over to the larger units
 */
type CalendarOperation = (state: CalendarState) => void;

/**
 * Create the state of a date
 */
const stateFromDate = (date: Date): CalendarState => ({
    year: date.getFullYear(),
    month: date.getMonth(),
    day: date.getDate(),
    hours: date.getHours(),
    minutes: date.getMinutes(),
    seconds: date.getSeconds(),
});

/**
 * Return whether a year is a leap year
 */
const isLeapYear = (year: number) => (year % 4 === 0 && year % 100 !== 0) || year % 400 === 0;

/**
 * Get the number of days of a month
 */
export const daysInMonth = (month: number, year: number) => (month === 1 && isLeapYear(year) ? 29 : [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][month]);

/**
 * Go to the previous year
 */
const prevYear: CalendarOperation = (state) => {
    state.year--;
};

/**
 * Go to the next year
 */
const nextYear: CalendarOperation = (state) => {
    state.year++;
};

/**
 * Go to the last day of the month
 */
const lastDayOfMonth: CalendarOperation = (state) => {
    state.day = daysInMonth(state.month, state.year);
};

/**
 * Go to the previous month, using its last day if the current day does not exist
 */
const prevMonth: CalendarOperation = (state) => {
    state.month = mod(state.month - 1, 12);
    if (state.month === 11) {
        prevYear(state);
    }
    if (state.day > daysInMonth(state.month, state.year)) {
        lastDayOfMonth(state);
    }
};

/**
 * Go to the next month, using its last day if the current day does not exist
 */
const nextMonth: CalendarOperation = (state) => {
    state.month = mod(state.month + 1, 12);
    if (state.month === 0) {
        nextYear(state);
    }
    if (state.day > daysInMonth(state.month, state.year)) {
        lastDayOfMonth(state);
    }
};

/**
 * Go back by one week
 */
const prevWeek: CalendarOperation = (state) => {
    state.day -= 7;
    if (state.day < 1) {
        prevMonth(state);
        state.day += daysInMonth(state.month, state.year);
    }
};

/**
 * Go forward by one week
 */
const nextWeek: CalendarOperation = (state) => {
    state.day += 7;
    if (state.day > daysInMonth(state.month, state.year)) {
        state.day -= daysInMonth(state.month, state.year);
        nextMonth(state);
    }
};

/**
 * Go to the previous day
 */
const prevDay: CalendarOperation = (state) => {
    state.day--;
    if (state.day < 1) {
        prevMonth(state);
        lastDayOfMonth(state);
    }
};

/**
 * Go to the next day
 */
const nextDay: CalendarOperation = (state) => {
    state.day++;
    if (state.day > daysInMonth(state.month, state.year)) {
        nextMonth(state);
        state.day = 1;
    }
};

/**
 * Go to the next hour
 */
const nextHour: CalendarOperation = (state) => {
    state.hours = mod(state.hours + 1, 24);
    if (state.hours === 0) {
        nextDay(state);
    }
};

/**
 * Go to the previous hour
 */
const prevHour: CalendarOperation = (state) => {
    state.hours = mod(state.hours - 1, 24);
    if (state.hours === 23) {
        prevDay(state);
    }
};

/**
 * Go to the next minute
 */
const nextMinute: CalendarOperation = (state) => {
    state.minutes = mod(state.minutes + 1, 60);
    if (state.minutes === 0) {
        nextHour(state);
    }
};

/**
 * Go to the previous minute
 */
const prevMinute: CalendarOperation = (state) => {
    state.minutes = mod(state.minutes - 1, 60);
    if (state.minutes === 59) {
        prevHour(state);
    }
};

/**
 * Holds the selected date of a calendar and its visibility
 *
 * Every action updates the date at once, so the calendar is rendered only for the final state
 */
export const CalendarModel = createModel(() => {
    const state = signal<CalendarState>(stateFromDate(new Date()));
    const shown = signal(false);

    const apply = (operation: CalendarOperation) => {
        const next = { ...state.value };
        operation(next);
        state.value = next;
    };

    return {
        state,
        shown,

        /**
         * Get the selected date
         */
        toDate() {
            const { year, month, day, hours, minutes, seconds } = state.value;
            return new Date(year, month, day, hours, minutes, seconds);
        },

        /**
         * Set the selected date
         */
        setDate(date: Date) {
            state.value = stateFromDate(date);
        },

        /**
         * Select the current date and time
         */
        now() {
            state.value = stateFromDate(new Date());
        },

        /**
         * Select a day of the current month
         */
        setDay(day: number) {
            apply((next) => {
                next.day = day;
            });
        },

        prevYear: () => apply(prevYear),
        nextYear: () => apply(nextYear),
        prevMonth: () => apply(prevMonth),
        nextMonth: () => apply(nextMonth),
        prevWeek: () => apply(prevWeek),
        nextWeek: () => apply(nextWeek),
        prevDay: () => apply(prevDay),
        nextDay: () => apply(nextDay),
        prevHour: () => apply(prevHour),
        nextHour: () => apply(nextHour),
        prevMinute: () => apply(prevMinute),
        nextMinute: () => apply(nextMinute),

        /**
         * Show the calendar
         */
        show() {
            shown.value = true;
        },

        /**
         * Hide the calendar
         */
        hide() {
            shown.value = false;
        },
    };
});
