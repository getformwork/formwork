import "../../../environment/dom";
import assert from "node:assert/strict";
import { beforeEach, describe, test } from "node:test";
import { ColorScheme } from "../../../../src/ts/components/ui/color-scheme";
import { app } from "../../../../src/ts/services/app";
import { getCookies } from "../../../../src/ts/utils/cookies";

beforeEach(() => {
    app.config.baseUri = "/";
    app.config.colorScheme = "auto";
});

/**
 * Make the system prefer a color scheme, returning the function that changes it
 */
function preferColorScheme(t: { mock: { method: typeof import("node:test").mock.method } }, initial: "light" | "dark" | null) {
    let scheme = initial;
    const listeners: (() => void)[] = [];
    t.mock.method(window, "matchMedia", (query: string) => ({
        get matches() {
            return scheme !== null && query === `(prefers-color-scheme: ${scheme})`;
        },
        addEventListener: (_type: string, listener: () => void) => listeners.push(listener),
    }));
    return (value: "light" | "dark") => {
        scheme = value;
        for (const listener of listeners) {
            listener();
        }
    };
}

const cookie = () => getCookies().formwork_preferred_color_scheme;

const classes = () => Array.from(document.documentElement.classList).filter((name) => name.startsWith("color-scheme-"));

describe("ColorScheme", () => {
    test("stores the color scheme preferred by the system and uses it", (t) => {
        preferColorScheme(t, "dark");
        new ColorScheme();
        assert.equal(cookie(), "dark");
        assert.deepEqual(classes(), ["color-scheme-dark"]);
    });

    test("uses the light color scheme without a preference", (t) => {
        preferColorScheme(t, null);
        new ColorScheme();
        assert.equal(cookie(), "light");
    });

    test("follows the changes of the preferred color scheme", (t) => {
        const change = preferColorScheme(t, "light");
        new ColorScheme();
        change("dark");
        assert.equal(cookie(), "dark");
        assert.deepEqual(classes(), ["color-scheme-dark"]);
        change("light");
        assert.deepEqual(classes(), ["color-scheme-light"]);
    });

    test("keeps the color scheme chosen in the configuration", (t) => {
        app.config.colorScheme = "light";
        const change = preferColorScheme(t, "light");
        new ColorScheme();
        change("dark");
        assert.equal(cookie(), "dark");
        assert.deepEqual(classes(), []);
    });

    test("stores the preferred color scheme when leaving the page", (t) => {
        app.config.colorScheme = "dark";
        preferColorScheme(t, "light");
        new ColorScheme();
        assert.equal(cookie(), undefined);
        window.dispatchEvent(new Event("pagehide"));
        assert.equal(cookie(), "light");
    });
});
