/**
 * Get the cookies of the page
 */
export function getCookies() {
    const result: Record<string, string> = {};
    const cookies = document.cookie.split(";");
    for (const cookie of cookies) {
        const index = cookie.indexOf("=");
        if (index !== -1) {
            result[cookie.slice(0, index).trim()] = decodeCookieValue(cookie.slice(index + 1).trim());
        }
    }
    return result;
}

/**
 * Set a cookie, encoding its value as getCookies() decodes it
 *
 * @param options Attributes of the cookie, e.g. `path` or `max-age`
 */
export function setCookie(name: string, value: string, options: Record<string, string | number>) {
    let cookie = `${name}=${encodeURIComponent(value)}`;
    for (const option in options) {
        cookie += `;${option}=${options[option]}`;
    }

    // biome-ignore lint/suspicious/noDocumentCookie: Setting cookies via document.cookie is intentional
    document.cookie = cookie;
}

/**
 * Decode the value of a cookie, keeping it as is if it is not encoded properly
 *
 * Other cookies of the domain may not be encoded with `encodeURIComponent()`
 */
function decodeCookieValue(value: string) {
    try {
        return decodeURIComponent(value);
    } catch {
        return value;
    }
}
