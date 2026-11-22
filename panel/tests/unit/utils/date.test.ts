import assert from "node:assert/strict";
import { afterEach, describe, test } from "node:test";
import { type DateFormatOptions, formatDateTime } from "../../../src/ts/utils/date";

const options: DateFormatOptions = {
    weekStarts: 1,
    labels: {
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

// Friday 5 January 2024, 09:07:03.045
const date = new Date(2024, 0, 5, 9, 7, 3, 45);

describe("Date Utils", () => {
    test("formats numeric tokens with and without padding", () => {
        assert.equal(formatDateTime(date, "YYYY YY M MM D DD h hh m mm s ss uuu", options), "2024 24 1 01 5 05 9 09 7 07 3 03 045");
    });

    test("formats month and weekday labels", () => {
        assert.equal(formatDateTime(date, "MMM MMMM DDD DDDD", options), "Jan January Fri Friday");
    });

    test("formats a full date and time", () => {
        assert.equal(formatDateTime(date, "YYYY-MM-DD hh:mm:ss", options), "2024-01-05 09:07:03");
    });

    test("pads two-digit years", () => {
        assert.equal(formatDateTime(new Date(2005, 0, 1), "YY", options), "05");
    });

    test("formats the 12-hour clock with the meridiem", () => {
        const cases: [number, string][] = [
            [0, "12 12 AM am"],
            [1, "1 01 AM am"],
            [11, "11 11 AM am"],
            [12, "12 12 PM pm"],
            [13, "1 01 PM pm"],
            [23, "11 11 PM pm"],
        ];
        for (const [hours, expected] of cases) {
            assert.equal(formatDateTime(new Date(2024, 0, 5, hours), "H HH A a", options), expected, `hours: ${hours}`);
        }
    });

    test("leaves text in square brackets as is", () => {
        assert.equal(formatDateTime(date, "[Today is] DDDD", options), "Today is Friday");
        assert.equal(formatDateTime(date, "[YYYY] YYYY", options), "YYYY 2024");
    });

    test("leaves characters that are not tokens unchanged", () => {
        assert.equal(formatDateTime(date, "YYYY/MM/DD, hh.mm", options), "2024/01/05, 09.07");
    });

    test("removes empty square brackets", () => {
        assert.equal(formatDateTime(date, "YYYY[]MM", options), "202401");
    });

    describe("week of the year (ISO 8601)", () => {
        const cases: [string, Date, string][] = [
            ["first days of a year in the last week of the previous one", new Date(2021, 0, 1), "53 53 20 2020"],
            ["sunday closing the last week of the previous year", new Date(2021, 0, 3), "53 53 20 2020"],
            ["monday opening the first week", new Date(2021, 0, 4), "1 01 21 2021"],
            ["sunday of the last week of a year", new Date(2024, 11, 29), "52 52 24 2024"],
            ["last days of a year in the first week of the next one", new Date(2024, 11, 30), "1 01 25 2025"],
            ["last day of a year with 53 weeks", new Date(2026, 11, 31), "53 53 26 2026"],
            ["first days of a year after a year with 53 weeks", new Date(2027, 0, 3), "53 53 26 2026"],
            ["leap day", new Date(2024, 1, 29), "9 09 24 2024"],
            ["middle of the year", new Date(2024, 5, 15), "24 24 24 2024"],
        ];

        for (const [label, weekDate, expected] of cases) {
            test(`numbers the ${label}`, () => {
                assert.equal(formatDateTime(weekDate, "W WW RR RRRR", options), expected);
            });
        }

        test("does not depend on the time of the day", () => {
            assert.equal(formatDateTime(new Date(2024, 11, 29, 23, 59, 59), "W RRRR", options), "52 2024");
            assert.equal(formatDateTime(new Date(2024, 11, 30, 0, 0, 0), "W RRRR", options), "1 2025");
        });
    });

    describe("timezone offset", () => {
        const timezone = process.env.TZ;

        afterEach(() => {
            process.env.TZ = timezone;
        });

        const cases: [string, string, string][] = [
            ["UTC", "UTC", "+00:00 +0000"],
            ["ahead of UTC", "Etc/GMT-2", "+02:00 +0200"],
            ["behind UTC", "Etc/GMT+5", "-05:00 -0500"],
            ["with minutes", "Asia/Kolkata", "+05:30 +0530"],
            ["behind UTC with minutes", "America/St_Johns", "-03:30 -0330"],
        ];

        for (const [label, zone, expected] of cases) {
            test(`formats offsets ${label}`, () => {
                process.env.TZ = zone;
                assert.equal(formatDateTime(new Date(Date.UTC(2024, 0, 5, 12)), "Z z", options), expected);
            });
        }

        test("formats the offset in effect at the date", () => {
            process.env.TZ = "Europe/Rome";
            assert.equal(formatDateTime(new Date(Date.UTC(2024, 0, 5, 12)), "Z", options), "+01:00");
            assert.equal(formatDateTime(new Date(Date.UTC(2024, 6, 5, 12)), "Z", options), "+02:00");
        });

        test("numbers weeks across daylight saving time changes", () => {
            process.env.TZ = "Europe/Rome";
            assert.equal(formatDateTime(new Date(2024, 2, 31, 12), "W", options), "13");
            assert.equal(formatDateTime(new Date(2024, 3, 1, 12), "W", options), "14");
            assert.equal(formatDateTime(new Date(2024, 9, 27, 12), "W", options), "43");
            assert.equal(formatDateTime(new Date(2024, 9, 28, 12), "W", options), "44");
        });
    });
});
