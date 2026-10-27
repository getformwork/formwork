/**
 * Limit a number to the safe integers
 */
export function getSafeInteger(value: number) {
    const max = Number.MAX_SAFE_INTEGER;
    const min = -max;
    if (value > max) {
        return max;
    }
    if (value < min) {
        return min;
    }
    return value;
}
