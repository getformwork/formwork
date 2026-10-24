import { app } from "../../services/app";
import { getCookies, setCookie } from "../../utils/cookies";

type ColorSchemeName = "light" | "dark";
type ColorSchemePreference = ColorSchemeName | "light dark";

/**
 * Stores the preferred color scheme in a cookie, so that PHP can render it, and follows its changes
 */
export class ColorScheme {
    constructor() {
        const getSupportedColorSchemes = (): ColorSchemePreference => {
            switch (app.config.colorScheme) {
                case "light":
                    return "light";
                case "dark":
                    return "dark";
                default:
                    return "light dark";
            }
        };

        const setColorScheme = (colorScheme: ColorSchemeName) => {
            document.documentElement.classList.remove("color-scheme-light", "color-scheme-dark");
            document.documentElement.classList.add(`color-scheme-${colorScheme}`);
        };

        const setPreferredColorScheme = (change = false) => {
            const cookies = getCookies();
            const cookieName = "formwork_preferred_color_scheme";
            const oldValue = cookieName in cookies ? cookies[cookieName] : null;
            let colorScheme: ColorSchemeName = "light";

            if (window.matchMedia("(prefers-color-scheme: light)").matches) {
                colorScheme = "light";
            } else if (window.matchMedia("(prefers-color-scheme: dark)").matches) {
                colorScheme = "dark";
            }

            if (colorScheme && colorScheme !== oldValue) {
                setCookie(cookieName, colorScheme, {
                    "max-age": 2592000, // 1 month
                    path: app.config.baseUri,
                    samesite: "strict",
                });

                if (change && getSupportedColorSchemes() === "light dark") {
                    setColorScheme(colorScheme);
                }
            }
        };

        window.addEventListener("beforeunload", () => setPreferredColorScheme());
        window.addEventListener("pagehide", () => setPreferredColorScheme());
        window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change", () => setPreferredColorScheme(true));

        if (getSupportedColorSchemes() === "light dark") {
            setPreferredColorScheme(true);
        }
    }
}
