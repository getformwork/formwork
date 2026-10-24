import { debounce } from "./events";
import { escapeRegExp, makeDiacriticsRegExp } from "./validation";

/**
 * Holds the options of a search regular expression
 */
interface SearchRegExpOptions {
    /**
     * Match only at the beginning of a word
     */
    wordStart?: boolean;
    /**
     * Add the `g` flag
     */
    global?: boolean;
}

/**
 * Make a regular expression matching a search text, ignoring case and diacritics
 */
export function makeSearchRegExp(value: string, { wordStart = true, global = false }: SearchRegExpOptions = {}) {
    return new RegExp(`${wordStart ? "(^|\\b)" : ""}${makeDiacriticsRegExp(escapeRegExp(value))}`, global ? "gi" : "i");
}

/**
 * Call a function when the user types in a search field or clears it
 *
 * The field is focused with Ctrl/Cmd+F
 *
 * @param onSearch Function called when the search text changes
 */
export function initSearchInput(input: HTMLInputElement, onSearch: () => void) {
    input.addEventListener("keyup", debounce(onSearch, 100));
    input.addEventListener("search", onSearch);

    document.addEventListener("keydown", (event) => {
        if ((event.ctrlKey || event.metaKey) && event.key === "f" && document.activeElement !== input) {
            input.focus();
            event.preventDefault();
        }
    });
}
