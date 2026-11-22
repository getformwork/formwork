import { debounce } from "./events";
import { escapeHtml, escapeRegExp, makeDiacriticsRegExp } from "./validation";

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
    // `\b` only knows ASCII word characters, so the start of a word is not preceded by any letter, mark, digit or underscore
    return new RegExp(`${wordStart ? "(?<![\\p{L}\\p{M}\\p{N}_])" : ""}${makeDiacriticsRegExp(escapeRegExp(value))}`, global ? "giu" : "iu");
}

/**
 * Get the HTML of a text with the matches of a regular expression highlighted with `<mark>`
 *
 * The text is matched as it is and escaped afterwards, so matches never fall inside HTML entities
 *
 * @returns HTML of the text, or `null` if there are no matches
 */
export function highlightMatches(text: string, regExp: RegExp) {
    const pattern = regExp.global ? regExp : new RegExp(regExp.source, `${regExp.flags}g`);

    let html = "";
    let index = 0;

    for (const match of text.matchAll(pattern)) {
        html += `${escapeHtml(text.slice(index, match.index))}<mark>${escapeHtml(match[0])}</mark>`;
        index = match.index + match[0].length;
    }

    // Each match adds a `<mark>`, so the HTML is empty only without matches
    return html === "" ? null : html + escapeHtml(text.slice(index));
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
