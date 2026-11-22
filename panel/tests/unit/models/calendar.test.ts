import assert from "node:assert/strict";
import { afterEach, describe, mock, test } from "node:test";
import { CalendarModel, daysInMonth } from "../../../src/ts/models/calendar";

/**
 * Make a calendar with the given date selected
 */
function calendarAt(...date: [number, number, number, number?, number?, number?]) {
    const calendar = new CalendarModel();
    calendar.setDate(new Date(date[0], date[1], date[2], date[3] ?? 0, date[4] ?? 0, date[5] ?? 0));
    return calendar;
}

describe("Calendar Model", () => {
    describe("daysInMonth", () => {
        test("returns the days of each month", () => {
            assert.deepEqual(
                Array.from({ length: 12 }, (_, month) => daysInMonth(month, 2023)),
                [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
            );
        });

        test("returns 29 days for february in leap years", () => {
            assert.equal(daysInMonth(1, 2024), 29);
            assert.equal(daysInMonth(1, 2000), 29);
        });

        test("returns 28 days for february in century years not divisible by 400", () => {
            assert.equal(daysInMonth(1, 1900), 28);
            assert.equal(daysInMonth(1, 2100), 28);
        });
    });

    describe("CalendarModel", () => {
        afterEach(() => {
            mock.timers.reset();
        });

        test("selects the current date by default", () => {
            mock.timers.enable({ apis: ["Date"], now: new Date(2024, 0, 15, 10, 30, 45) });
            assert.deepEqual(new CalendarModel().toDate(), new Date(2024, 0, 15, 10, 30, 45));
        });

        test("selects the current date and time", () => {
            const calendar = calendarAt(2020, 5, 1);
            mock.timers.enable({ apis: ["Date"], now: new Date(2024, 0, 15, 10, 30, 45) });
            calendar.now();
            assert.deepEqual(calendar.toDate(), new Date(2024, 0, 15, 10, 30, 45));
        });

        test("returns the selected date", () => {
            assert.deepEqual(calendarAt(2024, 1, 29, 23, 59, 58).toDate(), new Date(2024, 1, 29, 23, 59, 58));
        });

        test("ignores the milliseconds of the selected date", () => {
            const calendar = new CalendarModel();
            calendar.setDate(new Date(2024, 0, 15, 10, 30, 45, 500));
            assert.deepEqual(calendar.toDate(), new Date(2024, 0, 15, 10, 30, 45));
        });

        test("selects a day of the current month", () => {
            const calendar = calendarAt(2024, 0, 15, 10, 30);
            calendar.setDay(3);
            assert.deepEqual(calendar.toDate(), new Date(2024, 0, 3, 10, 30));
        });

        test("replaces the state at each change", () => {
            const calendar = calendarAt(2024, 0, 15);
            const state = calendar.state.value;
            calendar.nextDay();
            assert.notEqual(calendar.state.value, state);
            assert.equal(state.day, 15);
        });

        test("shows and hides the calendar", () => {
            const calendar = new CalendarModel();
            assert.equal(calendar.shown.value, false);
            calendar.show();
            assert.equal(calendar.shown.value, true);
            calendar.hide();
            assert.equal(calendar.shown.value, false);
        });

        type Navigation = "prevYear" | "nextYear" | "prevMonth" | "nextMonth" | "prevWeek" | "nextWeek" | "prevDay" | "nextDay" | "prevHour" | "nextHour" | "prevMinute" | "nextMinute";

        const cases: [Navigation, string, [number, number, number, number?, number?], Date][] = [
            ["prevYear", "goes to the previous year", [2024, 5, 15], new Date(2023, 5, 15)],
            ["prevYear", "goes from a leap day to the last day of february", [2024, 1, 29], new Date(2023, 1, 28)],
            ["nextYear", "goes to the next year", [2024, 5, 15], new Date(2025, 5, 15)],
            ["nextYear", "goes from a leap day to the last day of february", [2024, 1, 29], new Date(2025, 1, 28)],
            ["prevMonth", "goes to the previous month", [2024, 5, 15], new Date(2024, 4, 15)],
            ["prevMonth", "goes from january to december of the previous year", [2024, 0, 15], new Date(2023, 11, 15)],
            ["prevMonth", "goes to the last day of a shorter month", [2024, 2, 31], new Date(2024, 1, 29)],
            ["prevMonth", "goes to the last day of a shorter month of the previous year", [2024, 0, 31], new Date(2023, 11, 31)],
            ["nextMonth", "goes to the next month", [2024, 5, 15], new Date(2024, 6, 15)],
            ["nextMonth", "goes from december to january of the next year", [2024, 11, 15], new Date(2025, 0, 15)],
            ["nextMonth", "goes to the last day of a shorter month", [2023, 0, 31], new Date(2023, 1, 28)],
            ["nextMonth", "goes to the last day of a shorter month in a leap year", [2024, 0, 31], new Date(2024, 1, 29)],
            ["prevWeek", "goes back by one week", [2024, 5, 15], new Date(2024, 5, 8)],
            ["prevWeek", "goes back by one week into the previous month", [2024, 2, 3], new Date(2024, 1, 25)],
            ["prevWeek", "goes back by one week into the previous year", [2024, 0, 3], new Date(2023, 11, 27)],
            ["nextWeek", "goes forward by one week", [2024, 5, 15], new Date(2024, 5, 22)],
            ["nextWeek", "goes forward by one week into the next month", [2024, 1, 26], new Date(2024, 2, 4)],
            ["nextWeek", "goes forward by one week into the next year", [2024, 11, 28], new Date(2025, 0, 4)],
            ["prevDay", "goes to the previous day", [2024, 5, 15], new Date(2024, 5, 14)],
            ["prevDay", "goes from the first day to the last day of the previous month", [2024, 2, 1], new Date(2024, 1, 29)],
            ["prevDay", "goes from the first day of the year to the last day of the previous one", [2024, 0, 1], new Date(2023, 11, 31)],
            ["nextDay", "goes to the next day", [2024, 5, 15], new Date(2024, 5, 16)],
            ["nextDay", "goes from the last day to the first day of the next month", [2023, 1, 28], new Date(2023, 2, 1)],
            ["nextDay", "goes from the last day of the year to the first day of the next one", [2024, 11, 31], new Date(2025, 0, 1)],
            ["prevHour", "goes to the previous hour", [2024, 5, 15, 10], new Date(2024, 5, 15, 9)],
            ["prevHour", "goes from midnight to the last hour of the previous day", [2024, 0, 1, 0], new Date(2023, 11, 31, 23)],
            ["nextHour", "goes to the next hour", [2024, 5, 15, 10], new Date(2024, 5, 15, 11)],
            ["nextHour", "goes from the last hour to midnight of the next day", [2024, 11, 31, 23], new Date(2025, 0, 1, 0)],
            ["prevMinute", "goes to the previous minute", [2024, 5, 15, 10, 30], new Date(2024, 5, 15, 10, 29)],
            ["prevMinute", "goes from the first minute to the last minute of the previous hour", [2024, 0, 1, 0, 0], new Date(2023, 11, 31, 23, 59)],
            ["nextMinute", "goes to the next minute", [2024, 5, 15, 10, 30], new Date(2024, 5, 15, 10, 31)],
            ["nextMinute", "goes from the last minute to the first minute of the next hour", [2024, 11, 31, 23, 59], new Date(2025, 0, 1, 0, 0)],
        ];

        for (const [navigation, label, start, expected] of cases) {
            test(`${navigation} ${label}`, () => {
                const calendar = calendarAt(...start);
                calendar[navigation]();
                assert.deepEqual(calendar.toDate(), expected);
            });
        }

        test("preserves the seconds while navigating", () => {
            const calendar = calendarAt(2024, 11, 31, 23, 59, 30);
            calendar.nextMinute();
            assert.deepEqual(calendar.toDate(), new Date(2025, 0, 1, 0, 0, 30));
        });

        test("preserves the day of the month across a shorter month only for single steps", () => {
            const calendar = calendarAt(2024, 0, 31);
            calendar.nextMonth();
            calendar.nextMonth();
            assert.deepEqual(calendar.toDate(), new Date(2024, 2, 29));
        });

        test("returns to the same date going forward and back by one week", () => {
            for (let day = 1; day <= 31; day++) {
                const calendar = calendarAt(2024, 0, day);
                calendar.nextWeek();
                calendar.prevWeek();
                assert.deepEqual(calendar.toDate(), new Date(2024, 0, day), `day: ${day}`);
            }
        });

        test("navigates by days consistently with the native dates for a whole leap year", () => {
            const calendar = calendarAt(2024, 0, 1);
            for (let day = 1; day <= 366; day++) {
                calendar.nextDay();
                assert.deepEqual(calendar.toDate(), new Date(2024, 0, 1 + day), `day: ${day}`);
            }
        });

        test("navigates by weeks consistently with the native dates for a whole year", () => {
            const calendar = calendarAt(2023, 11, 31);
            for (let week = 1; week <= 53; week++) {
                calendar.prevWeek();
                assert.deepEqual(calendar.toDate(), new Date(2023, 11, 31 - 7 * week), `week: ${week}`);
            }
        });
    });
});
