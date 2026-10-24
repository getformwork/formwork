import type { App } from "./app";

/**
 * Provides the translated strings of the panel, passed by PHP in the configuration
 */
export class Translation {
    readonly code: string;

    private data: { [key: string]: string | string[] };

    constructor(app: App) {
        ({ code: this.code, data: this.data } = app.config.translation);
    }

    /**
     * Return whether a string exists for a key
     */
    has(key: string): boolean {
        return key in this.data;
    }

    /**
     * Get the string of a key, or the default value if missing
     *
     * @param defaultValue Value returned if the key is missing
     *
     * @throws Error If the value is not a string
     */
    get(key: string, defaultValue?: string): string {
        const translation = this.data[key] ?? defaultValue;

        if (typeof translation === "string") {
            return translation;
        }

        throw new Error(`Translation for key "${key}" is not a string.`);
    }

    /**
     * Get the list of strings of a key (e.g. singular and plural), or the default value if missing
     *
     * @param defaultValue Value returned if the key is missing
     *
     * @throws Error If the value is not a list
     */
    getStrings(key: string, defaultValue?: string[]): string[] {
        const translation = this.data[key] ?? defaultValue;

        if (Array.isArray(translation)) {
            return translation;
        }

        throw new Error(`Translation for key "${key}" is not an array.`);
    }
}
