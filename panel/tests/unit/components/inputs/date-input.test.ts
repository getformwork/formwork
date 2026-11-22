import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, mock, test } from "node:test";
import { DateInput, type DateInputOptions } from "../../../../src/ts/components/inputs/date-input";
import { press } from "../../../helpers/events";
import { setTranslation } from "../../../helpers/panel";

beforeEach(() => {
    setTranslation();
});

/**
 * Make a date input in the document as the panel renders it
 */
function makeDate(value = "2024-01-15T10:30", { time = false, ...options }: Partial<DateInputOptions> = {}) {
    document.body.innerHTML = `<div class="form-input-wrap"><input type="text" class="form-input form-input-date" id="date" name="date" value="${value}" data-time="${time}"></div>`;
    const element = document.getElementById("date") as HTMLInputElement;
    const input = new DateInput(element, options);
    const change = mock.fn();
    element.addEventListener("change", change);
    return { input, element, change };
}

const calendar = () => document.querySelector(".calendar") as HTMLElement;

const isShown = () => calendar().style.display === "block";

const selectedDay = () => calendar().querySelector(".calendar-day.selected")?.textContent;

const header = () => calendar().querySelector(".calendar-header")?.textContent;

/**
 * Press a button of the calendar with the mouse
 */
function pressButton(selector: string) {
    const button = calendar().querySelector(selector) as HTMLElement;
    button.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0 }));
    window.dispatchEvent(new MouseEvent("mouseup", { bubbles: true }));
}

