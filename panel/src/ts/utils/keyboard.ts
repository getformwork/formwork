/**
 * Get the index of the item to move to with a key in a list navigated with the keyboard
 *
 * The arrows move to the next or previous item (wrapping around), Home and End to the first and
 * last one
 *
 * @param key       Pressed key
 * @param index     Index of the current item
 * @param lastIndex Index of the last item
 *
 * @returns Index of the item, or `undefined` if the key does not move the focus
 */
export function getNextIndex(key: string, index: number, lastIndex: number) {
    switch (key) {
        case "ArrowRight":
            return index === lastIndex ? 0 : index + 1;
        case "ArrowLeft":
            return index === 0 ? lastIndex : index - 1;
        case "Home":
            return 0;
        case "End":
            return lastIndex;
    }
}
