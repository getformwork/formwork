export function debounce(callback, delay, leading) {
    let result;
    let timer = null;

    function wrapper() {
        const context = this;
        const args = arguments;
        if (timer) {
            clearTimeout(timer);
        }
        if (leading && !timer) {
            result = callback.apply(context, args);
        }
        timer = setTimeout(() => {
            if (!leading) {
                result = callback.apply(context, args);
            }
            timer = null;
        }, delay);
        return result;
    }

    return wrapper;
}

export function throttle(callback, delay) {
    let result;
    let previous = 0;
    let timer = null;

    function wrapper() {
        const now = Date.now();
        if (previous === 0) {
            previous = now;
        }
        const remaining = previous + delay - now;
        const context = this;
        const args = arguments;
        if (remaining <= 0 || remaining > delay) {
            if (timer) {
                clearTimeout(timer);
                timer = null;
            }
            previous = now;
            result = callback.apply(context, args);
        } else if (!timer) {
            timer = setTimeout(() => {
                previous = Date.now();
                result = callback.apply(context, args);
                timer = null;
            }, remaining);
        }
        return result;
    }

    return wrapper;
}