describe("DateInput", () => {
    describe("value", () => {
        test("shows the date with the format of the input", () => {
            const { element } = makeDate("2024-01-15T10:30", { dateFormat: "DD/MM/YYYY" });
            assert.equal(element.value, "15/01/2024");
            assert.equal(element.readOnly, true);
        });

        test("shows the time with the format of the input", () => {
            const { element } = makeDate("2024-01-15T15:30", { time: true, dateTimeFormat: "DD/MM/YYYY HH:mm A" });
            assert.equal(element.value, "15/01/2024 03:30 PM");
        });

        test("returns the date in the ISO format", () => {
            const { input } = makeDate("2024-01-15T15:30", { dateFormat: "DD/MM/YYYY" });
            assert.equal(input.value, "2024-01-15T15:30:00");
        });

        test("returns an empty value without a date", () => {
            const { input, element } = makeDate("");
            assert.equal(element.value, "");
            assert.equal(input.value, "");
        });

        test("clears invalid dates", () => {
            const { input, element } = makeDate("foo");
            assert.equal(element.value, "");
            assert.equal(input.value, "");
        });

        test("sets the date", () => {
            const { input, element } = makeDate("");
            input.value = "2024-02-29T08:05";
            assert.equal(element.value, "2024-02-29");
            assert.equal(input.value, "2024-02-29T08:05:00");
            input.value = "";
            assert.equal(element.value, "");
        });
    });

    describe("calendar", () => {
        test("shows the month of the date when focused and hides it when blurred", () => {
            const { element } = makeDate();
            element.focus();
            assert.equal(isShown(), true);
            assert.equal(header(), "January 2024");
            assert.equal(selectedDay(), "15");
            element.blur();
            assert.equal(isShown(), false);
        });

        test("shows the days of the weeks from the first day of the week", () => {
            makeDate("2024-01-15T10:30", { weekStarts: 1 });
            const days = Array.from(calendar().querySelectorAll(".calendar-header-day"), (day) => day.textContent);
            assert.deepEqual(days, ["Mon", "Tue", "Wed", "Thu", "Fri", "Sat", "Sun"]);
        });

        test("completes the weeks with the days of the previous and next months", () => {
            // January 2024 starts on Monday
            makeDate("2024-01-15T10:30");
            const cells = Array.from(calendar().querySelectorAll(".calendar-table td:not(.calendar-header-day)"), (cell) => `${cell.className.replace("calendar-", "").replace(" selected", "")}:${cell.textContent}`);
            assert.deepEqual(cells.slice(0, 2), ["prev-month-day:31", "day:1"]);
            assert.deepEqual(cells.slice(31, 33), ["day:31", "next-month-day:1"]);
            assert.equal(cells.length, 42);
        });

        test("chooses a day when clicked", () => {
            const { element, change } = makeDate();
            element.focus();
            const day = Array.from(calendar().querySelectorAll<HTMLElement>(".calendar-day")).find((cell) => cell.textContent === "20") as HTMLElement;
            day.click();
            assert.equal(element.value, "2024-01-20");
            assert.equal(change.mock.callCount(), 1);
        });

        test("goes to the previous and next months with the buttons", () => {
            const { element } = makeDate("2024-03-31T10:30");
            element.focus();
            pressButton(".prevMonth");
            assert.equal(element.value, "2024-02-29");
            pressButton(".nextMonth");
            pressButton(".nextMonth");
            assert.equal(element.value, "2024-04-29");
        });

        test("repeats the change while a button stays pressed", (t) => {
            t.mock.timers.enable({ apis: ["setTimeout", "setInterval"] });
            const { element } = makeDate();
            element.focus();
            calendar()
                .querySelector(".nextMonth")
                ?.dispatchEvent(new MouseEvent("mousedown", { bubbles: true, cancelable: true, button: 0 }));
            t.mock.timers.tick(750);
            t.mock.timers.tick(1000);
            window.dispatchEvent(new MouseEvent("mouseup"));
            t.mock.timers.tick(1000);
            assert.equal(element.value, "2024-04-15");
        });

        test("chooses the current date with the button", (t) => {
            t.mock.timers.enable({ apis: ["Date"], now: new Date(2025, 5, 10, 12, 0) });
            const { element } = makeDate();
            element.focus();
            (calendar().querySelector(".currentMonth") as HTMLElement).click();
            assert.equal(element.value, "2025-06-10");
        });

        test("changes the time with the buttons", () => {
            const { element } = makeDate("2024-01-15T23:59", { time: true, dateTimeFormat: "YYYY-MM-DD hh:mm:ss" });
            element.focus();
            pressButton(".nextMinute");
            assert.equal(element.value, "2024-01-16 00:00:00");
            pressButton(".prevHour");
            assert.equal(element.value, "2024-01-15 23:00:00");
        });

        test("shows the time with the clock of the format", () => {
            makeDate("2024-01-15T15:05", { time: true, dateTimeFormat: "YYYY-MM-DD HH:mm A" });
            assert.equal(calendar().querySelector(".calendar-hours")?.textContent, "03");
            assert.equal(calendar().querySelector(".calendar-minutes")?.textContent, "05");
            assert.equal(calendar().querySelector(".calendar-meridiem")?.textContent, "PM");

            makeDate("2024-01-15T15:05", { time: true, dateTimeFormat: "YYYY-MM-DD hh:mm" });
            assert.equal(calendar().querySelector(".calendar-hours")?.textContent, "15");
            assert.equal(calendar().querySelector(".calendar-meridiem")?.textContent, "");
        });
    });

    describe("keyboard", () => {
        test("changes the day with the arrows", () => {
            const { element, change } = makeDate();
            element.focus();
            press("ArrowRight", { target: element });
            assert.equal(element.value, "2024-01-16");
            press("ArrowLeft", { target: element });
            press("ArrowLeft", { target: element });
            assert.equal(element.value, "2024-01-14");
            press("ArrowDown", { target: element });
            assert.equal(element.value, "2024-01-21");
            press("ArrowUp", { target: element });
            assert.equal(element.value, "2024-01-14");
            assert.equal(change.mock.callCount(), 5);
        });

        test("changes the month and the year with Ctrl and Shift", () => {
            const { element } = makeDate("2024-01-31T10:30");
            element.focus();
            press("ArrowRight", { target: element, ctrlKey: true });
            assert.equal(element.value, "2024-02-29");
            press("ArrowRight", { target: element, metaKey: true, shiftKey: true });
            assert.equal(element.value, "2025-02-28");
            press("ArrowLeft", { target: element, ctrlKey: true });
            assert.equal(element.value, "2025-01-28");
        });

        test("hides the calendar with Escape and shows it again with the arrows, without changing the date", () => {
            const { element } = makeDate();
            element.focus();
            press("Escape", { target: element });
            assert.equal(isShown(), false);
            press("ArrowDown", { target: element });
            assert.equal(isShown(), true);
            assert.equal(element.value, "2024-01-15");
        });

        test("chooses the day and hides the calendar with Enter", () => {
            const { element, change } = makeDate();
            element.focus();
            press("ArrowRight", { target: element });
            press("Enter", { target: element });
            assert.equal(element.value, "2024-01-16");
            assert.equal(isShown(), false);
            assert.equal(change.mock.callCount(), 2);
        });

        test("clears the date with Backspace", () => {
            const { input, element, change } = makeDate();
            element.focus();
            press("Backspace", { target: element });
            assert.equal(element.value, "");
            assert.equal(input.value, "");
            assert.equal(isShown(), false);
            assert.equal(change.mock.callCount(), 1);
        });

        test("prevents typing in the input", () => {
            const { element } = makeDate();
            element.focus();
            assert.equal(press("a", { target: element }).defaultPrevented, true);
        });

        test("prevents typing without a date", () => {
            const { element } = makeDate("");
            element.focus();
            press("a", { target: element });
            assert.equal(element.value, "");
        });

        test("chooses the current date with Ctrl+0", (t) => {
            t.mock.timers.enable({ apis: ["Date"], now: new Date(2025, 5, 10, 12, 0) });
            const { element } = makeDate();
            element.focus();
            press("0", { target: element, ctrlKey: true });
            assert.equal(element.value, "2025-06-10");
        });

        test("ignores the keys while the calendar is hidden", () => {
            const { element, change } = makeDate();
            press("ArrowRight", { target: element });
            assert.equal(element.value, "2024-01-15");
            assert.equal(change.mock.callCount(), 0);
        });
    });
});
