/**
 * Get a function that calls a callback after a delay since the last call
 *
 * If `leading` is true the callback is called at the first call instead
 *
 * @param delay   Delay in milliseconds
 * @param leading Whether the callback is called at the first call instead
 */
export function debounce<Context, Args extends unknown[], Result>(callback: (this: Context, ...args: Args) => Result, delay: number, leading: boolean = false) {
    let result: Result | undefined;
    let timer: number | null = null;

    function wrapper(this: Context, ...args: Args) {
        // biome-ignore lint/complexity/noUselessThisAlias: This alias is necessary to maintain the correct `this` context for the callback function
        const context = this;

        if (timer) {
            clearTimeout(timer);
        }
        if (leading && !timer) {
            result = callback.apply(context, args);
        }
        timer = window.setTimeout(() => {
            if (!leading) {
                result = callback.apply(context, args);
            }
            timer = null;
        }, delay);
        return result;
    }

    return wrapper;
}

/**
 * Get a function that calls a callback at most once in a delay
 *
 * @param delay Minimum interval between calls in milliseconds
 */
export function throttle<Context, Args extends unknown[], Result>(callback: (this: Context, ...args: Args) => Result, delay: number) {
    let result: Result | undefined;
    let previous = 0;
    let timer: number | null = null;

    function wrapper(this: Context, ...args: Args) {
        const now = Date.now();
        if (previous === 0) {
            previous = now;
        }
        const remaining = previous + delay - now;

        // biome-ignore lint/complexity/noUselessThisAlias: This alias is necessary to maintain the correct `this` context for the callback function
        const context = this;

        if (remaining <= 0 || remaining > delay) {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            previous = now;
            result = callback.apply(context, args);
        } else if (!timer) {
            timer = window.setTimeout(() => {
                previous = Date.now();
                result = callback.apply(context, args);
                timer = null;
            }, remaining);
        }
        return result;
    }

    return wrapper;
}

/**
 * Call a callback when an element is pressed, and repeatedly while it stays pressed
 *
 * @param timeout  Time in milliseconds before the callback is repeated
 * @param interval Interval in milliseconds between repeated calls
 */
export function longClick(element: HTMLElement, callback: (event: MouseEvent) => void, timeout: number, interval: number) {
    let timer: number;
    function clear() {
        clearTimeout(timer);
    }
    element.addEventListener("mousedown", function (event: MouseEvent) {
        if (event.button !== 0) {
            clear();
        } else {
            callback.call(this, event);
            timer = window.setTimeout(() => {
                timer = window.setInterval(callback.bind(this, event), interval);
            }, timeout);
        }
    });
    element.addEventListener("mouseout", clear);
    window.addEventListener("mouseup", clear);
}
