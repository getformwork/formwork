/**
 * Read a value of the local storage, or `null` if it is missing or the storage is not available
 */
export function readStorage(key: string) {
    try {
        return window.localStorage.getItem(key);
    } catch {
        // The access to the storage may be denied, e.g. by the privacy settings
        return null;
    }
}

/**
 * Write a value to the local storage, or remove it if `null`, if the storage is available
 */
export function writeStorage(key: string, value: string | null) {
    try {
        if (value === null) {
            window.localStorage.removeItem(key);
        } else {
            window.localStorage.setItem(key, value);
        }
    } catch {
        // The access to the storage may be denied, e.g. by the privacy settings
    }
}
