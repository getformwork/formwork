import "../../environment/dom";
import assert from "node:assert/strict";
import { afterEach, beforeEach, describe, mock, test } from "node:test";
import { debounce, longClick, throttle } from "../../../src/ts/utils/events";

beforeEach(() => {
    mock.timers.enable({ apis: ["setTimeout", "setInterval", "Date"], now: 1000 });
});

afterEach(() => {
    mock.timers.reset();
});

describe("Event Utils", () => {
    describe("debounce", () => {
        test("calls the callback once after the delay since the last call", () => {
            const callback = mock.fn();
            const debounced = debounce(callback, 100);

            debounced("first");
            mock.timers.tick(50);
            debounced("second");
            mock.timers.tick(99);
            assert.equal(callback.mock.callCount(), 0);

            mock.timers.tick(1);
            assert.equal(callback.mock.callCount(), 1);
            assert.deepEqual(callback.mock.calls[0].arguments, ["second"]);
        });

        test("calls the callback again for calls after the delay", () => {
            const callback = mock.fn();
            const debounced = debounce(callback, 100);

            debounced();
            mock.timers.tick(100);
            debounced();
            mock.timers.tick(100);

            assert.equal(callback.mock.callCount(), 2);
        });

        test("returns the result of the last callback call", () => {
            let calls = 0;
            const debounced = debounce(() => ++calls, 100);

            assert.equal(debounced(), undefined);
            mock.timers.tick(100);
            assert.equal(debounced(), 1);
        });

        test("preserves the context of the call", () => {
            const callback = mock.fn(function (this: unknown) {
                return this;
            });
            const target = { method: debounce(callback, 100) };

            target.method();
            mock.timers.tick(100);

            assert.equal(callback.mock.calls[0].this, target);
        });

        describe("leading", () => {
            test("calls the callback at the first call", () => {
                const callback = mock.fn((value: string) => value);
                const debounced = debounce(callback, 100, true);

                assert.equal(debounced("first"), "first");
                assert.equal(callback.mock.callCount(), 1);
            });

            test("ignores calls until the delay since the last call has passed", () => {
                const callback = mock.fn();
                const debounced = debounce(callback, 100, true);

                debounced();
                mock.timers.tick(50);
                debounced();
                mock.timers.tick(50);
                debounced();
                mock.timers.tick(99);
                debounced();
                assert.equal(callback.mock.callCount(), 1);

                mock.timers.tick(100);
                assert.equal(callback.mock.callCount(), 1);

                debounced();
                assert.equal(callback.mock.callCount(), 2);
            });
        });
    });

    describe("throttle", () => {
        test("calls the callback at most once in the delay", () => {
            const callback = mock.fn();
            const throttled = throttle(callback, 100);

            for (let i = 0; i < 10; i++) {
                throttled();
                mock.timers.tick(10);
            }
            mock.timers.tick(100);

            assert.equal(callback.mock.callCount(), 1);
        });

        test("does not lose the calls made during the delay", () => {
            const callback = mock.fn();
            const throttled = throttle(callback, 100);

            throttled();
            mock.timers.tick(100);
            assert.equal(callback.mock.callCount(), 1);

            throttled();
            mock.timers.tick(100);
            assert.equal(callback.mock.callCount(), 2);
        });

        test("calls the callback at once when the delay has passed since the last call", () => {
            const callback = mock.fn();
            const throttled = throttle(callback, 100);

            throttled();
            mock.timers.tick(100);
            mock.timers.tick(100);
            throttled();

            assert.equal(callback.mock.callCount(), 2);
        });

        test("calls the callback repeatedly while called continuously", () => {
            const callback = mock.fn();
            const throttled = throttle(callback, 100);

            for (let i = 0; i < 50; i++) {
                throttled();
                mock.timers.tick(10);
            }

            assert.ok(callback.mock.callCount() >= 4, `calls: ${callback.mock.callCount()}`);
            assert.ok(callback.mock.callCount() <= 5, `calls: ${callback.mock.callCount()}`);
        });

        test("preserves the context of the call", () => {
            const callback = mock.fn(function (this: unknown) {
                return this;
            });
            const target = { method: throttle(callback, 100) };

            target.method();
            mock.timers.tick(100);

            assert.equal(callback.mock.calls[0].this, target);
        });
    });

    describe("longClick", () => {
        /**
         * Make a button calling a mock function on long click
         */
        function makeButton() {
            const button = document.createElement("button");
            document.body.append(button);
            const callback = mock.fn();
            longClick(button, callback, 500, 100);
            return { button, callback };
        }

        const mousedown = (button = 0) => new MouseEvent("mousedown", { button, bubbles: true });

        test("calls the callback when pressed", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown());
            assert.equal(callback.mock.callCount(), 1);
            assert.equal(callback.mock.calls[0].this, button);
        });

        test("repeats the callback while pressed after the timeout", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown());
            mock.timers.tick(499);
            assert.equal(callback.mock.callCount(), 1);
            mock.timers.tick(1);
            mock.timers.tick(100);
            assert.equal(callback.mock.callCount(), 2);
            mock.timers.tick(300);
            assert.equal(callback.mock.callCount(), 5);
        });

        test("passes the event of the press to the repeated calls", () => {
            const { button, callback } = makeButton();
            const event = mousedown();
            button.dispatchEvent(event);
            mock.timers.tick(500);
            mock.timers.tick(100);
            assert.equal(callback.mock.calls[1].arguments[0], event);
            assert.equal(callback.mock.calls[1].this, button);
        });

        test("stops when released, also outside the element", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown());
            mock.timers.tick(500);
            mock.timers.tick(100);
            window.dispatchEvent(new MouseEvent("mouseup"));
            mock.timers.tick(1000);
            assert.equal(callback.mock.callCount(), 2);
        });

        test("stops when released before the timeout", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown());
            window.dispatchEvent(new MouseEvent("mouseup"));
            mock.timers.tick(1000);
            assert.equal(callback.mock.callCount(), 1);
        });

        test("stops when the pointer leaves the element", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown());
            mock.timers.tick(500);
            mock.timers.tick(100);
            button.dispatchEvent(new MouseEvent("mouseout"));
            mock.timers.tick(1000);
            assert.equal(callback.mock.callCount(), 2);
        });

        test("ignores buttons other than the main one", () => {
            const { button, callback } = makeButton();
            button.dispatchEvent(mousedown(2));
            mock.timers.tick(1000);
            assert.equal(callback.mock.callCount(), 0);
        });
    });
});
