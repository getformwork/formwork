import { mod } from "./math";

/**
 * Holds the options to format a date
 */
export interface DateFormatOptions {
    weekStarts: number;
    labels: {
        weekdays: {
            long: string[];
            short: string[];
        };
        months: {
            long: string[];
            short: string[];
        };
    };
}

/**
 * Pad a number with leading zeros
 */
const pad = (value: number, length: number = 2) => `${value}`.padStart(length, "0");

/**
 * Get the first day of the week of a date
 *
 * @param firstDay First day of the week, from 0 (Sunday) to 6
 */
function getWeekStart(date: Date, firstDay: number) {
    const day = date.getDate() - mod(date.getDay() - firstDay, 7);

    return new Date(date.getFullYear(), date.getMonth(), day);
}

/**
 * Get the year a date belongs to for the numbering of the weeks (ISO 8601): the first week of the
 * year is the one with the fourth of January
 */
function getWeekNumberingYear(date: Date) {
    const year = date.getFullYear();
    const thisYearFirstWeekStart = getWeekStart(new Date(year, 0, 4), 1);
    const nextYearFirstWeekStart = getWeekStart(new Date(year + 1, 0, 4), 1);

    if (date.getTime() >= nextYearFirstWeekStart.getTime()) {
        return year + 1;
    }

    if (date.getTime() >= thisYearFirstWeekStart.getTime()) {
        return year;
    }

    return year - 1;
}

/**
 * Get the number of the week of a date (ISO 8601)
 */
function getWeekOfYear(date: Date) {
    const firstWeekStart = getWeekStart(new Date(getWeekNumberingYear(date), 0, 4), 1);
    const weekStart = getWeekStart(date, 1);

    return Math.round((weekStart.getTime() - firstWeekStart.getTime()) / 604800000) + 1;
}

/**
 * Split the offset of the timezone, in minutes, into the sign with the hours and the minutes
 */
function splitTimezoneOffset(offset: number) {
    // Note that the offset returned by Date.getTimezoneOffset()
    // is positive if behind UTC and negative if ahead UTC
    const sign = offset > 0 ? "-" : "+";
    const hours = Math.floor(Math.abs(offset) / 60);
    const minutes = Math.abs(offset) % 60;

    return [sign + pad(hours), pad(minutes)];
}

/**
 * Format a date with a pattern
 *
 * @param format  Pattern of the date: `YYYY`/`YY` year, `MM`/`M` month, `DD`/`D` day, `HH`/`H`
 *                12-hour clock hours, `hh`/`h` 24-hour clock hours, `mm`/`m` minutes, `ss`/`s`
 *                seconds, `A`/`a` meridiem, `Z`/`z` timezone offset, `W` week of the year. Text in
 *                square brackets is left as is
 * @param options Labels and first day of the week
 */
export function formatDateTime(date: Date, format: string, options: DateFormatOptions) {
    const regex = /\[([^\]]*)\]|[YR]{4}|uuu|[YR]{2}|[MD]{1,4}|[WHhms]{1,2}|[AaZz]/g;

    const twelveHours = mod(date.getHours(), 12) || 12;

    return format.replace(regex, (match: string, escaped) => {
        switch (match) {
            case "YY":
                return pad(date.getFullYear() % 100);
            case "YYYY":
                return date.getFullYear();
            case "M":
                return date.getMonth() + 1;
            case "MM":
                return pad(date.getMonth() + 1);
            case "MMM":
                return options.labels.months.short[date.getMonth()];
            case "MMMM":
                return options.labels.months.long[date.getMonth()];
            case "D":
                return date.getDate();
            case "DD":
                return pad(date.getDate());
            case "DDD":
                return options.labels.weekdays.short[date.getDay()];
            case "DDDD":
                return options.labels.weekdays.long[date.getDay()];
            case "W":
                return getWeekOfYear(date);
            case "WW":
                return pad(getWeekOfYear(date));
            case "RR":
                return pad(getWeekNumberingYear(date) % 100);
            case "RRRR":
                return getWeekNumberingYear(date);
            case "H":
                return twelveHours;
            case "HH":
                return pad(twelveHours);
            case "h":
                return date.getHours();
            case "hh":
                return pad(date.getHours());
            case "m":
                return date.getMinutes();
            case "mm":
                return pad(date.getMinutes());
            case "s":
                return date.getSeconds();
            case "ss":
                return pad(date.getSeconds());
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
                return escaped || match;
        }
    });
}
