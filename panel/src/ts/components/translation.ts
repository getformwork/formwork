import type { App } from "../app";

export class Translation {
    readonly code: string;

    private data: { [key: string]: string | string[] };

    constructor(app: App) {
        ({ code: this.code, data: this.data } = app.config.translation);
    }

    has(key: string): boolean {
        return key in this.data;
    }

    get(key: string, defaultValue?: string): string {
        const translation = this.data[key] ?? defaultValue;

        if (typeof translation === "string") {
            return translation;
        }

        throw new Error(`Translation for key "${key}" is not a string.`);
    }

    getStrings(key: string, defaultValue?: string[]): string[] {
        const translation = this.data[key] ?? defaultValue;

        if (Array.isArray(translation)) {
            return translation;
        }

        throw new Error(`Translation for key "${key}" is not an array.`);
    }
}
