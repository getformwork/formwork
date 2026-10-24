/**
 * Convert a string to camel case
 */
export function toCamelCase(string: string) {
    return string
        .split(/[-_ ]+/)
        .map((part, index) => {
            if (index === 0) {
                return part.toLowerCase();
            }
            return part.charAt(0).toUpperCase() + part.slice(1).toLowerCase();
        })
        .join("");
}
